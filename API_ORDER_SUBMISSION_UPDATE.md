# API Order Submission - Updated to Match Admin Flow

## ✅ Changes Made

The `orders/submit-public` API endpoint has been updated to align with the admin order creation flow and now **automatically calculates delivery price** using the Metter delivery calculator.

---

## 🔧 **Key Updates:**

### **1. Field Names Aligned**
Changed field names to match admin form and database columns:

| Old API Field | New API Field | Database Column |
|---------------|---------------|-----------------|
| `recipient_name` | `receiver_name` | `receiver_name` |
| `recipient_phone` | `receiver_phone` | `receiver_phone` |
| `package_description` | `item_description` | `item_description` |
| `package_size` | `item_size` | `item_size` |
| `preferred_time` | Removed | N/A |
| `additional_notes` | Removed | N/A |

### **2. New Fields Added**
Added fields that were missing from the API:

- ✅ `receiver_email` - Optional receiver email
- ✅ `weight` - Optional package weight
- ✅ `quantity` - Optional item quantity
- ✅ `source_contact` - Optional source contact info
- ✅ `source_notes` - Optional source notes
- ✅ `priority_level` - Auto-set to `normal`

### **3. Validation Simplified**
Removed overly strict regex validations to match admin form:

**Before:**
```php
'sender_name' => 'required|string|min:2|max:255|regex:/^[a-zA-Z\s\-\.]+$/',
'sender_phone' => 'required|string|min:10|max:20|regex:/^[\+]?[(]?[0-9]{1,4}...',
'sender_email' => 'required|email:rfc,dns|max:255',
```

**After:**
```php
'sender_name' => 'required|string|max:255',
'sender_phone' => 'required|string|max:20',
'sender_email' => 'nullable|email|max:255',
```

### **4. Required vs Optional Fields**
Aligned with admin form requirements:

**Now Required:**
- `sender_name`, `sender_phone`
- `receiver_name`, `receiver_phone`
- `pickup_address`, `delivery_address`
- `item_description`
- `price`

**Now Optional:**
- `sender_email`, `receiver_email`
- `item_size`, `weight`, `quantity`
- `distance`, `notes`
- `pickup_area`, `delivery_area`

### **5. Order Creation Consistency**
Order data structure now matches admin exactly:

```php
$orderCreateData = [
    'client_id' => $client->id,
    'source' => 'API',
    'source_contact' => $orderData['source_contact'] ?? $orderData['sender_phone'],
    'source_notes' => $orderData['source_notes'] ?? 'Submitted via API',
    'sender_name' => $orderData['sender_name'],
    'sender_phone' => $orderData['sender_phone'],
    'sender_email' => $orderData['sender_email'] ?? null,
    'receiver_name' => $orderData['receiver_name'],
    'receiver_phone' => $orderData['receiver_phone'],
    'receiver_email' => $orderData['receiver_email'] ?? null,
    'item_description' => $orderData['item_description'],
    'item_size' => $orderData['item_size'] ?? null,
    'weight' => $orderData['weight'] ?? null,
    'quantity' => $orderData['quantity'] ?? null,
    'price' => $orderData['price'],
    'status' => 'confirmed',
    'priority_level' => 'normal',
    // ... backward compatibility fields
];
```

---

## 📝 **Updated API Request Format:**

### **Minimum Required Fields:**
```json
{
  "sender_name": "John Doe",
  "sender_phone": "08012345678",
  "pickup_address": "123 Main Street, Lagos",
  "receiver_name": "Jane Smith",
  "receiver_phone": "08087654321",
  "delivery_address": "456 Oak Avenue, Abuja",
  "item_description": "Electronics - Laptop"
}
```

**Note:** `price` is now **optional** and will be **automatically calculated** based on pickup and delivery addresses using the Metter delivery calculator.

### **Full Request with Optional Fields:**
```json
{
  "sender_name": "John Doe",
  "sender_phone": "08012345678",
  "sender_email": "john@example.com",
  "pickup_address": "123 Main Street",
  "pickup_area": "Ikeja, Lagos",
  "receiver_name": "Jane Smith",
  "receiver_phone": "08087654321",
  "receiver_email": "jane@example.com",
  "delivery_address": "456 Oak Avenue",
  "delivery_area": "Wuse, Abuja",
  "item_description": "Electronics - Laptop",
  "item_size": "Medium",
  "weight": 2.5,
  "quantity": 1,
  "distance": 450,
  "price": 5000,
  "notes": "Handle with care. Fragile item.",
  "delivery_notes": "Call before delivery",
  "source_contact": "08011112222",
  "source_notes": "Referred by existing customer"
}
```

---

