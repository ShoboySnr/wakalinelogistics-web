@extends('Admin::layout')

@section('title', 'Communications')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Communications</h1>

    <!-- Flash alert placeholder -->
    <div id="flash-message" class="hidden"></div>

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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($subscriptions as $sub)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $sub->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $sub->created_at->toDayDateTimeString() }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <button class="px-3 py-1 mr-2 bg-blue-50 text-blue-700 rounded edit-subscription" data-id="{{ $sub->id }}" data-email="{{ e($sub->email) }}">Edit</button>
                                <form method="POST" action="{{ route('admin.communications.subscriptions.delete', $sub->id) }}" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this subscription?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-1 bg-red-50 text-red-700 rounded">Delete</button>
                                </form>
                            </td>
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
                            data-id="{{ $m->id }}"
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
                            <td colspan="6" class="px-6 py-4 text-sm text-gray-500">No messages found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $messages->links() }}</div>
        </div>
    </div>
</div>

<!-- Modal for showing full contact details -->
<div id="contact-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 id="modal-title" class="text-lg font-semibold">Contact Details</h3>
            <button id="modal-close" class="text-gray-600 hover:text-gray-800">&times;</button>
        </div>
        <div class="p-6 space-y-4">
            <div id="modal-display-area">
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
        </div>
        <div class="px-6 py-4 border-t text-right">
            <button id="modal-edit" class="px-3 py-1 mr-2 bg-yellow-100 text-yellow-700 rounded">Edit</button>
            <form id="modal-delete-form" method="POST" style="display:inline-block;">
                @csrf
                @method('DELETE')
                <button class="px-3 py-1 mr-2 bg-red-50 text-red-700 rounded">Delete</button>
            </form>
            <button id="modal-close-2" class="px-4 py-2 bg-gray-100 rounded">Close</button>
        </div>
    </div>
</div>

