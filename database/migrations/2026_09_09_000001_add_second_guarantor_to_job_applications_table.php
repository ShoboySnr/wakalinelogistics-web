<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->renameColumn('guarantor_name', 'guarantor1_full_name');
            $table->renameColumn('guarantor_phone', 'guarantor1_phone');
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('guarantor1_relationship')->nullable()->after('guarantor1_phone');
            $table->string('guarantor1_occupation')->nullable()->after('guarantor1_relationship');
            $table->string('guarantor1_residential_address')->nullable()->after('guarantor1_occupation');

            $table->string('guarantor2_full_name')->nullable()->after('guarantor1_residential_address');
            $table->string('guarantor2_phone')->nullable()->after('guarantor2_full_name');
            $table->string('guarantor2_relationship')->nullable()->after('guarantor2_phone');
            $table->string('guarantor2_occupation')->nullable()->after('guarantor2_relationship');
            $table->string('guarantor2_residential_address')->nullable()->after('guarantor2_occupation');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'guarantor1_relationship',
                'guarantor1_occupation',
                'guarantor1_residential_address',
                'guarantor2_full_name',
                'guarantor2_phone',
                'guarantor2_relationship',
                'guarantor2_occupation',
                'guarantor2_residential_address',
            ]);
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->renameColumn('guarantor1_full_name', 'guarantor_name');
            $table->renameColumn('guarantor1_phone', 'guarantor_phone');
        });
    }
};