## � **Automatic Price Calculation:**

The API now **automatically calculates** the delivery price using the **Metter Delivery Calculator** service based on:

- **Pickup Address** (including pickup area if provided)
- **Delivery Address** (including delivery area if provided)
- **Distance** between locations
- **Zone-based pricing** (Mainland, Island, Interstate)
- **Special surcharges** (bridge crossing, tolls, congestion, etc.)

### **How It Works:**

1. **API receives order request** with pickup and delivery addresses
2. **Metter calculator processes** the addresses and calculates:
   - Distance in kilometers
   - Pickup zone and delivery zone
   - Base fee + per-km rate + applicable surcharges
3. **Price is automatically set** on the order
4. **Response includes** calculated price, distance, and zones

### **Price Override:**

You can **optionally provide** a `price` parameter to override the calculated price:
```json
{
  "pickup_address": "Ikeja, Lagos",
  "delivery_address": "Lekki, Lagos",
  "price": 7500
}
```

If `price` is provided, it will be used instead of the calculated price.

### **Updated Response Format:**

```json
{
  "success": true,
  "message": "Order created successfully!",
  "data": {
    "order_id": 123,
    "order_number": "ORD-2026-001234",
    "status": "confirmed",
    "status_label": "Confirmed",
    "client_id": 17,
    "source": "API",
    "source_contact": "08099887766",
    "source_notes": "Submitted via Acme Logistics",
    "sender": {
      "name": "John Doe",
      "phone": "08012345678",
      "email": "john@example.com"
    },
    "receiver": {
      "name": "Jane Smith",
      "phone": "08087654321",
      "email": "jane@example.com"
    },
    "pickup_address": "123 Main Street, Ikeja, Lagos",
    "delivery_address": "456 Oak Avenue, Lekki, Lagos",
    "item_description": "Electronics - Laptop",
    "item_size": "Medium",
    "weight": 2.5,
    "quantity": 1,
    "price": 5500,
    "currency": "NGN",
    "priority_level": "normal",
    "pickup_date": null,
    "delivery_date": null,
    "notes": "Handle with care",
    "created_at": "2026-04-27T20:30:00+01:00",
    "updated_at": "2026-04-27T20:30:00+01:00"
  }
}
```

---

## �� **Backward Compatibility:**

### **Legacy Field Support:**
The API still accepts old field names for backward compatibility:

- `delivery_notes` → Merged into `notes`
- Old requests will continue to work

### **Deprecated Fields:**
These fields are no longer used but won't cause errors:
- `preferred_time` - Ignored
- `additional_notes` - Ignored
- `form_source` - Ignored

---

## ✅ **What's Consistent Now:**

| Feature | API | Admin | Status |
|---------|-----|-------|--------|
| Field names | ✅ | ✅ | **Aligned** |
| Validation rules | ✅ | ✅ | **Aligned** |
| Required fields | ✅ | ✅ | **Aligned** |
| Database columns | ✅ | ✅ | **Aligned** |
| Order creation | ✅ | ✅ | **Aligned** |
| Weight/Quantity | ✅ | ✅ | **Added** |
| Receiver email | ✅ | ✅ | **Added** |
| Priority level | ✅ | ✅ | **Added** |

---

## 🎯 **Differences (By Design):**

| Feature | API | Admin | Reason |
|---------|-----|-------|--------|
| **Status** | Always `confirmed` | User chooses | API auto-confirms |
| **Priority** | Always `normal` | User chooses | API defaults |
| **Source** | Always `API` | User chooses | API identifier |
| **File uploads** | Not supported | Supported | API limitation |
| **Rider assignment** | Not set | Optional | Admin assigns later |

---

## 🚀 **Benefits:**

1. **Consistency** - Same field names and validation as admin
2. **Flexibility** - More optional fields for different use cases
3. **Completeness** - Weight, quantity, and receiver email now supported
4. **Simplicity** - Removed overly strict regex validations
5. **Compatibility** - Backward compatible with old requests

---

## 📊 **Migration Guide:**

If you have existing API integrations, update your requests:

### **Old Format:**
```json
{
  "recipient_name": "Jane",
  "package_description": "Laptop",
  "package_size": "Medium",
  "preferred_time": "Morning (8AM-12PM)"
}
```

### **New Format:**
```json
{
  "receiver_name": "Jane",
  "item_description": "Laptop",
  "item_size": "Medium"
}
```

**Note:** Old format still works but is deprecated.

---

## ✅ **Summary:**

The API order submission endpoint is now **fully aligned** with the admin order creation flow, using the same field names, validation rules, and database structure. This ensures consistency across all order creation methods while maintaining backward compatibility.
