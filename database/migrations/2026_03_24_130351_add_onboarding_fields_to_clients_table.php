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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('contact_person')->nullable()->after('company_name');
            $table->text('business_address')->nullable()->after('pickup_address');
            $table->string('city')->nullable()->after('business_address');
            $table->string('state')->nullable()->after('city');
            $table->string('business_type')->nullable()->after('state');
            $table->string('tax_id')->nullable()->after('business_type');
            $table->string('website')->nullable()->after('tax_id');
            $table->string('alternate_phone')->nullable()->after('phone');
            $table->string('alternate_email')->nullable()->after('email');
            $table->enum('payment_terms', ['prepaid', 'postpaid', 'credit_30', 'credit_60'])->default('prepaid')->after('alternate_email');
            $table->decimal('credit_limit', 10, 2)->nullable()->after('payment_terms');
            $table->text('special_instructions')->nullable()->after('notes');
            $table->date('onboarded_date')->nullable()->after('special_instructions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'contact_person',
                'business_address',
                'city',
                'state',
                'business_type',
                'tax_id',
                'website',
                'alternate_phone',
                'alternate_email',
                'payment_terms',
                'credit_limit',
                'special_instructions',
                'onboarded_date',
            ]);
        });
    }
};
