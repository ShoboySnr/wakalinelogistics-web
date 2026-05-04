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
            // Only add columns that don't already exist
            if (!Schema::hasColumn('clients', 'theme_preference')) {
                $table->enum('theme_preference', ['light', 'dark'])->default('light')->after('email');
            }
            if (!Schema::hasColumn('clients', 'default_pickup_address')) {
                $table->string('default_pickup_address', 500)->nullable()->after('theme_preference');
            }
            if (!Schema::hasColumn('clients', 'default_pickup_contact_name')) {
                $table->string('default_pickup_contact_name')->nullable()->after('default_pickup_address');
            }
            if (!Schema::hasColumn('clients', 'default_pickup_contact_phone')) {
                $table->string('default_pickup_contact_phone', 20)->nullable()->after('default_pickup_contact_name');
            }
            if (!Schema::hasColumn('clients', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable()->after('default_pickup_contact_phone');
            }
            if (!Schema::hasColumn('clients', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('notification_preferences');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $columns = [
                'theme_preference',
                'default_pickup_address',
                'default_pickup_contact_name',
                'default_pickup_contact_phone',
                'notification_preferences',
                'two_factor_enabled',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
