<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('pickup_latitude', 10, 7)->nullable()->after('pickup_city');
            $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude');
            $table->decimal('delivery_latitude', 10, 7)->nullable()->after('delivery_city');
            $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
            $table->timestamp('geocoded_at')->nullable()->after('delivery_longitude');

            $table->index(['pickup_latitude', 'pickup_longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['pickup_latitude', 'pickup_longitude']);
            $table->dropColumn([
                'pickup_latitude', 'pickup_longitude',
                'delivery_latitude', 'delivery_longitude', 'geocoded_at',
            ]);
        });
    }
};
