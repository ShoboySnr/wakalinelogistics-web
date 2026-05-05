<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Lagos Island", "Mainland", "Interstate"
            $table->string('code')->unique(); // e.g., "LI", "ML", "IS"
            $table->text('description')->nullable();
            $table->json('areas')->nullable(); // List of areas/LGAs in this zone
            $table->integer('base_credits')->default(1); // Base credits for delivery within this zone
            $table->decimal('base_price', 10, 2)->nullable(); // Base price equivalent
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default zones for Lagos
        DB::table('delivery_zones')->insert([
            [
                'name' => 'Lagos Island (Same Zone)',
                'code' => 'LI-SAME',
                'description' => 'Deliveries within Lagos Island',
                'areas' => json_encode(['Victoria Island', 'Ikoyi', 'Lekki Phase 1', 'Ajah', 'Eko Atlantic']),
                'base_credits' => 1,
                'base_price' => 1000.00,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mainland (Same Zone)',
                'code' => 'ML-SAME',
                'description' => 'Deliveries within Lagos Mainland',
                'areas' => json_encode(['Ikeja', 'Yaba', 'Surulere', 'Gbagada', 'Maryland', 'Anthony']),
                'base_credits' => 1,
                'base_price' => 1000.00,
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Island to Mainland',
                'code' => 'LI-ML',
                'description' => 'Deliveries from Lagos Island to Mainland or vice versa',
                'areas' => json_encode([]),
                'base_credits' => 2,
                'base_price' => 1500.00,
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Outskirts Lagos',
                'code' => 'OUTSKIRTS',
                'description' => 'Deliveries to Lagos outskirts',
                'areas' => json_encode(['Ikorodu', 'Epe', 'Badagry', 'Ibeju-Lekki']),
                'base_credits' => 3,
                'base_price' => 2500.00,
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Interstate (Nearby)',
                'code' => 'IS-NEAR',
                'description' => 'Interstate deliveries to nearby states',
                'areas' => json_encode(['Ogun State', 'Oyo State']),
                'base_credits' => 5,
                'base_price' => 5000.00,
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Interstate (Far)',
                'code' => 'IS-FAR',
                'description' => 'Interstate deliveries to distant states',
                'areas' => json_encode(['Abuja', 'Port Harcourt', 'Kano', 'Enugu']),
                'base_credits' => 10,
                'base_price' => 10000.00,
                'sort_order' => 6,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
