<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Expense;
use App\Modules\Admin\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public const CATEGORIES = ['fuel', 'maintenance', 'salaries', 'rent', 'utilities', 'insurance', 'marketing', 'other'];
    public const PAYMENT_METHODS = ['cash', 'bank_transfer', 'card', 'mobile_money'];

    public function index(Request $request): JsonResponse
    {
        $query = Expense::with('creator');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('expense_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('expense_date', '<=', $request->end_date);
        }

        $expenses = $query->latest('expense_date')->paginate(min(100, max(5, (int) $request->get('per_page', 20))));

        return response()->json([
            'success' => true,
            'data' => collect($expenses->items())->map(fn (Expense $expense) => [
                'id' => $expense->id,
                'category' => $expense->category,
                'description' => $expense->description,
                'amount' => (float) $expense->amount,
                'expense_date' => $expense->expense_date?->toDateString(),
                'payment_method' => $expense->payment_method,
                'receipt_number' => $expense->receipt_number,
                'vendor_name' => $expense->vendor_name,
                'notes' => $expense->notes,
                'created_by' => $expense->creator?->name,
                'created_at' => $expense->created_at,
            ]),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
            'stats' => $this->stats(),
        ]);
    }

    public function formOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'categories' => self::CATEGORIES,
                'payment_methods' => self::PAYMENT_METHODS,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:255',
            'vendor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();
        $expense = Expense::create($validated);

        ActivityLog::log('expense_created', "Created expense: {$expense->description} - ₦" . number_format($expense->amount, 2), $expense);

        return response()->json(['success' => true, 'message' => 'Expense added successfully', 'data' => ['id' => $expense->id]], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);
        $description = $expense->description;
        $expense->delete();

        ActivityLog::log('expense_deleted', "Deleted expense: {$description}");

        return response()->json(['success' => true, 'message' => 'Expense deleted successfully']);
    }

    public function statement(Request $request): JsonResponse
    {
        [$start, $end, $label] = $this->periodRange($request);

        $revenue = Order::whereBetween('delivery_date', [$start, $end])
            ->where('status', 'delivered')
            ->whereNotNull('delivery_date')
            ->orderByDesc('delivery_date')
            ->get(['delivery_date', 'customer_name', 'order_number', 'price']);

        $expenses = Expense::whereBetween('expense_date', [$start, $end])
            ->orderByDesc('expense_date')
            ->get(['expense_date', 'category', 'description', 'vendor_name', 'amount']);

        $totalRevenue = (float) $revenue->sum('price');
        $totalExpenses = (float) $expenses->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $label,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'generated_at' => now()->toIso8601String(),
                'revenue' => $revenue->map(fn ($o) => [
                    'date' => $o->delivery_date?->toDateString(),
                    'order_number' => $o->order_number,
                    'customer_name' => $o->customer_name,
                    'amount' => (float) $o->price,
                ]),
                'expenses' => $expenses->map(fn ($e) => [
                    'date' => $e->expense_date?->toDateString(),
                    'category' => $e->category,
                    'description' => $e->description,
                    'vendor_name' => $e->vendor_name,
                    'amount' => (float) $e->amount,
                ]),
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'profit' => $totalRevenue - $totalExpenses,
            ],
        ]);
    }

    private function periodRange(Request $request): array
    {
        $period = $request->get('period', 'this_month');
        $now = now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'Today · ' . $now->format('M d, Y')],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), 'Yesterday · ' . $now->copy()->subDay()->format('M d, Y')],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'This Week'],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek(), 'Last Week'],
            'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth(), 'Last Month · ' . $now->copy()->subMonth()->format('F Y')],
            'all_time' => [\Illuminate\Support\Carbon::parse('2000-01-01'), $now->copy()->endOfDay(), 'All Time'],
            'custom' => [
                $request->filled('start_date') ? \Illuminate\Support\Carbon::parse($request->start_date)->startOfDay() : $now->copy()->startOfMonth(),
                $request->filled('end_date') ? \Illuminate\Support\Carbon::parse($request->end_date)->endOfDay() : $now->copy()->endOfDay(),
                'Custom Range',
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'This Month · ' . $now->format('F Y')],
        };
    }

    private function stats(): array
    {
        $delivered = fn () => Order::where('status', 'delivered')->whereNotNull('delivery_date');

        $stats = [
            'total_expenses' => (float) Expense::sum('amount'),
            'today_expenses' => (float) Expense::whereDate('expense_date', today())->sum('amount'),
            'week_expenses' => (float) Expense::whereBetween('expense_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount'),
            'month_expenses' => (float) Expense::whereMonth('expense_date', now()->month)->whereYear('expense_date', now()->year)->sum('amount'),
            'total_revenue' => (float) $delivered()->sum('price'),
            'today_revenue' => (float) $delivered()->whereDate('delivery_date', today())->sum('price'),
            'week_revenue' => (float) $delivered()->whereBetween('delivery_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('price'),
            'month_revenue' => (float) $delivered()->whereMonth('delivery_date', now()->month)->whereYear('delivery_date', now()->year)->sum('price'),
            'today_delivered' => $delivered()->whereDate('delivery_date', today())->count(),
        ];

        $stats['total_profit'] = $stats['total_revenue'] - $stats['total_expenses'];
        $stats['today_profit'] = $stats['today_revenue'] - $stats['today_expenses'];
        $stats['week_profit'] = $stats['week_revenue'] - $stats['week_expenses'];
        $stats['month_profit'] = $stats['month_revenue'] - $stats['month_expenses'];

        return $stats;
    }
}
