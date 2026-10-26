@php
    $existingStockItems = collect(old('stock_items', $existingStockItems ?? []))
        ->map(fn ($item) => ['client_stock_id' => $item['client_stock_id'] ?? null, 'quantity' => $item['quantity'] ?? 1])
        ->values();
    $stockLocked = $stockLocked ?? false;
@endphp

<div id="stock_items_wrapper" class="md:col-span-2 mt-6 hidden">
    <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b-2 border-gray-200">Products Going Out</h3>

    @if($stockLocked)
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-sm text-gray-600 mb-2">Stock has already been deducted for this order.</p>
            <ul class="text-sm text-gray-900 space-y-1">
                @foreach($lockedStockItems as $item)
                    <li>{{ $item->product_name }}: {{ $item->quantity }}</li>
                @endforeach
            </ul>
        </div>
    @else
        <div id="stock_items_rows" class="space-y-3"></div>
        <button type="button" id="add_stock_item_btn"
                class="mt-3 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors">
            + Add Product
        </button>
        <p class="mt-1 text-xs text-gray-500">Quantities are deducted from the client's stock once the remittance is marked.</p>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const stockClients = @json($stockClients ?? []);
    const existingItems = @json($existingStockItems);
    const locked = @json($stockLocked);
    const wrapper = document.getElementById('stock_items_wrapper');
    const rowsEl = document.getElementById('stock_items_rows');
    const addBtn = document.getElementById('add_stock_item_btn');
    const clientSelect = document.getElementById('client_id');
    let rowIndex = 0;

    function esc(value) {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    function currentStocks() {
        return stockClients[clientSelect.value] || [];
    }

    function addRow(item = {}) {
        const stocks = currentStocks();
        const index = rowIndex++;
        const row = document.createElement('div');
        row.className = 'stock-item-row flex items-center gap-3';
        const options = stocks.map(s =>
            `<option value="${s.id}" ${String(s.id) === String(item.client_stock_id) ? 'selected' : ''}>${esc(s.product_name)} (${s.quantity} left)</option>`
        ).join('');
        row.innerHTML = `
            <select name="stock_items[${index}][client_stock_id]"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 bg-white">
                <option value="">Select product</option>${options}
            </select>
            <input type="number" min="1" name="stock_items[${index}][quantity]" value="${item.quantity || 1}"
                   class="block w-28 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
            <button type="button" class="text-xs text-gray-500 hover:text-gray-700 underline">Remove</button>`;
        row.querySelector('button').addEventListener('click', () => row.remove());
        rowsEl.appendChild(row);
    }

    function render(items) {
        const hasStock = currentStocks().length > 0;
        wrapper.classList.toggle('hidden', !hasStock && !locked);
        if (locked || !rowsEl) return;
        rowsEl.innerHTML = '';
        if (!hasStock) return;
        (items.length ? items : [{}]).forEach(addRow);
    }

    if (addBtn) addBtn.addEventListener('click', () => addRow());
    clientSelect.addEventListener('change', () => render([]));
    render(existingItems);
});
</script>
