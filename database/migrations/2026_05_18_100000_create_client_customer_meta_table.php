<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_customer_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('receiver_phone', 30);
            $table->boolean('starred')->default(false);
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->json('addresses')->nullable(); // [{label, address}]
            $table->timestamps();

            $table->unique(['client_id', 'receiver_phone']);
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_customer_meta');
    }
};
