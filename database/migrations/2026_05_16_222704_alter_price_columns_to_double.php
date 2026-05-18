<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->double('price')->change();
        });

        Schema::table('credit_packages', function (Blueprint $table) {
            $table->double('price')->change();
            $table->double('price_per_credit')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->change();
        });

        Schema::table('credit_packages', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->change();
            $table->decimal('price_per_credit', 10, 2)->default(0)->change();
        });
    }
};
