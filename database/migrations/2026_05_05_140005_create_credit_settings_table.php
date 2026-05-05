<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credit_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Setting key
            $table->text('value')->nullable(); // Setting value
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->string('group')->default('general'); // Group settings together
            $table->timestamps();
        });

        // Insert default settings
        DB::table('credit_settings')->insert([
            [
                'key' => 'credits_per_delivery',
                'value' => '1',
                'type' => 'integer',
                'description' => 'Number of credits required per delivery',
                'group' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'allow_negative_credits',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Allow clients to go into negative credit balance',
                'group' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'low_credit_threshold',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Show low credit warning when balance falls below this',
                'group' => 'notifications',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'credit_expiry_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable credit expiry',
                'group' => 'expiry',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_credit_validity_days',
                'value' => '365',
                'type' => 'integer',
                'description' => 'Default number of days credits are valid',
                'group' => 'expiry',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_settings');
    }
};
