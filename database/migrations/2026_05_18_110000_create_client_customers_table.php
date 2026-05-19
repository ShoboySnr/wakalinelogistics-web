<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone', 30)->nullable();
            $table->string('receiver_email')->nullable();
            $table->string('delivery_address')->nullable();
            $table->unsignedInteger('total_orders')->default(0);
            $table->double('total_spend')->default(0);
            $table->timestamp('last_order_at')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'receiver_phone']);
            $table->index(['client_id', 'receiver_email']);
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_customers');
    }
};
