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
        Schema::create('client_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('integration_type'); // mailchimp, sendgrid, slack, etc.
            $table->boolean('is_active')->default(true);
            $table->json('credentials')->nullable(); // Encrypted API keys, tokens
            $table->json('settings')->nullable(); // Integration-specific settings
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->integer('sync_count')->default(0);
            $table->timestamps();
            
            $table->unique(['client_id', 'integration_type']);
            $table->index('integration_type');
        });

        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_integration_id')->constrained('client_integrations')->onDelete('cascade');
            $table->string('action'); // sync, send, webhook, etc.
            $table->string('status'); // success, failed, pending
            $table->json('data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['client_integration_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('client_integrations');
    }
};
