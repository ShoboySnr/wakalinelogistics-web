<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->boolean('owns_vehicle')->nullable()->after('license_number');
            $table->string('vehicle_type')->nullable()->after('owns_vehicle');
            $table->string('vehicle_registration')->nullable()->after('vehicle_type');
            $table->text('coverage_areas')->nullable()->after('vehicle_registration');
            $table->boolean('has_smartphone')->nullable()->after('coverage_areas');
            $table->string('guarantor_name')->nullable()->after('has_smartphone');
            $table->string('guarantor_phone')->nullable()->after('guarantor_name');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'owns_vehicle',
                'vehicle_type',
                'vehicle_registration',
                'coverage_areas',
                'has_smartphone',
                'guarantor_name',
                'guarantor_phone',
            ]);
        });
    }
};
