# DeliveryCalculator Module

A standalone Laravel module for calculating Lagos logistics delivery prices based on pickup and delivery locations.

## Features

- **Zone-based pricing**: Automatic detection of Lagos delivery zones (A-F)
- **Distance calculation**: Real-time distance calculation using Google Maps API
- **Smart pricing**: Dynamic pricing with adjustments for bridges, tolls, and congestion
- **Google Places Autocomplete**: Easy address input with autocomplete suggestions
- **Responsive UI**: Modern, mobile-friendly interface with brand colors (#C1666B, #2F3437)
- **Isolated architecture**: Completely self-contained module that doesn't affect existing code

## Module Structure

```
app/Modules/DeliveryCalculator/
├── Controllers/
│   └── DeliveryCalculatorController.php
├── Services/
│   └── DeliveryPriceService.php
├── Routes/
│   └── web.php
├── Views/
│   └── calculator.blade.php
├── Helpers/
│   └── ZoneDetector.php
└── README.md
```

## Installation & Setup

### 1. Google Maps API Key

Get your API key from [Google Cloud Console](https://console.cloud.google.com/) and enable:
- Geocoding API
- Distance Matrix API
- Places API

Add to `.env`:
```env
GOOGLE_MAPS_API_KEY=your_api_key_here
```

### 2. Routes Registration

Routes are automatically registered in `bootstrap/app.php`:
```php
Route::middleware('web')
    ->group(base_path('app/Modules/DeliveryCalculator/Routes/web.php'));
```

### 3. Views Registration

Views are registered in `AppServiceProvider`:
```php
$this->loadViewsFrom(
    app_path('Modules/DeliveryCalculator/Views'),
    'delivery-calculator'
);
```

## Usage

### Access the Calculator

Visit: `http://your-domain.com/delivery-calculator`

### API Endpoint

**POST** `/delivery-calculator/calculate`

**Request:**
```json
{
    "pickup_address": "Iju Fagba Lagos",
    "delivery_address": "Lekki Phase 1 Lagos"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "pickup": "Iju Fagba, Lagos, Nigeria",
        "delivery": "Lekki Phase 1, Lagos, Nigeria",
        "distance_km": 34.5,
        "pickup_zone": "Zone A",
        "delivery_zone": "Zone E",
        "delivery_fee": 5500
    }
}
```

## Lagos Zones

### Zone A (Mainland)
Agege, Ogba, Ikeja, Iju, Abule Egba, Fagba

### Zone B (Mainland)
Ketu, Ojota, Maryland, Gbagada, Ogudu, Magodo

### Zone C (Mainland)
Yaba, Surulere, Isolo, Festac, Oshodi, Mushin

### Zone D (Island)
Ikoyi, Victoria Island, Lagos Island, Marina

### Zone E (Island)
Lekki, Ajah, Sangotedo, Chevron, VGC

### Zone F (Interstate)
Ikorodu, Mowe, Sango Ota, Agbara, Arepo

## Pricing Formula

### Base Price
```
price = 2500 + (distance_km × 100)
```

### Adjustments

| Condition | Additional Fee |
|-----------|---------------|
| Bridge crossing (Mainland ↔ Island) | ₦500 |
| Lekki toll (Lekki, Ajah, Sangotedo) | ₦500 |
| Apapa congestion (Apapa, Ajegunle) | ₦1,000 |
| Interstate (Mowe, Sango Ota) | ₦1,000 |

**Final price is rounded to the nearest ₦500**

## Example Calculations

### Example 1: Ikeja to Lekki
- Distance: 30km
- Base: 2500 + (30 × 100) = 5500
- Bridge crossing: +500
- Lekki toll: +500
- **Total: ₦6,500**

### Example 2: Yaba to Surulere
- Distance: 8km
- Base: 2500 + (8 × 100) = 3300
- No adjustments
- Rounded: **₦3,500**

### Example 3: Ikeja to Apapa
- Distance: 25km
- Base: 2500 + (25 × 100) = 5000
- Apapa congestion: +1000
- **Total: ₦6,000**

## Components

### ZoneDetector
Detects Lagos zones from addresses using keyword matching.

```php
use App\Modules\DeliveryCalculator\Helpers\ZoneDetector;

$zone = ZoneDetector::detectZone('Lekki Phase 1, Lagos');
// Returns: "Zone E"
```

### DeliveryPriceService
Handles geocoding, distance calculation, and price computation.

```php
use App\Modules\DeliveryCalculator\Services\DeliveryPriceService;

$service = new DeliveryPriceService();
$result = $service->processDeliveryCalculation(
    'Ikeja, Lagos',
    'Lekki, Lagos'
);
```

### DeliveryCalculatorController
Manages HTTP requests and responses.

- `index()`: Returns calculator view
- `calculate()`: Processes calculation requests

## UI Features

- **Google Places Autocomplete**: Intelligent address suggestions
- **Real-time calculation**: Instant price quotes
- **Responsive design**: Works on all devices
- **Brand colors**: Primary (#C1666B), Secondary (#2F3437)
- **Loading states**: Visual feedback during calculations
- **Error handling**: User-friendly error messages
- **Zone information**: Educational zone reference guide

## Testing

### Manual Testing
1. Visit `/delivery-calculator`
2. Enter pickup address (e.g., "Ikeja, Lagos")
3. Enter delivery address (e.g., "Lekki, Lagos")
4. Click "Calculate Delivery Fee"
5. Verify results display correctly

### API Testing
```bash
curl -X POST http://localhost:8000/delivery-calculator/calculate \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-token" \
  -d '{
    "pickup_address": "Ikeja Lagos",
    "delivery_address": "Lekki Lagos"
  }'
```

## Troubleshooting

### "Could not geocode address"
- Verify Google Maps API key is set in `.env`
- Check that Geocoding API is enabled in Google Cloud Console
- Ensure address includes "Lagos" or "Nigeria"

### "Could not calculate distance"
- Verify Distance Matrix API is enabled
- Check API key has proper permissions
- Ensure both addresses are valid Lagos locations

### Routes not working
- Run `php artisan route:clear`
- Verify routes are registered in `bootstrap/app.php`
- Check middleware configuration

### Views not loading
- Verify view namespace is registered in `AppServiceProvider`
- Run `php artisan view:clear`
- Check file permissions

## Performance

- **Caching**: Consider caching geocoding results for frequently used addresses
- **Rate limiting**: Google Maps API has usage limits
- **Optimization**: Distance calculations are cached by Google for 24 hours

## Future Enhancements

- [ ] Add estimated travel time
- [ ] Show route map preview
- [ ] Implement "Book Delivery" functionality
- [ ] Add price history tracking
- [ ] Support for multiple delivery stops
- [ ] Integration with payment gateway
- [ ] SMS/Email quote notifications
- [ ] Admin dashboard for pricing rules

## Security

- CSRF protection enabled on all POST requests
- Input validation on all user inputs
- API key stored securely in `.env` file
- No sensitive data exposed in frontend

## License

This module is part of the Wakaline Logistics application.

## Support

For issues or questions, contact the development team.
