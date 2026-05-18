<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->default('cash')->after('status');
            $table->boolean('paid_with_credits')->default(false)->after('payment_method');
            $table->integer('credits_used')->default(0)->after('paid_with_credits');
            $table->string('package_description')->nullable()->after('notes');
            $table->integer('package_quantity')->default(1)->after('package_description');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'paid_with_credits', 'credits_used', 'package_description', 'package_quantity']);
        });
    }
};
