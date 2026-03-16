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
        Schema::create('metter_features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('settings')->nullable(); // Feature-specific settings
            $table->decimal('price', 10, 2)->nullable(); // If feature has additional cost
            $table->timestamps();
            
            $table->index('is_enabled');
            $table->index('is_premium');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metter_features');
    }
};
