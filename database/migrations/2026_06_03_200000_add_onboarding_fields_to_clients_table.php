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
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'onboarding_completed')) {
                $table->boolean('onboarding_completed')->default(false);
            }
            if (!Schema::hasColumn('clients', 'onboarding_skipped')) {
                $table->boolean('onboarding_skipped')->default(false);
            }
            if (!Schema::hasColumn('clients', 'onboarding_completed_at')) {
                $table->timestamp('onboarding_completed_at')->nullable();
            }
            if (!Schema::hasColumn('clients', 'onboarding_current_step')) {
                $table->integer('onboarding_current_step')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'onboarding_completed',
                'onboarding_skipped',
                'onboarding_completed_at',
                'onboarding_current_step',
            ]);
        });
    }
};
