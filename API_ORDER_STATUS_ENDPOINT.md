# Order Status Check API Endpoint

## 📍 **Endpoint:**

```
GET /api/wakalinelogistics/v1/orders/{orderNumber}/status
```

---

## 🔐 **Authentication:**

Requires **Client API Key** via `client.api` middleware.

**Headers:**
```
Authorization: Bearer wkl_your_api_key_here
```

---

## 📝 **Request:**

### **URL Parameters:**
- `orderNumber` (required) - The order number to check (e.g., `ORD-2026-001234`)

### **Example Request:**
```bash
curl -X GET https://yourdomain.com/api/wakalinelogistics/v1/orders/ORD-2026-001234/status \
  -H "Authorization: Bearer wkl_a1b2c3d4e5f6..."
```

---

## ✅ **Success Response (200):**

```json
{
  "success": true,
  "data": {
    "order_number": "ORD-2026-001234",
    "status": "in_transit",
    "status_label": "In transit",
    "created_at": "2026-04-27T19:30:00+01:00",
    "updated_at": "2026-04-27T20:15:00+01:00",
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
    "distance_km": 12.5,
    "price": 5500,
    "currency": "NGN",
    "priority_level": "normal",
    "pickup_date": "2026-04-27",
    "delivery_date": "2026-04-28",
    "notes": "Handle with care. Fragile item.",
    "rider": {
      "id": 5,
      "name": "Ahmed Ibrahim",
      "phone": "08099887766"
    }
  }
}
```

---

## ❌ **Error Responses:**

### **Order Not Found (404):**
```json
{
  "success": false,
  "message": "Order not found or you do not have permission to view this order.",
  "error": "order_not_found"
}
```

**Reasons:**
- Order number doesn't exist
- Order belongs to a different client
- Invalid order number format

### **Unauthorized (401):**
```json
{
  "success": false,
  "message": "Unauthorized",
  "error": "unauthorized"
}
```

**Reasons:**
- Missing API key
- Invalid API key
- API access disabled

---

## 📊 **Order Status Values:**

| Status | Description |
|--------|-------------|
| `pending` | Order received, awaiting confirmation |
| `confirmed` | Order confirmed and ready for pickup |
| `in_transit` | Order picked up and in transit |
| `delivered` | Order successfully delivered |
| `cancelled` | Order cancelled |

---

## 🔒 **Security:**

- ✅ **Client Isolation:** Clients can only view their own orders
- ✅ **API Key Required:** Must authenticate with valid API key
- ✅ **Order Ownership:** Validates `client_id` matches authenticated client
- ✅ **No Sensitive Data:** Doesn't expose internal system information

---

## 💡 **Use Cases:**

### **1. Order Tracking Integration:**
```javascript
async function trackOrder(orderNumber) {
  const response = await fetch(
    `https://api.wakaline.com/api/wakalinelogistics/v1/orders/${orderNumber}/status`,
    {
      headers: {
        'Authorization': 'Bearer wkl_your_api_key',
        'Content-Type': 'application/json'
      }
    }
  );
  
  const data = await response.json();
  
  if (data.success) {
    console.log(`Order Status: ${data.data.status_label}`);
    console.log(`Rider: ${data.data.rider?.name || 'Not assigned'}`);
  }
}
```

### **2. Customer Notifications:**
```javascript
// Check order status and notify customer
const order = await getOrderStatus('ORD-2026-001234');

if (order.status === 'in_transit') {
  sendSMS(order.receiver.phone, 
    `Your order ${order.order_number} is on the way! 
     Rider: ${order.rider.name} (${order.rider.phone})`
  );
}
```

### **3. Dashboard Integration:**
```javascript
// Display order status in customer dashboard
const orders = ['ORD-2026-001234', 'ORD-2026-001235'];

for (const orderNumber of orders) {
  const status = await getOrderStatus(orderNumber);
  updateDashboard(orderNumber, status.data);
}
```

---

## 🎯 **Response Fields Explained:**

| Field | Type | Description |
|-------|------|-------------|
| `order_number` | string | Unique order identifier |
| `status` | string | Current order status (pending/confirmed/in_transit/delivered/cancelled) |
| `status_label` | string | Human-readable status label |
| `created_at` | ISO8601 | Order creation timestamp |
| `updated_at` | ISO8601 | Last update timestamp |
| `sender` | object | Sender information |
| `receiver` | object | Receiver information |
| `pickup_address` | string | Full pickup address |
| `delivery_address` | string | Full delivery address |
| `item_description` | string | Description of item being delivered |
| `item_size` | string | Size of item (Small/Medium/Large/Extra Large) |
| `weight` | number | Weight in kg (nullable) |
| `quantity` | integer | Number of items (nullable) |
| `distance_km` | number | Distance in kilometers |
| `price` | number | Delivery price in Naira |
| `currency` | string | Currency code (NGN) |
| `priority_level` | string | Priority (normal/high/urgent) |
| `pickup_date` | date | Scheduled pickup date (nullable) |
| `delivery_date` | date | Scheduled/actual delivery date (nullable) |
| `notes` | string | Order notes (nullable) |
| `rider` | object | Assigned rider info (null if not assigned) |

---

## 📱 **Example Implementations:**

### **PHP:**
```php
$orderNumber = 'ORD-2026-001234';
$apiKey = 'wkl_your_api_key';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.wakaline.com/api/wakalinelogistics/v1/orders/{$orderNumber}/status");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$data = json_decode($response, true);

if ($data['success']) {
    echo "Status: " . $data['data']['status_label'];
}
```

### **Python:**
```python
import requests

order_number = 'ORD-2026-001234'
api_key = 'wkl_your_api_key'

response = requests.get(
    f'https://api.wakaline.com/api/wakalinelogistics/v1/orders/{order_number}/status',
    headers={
        'Authorization': f'Bearer {api_key}',
        'Content-Type': 'application/json'
    }
)

data = response.json()

if data['success']:
    print(f"Status: {data['data']['status_label']}")
    if data['data']['rider']:
        print(f"Rider: {data['data']['rider']['name']}")
```

### **JavaScript (Node.js):**
```javascript
const axios = require('axios');

async function getOrderStatus(orderNumber) {
  try {
    const response = await axios.get(
      `https://api.wakaline.com/api/wakalinelogistics/v1/orders/${orderNumber}/status`,
      {
        headers: {
          'Authorization': 'Bearer wkl_your_api_key',
          'Content-Type': 'application/json'
        }
      }
    );
    
    return response.data;
  } catch (error) {
    console.error('Error:', error.response?.data || error.message);
    throw error;
  }
}

// Usage
getOrderStatus('ORD-2026-001234')
  .then(data => console.log('Order Status:', data.data.status_label))
  .catch(err => console.error(err));
```

---

## ✅ **Summary:**

The Order Status API endpoint allows clients to:
- ✅ Check real-time order status
- ✅ Get complete order details
- ✅ Track assigned rider information
- ✅ Monitor delivery progress
- ✅ Integrate with customer-facing applications

**Secure, simple, and comprehensive order tracking for API clients!** 🚀
