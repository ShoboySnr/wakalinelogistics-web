# Wakaline Meter API Documentation

## Overview

The Wakaline Meter API provides real-time delivery pricing for Lagos, Nigeria. This API is designed for ecommerce businesses and logistics platforms to integrate delivery cost calculations into their checkout process.

**Base URL**: `https://your-domain.com/api/wakalinelogistics/v1/meter`

---

## Authentication

Currently, the API is open for testing. For production use, contact us for API key authentication.

---

## Endpoints

### 1. Calculate Delivery Price (Full Details)

Get complete delivery information including zones, distance, and pricing.

**Endpoint**: `POST /api/wakalinelogistics/v1/meter/calculate`

**Request Body**:
```json
{
  "pickup_address": "Ikeja, Lagos",
  "dropoff_address": "Lekki Phase 1, Lagos"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "pickup": {
      "address": "Ikeja, Lagos, Nigeria",
      "zone": "Zone A"
    },
    "delivery": {
      "address": "Lekki Phase 1, Lagos, Nigeria",
      "zone": "Zone E"
    },
    "distance_km": 34.5,
    "delivery_fee": 6500,
    "currency": "NGN"
  }
}
```

**Error Response** (400 Bad Request):
```json
{
  "success": false,
  "error": "Could not geocode delivery address",
  "message": "Failed to calculate delivery price"
}
```

---

### 2. Quick Quote (Price Only)

Get just the delivery fee and distance for faster responses.

**Endpoint**: `POST /api/wakalinelogistics/v1/meter/quote`

**Request Body**:
```json
{
  "pickup_address": "Yaba, Lagos",
  "dropoff_address": "Surulere, Lagos"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "delivery_fee": 3500,
  "distance_km": 8.2,
  "currency": "NGN"
}
```

---

### 3. Get Delivery Zones

Retrieve all available Lagos delivery zones and their locations.

**Endpoint**: `GET /api/wakalinelogistics/v1/meter/zones`

**Response** (200 OK):
```json
{
  "success": true,
  "zones": {
    "Zone A": {
      "name": "Zone A",
      "type": "Mainland",
      "locations": ["Agege", "Ogba", "Ikeja", "Iju", "Abule Egba"]
    },
    "Zone B": {
      "name": "Zone B",
      "type": "Mainland",
      "locations": ["Ketu", "Ojota", "Maryland", "Gbagada"]
    }
  }
}
```

---

### 4. Get Pricing Rules

Understand how delivery fees are calculated.

**Endpoint**: `GET /api/wakalinelogistics/v1/meter/pricing-rules`

**Response** (200 OK):
```json
{
  "success": true,
  "pricing": {
    "base_fee": 2500,
    "per_km_rate": 100,
    "adjustments": {
      "bridge_crossing": {
        "description": "Mainland to Island or Island to Mainland",
        "fee": 500
      },
      "lekki_toll": {
        "description": "Delivery to Lekki, Ajah, or Sangotedo",
        "fee": 500
      },
      "apapa_congestion": {
        "description": "Delivery to Apapa or Ajegunle",
        "fee": 1000
      },
      "interstate": {
        "description": "Delivery to Mowe, Sango Ota, or Ota",
        "fee": 1000
      }
    },
    "rounding": "Rounded to nearest 500 Naira",
    "currency": "NGN"
  }
}
```

---

### 5. Health Check

Check API service status.

**Endpoint**: `GET /api/wakalinelogistics/v1/meter/health`

**Response** (200 OK):
```json
{
  "success": true,
  "service": "Wakaline Meter API",
  "status": "operational",
  "version": "1.0.0",
  "timestamp": "2026-03-11T20:20:00.000000Z"
}
```

---

## Pricing Formula

```
Base Price = ₦2,500 + (Distance in KM × ₦100)

Adjustments:
+ ₦500  (Bridge crossing: Mainland ↔ Island)
+ ₦500  (Lekki toll: Lekki, Ajah, Sangotedo)
+ ₦1,000 (Apapa congestion: Apapa, Ajegunle)
+ ₦1,000 (Interstate: Mowe, Sango Ota)

Final Price = Rounded to nearest ₦500
```

