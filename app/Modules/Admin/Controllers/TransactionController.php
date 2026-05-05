<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = WalletTransaction::with(['wallet.walletable', 'transactable'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('transaction_reference', 'like', '%' . $request->search . '%')
                  ->orWhere('payment_reference', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $transactions = $query->paginate(20);

        $stats = [
            'total' => WalletTransaction::count(),
            'pending' => WalletTransaction::where('status', 'pending')->count(),
            'completed' => WalletTransaction::where('status', 'completed')->count(),
            'failed' => WalletTransaction::where('status', 'failed')->count(),
            'total_amount' => WalletTransaction::where('status', 'completed')->sum('amount'),
        ];

        return view('Admin::transactions.index', compact('transactions', 'stats'));
    }

    public function show($id)
    {
        $transaction = WalletTransaction::with(['wallet.walletable', 'transactable', 'order'])
            ->findOrFail($id);

        return view('Admin::transactions.show', compact('transaction'));
    }

    public function approve(Request $request, $id)
    {
        $transaction = WalletTransaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Only pending transactions can be approved');
        }

        DB::beginTransaction();
        try {
            $wallet = $transaction->wallet;

            // Update wallet balance
            if ($transaction->type === 'credit') {
                $wallet->balance += $transaction->amount;
                $wallet->total_credited += $transaction->amount;
            } else {
                $wallet->balance -= $transaction->amount;
                $wallet->total_debited += $transaction->amount;
            }
            
            $wallet->last_transaction_at = now();
            $wallet->save();

            // Update transaction
            $transaction->update([
                'status' => 'completed',
                'balance_after' => $wallet->balance,
                'completed_at' => now(),
                'processed_by' => auth()->id(),
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'manually_approved_by' => auth()->user()->name,
                    'manually_approved_at' => now()->toDateTimeString(),
                ]),
            ]);

            DB::commit();

            return back()->with('success', 'Transaction approved successfully');
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

        $transaction = WalletTransaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Only pending transactions can be rejected');
        }

        $transaction->update([
            'status' => 'failed',
            'processed_by' => auth()->id(),
            'metadata' => array_merge($transaction->metadata ?? [], [
                'rejection_reason' => $request->reason,
                'rejected_by' => auth()->user()->name,
                'rejected_at' => now()->toDateTimeString(),
            ]),
        ]);

        return back()->with('success', 'Transaction rejected successfully');
    }

    public function reverse(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $transaction = WalletTransaction::findOrFail($id);

        if ($transaction->status !== 'completed') {
            return back()->with('error', 'Only completed transactions can be reversed');
        }

        DB::beginTransaction();
        try {
            $wallet = $transaction->wallet;

            // Reverse wallet balance
            if ($transaction->type === 'credit') {
                $wallet->balance -= $transaction->amount;
                $wallet->total_credited -= $transaction->amount;
            } else {
                $wallet->balance += $transaction->amount;
                $wallet->total_debited -= $transaction->amount;
            }
            
            $wallet->last_transaction_at = now();
            $wallet->save();

            // Update transaction
            $transaction->update([
                'status' => 'reversed',
                'processed_by' => auth()->id(),
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'reversal_reason' => $request->reason,
                    'reversed_by' => auth()->user()->name,
                    'reversed_at' => now()->toDateTimeString(),
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
