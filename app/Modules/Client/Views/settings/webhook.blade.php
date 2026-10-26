@extends('Client::layouts.app')

@section('title', 'Webhook Settings')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Webhook Settings</h1>
            <p class="mt-2 text-gray-600">Configure webhooks to receive real-time notifications about your orders</p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Webhook Configuration</h2>
                @if($webhook)
                    <div class="flex items-center space-x-3">
                        <span class="text-sm text-gray-600">Status:</span>
                        <form action="{{ route('client.webhook.toggle') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $webhook->is_active ? 'bg-blue-600' : 'bg-gray-200' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $webhook->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </form>
                        <span class="text-sm font-medium {{ $webhook->is_active ? 'text-green-600' : 'text-gray-500' }}">
                            {{ $webhook->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                @endif
            </div>

            <form action="{{ route('client.webhook.update') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label for="webhook_url" class="block text-sm font-medium text-gray-700 mb-2">
                        Webhook URL <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="url" 
                        id="webhook_url" 
                        name="webhook_url" 
                        value="{{ old('webhook_url', $webhook->credentials['webhook_url'] ?? '') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="https://your-app.com/webhooks/wakaline"
                        required
                    >
                    <p class="mt-1 text-sm text-gray-500">The URL where webhook notifications will be sent</p>
                    @error('webhook_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="secret" class="block text-sm font-medium text-gray-700 mb-2">
                        Webhook Secret (Optional)
                    </label>
                    <input 
                        type="password" 
                        id="secret" 
                        name="secret" 
                        value="{{ old('secret') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter a secret key for signature verification"
                    >
                    <p class="mt-1 text-sm text-gray-500">Used to generate HMAC-SHA256 signature for webhook verification</p>
                    @error('secret')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-4 border-t">
                    <div class="flex space-x-3">
                        <button 
                            type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                        >
                            Save Configuration
                        </button>

                        @if($webhook)
                            <button 
                                type="button"
                                onclick="document.getElementById('test-webhook-form').submit()"
                                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"
                            >
                                Test Webhook
                            </button>
                        @endif
                    </div>

                    @if($webhook)
                        <button 
                            type="button"
                            onclick="if(confirm('Are you sure you want to delete this webhook configuration?')) { document.getElementById('delete-webhook-form').submit(); }"
                            class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
                        >
                            Delete Webhook
                        </button>
                    @endif
                </div>
            </form>
        </div>

        @if($webhook)
            <form id="test-webhook-form" action="{{ route('client.webhook.test') }}" method="POST" class="hidden">
                @csrf
            </form>

            <form id="delete-webhook-form" action="{{ route('client.webhook.delete') }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Webhook Events</h2>
            <p class="text-gray-600 mb-4">Your webhook will receive notifications for the following events:</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($events as $event => $label)
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                        <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">{{ $label }}</p>
                            <p class="text-sm text-gray-500">{{ $event }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($webhook && $logs->count() > 0)
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Webhook Activity</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($logs as $log)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $log->created_at->format('M d, Y H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $log->data['event'] ?? $log->action }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $log->status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        @if($log->status === 'success')
                                            HTTP {{ $log->data['response_code'] ?? 200 }}
                                        @else
                                            {{ Str::limit($log->error_message, 50) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">How to Use Webhooks</h3>
            <div class="space-y-3 text-sm text-blue-800">
                <p><strong>1. Configure your endpoint:</strong> Enter the URL where you want to receive webhook notifications.</p>
                <p><strong>2. Verify signatures:</strong> Use the webhook secret to verify that requests are coming from WakaLine.</p>
                <p><strong>3. Respond quickly:</strong> Your endpoint should respond within 10 seconds with a 2xx status code.</p>
                <p><strong>4. Handle retries:</strong> Failed webhooks will be retried automatically with exponential backoff.</p>
            </div>
            
            <div class="mt-4 p-4 bg-white rounded border border-blue-300">
                <p class="text-sm font-medium text-blue-900 mb-2">Example signature verification (PHP):</p>
                <pre class="text-xs text-gray-800 overflow-x-auto"><code>$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = 'your_webhook_secret';

$expected = hash_hmac('sha256', $payload, $secret);
if (hash_equals($expected, $signature)) {
    // Valid webhook
}</code></pre>
            </div>
        </div>
    </div>
</div>
@endsection
