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
        Schema::table('orders', function (Blueprint $table) {
            // Add birthday field for customers
            if (!Schema::hasColumn('orders', 'receiver_birthday')) {
                $table->date('receiver_birthday')->nullable()->after('receiver_phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'receiver_birthday')) {
                $table->dropColumn('receiver_birthday');
            }
        });
    }
};
