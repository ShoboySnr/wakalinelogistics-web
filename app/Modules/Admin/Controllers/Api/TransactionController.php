<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\CreditTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CreditTransaction::with(['client', 'subscriptionPlan', 'creditPackage', 'order'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_reference', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(min(100, max(5, (int) $request->get('per_page', 20))));

        return response()->json([
            'success' => true,
            'data' => collect($transactions->items())->map(fn (CreditTransaction $t) => $this->summary($t)),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
            'stats' => [
                'total' => CreditTransaction::count(),
                'pending' => CreditTransaction::where('status', 'pending')->count(),
                'completed' => CreditTransaction::where('status', 'completed')->count(),
                'failed' => CreditTransaction::where('status', 'failed')->count(),
                'total_amount' => (float) CreditTransaction::where('status', 'completed')->sum('amount_paid'),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = CreditTransaction::with(['client', 'subscriptionPlan', 'creditPackage', 'order'])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->detail($transaction)]);
    }

    public function approve(int $id): JsonResponse
    {
        $transaction = CreditTransaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending transactions can be approved'], 422);
        }

        DB::transaction(function () use ($transaction) {
            $clientCredit = $transaction->client->getOrCreateCredits();
            $balanceBefore = $clientCredit->available_credits;

            if (in_array($transaction->type, ['purchase', 'refund'], true)) {
                $clientCredit->addCredits($transaction->credits);
            }

            $transaction->update([
                'status' => 'completed',
                'balance_before' => $balanceBefore,
                'balance_after' => $clientCredit->available_credits,
                'processed_by' => auth()->id(),
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'manually_approved_by' => auth()->user()->name,
                    'manually_approved_at' => now()->toDateTimeString(),
                ]),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Transaction approved and credits added', 'data' => $this->detail($transaction->fresh(['client', 'subscriptionPlan', 'creditPackage', 'order']))]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $reason = $request->validate(['reason' => 'required|string|max:500'])['reason'];
        $transaction = CreditTransaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending transactions can be rejected'], 422);
        }

        $transaction->update([
            'status' => 'failed',
            'processed_by' => auth()->id(),
            'metadata' => array_merge($transaction->metadata ?? [], [
                'rejection_reason' => $reason,
                'rejected_by' => auth()->user()->name,
                'rejected_at' => now()->toDateTimeString(),
            ]),
        ]);

        return response()->json(['success' => true, 'message' => 'Transaction rejected', 'data' => $this->detail($transaction->fresh(['client', 'subscriptionPlan', 'creditPackage', 'order']))]);
    }

    public function reverse(Request $request, int $id): JsonResponse
    {
        $reason = $request->validate(['reason' => 'required|string|max:500'])['reason'];
        $transaction = CreditTransaction::findOrFail($id);

        if ($transaction->status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'Only completed transactions can be reversed'], 422);
        }

        DB::transaction(function () use ($transaction) {
            $clientCredit = $transaction->client->getOrCreateCredits();

            if (in_array($transaction->type, ['purchase', 'refund'], true)) {
                $clientCredit->deductCredits($transaction->credits);
            } elseif ($transaction->type === 'usage') {
                $clientCredit->addCredits(abs($transaction->credits));
            }

            $transaction->update([
                'status' => 'reversed',
                'processed_by' => auth()->id(),
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'reversal_reason' => $reason,
                    'reversed_by' => auth()->user()->name,
                    'reversed_at' => now()->toDateTimeString(),
                ]),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Transaction reversed', 'data' => $this->detail($transaction->fresh(['client', 'subscriptionPlan', 'creditPackage', 'order']))]);
    }

    private function summary(CreditTransaction $t): array
    {
        return [
            'id' => $t->id,
            'transaction_reference' => $t->transaction_reference,
            'payment_reference' => $t->payment_reference,
            'type' => $t->type,
            'status' => $t->status,
            'credits' => $t->credits,
            'amount_paid' => $t->amount_paid !== null ? (float) $t->amount_paid : null,
            'description' => $t->description,
            'client' => $t->client ? ['id' => $t->client->id, 'name' => $t->client->name, 'company_name' => $t->client->company_name] : null,
            'created_at' => $t->created_at,
        ];
    }

    private function detail(CreditTransaction $t): array
    {
        return $this->summary($t) + [
            'payment_method' => $t->payment_method,
            'balance_before' => $t->balance_before,
            'balance_after' => $t->balance_after,
            'subscription_plan' => $t->subscriptionPlan?->name,
            'credit_package' => $t->creditPackage?->name,
            'order_number' => $t->order?->order_number,
            'metadata' => $t->metadata,
            'processed_by' => $t->processed_by,
            'updated_at' => $t->updated_at,
        ];
    }
}
