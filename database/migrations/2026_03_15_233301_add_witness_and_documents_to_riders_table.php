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
        Schema::table('riders', function (Blueprint $table) {
            // Witness Information
            $table->string('witness_full_name')->nullable()->after('guarantor2_years_known');
            $table->string('witness_phone')->nullable()->after('witness_full_name');
            $table->text('witness_address')->nullable()->after('witness_phone');
            $table->string('witness_signature')->nullable()->after('witness_address'); // File path
            $table->date('witness_date')->nullable()->after('witness_signature');
            
            // Document Uploads for Guarantors
            $table->string('guarantor1_id_document')->nullable()->after('witness_date'); // ID card/passport
            $table->string('guarantor1_proof_of_address')->nullable()->after('guarantor1_id_document');
            $table->string('guarantor1_employment_letter')->nullable()->after('guarantor1_proof_of_address');
            $table->string('guarantor1_additional_doc')->nullable()->after('guarantor1_employment_letter');
            
            $table->string('guarantor2_id_document')->nullable()->after('guarantor1_additional_doc');
            $table->string('guarantor2_proof_of_address')->nullable()->after('guarantor2_id_document');
            $table->string('guarantor2_employment_letter')->nullable()->after('guarantor2_proof_of_address');
            $table->string('guarantor2_additional_doc')->nullable()->after('guarantor2_employment_letter');
            
            // Rider's own documents
            $table->string('rider_photo')->nullable()->after('guarantor2_additional_doc');
            $table->string('rider_id_document')->nullable()->after('rider_photo');
            $table->string('driver_license_doc')->nullable()->after('rider_id_document');
            $table->string('vehicle_registration')->nullable()->after('driver_license_doc');
            $table->string('vehicle_insurance')->nullable()->after('vehicle_registration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn([
                'witness_full_name', 'witness_phone', 'witness_address', 'witness_signature', 'witness_date',
                'guarantor1_id_document', 'guarantor1_proof_of_address', 'guarantor1_employment_letter', 'guarantor1_additional_doc',
                'guarantor2_id_document', 'guarantor2_proof_of_address', 'guarantor2_employment_letter', 'guarantor2_additional_doc',
                'rider_photo', 'rider_id_document', 'driver_license_doc', 'vehicle_registration', 'vehicle_insurance',
            ]);
        });
    }
};
