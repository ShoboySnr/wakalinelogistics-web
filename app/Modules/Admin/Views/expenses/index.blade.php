@extends('Admin::layout')

@section('title', 'Expenses & Accounting')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Expenses & Accounting</h1>
        <button onclick="document.getElementById('addExpenseModal').classList.remove('hidden')" class="px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover transition-colors flex items-center">
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Expense
        </button>
    </div>

    <!-- Profit Overview -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Profit Overview</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white rounded-lg shadow border-l-4 border-pink-600 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Profit</p>
                        <p class="text-2xl font-bold {{ $stats['total_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                            ₦{{ number_format(abs($stats['total_profit']), 2) }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Revenue - Expenses</p>
                    </div>
                    <div class="p-3 bg-pink-50 rounded-lg">
                        <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border-l-4 border-pink-600 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Today's Profit</p>
                        <p class="text-2xl font-bold {{ $stats['today_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                            ₦{{ number_format(abs($stats['today_profit']), 2) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border-l-4 border-pink-600 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">This Week's Profit</p>
                        <p class="text-2xl font-bold {{ $stats['week_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                            ₦{{ number_format(abs($stats['week_profit']), 2) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border-l-4 border-pink-600 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">This Month's Profit</p>
                        <p class="text-2xl font-bold {{ $stats['month_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                            ₦{{ number_format(abs($stats['month_profit']), 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue vs Expenses -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Summary -->
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                    <span class="text-sm text-gray-600">Total Revenue</span>
                    <span class="text-lg font-bold text-green-600">₦{{ number_format($stats['total_revenue'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Today</span>
                    <span class="text-lg font-bold text-gray-900">₦{{ number_format($stats['today_revenue'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">This Week</span>
                    <span class="text-lg font-bold text-gray-900">₦{{ number_format($stats['week_revenue'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">This Month</span>
                    <span class="text-lg font-bold text-gray-900">₦{{ number_format($stats['month_revenue'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Expenses Summary -->
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Expenses Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                    <span class="text-sm text-gray-600">Total Expenses</span>
                    <span class="text-lg font-bold text-red-600">₦{{ number_format($stats['total_expenses'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Today</span>
                    <span class="text-lg font-bold text-gray-900">₦{{ number_format($stats['today_expenses'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">This Week</span>
                    <span class="text-lg font-bold text-gray-900">₦{{ number_format($stats['week_expenses'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">This Month</span>
                    <span class="text-lg font-bold text-gray-900">₦{{ number_format($stats['month_expenses'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <form method="GET" action="{{ route('admin.expenses') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                    <option value="">All Categories</option>
                    <option value="fuel" {{ request('category') == 'fuel' ? 'selected' : '' }}>Fuel</option>
                    <option value="maintenance" {{ request('category') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="salaries" {{ request('category') == 'salaries' ? 'selected' : '' }}>Salaries</option>
                    <option value="rent" {{ request('category') == 'rent' ? 'selected' : '' }}>Rent</option>
                    <option value="utilities" {{ request('category') == 'utilities' ? 'selected' : '' }}>Utilities</option>
                    <option value="insurance" {{ request('category') == 'insurance' ? 'selected' : '' }}>Insurance</option>
                    <option value="marketing" {{ request('category') == 'marketing' ? 'selected' : '' }}>Marketing</option>
                    <option value="other" {{ request('category') == 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="min-w-[150px]">
                <input type="date" name="start_date" value="{{ request('start_date') }}" placeholder="Start Date"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
            </div>
            <div class="min-w-[150px]">
                <input type="date" name="end_date" value="{{ request('end_date') }}" placeholder="End Date"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
            </div>
            <button type="submit" class="px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover transition-colors">
                Filter
            </button>
            <a href="{{ route('admin.expenses') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                Clear
            </a>
        </form>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($expenses as $expense)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $expense->expense_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ ucfirst($expense->category) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $expense->description }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $expense->vendor_name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                            ₦{{ number_format($expense->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $expense->payment_method ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <form method="POST" action="{{ route('admin.expenses.delete', $expense->id) }}" onsubmit="return confirm('Are you sure you want to delete this expense?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No expenses recorded yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($expenses->hasPages())
        <div class="px-6 py-4 bg-gray-50">
            {{ $expenses->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Add Expense Modal -->
<div id="addExpenseModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Add New Expense</h3>
            <button onclick="document.getElementById('addExpenseModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form method="POST" action="{{ route('admin.expenses.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select name="category" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                        <option value="">Select Category</option>
                        <option value="fuel">Fuel</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="salaries">Salaries</option>
                        <option value="rent">Rent</option>
                        <option value="utilities">Utilities</option>
                        <option value="insurance">Insurance</option>
                        <option value="marketing">Marketing</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₦) *</label>
                    <input type="number" name="amount" step="0.01" min="0" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                    <input type="text" name="description" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expense Date *</label>
                    <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                        <option value="">Select Method</option>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Card</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Name</label>
                    <input type="text" name="vendor_name" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Number</label>
                    <input type="text" name="receipt_number" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addExpenseModal').classList.add('hidden')" 
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover transition-colors">
                    Add Expense
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
