<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\CreditTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
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
            $query->where(function ($q) use ($request) {
                $q->where('transaction_reference', 'like', '%' . $request->search . '%')
                  ->orWhere('payment_reference', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $transactions = $query->paginate(20);

        $stats = [
            'total'        => CreditTransaction::count(),
            'pending'      => CreditTransaction::where('status', 'pending')->count(),
            'completed'    => CreditTransaction::where('status', 'completed')->count(),
            'failed'       => CreditTransaction::where('status', 'failed')->count(),
            'total_amount' => CreditTransaction::where('status', 'completed')->sum('amount_paid'),
        ];

        return view('Admin::transactions.index', compact('transactions', 'stats'));
    }

    public function show($id)
    {
        $transaction = CreditTransaction::with(['client', 'subscriptionPlan', 'creditPackage', 'order'])
            ->findOrFail($id);

        return view('Admin::transactions.show', compact('transaction'));
    }

    public function approve(Request $request, $id)
    {
        $transaction = CreditTransaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Only pending transactions can be approved');
        }

        DB::beginTransaction();
        try {
            $client = $transaction->client;
            $clientCredit = $client->getOrCreateCredits();
            $balanceBefore = $clientCredit->available_credits;

            if ($transaction->type === 'purchase' || $transaction->type === 'refund') {
                $clientCredit->addCredits($transaction->credits);
            }

            $transaction->update([
                'status'        => 'completed',
                'balance_before' => $balanceBefore,
                'balance_after'  => $clientCredit->available_credits,
                'processed_by'   => auth()->id(),
                'metadata'       => array_merge($transaction->metadata ?? [], [
                    'manually_approved_by' => auth()->user()->name,
                    'manually_approved_at' => now()->toDateTimeString(),
                ]),
            ]);

            DB::commit();
            return back()->with('success', 'Transaction approved and credits added successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve transaction: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $transaction = CreditTransaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Only pending transactions can be rejected');
        }

        $transaction->update([
            'status'       => 'failed',
            'processed_by' => auth()->id(),
            'metadata'     => array_merge($transaction->metadata ?? [], [
                'rejection_reason' => $request->reason,
                'rejected_by'      => auth()->user()->name,
                'rejected_at'      => now()->toDateTimeString(),
            ]),
        ]);

        return back()->with('success', 'Transaction rejected successfully');
    }

    public function reverse(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $transaction = CreditTransaction::findOrFail($id);

        if ($transaction->status !== 'completed') {
            return back()->with('error', 'Only completed transactions can be reversed');
        }

        DB::beginTransaction();
        try {
            $client = $transaction->client;
            $clientCredit = $client->getOrCreateCredits();

            // Reverse the credit effect
            if ($transaction->type === 'purchase' || $transaction->type === 'refund') {
                $clientCredit->deductCredits($transaction->credits);
            } elseif ($transaction->type === 'usage') {
                $clientCredit->addCredits(abs($transaction->credits));
            }

            $transaction->update([
                'status'       => 'reversed',
                'processed_by' => auth()->id(),
                'metadata'     => array_merge($transaction->metadata ?? [], [
                    'reversal_reason' => $request->reason,
                    'reversed_by'     => auth()->user()->name,
                    'reversed_at'     => now()->toDateTimeString(),
                ]),
            ]);

            DB::commit();
            return back()->with('success', 'Transaction reversed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reverse transaction: ' . $e->getMessage());
        }
    }
}
