<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Statement Preview - {{ $period }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Action Bar -->
    <div class="no-print bg-white shadow-sm border-b sticky top-0 z-10">
        <div class="max-w-6xl mx-auto px-4 py-3 flex justify-between items-center">
            <h1 class="text-lg font-semibold text-gray-900">Statement Preview</h1>
            <div class="flex gap-2">
                <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print
                </button>
                <button onclick="window.close()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Statement Content -->
    <div class="max-w-6xl mx-auto p-6">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8 pb-6 border-b-4" style="border-color: #C1666B;">
                <h1 class="text-3xl font-bold mb-2" style="color: #C1666B;">STATEMENT OF ACCOUNTS</h1>
                <p class="text-gray-600"><strong>Period:</strong> {{ $period }}</p>
                <p class="text-gray-600"><strong>Generated:</strong> {{ $generated_at }}</p>
            </div>

            <!-- Revenue Section -->
            <div class="mb-8">
                <h2 class="text-lg font-bold text-white px-4 py-2 mb-4 rounded" style="background-color: #C1666B;">REVENUE</h2>
                @if($revenue->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (₦)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($revenue as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">{{ \Carbon\Carbon::parse($item->delivery_date)->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->order_number ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->customer_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->customer_phone }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($item->price, 2) }}</td>
                            </tr>
                            @endforeach
                            <tr class="bg-gray-100 font-bold border-t-2 border-gray-900">
                                <td colspan="4" class="px-4 py-3 text-sm text-right">TOTAL REVENUE:</td>
                                <td class="px-4 py-3 text-sm text-right">₦{{ number_format($total_revenue, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-center py-8 text-gray-500 bg-gray-50 rounded">No revenue recorded for this period</p>
                @endif
            </div>

            <!-- Expenses Section -->
            <div class="mb-8">
                <h2 class="text-lg font-bold text-white px-4 py-2 mb-4 rounded" style="background-color: #C1666B;">EXPENSES</h2>
                @if($expenses->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (₦)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($expenses as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">{{ \Carbon\Carbon::parse($item->expense_date)->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ ucfirst($item->category) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->vendor_name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($item->amount, 2) }}</td>
                            </tr>
                            @endforeach
                            <tr class="bg-gray-100 font-bold border-t-2 border-gray-900">
                                <td colspan="4" class="px-4 py-3 text-sm text-right">TOTAL EXPENSES:</td>
                                <td class="px-4 py-3 text-sm text-right">₦{{ number_format($total_expenses, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-center py-8 text-gray-500 bg-gray-50 rounded">No expenses recorded for this period</p>
                @endif
            </div>

            <!-- Summary Section -->
            <div class="bg-gray-50 rounded-lg p-6">
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-gray-300">
                        <span class="font-medium text-gray-700">Total Revenue:</span>
                        <span class="font-bold text-gray-900">₦{{ number_format($total_revenue, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-300">
                        <span class="font-medium text-gray-700">Total Expenses:</span>
                        <span class="font-bold text-gray-900">₦{{ number_format($total_expenses, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-t-2 border-gray-900">
                        <span class="font-bold text-lg text-gray-900">Net {{ $profit >= 0 ? 'Profit' : 'Loss' }}:</span>
                        <span class="font-bold text-xl {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ₦{{ number_format(abs($profit), 2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t text-center text-sm text-gray-500">
                <p>Generated from Waka Line Logistics Management System</p>
            </div>
        </div>
    </div>
</body>
</html>
