<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Base Delivery Pricing
    |--------------------------------------------------------------------------
    |
    | Configure the base pricing structure for delivery calculations.
    | All amounts are in Naira (NGN).
    |
    */

    'base_fee' => 2500, // Base delivery fee in NGN

    'per_km_rate' => 100, // Cost per kilometer in NGN

    'currency' => 'NGN',

    /*
    |--------------------------------------------------------------------------
    | Fuel Cost Calculation
    |--------------------------------------------------------------------------
    |
    | Configure fuel-related parameters for dynamic fuel cost calculation.
    |
    */

    'fuel' => [
        'price_per_liter' => 500, // Current fuel price in NGN per liter
        'vehicle_consumption' => 12, // Average km per liter (fuel efficiency)
        'fuel_surcharge_percentage' => 10, // Additional percentage for fuel price volatility
    ],

    /*
    |--------------------------------------------------------------------------
    | Zone-Based Adjustments
    |--------------------------------------------------------------------------
    |
    | Additional fees based on specific zones or crossing between zones.
    |
    */

    'adjustments' => [
        // Fee for crossing between Mainland and Island
        'bridge_crossing' => 500,

        // Additional fee for Lekki/Ajah/Sangotedo areas
        'lekki_premium' => 500,

        // Additional fee for Apapa/Ajegunle areas
        'apapa_premium' => 1000,

        // Additional fee for Mowe/Sango Ota/Ogun areas
        'interstate_premium' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Price Rounding
    |--------------------------------------------------------------------------
    |
    | Round final prices to the nearest multiple of this amount.
    | Set to 500 to round to nearest ₦500, or 100 for nearest ₦100.
    |
    */

    'round_to_nearest' => 500,

    /*
    |--------------------------------------------------------------------------
    | Minimum Delivery Fee
    |--------------------------------------------------------------------------
    |
    | The minimum amount to charge for any delivery, regardless of distance.
    |
    */

    'minimum_fee' => 2500,

    /*
    |--------------------------------------------------------------------------
    | Maximum Distance (Optional)
    |--------------------------------------------------------------------------
    |
    | Set a maximum distance limit in kilometers. Set to null for no limit.
    |
    */

    'max_distance_km' => null, // e.g., 100 for 100km limit

];