<!-- Modal for editing subscription email -->
<div id="subscription-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Edit Subscription</h3>
            <button id="subscription-modal-close" class="text-gray-600 hover:text-gray-800">&times;</button>
        </div>
        <div class="p-6">
            <form id="subscription-edit-form" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input id="subscription-email" name="email" class="mt-1 block w-full rounded border-gray-300" />
                </div>
                <div class="text-right space-x-2">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Save</button>
                    <button type="button" id="subscription-cancel" class="px-4 py-2 bg-gray-100 rounded">Cancel</button>
                </div>
            </form>
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

        // contact message modal for viewing
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
            if (data.id) {
                modal.setAttribute('data-current-id', data.id);
                // store separate first/last for editing
                modal.setAttribute('data-first-name', data.firstName || '');
                modal.setAttribute('data-last-name', data.lastName || '');
            } else {
                modal.removeAttribute('data-current-id');
                modal.removeAttribute('data-first-name');
                modal.removeAttribute('data-last-name');
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            const existingForm = document.getElementById('message-edit-form');
            if (existingForm) existingForm.remove();
            if (msgModalDisplayArea) msgModalDisplayArea.style.display = '';
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
                    receivedAt: row.getAttribute('data-received-at') || '',
                    id: row.getAttribute('data-id') || ''
                };
                openModal(data);
            });
        });

        // message modal for edit and delete actions
        const msgModalEdit = document.getElementById('modal-edit');
        const msgModalDeleteForm = document.getElementById('modal-delete-form');
        const msgModalDisplayArea = document.getElementById('modal-display-area');

        msgModalEdit.addEventListener('click', function () {
            if (document.getElementById('message-edit-form')) return;

            if (msgModalDisplayArea) msgModalDisplayArea.style.display = 'none';

            const formHtml = `
                <form id="message-edit-form" class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First name</label>
                            <input id="edit-first" name="first_name" class="mt-1 block w-full rounded border-gray-300" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last name</label>
                            <input id="edit-last" name="last_name" class="mt-1 block w-full rounded border-gray-300" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input id="edit-email" name="email" class="mt-1 block w-full rounded border-gray-300" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input id="edit-phone" name="phone" class="mt-1 block w-full rounded border-gray-300" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Message</label>
                        <textarea id="edit-message" name="message" rows="4" class="mt-1 block w-full rounded border-gray-300"></textarea>
                    </div>
                    <div class="text-right space-x-2">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Save</button>
                        <button type="button" id="message-edit-cancel" class="px-4 py-2 bg-gray-100 rounded">Cancel</button>
                    </div>
                </form>
            `;

            const panel = modal.querySelector('.p-6');
            panel.insertAdjacentHTML('beforeend', formHtml);

            // update form values from stored modal attributes (first/last separate)
            document.getElementById('edit-first').value = modal.getAttribute('data-first-name') || '';
            document.getElementById('edit-last').value = modal.getAttribute('data-last-name') || '';
            document.getElementById('edit-email').value = modalEmail.textContent || '';
            document.getElementById('edit-phone').value = modalPhone.textContent || '';
            document.getElementById('edit-message').value = modalMessage.textContent || '';

            const msgId = modal.getAttribute('data-current-id');
            if (msgId) {
                msgModalDeleteForm.action = `{{ url('super-admin/communications/messages') }}/${msgId}`;
            }

            const formEl = document.getElementById('message-edit-form');
            const cancelBtn = document.getElementById('message-edit-cancel');

            formEl.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!msgId) return alert('No message selected');

                const payload = {
                    first_name: document.getElementById('edit-first').value,
                    last_name: document.getElementById('edit-last').value,
                    email: document.getElementById('edit-email').value,
                    phone: document.getElementById('edit-phone').value,
                    message: document.getElementById('edit-message').value
                };

                fetch(`{{ url('super-admin/communications/messages') }}/${msgId}`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: new URLSearchParams(Object.assign({}, payload, { _method: 'PUT' }))
                }).then(resp => {
                    if (!resp.ok) throw resp;
                    return resp.json();
                }).then(data => {
                    // update modal display
                    modalFrom.textContent = payload.first_name + (payload.last_name ? ' ' + payload.last_name : '');
                    modalEmail.textContent = payload.email || '-';
                    modalPhone.textContent = payload.phone || '-';
                    modalMessage.textContent = payload.message || '';
                    // update stored modal attributes for first/last
                    try {
                        modal.setAttribute('data-first-name', payload.first_name || '');
                        modal.setAttribute('data-last-name', payload.last_name || '');
                    } catch (err) {}

                    // update table row
                    const row = document.querySelector(`.message-row[data-id="${msgId}"]`);
                    if (row) {
                        row.setAttribute('data-first-name', payload.first_name);
                        row.setAttribute('data-last-name', payload.last_name);
                        row.setAttribute('data-email', payload.email);
                        row.setAttribute('data-phone', payload.phone);
                        row.setAttribute('data-message', payload.message);
                        // update visible cells
                        const nameCell = row.querySelector('td:nth-child(1)');
                        const emailCell = row.querySelector('td:nth-child(2)');
                        const phoneCell = row.querySelector('td:nth-child(3)');
                        const messageCell = row.querySelector('td:nth-child(4)');
                        if (nameCell) nameCell.textContent = payload.first_name + (payload.last_name ? ' ' + payload.last_name : '');
                        if (emailCell) emailCell.textContent = payload.email || '';
                        if (phoneCell) phoneCell.textContent = payload.phone || '';
                        if (messageCell) messageCell.textContent = payload.message || '';
                    }

                    formEl.remove();
                    if (msgModalDisplayArea) msgModalDisplayArea.style.display = '';
                    try { showAlert('success', data.message || 'Message updated'); } catch (e) {}
                    
                    setTimeout(() => {
                        closeModal();
                    }, 1000);
                }).catch((e) => {
                    console.log(e.statusText || e.status || e);
                    try { showAlert('error', 'Failed to save message'); } catch (err) { alert('Failed to save message'); }
                });
            });

            cancelBtn.addEventListener('click', function () {
                const f = document.getElementById('message-edit-form');
                if (f) f.remove();
                if (msgModalDisplayArea) msgModalDisplayArea.style.display = '';
            });
        });

        modalClose.addEventListener('click', closeModal);
        modalClose2.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        msgModalDeleteForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this message?')) return;
            const msgId = modal.getAttribute('data-current-id');
            if (!msgId) return;

            fetch(`{{ url('super-admin/communications/messages') }}/${msgId}`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ _method: 'DELETE' })
            }).then(r => {
                if (!r.ok) throw r;
                return r.json();
            }).then((data) => {
                const row = document.querySelector(`.message-row[data-id="${msgId}"]`);
                if (row) row.remove();
                closeModal();
                try { showAlert('success', data.message || 'Message deleted'); } catch (e) {}
            }).catch(() => alert('Failed to delete'));
        });

        // email edit and delete
        const subscriptionModal = document.getElementById('subscription-modal');
        const subscriptionClose = document.getElementById('subscription-modal-close');
        const subscriptionCancel = document.getElementById('subscription-cancel');
        const subscriptionForm = document.getElementById('subscription-edit-form');
        const subscriptionEmailInput = document.getElementById('subscription-email');
        let currentSubRow = null;

        function openSubscriptionModal(row, id, email) {
            currentSubRow = row;
            subscriptionModal.setAttribute('data-current-id', id);
            subscriptionEmailInput.value = email || '';
            subscriptionModal.classList.remove('hidden');
            subscriptionModal.classList.add('flex');
        }

        function closeSubscriptionModal() {
            subscriptionModal.classList.add('hidden');
            subscriptionModal.classList.remove('flex');
            subscriptionModal.removeAttribute('data-current-id');
            currentSubRow = null;
        }

        subscriptionClose.addEventListener('click', closeSubscriptionModal);
        subscriptionCancel.addEventListener('click', closeSubscriptionModal);

        document.querySelectorAll('.edit-subscription').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const id = this.getAttribute('data-id');
                const email = this.getAttribute('data-email');
                const row = this.closest('tr');
                openSubscriptionModal(row, id, email);
            });
        });

        subscriptionForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const id = subscriptionModal.getAttribute('data-current-id');
            if (!id) return;
            const newEmail = subscriptionEmailInput.value;

            fetch(`{{ url('super-admin/communications/subscriptions') }}/${id}`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ email: newEmail, _method: 'PUT' })
            }).then(r => {
                if (!r.ok) throw r;
                return r.json();
            }).then(data => {
                if (currentSubRow) {
                    const emailCell = currentSubRow.querySelector('td:nth-child(1)');
                    const editBtn = currentSubRow.querySelector('.edit-subscription');
                    if (emailCell) emailCell.textContent = newEmail;
                    if (editBtn) editBtn.setAttribute('data-email', newEmail);
                }
                showAlert('success', data.message || 'Subscription updated');
                closeSubscriptionModal();
            }).catch((e) => {
                alert('Failed to update subscription');
            });
        });

        const flashEl = document.getElementById('flash-message');
        function showAlert(type, message, timeout = 4000) {
            const isSuccess = type === 'success';
            const html = `
                <div class="mb-4 ${isSuccess ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500'} p-4 rounded-r-lg" id="flash-inner">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 ${isSuccess ? 'text-green-500' : 'text-red-500'} mr-3" fill="currentColor" viewBox="0 0 20 20">
                            ${isSuccess ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>' : '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 7a1 1 0 012 0v4a1 1 0 11-2 0V7zm1 8a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" clip-rule="evenodd"></path>'}
                        </svg>
                        <span class="${isSuccess ? 'text-green-700 font-medium' : 'text-red-700 font-medium'}">${message}</span>
                    </div>
                </div>`;

            flashEl.innerHTML = html;
            flashEl.classList.remove('hidden');

            if (timeout > 0) {
                setTimeout(() => {
                    const inner = document.getElementById('flash-inner');
                    if (inner) inner.remove();
                }, timeout);
            }
        }
    });
</script>

@endsection

