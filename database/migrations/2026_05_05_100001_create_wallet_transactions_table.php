<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->morphs('transactable'); // Polymorphic relationship (transactable_id, transactable_type) - automatically creates index
            $table->string('transaction_reference')->unique();
            $table->enum('type', ['credit', 'debit'])->index();
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('payment_method')->nullable(); // paystack, bank_transfer, manual, etc
            $table->string('payment_reference')->nullable(); // Paystack reference
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending')->index();
            $table->string('description');
            $table->json('metadata')->nullable(); // Store additional info (paystack response, order_id, etc)
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'status'], 'wt_type_status_idx');
            $table->index('created_at', 'wt_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
