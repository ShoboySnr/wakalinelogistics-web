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
            $table->string('package_image_1')->nullable()->after('notes');
            $table->string('package_image_2')->nullable()->after('package_image_1');
            $table->string('package_image_3')->nullable()->after('package_image_2');
            $table->string('package_image_4')->nullable()->after('package_image_3');
            $table->string('delivery_proof_image')->nullable()->after('package_image_4');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'package_image_1',
                'package_image_2',
                'package_image_3',
                'package_image_4',
                'delivery_proof_image'
            ]);
        });
    }
};
