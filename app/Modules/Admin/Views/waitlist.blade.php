@extends('Admin::layout')

@section('title', 'Waitlist')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">

    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Waitlist</h1>
            <p class="text-sm text-gray-500 mt-1">Users waiting to be activated when the platform launches</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openSampleModal()"
                class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 text-sm font-medium whitespace-nowrap">
                Send Sample Email
            </button>
            <button type="button" onclick="confirmLaunch()"
                {{ $waitlistClients->isEmpty() ? 'disabled' : '' }}
                class="px-4 py-2 text-white rounded-md bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium whitespace-nowrap">
                Send Launch Emails
            </button>
            <form method="POST" action="{{ route('admin.waitlist.send-launch-emails') }}" id="launch-form" class="hidden">
                @csrf
            </form>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Waitlist</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $waitlistClients->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">With Activation Link</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $waitlistClients->whereNotNull('waitlist_token')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">No Link Yet</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $waitlistClients->whereNull('waitlist_token')->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Waitlist table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activation Link</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($waitlistClients as $i => $client)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $client->name }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $client->email }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $client->created_at->format('d M Y, g:ia') }}</td>
                        <td class="px-6 py-4">
                            @if($client->waitlist_token)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ready</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <form method="POST" action="{{ route('admin.waitlist.send-activation-email', $client->id) }}"
                                onsubmit="return confirm('Send activation email to {{ addslashes($client->name) }}?');">
                                @csrf
                                <button type="submit" class="brand-accent-text hover:underline text-sm">
                                    Send Link
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="mt-2 text-sm">No waitlist users yet</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Sample email modal --}}
<div id="sample-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Send Sample Email</h3>
        <p class="text-sm text-gray-500 mb-5">Preview the launch email before sending to everyone. The activation link in the sample will not work — it is just for preview.</p>
        <form method="POST" action="{{ route('admin.waitlist.send-sample-email') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Send to</label>
                <input type="email" name="email" required placeholder="your@email.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500">
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeSampleModal()"
                    class="px-4 py-2 text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 text-sm text-white rounded-md brand-accent-bg brand-accent-hover">
                    Send Sample
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Launch confirmation modal --}}
<div id="confirm-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Send Launch Emails?</h3>
        <p class="text-sm text-gray-500 mb-4">This will email all <strong>{{ $waitlistClients->count() }} waitlist users</strong> with a unique activation link.</p>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded mb-5">
            <p class="text-sm text-yellow-800">
                Each user's link works only once — once they activate, the link is invalidated and their ₦2,000 credit is added automatically.
                Make sure the platform is fully live before proceeding.
            </p>
        </div>
        <div class="flex gap-3 justify-end">
            <button onclick="closeModal()"
                class="px-4 py-2 text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                Cancel
            </button>
            <button onclick="document.getElementById('launch-form').submit()"
                class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-md font-medium">
                Yes, Send to All
            </button>
        </div>
    </div>
</div>

<script>
function confirmLaunch() { document.getElementById('confirm-modal').classList.remove('hidden'); }
function closeModal() { document.getElementById('confirm-modal').classList.add('hidden'); }
function openSampleModal() { document.getElementById('sample-modal').classList.remove('hidden'); }
function closeSampleModal() { document.getElementById('sample-modal').classList.add('hidden'); }
document.getElementById('confirm-modal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
document.getElementById('sample-modal').addEventListener('click', function(e) { if (e.target === this) closeSampleModal(); });
</script>
@endsection
