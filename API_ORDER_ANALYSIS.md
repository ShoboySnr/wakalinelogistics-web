# API Order Submission Analysis

## 📍 Current API Endpoint

**Endpoint:** `POST /wakalinelogistics/v1/orders/submit`  
**Controller:** `App\Http\Controllers\Api\OrderController@submitOrder`  
**Middleware:** `api.token` (requires API token authentication)

---

## 🔍 How the API Works

### 1. **Request Validation**

The API accepts the following fields:

#### Required Fields:
- `sender_name` - String (2-255 chars, letters/spaces/hyphens/periods only)
- `sender_phone` - String (10-20 chars, valid phone format)
- `sender_email` - Valid email with DNS check
- `pickup_address` - String (10-500 chars)
- `recipient_name` - String (2-255 chars, letters/spaces/hyphens/periods only)
- `recipient_phone` - String (10-20 chars, valid phone format)
- `delivery_address` - String (10-500 chars)
- `package_description` - String (3-255 chars)
- `package_size` - Enum: `Small`, `Medium`, `Large`, `Extra Large`
- `preferred_time` - Enum: `Morning (8AM-12PM)`, `Afternoon (12PM-4PM)`, `Evening (4PM-8PM)`, `Anytime`

#### Optional Fields:
- `pickup_area` - String (max 255 chars)
- `delivery_area` - String (max 255 chars)
- `delivery_notes` - String (max 1000 chars)
- `additional_notes` - String (max 1000 chars)
- `price` - Numeric (0-999999.99)
- `distance` - Numeric (0-9999.99)
- `form_source` - String (max 100 chars, e.g., "Landing Page")

### 2. **Order Creation Process**

```php
Order::create([
    // Source tracking
    'source' => 'Website Form',
    'source_contact' => $sender_phone,
    'source_notes' => 'Submitted via Landing Page',
    
    // Customer/Sender info (backward compatibility)
    'customer_name' => $sender_name,
    'customer_email' => $sender_email,
    'customer_phone' => $sender_phone,
    'sender_name' => $sender_name,
    'sender_phone' => $sender_phone,
    'sender_email' => $sender_email,
    
    // Addresses
    'pickup_address' => $pickup_address . ', ' . $pickup_area,
    'delivery_address' => $delivery_address . ', ' . $delivery_area,
    
    // Receiver info
    'receiver_name' => $recipient_name,
    'receiver_phone' => $recipient_phone,
    
    // Package details
    'item_description' => $package_description,
    'item_size' => $package_size,
    
    // Pricing & distance
    'price' => $price ?? 0,
    'distance' => $distance ?? 0,
    
    // Status
    'status' => 'confirmed', // ✅ Auto-confirmed
    
    // Notes (combined)
    'notes' => "Preferred Pickup Time: {$preferred_time}\n
                Delivery Notes: {$delivery_notes}\n
                Additional Notes: {$additional_notes}"
]);
```

### 3. **Response Format**

**Success (200):**
```json
{
    "success": true,
    "message": "Order submitted successfully! We will contact you shortly.",
    "order_id": 123,
    "order_number": "WKL202604270001"
}
```

