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
                        <tr class="cursor-pointer message-row" 
                            data-first-name="{{ e($m->first_name) }}"
                            data-last-name="{{ e($m->last_name) }}"
                            data-email="{{ e($m->email) }}"
                            data-phone="{{ e($m->phone) }}"
                            data-message="{{ htmlspecialchars(html_entity_decode($m->message), ENT_NOQUOTES, 'UTF-8') }}"
                            data-received-at="{{ e($m->created_at->toDayDateTimeString()) }}">
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $m->first_name }} {{ $m->last_name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $m->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $m->phone }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-xl truncate">{!! htmlspecialchars(html_entity_decode($m->message), ENT_NOQUOTES, 'UTF-8') !!}</td>
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

<!-- Modal for showing full contact details -->
<div id="contact-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 id="modal-title" class="text-lg font-semibold">Contact Details</h3>
            <button id="modal-close" class="text-gray-600 hover:text-gray-800">&times;</button>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div><strong>From:</strong> <span id="modal-from"></span></div>
                <div><strong>Email:</strong> <span id="modal-email"></span></div>
                <div><strong>Phone:</strong> <span id="modal-phone"></span></div>
                <div><strong>Received:</strong> <span id="modal-received"></span></div>
            </div>
            <div>
                <strong>Message</strong>
                <div id="modal-message" class="mt-2 py-4 bg-gray-50 rounded text-sm text-gray-800 whitespace-pre-wrap"></div>
            </div>
        </div>
        <div class="px-6 py-4 border-t text-right">
            <button id="modal-close-2" class="px-4 py-2 bg-gray-100 rounded">Close</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rows = document.querySelectorAll('.message-row');
        const modal = document.getElementById('contact-modal');
        const modalFrom = document.getElementById('modal-from');
        const modalEmail = document.getElementById('modal-email');
        const modalPhone = document.getElementById('modal-phone');
        const modalMessage = document.getElementById('modal-message');
        const modalReceived = document.getElementById('modal-received');
        const modalClose = document.getElementById('modal-close');
        const modalClose2 = document.getElementById('modal-close-2');

        function openModal(data) {
            modalFrom.textContent = data.firstName + (data.lastName ? ' ' + data.lastName : '');
            modalEmail.textContent = data.email || '-';
            modalPhone.textContent = data.phone || '-';
            modalMessage.textContent = data.message || '';
            modalReceived.textContent = data.receivedAt || '-';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        rows.forEach(function (row) {
            row.addEventListener('click', function () {
                const data = {
                    firstName: row.getAttribute('data-first-name') || '',
                    lastName: row.getAttribute('data-last-name') || '',
                    email: row.getAttribute('data-email') || '',
                    phone: row.getAttribute('data-phone') || '',
                    message: row.getAttribute('data-message') || '',
                    receivedAt: row.getAttribute('data-received-at') || ''
                };
                openModal(data);
            });
        });

        modalClose.addEventListener('click', closeModal);
        modalClose2.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
    });
</script>

@endsection

