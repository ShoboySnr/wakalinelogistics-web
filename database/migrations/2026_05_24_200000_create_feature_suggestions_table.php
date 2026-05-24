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
        Schema::create('feature_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('category')->nullable(); // UI/UX, Integration, Automation, etc.
            $table->enum('status', ['pending', 'under_review', 'planned', 'in_progress', 'completed', 'declined'])->default('pending');
            $table->integer('upvotes')->default(0);
            $table->text('admin_response')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('reward_given')->default(false);
            $table->decimal('reward_amount', 10, 2)->nullable();
            $table->timestamps();
            
            $table->index(['client_id', 'status']);
            $table->index('status');
        });

        Schema::create('feature_suggestion_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_suggestion_id')->constrained('feature_suggestions')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['feature_suggestion_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_suggestion_votes');
        Schema::dropIfExists('feature_suggestions');
    }
};