---

## Example Use Cases

### Ecommerce Checkout Integration

```javascript
// Calculate delivery fee during checkout
async function getDeliveryFee(pickupAddress, deliveryAddress) {
  const response = await fetch('https://your-domain.com/api/wakalinelogistics/v1/meter/quote', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      pickup_address: pickupAddress,
      dropoff_address: deliveryAddress
    })
  });
  
  const data = await response.json();
  
  if (data.success) {
    return data.delivery_fee;
  } else {
    throw new Error(data.error);
  }
}

// Usage
const fee = await getDeliveryFee('Ikeja, Lagos', 'Lekki, Lagos');
console.log(`Delivery Fee: ₦${fee.toLocaleString()}`);
```

### PHP Integration

```php
<?php

function calculateDeliveryPrice($pickupAddress, $deliveryAddress) {
    $url = 'https://your-domain.com/api/wakalinelogistics/v1/meter/calculate';
    
    $data = [
        'pickup_address' => $pickupAddress,
        'dropoff_address' => $deliveryAddress
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Usage
$result = calculateDeliveryPrice('Yaba, Lagos', 'Surulere, Lagos');

if ($result['success']) {
    echo "Delivery Fee: ₦" . number_format($result['data']['delivery_fee']);
    echo "\nDistance: " . $result['data']['distance_km'] . " km";
}
?>
```

### Python Integration

```python
import requests

def get_delivery_quote(pickup_address, delivery_address):
    url = 'https://your-domain.com/api/wakalinelogistics/v1/meter/quote'
    
    payload = {
        'pickup_address': pickup_address,
        'dropoff_address': delivery_address
    }
    
    response = requests.post(url, json=payload)
    data = response.json()
    
    if data['success']:
        return {
            'fee': data['delivery_fee'],
            'distance': data['distance_km']
        }
    else:
        raise Exception(data['error'])

# Usage
quote = get_delivery_quote('Ikeja, Lagos', 'Lekki, Lagos')
print(f"Delivery Fee: ₦{quote['fee']:,}")
print(f"Distance: {quote['distance']} km")
```

---

## Rate Limits

- **Development**: No rate limits
- **Production**: Contact us for rate limit information

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request - Invalid parameters or geocoding failed |
| 422 | Validation Error - Missing required fields |
| 500 | Server Error |

---

## Support Zones

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

---

## Best Practices

1. **Cache Results**: Cache delivery fees for common routes to reduce API calls
2. **Validate Addresses**: Ensure addresses include "Lagos" or "Nigeria" for better accuracy
3. **Handle Errors**: Always handle API errors gracefully in your application
4. **Use Quick Quote**: Use `/quote` endpoint for faster responses when you only need the price
5. **Display Zones**: Show available zones to users to help them format addresses correctly

---

## Testing

### Test Addresses

**Mainland to Mainland** (Lower fees):
- Pickup: `Ikeja, Lagos`
- Delivery: `Yaba, Lagos`
- Expected: ~₦3,500

**Mainland to Island** (Bridge fee):
- Pickup: `Ogba, Lagos`
- Delivery: `Lekki, Lagos`
- Expected: ~₦6,500

**Lekki Delivery** (Toll fee):
- Pickup: `Ikeja, Lagos`
- Delivery: `Lekki Phase 1, Lagos`
- Expected: ~₦6,500

**Interstate** (Higher fees):
- Pickup: `Ikeja, Lagos`
- Delivery: `Mowe, Ogun State`
- Expected: ~₦8,000+

---

## Contact & Support

For API keys, rate limit increases, or technical support:
- **WhatsApp**: +234 810 066 5758
- **Email**: api@wakalinelogistics.com

---

## Changelog

### Version 1.0.0 (March 2026)
- Initial API release
- Calculate delivery price endpoint
- Quick quote endpoint
- Zone information endpoint
- Pricing rules endpoint
- Health check endpoint

---

**API Status**: ✅ Operational  
**Last Updated**: March 11, 2026
