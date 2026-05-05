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
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "10 Deliveries", "50 Deliveries"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('credits'); // Number of delivery credits
            $table->decimal('price', 10, 2); // Price in NGN
            $table->decimal('price_per_credit', 10, 2); // Calculated price per credit
            $table->integer('validity_days')->nullable(); // How long credits are valid (null = forever)
            $table->integer('bonus_credits')->default(0); // Extra credits as bonus
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_packages');
    }
};
