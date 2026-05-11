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
        if (!Schema::hasTable('bikes')) {
            Schema::create('bikes', function (Blueprint $table) {
                $table->id();
                $table->string('bike_number')->unique(); // e.g., BIKE-001
                $table->string('brand')->nullable(); // Honda, Yamaha, etc.
                $table->string('model')->nullable();
                $table->string('plate_number')->unique();
                $table->string('color')->nullable();
                $table->year('year')->nullable();
                $table->string('engine_number')->nullable();
                $table->string('chassis_number')->nullable();
                $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
                
                // Document fields
                $table->string('registration_document')->nullable(); // File path
                $table->date('registration_expiry_date')->nullable();
                
                $table->string('insurance_document')->nullable(); // File path
                $table->date('insurance_expiry_date')->nullable();
                
                $table->string('roadworthiness_document')->nullable(); // File path
                $table->date('roadworthiness_expiry_date')->nullable();
                
                $table->string('hackney_permit_document')->nullable(); // File path
                $table->date('hackney_permit_expiry_date')->nullable();
                
                $table->string('vehicle_license_document')->nullable(); // File path
                $table->date('vehicle_license_expiry_date')->nullable();
                
                // Stickers/Permits (flexible - multiple items)
                $table->json('stickers_permits')->nullable(); // [{name, serial_number, expiry_date, document_path}]
                
                // Assignment
                $table->foreignId('assigned_rider_id')->nullable()->constrained('riders')->onDelete('set null');
                $table->date('assignment_date')->nullable();
                
                // Maintenance tracking
                $table->date('last_maintenance_date')->nullable();
                $table->date('next_maintenance_date')->nullable();
                $table->text('notes')->nullable();
                
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bikes');
    }
};
