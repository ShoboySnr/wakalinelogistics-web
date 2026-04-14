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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            
            // Job Information
            $table->string('job_type')->default('dispatch_rider'); // dispatch_rider, warehouse_staff, etc.
            $table->enum('status', ['pending', 'reviewing', 'shortlisted', 'rejected', 'hired'])->default('pending');
            
            // Personal Information
            $table->string('full_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address');
            $table->integer('age');
            
            // License Information (for riders)
            $table->string('license_number')->nullable();
            
            // Experience
            $table->string('experience_years')->nullable(); // Less than 1 year, 1-2 years, etc.
            $table->text('previous_work')->nullable();
            
            // Availability
            $table->string('availability')->nullable(); // Immediately, Within 1 week, etc.
            
            // Additional Information
            $table->text('why_join')->nullable();
            
            // Admin Notes
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('job_type');
            $table->index('status');
            $table->index('created_at');
            
            // Foreign key for reviewer
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
