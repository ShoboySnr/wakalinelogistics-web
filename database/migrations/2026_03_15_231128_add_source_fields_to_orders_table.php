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
            $table->string('source')->nullable()->after('rider_id'); // whatsapp, web, phone, walk-in
            $table->string('source_contact')->nullable()->after('source'); // phone number or email
            $table->text('source_notes')->nullable()->after('source_contact'); // additional source information
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['source', 'source_contact', 'source_notes']);
        });
    }
};
