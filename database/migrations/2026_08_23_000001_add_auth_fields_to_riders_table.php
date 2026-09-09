<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('password');
            $table->string('login_otp', 10)->nullable()->after('phone_verified_at');
            $table->timestamp('login_otp_expires_at')->nullable()->after('login_otp');
            $table->timestamp('last_login_at')->nullable()->after('login_otp_expires_at');
            $table->boolean('is_online')->default(false)->after('last_login_at');
            $table->timestamp('last_seen_at')->nullable()->after('is_online');
            $table->string('app_version', 20)->nullable()->after('last_seen_at');
            $table->rememberToken();

            $table->unique('phone');
            $table->index(['is_online', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropIndex(['is_online', 'status']);
            $table->dropColumn([
                'password', 'phone_verified_at', 'login_otp', 'login_otp_expires_at',
                'last_login_at', 'is_online', 'last_seen_at', 'app_version', 'remember_token',
            ]);
        });
    }
};
