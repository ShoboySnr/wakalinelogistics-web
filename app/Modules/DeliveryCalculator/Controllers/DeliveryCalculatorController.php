<?php

namespace App\Modules\DeliveryCalculator\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DeliveryCalculator\Services\DeliveryPriceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DeliveryCalculatorController extends Controller
{
    private DeliveryPriceService $priceService;

    public function __construct(DeliveryPriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    public function index(): View
    {
        return view('delivery-calculator::calculator');
    }

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_address' => 'required|string|max:500',
            'dropoff_address' => 'required|string|max:500',
        ]);

        $pickupGeo = $this->priceService->geocodeAddress($validated['pickup_address']);
        
        if (!$pickupGeo) {
            return response()->json([
                'success' => false,
                'message' => 'Could not find pickup location. Please enter a complete address (e.g., "Ikeja, Lagos" or "Lekki Phase 1")',
                'hint' => 'Try entering a more specific location like area name or landmark'
            ], 400);
        }

        $deliveryGeo = $this->priceService->geocodeAddress($validated['dropoff_address']);
        
        if (!$deliveryGeo) {
            return response()->json([
                'success' => false,
                'message' => 'Could not find drop-off location. Please enter a complete address (e.g., "Ikeja, Lagos" or "Lekki Phase 1")',
                'hint' => 'Try entering a more specific location like area name or landmark'
            ], 400);
        }

        $result = $this->priceService->processDeliveryCalculation(
            $validated['pickup_address'],
            $validated['dropoff_address']
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
}
