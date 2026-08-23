<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('deduction_amount', 12, 2)->default(0)->after('tax_amount');
            $table->string('deduction_label')->nullable()->after('deduction_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['deduction_amount', 'deduction_label']);
        });
    }
};
