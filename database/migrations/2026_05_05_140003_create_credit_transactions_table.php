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
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('transaction_reference')->unique();
            $table->enum('type', ['purchase', 'usage', 'refund', 'adjustment', 'expiry'])->index();
            $table->integer('credits'); // Number of credits (positive for add, negative for deduct)
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->onDelete('set null');
            $table->foreignId('credit_package_id')->nullable()->constrained('credit_packages')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->decimal('amount_paid', 10, 2)->nullable(); // Amount paid for purchase
            $table->string('payment_method')->nullable(); // wallet, paystack, etc.
            $table->string('payment_reference')->nullable();
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['client_id', 'type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
