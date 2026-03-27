@extends('Admin::layout')

@section('title', 'Communications')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Communications</h1>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center space-x-4 mb-4">
            <button id="tab-subscriptions" class="px-4 py-2 rounded bg-gray-100 text-gray-800 font-medium">Subscriptions</button>
            <button id="tab-messages" class="px-4 py-2 rounded text-gray-600">Contact Messages</button>
        </div>

        <div id="panel-subscriptions">
            <h2 class="text-lg font-semibold mb-3">Newsletter Subscriptions</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscribed At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($subscriptions as $sub)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $sub->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $sub->created_at->toDayDateTimeString() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-6 py-4 text-sm text-gray-500">No subscriptions found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $subscriptions->links() }}</div>
        </div>

        <div id="panel-messages" class="hidden">
            <h2 class="text-lg font-semibold mb-3">Contact Messages</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Received</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($messages as $m)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $m->first_name }} {{ $m->last_name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $m->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $m->phone }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-xl truncate">{{ $m->message }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $m->created_at->toDayDateTimeString() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-sm text-gray-500">No messages found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $messages->links() }}</div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabSubscriptions = document.getElementById('tab-subscriptions');
        const tabMessages = document.getElementById('tab-messages');
        const panelSubscriptions = document.getElementById('panel-subscriptions');
        const panelMessages = document.getElementById('panel-messages');

        tabSubscriptions.addEventListener('click', function () {
            panelSubscriptions.classList.remove('hidden');
            panelMessages.classList.add('hidden');
            tabSubscriptions.classList.add('bg-gray-100');
            tabMessages.classList.remove('bg-gray-100');
        });

        tabMessages.addEventListener('click', function () {
            panelMessages.classList.remove('hidden');
            panelSubscriptions.classList.add('hidden');
            tabMessages.classList.add('bg-gray-100');
            tabSubscriptions.classList.remove('bg-gray-100');
        });
    });
</script>

@endsection
