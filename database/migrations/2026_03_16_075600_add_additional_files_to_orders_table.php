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
            $table->string('additional_file_1')->nullable()->after('delivery_proof_image');
            $table->string('additional_file_2')->nullable()->after('additional_file_1');
            $table->string('additional_file_3')->nullable()->after('additional_file_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'additional_file_1',
                'additional_file_2',
                'additional_file_3',
            ]);
        });
    }
};
