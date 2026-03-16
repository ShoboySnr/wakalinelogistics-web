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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone');
            $table->string('pickup_address');
            $table->string('delivery_address');
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('distance', 10, 2)->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('pickup_date')->nullable();
            $table->timestamp('delivery_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
