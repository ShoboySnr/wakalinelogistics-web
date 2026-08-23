<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pickup_zone')->nullable()->after('delivery_city');
            $table->string('delivery_zone')->nullable()->after('pickup_zone');
            $table->decimal('base_price', 10, 2)->nullable()->after('price');
            $table->decimal('zone_discount_percent', 5, 2)->nullable()->after('base_price');
            $table->decimal('zone_discount_amount', 10, 2)->nullable()->after('zone_discount_percent');
            $table->unsignedInteger('zone_batch_size')->nullable()->after('zone_discount_amount');
            $table->index(['client_id', 'delivery_zone', 'status'], 'orders_client_zone_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_client_zone_status_index');
            $table->dropColumn([
                'pickup_zone',
                'delivery_zone',
                'base_price',
                'zone_discount_percent',
                'zone_discount_amount',
                'zone_batch_size',
            ]);
        });
    }
};
