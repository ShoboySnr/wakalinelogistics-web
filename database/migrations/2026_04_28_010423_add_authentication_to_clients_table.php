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
            // Make email unique and required for authentication
            $table->string('email')->unique()->change();
            
            // Add password field for authentication
            $table->string('password')->nullable()->after('email');
            
            // Add remember token for "remember me" functionality
            $table->rememberToken()->after('password');
            
            // Add email verification
            $table->timestamp('email_verified_at')->nullable()->after('remember_token');
            
            // Add dashboard access control
            $table->boolean('dashboard_enabled')->default(false)->after('is_active');
            
            // Add last login tracking
            $table->timestamp('last_login_at')->nullable()->after('dashboard_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'remember_token',
                'email_verified_at',
                'dashboard_enabled',
                'last_login_at'
            ]);
            
            // Revert email to nullable
            $table->string('email')->nullable()->change();
        });
    }
};
