<?php

namespace App\Jobs;

use App\Modules\Admin\Models\ClientIntegration;
use App\Modules\Admin\Models\IntegrationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900];
    public $timeout = 15;

    protected $integrationId;
    protected $webhookUrl;
    protected $payload;
    protected $event;
    protected $secret;

    public function __construct(int $integrationId, string $webhookUrl, array $payload, string $event, ?string $secret = null)
    {
        $this->integrationId = $integrationId;
        $this->webhookUrl = $webhookUrl;
        $this->payload = $payload;
        $this->event = $event;
        $this->secret = $secret;
    }

    public function handle(): void
    {
        try {
            $signature = $this->secret 
                ? hash_hmac('sha256', json_encode($this->payload), $this->secret) 
                : '';

            $headers = [
                'Content-Type' => 'application/json',
                'X-Webhook-Event' => $this->event,
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Attempt' => (string) $this->attempts(),
                'User-Agent' => 'WakaLine-Webhooks/1.0',
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($this->webhookUrl, $this->payload);

            $success = $response->successful();

            $this->logWebhook($success, $response->status(), $response->body(), null);

            if ($success) {
                $this->updateIntegrationSuccess();
            } else {
                $this->updateIntegrationFailure("HTTP {$response->status()}");
                
                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff[$this->attempts() - 1] ?? 900);
                }
            }

        } catch (\Exception $e) {
            $this->logWebhook(false, null, null, $e->getMessage());
            $this->updateIntegrationFailure($e->getMessage());

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 900);
            } else {
                Log::error('Webhook delivery failed after all retries', [
                    'integration_id' => $this->integrationId,
                    'url' => $this->webhookUrl,
                    'event' => $this->event,
                    'attempts' => $this->attempts(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function logWebhook(bool $success, ?int $responseCode, ?string $responseBody, ?string $errorMessage): void
    {
        IntegrationLog::create([
            'client_integration_id' => $this->integrationId,
            'action' => 'webhook_sent',
            'status' => $success ? 'success' : 'failed',
            'data' => [
                'event' => $this->event,
                'order_number' => $this->payload['data']['order_number'] ?? null,
                'url' => $this->webhookUrl,
                'response_code' => $responseCode,
                'response_body' => $responseBody ? substr($responseBody, 0, 500) : null,
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
            ],
            'error_message' => $errorMessage ?? ($success ? null : "HTTP {$responseCode}"),
        ]);
    }

    private function updateIntegrationSuccess(): void
    {
        $integration = ClientIntegration::find($this->integrationId);
        if ($integration) {
            $integration->increment('sync_count');
            $integration->update([
                'last_synced_at' => now(),
                'last_error' => null,
            ]);
        }
    }

    private function updateIntegrationFailure(string $error): void
    {
        $integration = ClientIntegration::find($this->integrationId);
        if ($integration) {
            $integration->update([
                'last_error' => $error,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook job failed permanently', [
            'integration_id' => $this->integrationId,
            'url' => $this->webhookUrl,
            'event' => $this->event,
            'error' => $exception->getMessage(),
        ]);

        IntegrationLog::create([
            'client_integration_id' => $this->integrationId,
            'action' => 'webhook_failed',
            'status' => 'failed',
            'data' => [
                'event' => $this->event,
                'url' => $this->webhookUrl,
                'attempts' => $this->tries,
            ],
            'error_message' => "Failed after {$this->tries} attempts: {$exception->getMessage()}",
        ]);
    }
}
