<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Expense;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('creator');

        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }

        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('expense_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('expense_date', '<=', $request->end_date);
        }

        $expenses = $query->latest('expense_date')->paginate(20);

        $stats = [
            'total_expenses' => Expense::sum('amount'),
            'today_expenses' => Expense::whereDate('expense_date', today())->sum('amount'),
            'week_expenses' => Expense::whereBetween('expense_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount'),
            'month_expenses' => Expense::whereMonth('expense_date', now()->month)
                                       ->whereYear('expense_date', now()->year)
                                       ->sum('amount'),
            
            'total_revenue' => Order::where('status', 'delivered')
                                    ->whereNotNull('delivery_date')
                                    ->sum('price'),
            'today_revenue' => Order::whereDate('delivery_date', today())
                                    ->where('status', 'delivered')
                                    ->whereNotNull('delivery_date')
                                    ->sum('price'),
            'week_revenue' => Order::whereBetween('delivery_date', [now()->startOfWeek(), now()->endOfWeek()])
                                   ->where('status', 'delivered')
                                   ->whereNotNull('delivery_date')
                                   ->sum('price'),
            'month_revenue' => Order::whereMonth('delivery_date', now()->month)
                                    ->whereYear('delivery_date', now()->year)
                                    ->where('status', 'delivered')
                                    ->whereNotNull('delivery_date')
                                    ->sum('price'),
            
            'today_delivered' => Order::whereDate('delivery_date', today())
                                      ->where('status', 'delivered')
                                      ->whereNotNull('delivery_date')
                                      ->count(),
        ];

        $stats['total_profit'] = $stats['total_revenue'] - $stats['total_expenses'];
        $stats['today_profit'] = $stats['today_revenue'] - $stats['today_expenses'];
        $stats['week_profit'] = $stats['week_revenue'] - $stats['week_expenses'];
        $stats['month_profit'] = $stats['month_revenue'] - $stats['month_expenses'];

        return view('Admin::expenses.index', compact('expenses', 'stats'));
    }

    public function store(Request $request)
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

        return redirect()->route('admin.expenses')->with('success', 'Expense added successfully');
    }

    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        $description = $expense->description;
        $amount = $expense->amount;
        
        $expense->delete();

        ActivityLog::log('expense_deleted', "Deleted expense: {$description} - ₦" . number_format($amount, 2));

        return redirect()->route('admin.expenses')->with('success', 'Expense deleted successfully');
    }

    public function previewStatement(Request $request)
    {
        $period = $request->get('period', 'today');
        
        $dateData = $this->getDateRangeForPeriod($period, $request);
        
        $statementData = $this->getStatementData($dateData['startDate'], $dateData['endDate'], $dateData['periodLabel']);
        
        return view('Admin::exports.statement-preview', $statementData);
    }

    public function exportStatement(Request $request)
    {
        $period = $request->get('period', 'today');
        $format = $request->get('format', 'pdf');
        
        $dateData = $this->getDateRangeForPeriod($period, $request);
        $data = $this->getStatementData($dateData['startDate'], $dateData['endDate'], $dateData['periodLabel']);

        if ($format === 'excel') {
            return $this->exportToExcel($data);
        } else {
            return $this->exportToPDF($data);
        }
    }

    private function getDateRangeForPeriod($period, $request)
    {
        switch ($period) {
            case 'today':
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
                $periodLabel = 'Today - ' . now()->format('M d, Y');
                break;
            case 'yesterday':
                $startDate = now()->subDay()->startOfDay();
                $endDate = now()->subDay()->endOfDay();
                $periodLabel = 'Yesterday - ' . now()->subDay()->format('M d, Y');
                break;
            case 'this_week':
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfWeek();
                $periodLabel = 'This Week - ' . $startDate->format('M d') . ' to ' . $endDate->format('M d, Y');
                break;
            case 'last_week':
                $startDate = now()->subWeek()->startOfWeek();
                $endDate = now()->subWeek()->endOfWeek();
                $periodLabel = 'Last Week - ' . $startDate->format('M d') . ' to ' . $endDate->format('M d, Y');
                break;
            case 'this_month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                $periodLabel = 'This Month - ' . now()->format('F Y');
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                $periodLabel = 'Last Month - ' . now()->subMonth()->format('F Y');
                break;
            case 'all_time':
                $startDate = Carbon::parse('2000-01-01')->startOfDay();
                $endDate = now()->endOfDay();
                $periodLabel = 'All Time - From Beginning to ' . now()->format('M d, Y');
                break;
            case 'custom':
                $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : now()->startOfMonth();
                $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();
                $periodLabel = 'Custom - ' . $startDate->format('M d, Y') . ' to ' . $endDate->format('M d, Y');
                break;
            default:
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
                $periodLabel = 'Today - ' . now()->format('M d, Y');
        }

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodLabel' => $periodLabel,
        ];
    }

    private function getStatementData($startDate, $endDate, $periodLabel)
    {
        $revenue = Order::whereBetween('delivery_date', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->whereNotNull('delivery_date')
            ->select('delivery_date', 'customer_name', 'customer_phone', 'price', 'order_number')
            ->orderBy('delivery_date', 'desc')
            ->get();

        $totalRevenue = $revenue->sum('price');

        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->select('expense_date', 'category', 'description', 'amount', 'vendor_name', 'payment_method')
            ->orderBy('expense_date', 'desc')
            ->get();

        $totalExpenses = $expenses->sum('amount');

        $profit = $totalRevenue - $totalExpenses;

        return [
            'period' => $periodLabel,
            'start_date' => $startDate->format('M d, Y'),
            'end_date' => $endDate->format('M d, Y'),
            'revenue' => $revenue,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'profit' => $profit,
            'generated_at' => now()->format('M d, Y h:i A'),
        ];
    }

    private function exportToPDF($data)
    {
        $html = view('Admin::exports.statement-pdf', $data)->render();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'statement-' . str_replace(' ', '-', strtolower($data['period'])) . '.pdf';
        
        return $pdf->download($filename);
    }

    private function exportToExcel($data)
    {
        $filename = 'statement-' . str_replace(' ', '-', strtolower($data['period'])) . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Financial Statement']);
            fputcsv($file, ['Period', $data['period']]);
            fputcsv($file, ['Generated', $data['generated_at']]);
            fputcsv($file, []);
            
            fputcsv($file, ['REVENUE']);
            fputcsv($file, ['Date', 'Order Number', 'Customer', 'Phone', 'Amount']);
            foreach ($data['revenue'] as $order) {
                fputcsv($file, [
                    $order->delivery_date->format('M d, Y'),
                    $order->order_number,
                    $order->customer_name,
                    $order->customer_phone,
                    number_format($order->price, 2),
                ]);
            }
            fputcsv($file, ['Total Revenue', '', '', '', number_format($data['total_revenue'], 2)]);
            fputcsv($file, []);
            
            fputcsv($file, ['EXPENSES']);
            fputcsv($file, ['Date', 'Category', 'Description', 'Vendor', 'Payment Method', 'Amount']);
            foreach ($data['expenses'] as $expense) {
                fputcsv($file, [
                    $expense->expense_date->format('M d, Y'),
                    $expense->category,
                    $expense->description,
                    $expense->vendor_name,
                    $expense->payment_method,
                    number_format($expense->amount, 2),
                ]);
            }
            fputcsv($file, ['Total Expenses', '', '', '', '', number_format($data['total_expenses'], 2)]);
            fputcsv($file, []);
            
            fputcsv($file, ['SUMMARY']);
            fputcsv($file, ['Total Revenue', number_format($data['total_revenue'], 2)]);
            fputcsv($file, ['Total Expenses', number_format($data['total_expenses'], 2)]);
            fputcsv($file, ['Net Profit', number_format($data['profit'], 2)]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