**Validation Error (422):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "sender_name": ["Sender name is required"],
        "pickup_address": ["Pickup address must be at least 10 characters"]
    }
}
```

**Server Error (500):**
```json
{
    "success": false,
    "message": "An error occurred while processing your order. Please try again."
}
```

---

## 🔄 Comparison: API vs Admin Order Creation

### **Similarities:**

| Field | API | Admin | Notes |
|-------|-----|-------|-------|
| `sender_name` | ✅ Required | ✅ Required | Same validation |
| `sender_phone` | ✅ Required | ✅ Required | Same validation |
| `sender_email` | ✅ Required | ⚠️ Optional | API stricter |
| `pickup_address` | ✅ Required | ✅ Required | Same |
| `receiver_name` | ✅ Required | ✅ Required | Same |
| `receiver_phone` | ✅ Required | ✅ Required | Same |
| `delivery_address` | ✅ Required | ✅ Required | Same |
| `item_description` | ✅ Required | ✅ Required | Same |
| `item_size` | ✅ Required | ⚠️ Optional | API stricter |
| `price` | ⚠️ Optional | ✅ Required | Admin stricter |
| `distance` | ⚠️ Optional | ⚠️ Optional | Same |
| `notes` | ⚠️ Auto-generated | ⚠️ Optional | Different handling |

### **Differences:**

| Feature | API | Admin |
|---------|-----|-------|
| **Status** | Always `confirmed` | Admin chooses: `pending`, `confirmed`, `in_transit`, `delivered`, `cancelled` |
| **Priority** | Not set (defaults to `normal`) | Admin sets: `normal`, `high`, `urgent` |
| **Source** | Always `Website Form` | Admin chooses: `whatsapp`, `instagram`, `web`, `phone`, `walk-in`, `email`, `other` |
| **Client Assignment** | Not assigned | Admin can assign to existing client |
| **Rider Assignment** | Not assigned | Admin can assign to rider |
| **Images** | Not supported | Supports 4 package images + 3 additional files |
| **Weight/Quantity** | Not supported | Admin can set weight & quantity |
| **Pickup/Delivery Dates** | Not set | Admin can set dates |
| **Created By** | Not tracked | Tracks which admin created it |

---

## 🎯 Order Number Generation

**Format:** `WKL{YYYYMMDD}{XXXX}`

**Example:** `WKL202604270001`
- `WKL` = Prefix (Wakaline Logistics)
- `20260427` = Date (April 27, 2026)
- `0001` = Random 4-digit number (0000-9999)

**Generated automatically** via model boot method when order is created.

---

## 📊 Database Fields (Order Model)

### Fillable Fields:
```php
[
    'order_number',      // Auto-generated
    'user_id',           // Not used in API
    'created_by',        // Admin only
    'rider_id',          // Admin only
    'client_id',         // Admin only
    'source',            // 'Website Form' for API
    'source_contact',    // Sender phone
    'source_notes',      // Form source info
    'customer_name',     // = sender_name (backward compat)
    'customer_email',    // = sender_email
    'customer_phone',    // = sender_phone
    'sender_name',       // ✅ API provides
    'sender_phone',      // ✅ API provides
    'sender_email',      // ✅ API provides
    'pickup_address',    // ✅ API provides
    'delivery_address',  // ✅ API provides
    'receiver_name',     // ✅ API provides (as recipient_name)
    'receiver_phone',    // ✅ API provides (as recipient_phone)
    'item_description',  // ✅ API provides (as package_description)
    'item_size',         // ✅ API provides (as package_size)
    'weight',            // ❌ Not in API
    'quantity',          // ❌ Not in API
    'distance',          // ⚠️ Optional in API
    'price',             // ⚠️ Optional in API
    'status',            // Always 'confirmed' in API
    'priority_level',    // ❌ Not in API (defaults to 'normal')
    'notes',             // Auto-generated from multiple fields
    'pickup_date',       // ❌ Not in API
    'delivery_date',     // ❌ Not in API
    'package_image_1-4', // ❌ Not in API
    'delivery_proof_image', // ❌ Not in API
    'additional_file_1-3',  // ❌ Not in API
]
```

---

## 🔐 Authentication

**Middleware:** `api.token`

The API requires a valid API token to be sent with the request. This is likely configured in the middleware to validate against a token stored in the database or environment.

---

## 📝 Logging

Orders submitted via API are logged:
```php
Log::info('Order Created from Website Form', [
    'order_id' => $order->id,
    'order_number' => $order->order_number,
    'sender' => $sender_name,
    'pickup' => $pickup_area ?? $pickup_address,
    'delivery' => $delivery_area ?? $delivery_address,
    'package' => $package_description,
    'status' => 'confirmed',
    'timestamp' => now()
]);
```

---

## ✅ API is Aligned with Admin

**Conclusion:** The API order submission is **well-aligned** with the admin order creation process:

1. ✅ Uses the same `Order` model
2. ✅ Follows the same database structure
3. ✅ Generates order numbers the same way
4. ✅ Maps API fields correctly to database columns
5. ✅ Maintains backward compatibility (`customer_*` fields)
6. ✅ Auto-confirms orders (reasonable for public submissions)
7. ✅ Proper validation and error handling
8. ✅ Comprehensive logging

**Minor Differences:**
- API doesn't support file uploads (reasonable for web forms)
- API doesn't allow rider/client assignment (admin responsibility)
- API auto-confirms orders (makes sense for public submissions)
- API combines notes from multiple fields (better UX)

---

## 🎯 Next Step: Order Status Check Endpoint

Based on this analysis, we can create an order status check endpoint that:

1. **Accepts:** `order_number` or `order_id`
2. **Returns:** Order status, tracking info, and delivery details
3. **Uses:** Same authentication (`api.token`)
4. **Format:** Similar JSON response structure

**Proposed Endpoint:** `GET /wakalinelogistics/v1/orders/{order_number}/status`

Would you like me to proceed with creating this endpoint?
