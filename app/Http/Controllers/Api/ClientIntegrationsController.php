<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\ClientIntegration;
use App\Modules\Admin\Models\IntegrationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ClientIntegrationsController extends Controller
{
    /**
     * Available integrations configuration
     */
    private function getAvailableIntegrations(): array
    {
        return [
            'mailchimp' => [
                'name' => 'Mailchimp',
                'description' => 'Email marketing and automation',
                'category' => 'email_marketing',
                'icon' => '',
                'fields' => [
                    ['name' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
                    ['name' => 'server_prefix', 'label' => 'Server Prefix (e.g., us1)', 'type' => 'text', 'required' => true],
                ],
                'features' => ['Sync customers', 'Send campaigns', 'Track engagement'],
            ],
            'slack' => [
                'name' => 'Slack',
                'description' => 'Team communication and notifications',
                'category' => 'communication',
                'icon' => '💬',
                'fields' => [
                    ['name' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'url', 'required' => true],
                    ['name' => 'channel', 'label' => 'Channel Name', 'type' => 'text', 'required' => false],
                ],
                'features' => ['Order notifications', 'Customer alerts', 'Team updates'],
            ],
            'zapier' => [
                'name' => 'Zapier',
                'description' => 'Connect to 5000+ apps',
                'category' => 'automation',
                'icon' => '⚡',
                'fields' => [
                    ['name' => 'webhook_url', 'label' => 'Zapier Webhook URL', 'type' => 'url', 'required' => true],
                ],
                'features' => ['Automate workflows', 'Connect any app', 'Custom triggers'],
            ],
        ];
    }

    /**
     * List all available integrations
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $available = $this->getAvailableIntegrations();
        $connected = ClientIntegration::where('client_id', $user->id)->get();

        $integrations = [];
        foreach ($available as $type => $config) {
            $existing = $connected->firstWhere('integration_type', $type);
            
            $integrations[] = [
                'type' => $type,
                'name' => $config['name'],
                'description' => $config['description'],
                'category' => $config['category'],
                'icon' => $config['icon'],
                'features' => $config['features'],
                'is_connected' => $existing !== null,
                'is_active' => $existing?->is_active ?? false,
                'last_synced_at' => $existing?->last_synced_at,
                'sync_count' => $existing?->sync_count ?? 0,
                'has_error' => $existing?->last_error !== null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $integrations,
        ]);
    }

    /**
     * Get configuration for a specific integration
     */
    public function show(string $type)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $available = $this->getAvailableIntegrations();
        
        if (!isset($available[$type])) {
            return response()->json(['success' => false, 'message' => 'Integration not found'], 404);
        }

        $integration = ClientIntegration::where('client_id', $user->id)
            ->where('integration_type', $type)
            ->first();

        $config = $available[$type];
        $config['type'] = $type;
        $config['is_connected'] = $integration !== null;
        
        if ($integration) {
            $config['is_active'] = $integration->is_active;
            $config['settings'] = $integration->settings;
            $config['last_synced_at'] = $integration->last_synced_at;
            $config['last_error'] = $integration->last_error;
            $config['sync_count'] = $integration->sync_count;
            $config['safe_credentials'] = $integration->getSafeCredentials();
        }

        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }

    /**
     * Connect/Update an integration
     */
    public function store(Request $request, string $type)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $available = $this->getAvailableIntegrations();
        
        if (!isset($available[$type])) {
            return response()->json(['success' => false, 'message' => 'Integration not found'], 404);
        }

        // Build validation rules from integration config
        $rules = [];
        foreach ($available[$type]['fields'] as $field) {
            $fieldRules = [];
            if ($field['required']) {
                $fieldRules[] = 'required';
            }
            if ($field['type'] === 'url') {
                $fieldRules[] = 'url';
            }
            if (!empty($fieldRules)) {
                $rules[$field['name']] = implode('|', $fieldRules);
            }
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Extract credentials from request
        $credentials = [];
        foreach ($available[$type]['fields'] as $field) {
            if ($request->has($field['name'])) {
                $credentials[$field['name']] = $request->input($field['name']);
            }
        }

        // Create or update integration
        $integration = ClientIntegration::updateOrCreate(
            [
                'client_id' => $user->id,
                'integration_type' => $type,
            ],
            [
                'credentials' => $credentials,
                'settings' => $request->input('settings', []),
                'is_active' => true,
            ]
        );

        // Log the connection
        IntegrationLog::create([
            'client_integration_id' => $integration->id,
            'action' => 'connected',
            'status' => 'success',
            'data' => ['message' => 'Integration connected successfully'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Integration connected successfully',
            'data' => [
                'type' => $type,
                'is_active' => $integration->is_active,
            ],
        ]);
    }

    /**
     * Toggle integration active status
     */
    public function toggle(string $type)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $integration = ClientIntegration::where('client_id', $user->id)
            ->where('integration_type', $type)
            ->first();

        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'Integration not found'], 404);
        }

        $integration->is_active = !$integration->is_active;
        $integration->save();

        IntegrationLog::create([
            'client_integration_id' => $integration->id,
            'action' => $integration->is_active ? 'enabled' : 'disabled',
            'status' => 'success',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Integration ' . ($integration->is_active ? 'enabled' : 'disabled'),
            'data' => [
                'is_active' => $integration->is_active,
            ],
        ]);
    }

    /**
     * Disconnect an integration
     */
    public function destroy(string $type)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $integration = ClientIntegration::where('client_id', $user->id)
            ->where('integration_type', $type)
            ->first();

        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'Integration not found'], 404);
        }

        $integration->delete();

        return response()->json([
            'success' => true,
            'message' => 'Integration disconnected successfully',
        ]);
    }

    /**
     * Get integration logs
     */
    public function logs(string $type)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $integration = ClientIntegration::where('client_id', $user->id)
            ->where('integration_type', $type)
            ->first();

        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'Integration not found'], 404);
        }

        $logs = IntegrationLog::where('client_integration_id', $integration->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
