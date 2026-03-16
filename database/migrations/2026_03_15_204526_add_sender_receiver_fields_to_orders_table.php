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
            $table->string('sender_name')->nullable()->after('customer_phone');
            $table->string('sender_phone')->nullable()->after('sender_name');
            $table->string('sender_email')->nullable()->after('sender_phone');
            $table->string('receiver_name')->nullable()->after('delivery_address');
            $table->string('receiver_phone')->nullable()->after('receiver_name');
            $table->text('item_description')->nullable()->after('receiver_phone');
            $table->string('item_size')->nullable()->after('item_description');
            $table->integer('quantity')->default(1)->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'sender_name',
                'sender_phone',
                'sender_email',
                'receiver_name',
                'receiver_phone',
                'item_description',
                'item_size',
                'quantity'
            ]);
        });
    }
};
