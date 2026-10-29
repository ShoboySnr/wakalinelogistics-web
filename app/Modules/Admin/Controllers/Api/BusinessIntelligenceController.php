<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Expense;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Rider;
use App\Services\Ai\AiAnalystService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessIntelligenceController extends Controller
{
    public function __construct(private AiAnalystService $analyst)
    {
    }

    public function bootstrap(Request $request): JsonResponse
    {
        $conversationId = $request->get('conversation_id') ?: $this->analyst->newConversationId();

        return response()->json([
            'success' => true,
            'data' => [
                'configured' => $this->analyst->isConfigured(),
                'conversation_id' => $conversationId,
                'transcript' => $this->analyst->transcript($conversationId),
                'kpis' => $this->headlineKpis(),
            ],
        ]);
    }

    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => 'required|string|max:64',
            'message' => 'required|string|max:4000',
        ]);

        return response()->json(
            $this->analyst->ask($validated['conversation_id'], $validated['message'], (int) auth()->id())
        );
    }

    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => 'required|string|max:64',
            'decisions' => 'required|array',
            'decisions.*' => 'required|in:approve,deny',
        ]);

        return response()->json(
            $this->analyst->resume($validated['conversation_id'], $validated['decisions'], (int) auth()->id())
        );
    }

    public function reset(Request $request): JsonResponse
    {
        $existing = $request->get('conversation_id');
        if ($existing) {
            $this->analyst->reset($existing);
        }

        return response()->json([
            'success' => true,
            'data' => ['conversation_id' => $this->analyst->newConversationId()],
        ]);
    }

    private function headlineKpis(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $revenue = (float) Order::whereBetween('delivery_date', [$monthStart, $monthEnd])
            ->where('status', 'delivered')->sum('price');
        $expenses = (float) Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount');

        return [
            'month_label' => now()->format('F Y'),
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit' => $revenue - $expenses,
            'orders_this_month' => Order::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            'delivered_this_month' => Order::whereBetween('delivery_date', [$monthStart, $monthEnd])->where('status', 'delivered')->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'unassigned_orders' => Order::whereNull('rider_id')->whereIn('status', ['pending', 'confirmed'])->count(),
            'active_riders' => Rider::where('status', 'active')->count(),
        ];
    }
}
