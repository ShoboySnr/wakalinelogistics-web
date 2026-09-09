<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const WIDENED = "enum('pending','confirmed','accepted','arrived_at_pickup','picked_up','in_transit','arrived_at_dropoff','delivered','failed','cancelled')";

    private const ORIGINAL = "enum('pending','confirmed','in_transit','delivered','cancelled')";

    public function up(): void
    {
        DB::statement('ALTER TABLE orders MODIFY status '.self::WIDENED." NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('orders')->whereIn('status', ['accepted', 'arrived_at_pickup'])->update(['status' => 'confirmed']);
        DB::table('orders')->whereIn('status', ['picked_up', 'arrived_at_dropoff'])->update(['status' => 'in_transit']);
        DB::table('orders')->where('status', 'failed')->update(['status' => 'cancelled']);

        DB::statement('ALTER TABLE orders MODIFY status '.self::ORIGINAL." NOT NULL DEFAULT 'pending'");
    }
};
