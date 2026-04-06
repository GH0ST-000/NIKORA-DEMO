# Dashboard API

Comprehensive dashboard endpoints for visualizing and monitoring the NIKORA Supply Chain Management System.

---

## Overview

The Dashboard API provides real-time insights into:
- Product and manufacturer statistics
- Batch expiration monitoring
- Recent system additions
- Visual data for charts and graphs
- Inventory status and locations

All endpoints require authentication and proper permissions.

---

## Base URL

```
http://localhost/api/dashboard
```

**Authentication Required:** All endpoints require JWT Bearer token

```http
Authorization: Bearer {your_token}
```

---

## Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/dashboard/stats` | Get comprehensive dashboard statistics |
| GET | `/dashboard/expiring-batches` | Get batches expiring within specified days |
| GET | `/dashboard/recent-additions` | Get recently added manufacturers and products |
| GET | `/dashboard/visualization` | Get data formatted for visualizations |

---

## 1. Dashboard Statistics

**Endpoint:** `GET /api/dashboard/stats`

Get comprehensive statistics for the entire system.

### Response

```json
{
  "data": {
    "products": {
      "total": 150,
      "active": 145,
      "local": 90,
      "imported": 60
    },
    "manufacturers": {
      "total": 45,
      "active": 42
    },
    "batches": {
      "total": 500,
      "active": 320,
      "expired": 15,
      "expiring_soon": 45,
      "blocked": 5,
      "recalled": 2
    },
    "receivings": {
      "total": 450,
      "pending": 12,
      "accepted": 400,
      "rejected": 25,
      "quarantined": 13
    },
    "inventory": {
      "total_quantity": 15000.50,
      "total_value": 0.0
    }
  }
}
```

### Response Fields

#### Products Statistics
- `total` - Total number of products in system
- `active` - Number of active products
- `local` - Number of locally sourced products
- `imported` - Number of imported products

#### Manufacturers Statistics
- `total` - Total number of manufacturers
- `active` - Number of active manufacturers

#### Batches Statistics
- `total` - Total number of batches
- `active` - Batches in `received` or `in_storage` status
- `expired` - Batches past expiry date
- `expiring_soon` - Batches expiring within 30 days
- `blocked` - Batches blocked for quality issues
- `recalled` - Batches under recall

#### Receivings Statistics
- `total` - Total receiving records
- `pending` - Awaiting inspection
- `accepted` - Approved for storage
- `rejected` - Rejected quality
- `quarantined` - Under investigation

#### Inventory Statistics
- `total_quantity` - Sum of all remaining quantities
- `total_value` - Total inventory value (reserved for future use)

### Example Request

```bash
curl -X GET http://localhost/api/dashboard/stats \
  -H "Authorization: Bearer {token}"
```

---

## 2. Expiring Batches

**Endpoint:** `GET /api/dashboard/expiring-batches`

Get paginated list of batches expiring within specified days.

### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `days` | integer | 30 | Number of days to look ahead |
| `per_page` | integer | 25 | Items per page (1-100) |
| `cursor` | string | null | Pagination cursor |

### Response

```json
{
  "data": [
    {
      "id": 1,
      "batch_number": "BATCH-001",
      "production_date": "2026-01-01",
      "expiry_date": "2026-04-15",
      "quantity": 500.0,
      "remaining_quantity": 450.0,
      "unit": "kg",
      "status": "in_storage",
      "product": {
        "id": 10,
        "name": "Fresh Milk",
        "sku": "SKU-MILK-001",
        "category": "Dairy"
      },
      "warehouse_location": {
        "id": 5,
        "name": "Cold Storage A",
        "code": "CS-A-001",
        "type": "storage_unit"
      }
    }
  ],
  "meta": {
    "path": "http://localhost/api/dashboard/expiring-batches",
    "per_page": 25,
    "next_cursor": "eyJleHBpcnlfZGF0ZSI6IjIwMjYtMDQtMTUiLCJpZCI6MSwic...",
    "prev_cursor": null
  },
  "links": {
    "first": "http://localhost/api/dashboard/expiring-batches?per_page=25",
    "last": null,
    "prev": null,
    "next": "http://localhost/api/dashboard/expiring-batches?cursor=eyJle..."
  }
}
```

### Filtering

This endpoint automatically filters to:
- Only batches expiring within specified days
- Only batches with status `received` or `in_storage`
- Only batches with remaining quantity > 0

### Example Requests

```bash
# Get batches expiring in next 30 days (default)
curl -X GET http://localhost/api/dashboard/expiring-batches \
  -H "Authorization: Bearer {token}"

# Get batches expiring in next 7 days
curl -X GET http://localhost/api/dashboard/expiring-batches?days=7 \
  -H "Authorization: Bearer {token}"

# Custom pagination
curl -X GET http://localhost/api/dashboard/expiring-batches?days=14&per_page=50 \
  -H "Authorization: Bearer {token}"
```

---

## 3. Recent Additions

**Endpoint:** `GET /api/dashboard/recent-additions`

Get recently added manufacturers and products.

### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `days` | integer | 7 | Number of days to look back |
| `limit` | integer | 10 | Maximum items per category |

### Response

```json
{
  "data": {
    "manufacturers": {
      "count": 3,
      "items": [
        {
          "id": 42,
          "name": "Georgian Dairy LLC",
          "short_name": "GD",
          "country": "Georgia",
          "created_at": "2026-04-05T10:30:00.000000Z"
        }
      ]
    },
    "products": {
      "count": 5,
      "items": [
        {
          "id": 125,
          "name": "Fresh Milk",
          "sku": "SKU-MILK-001",
          "category": "Dairy",
          "manufacturer": {
            "id": 42,
            "name": "Georgian Dairy LLC"
          },
          "created_at": "2026-04-05T14:20:00.000000Z"
        }
      ]
    }
  }
}
```

### Example Requests

```bash
# Get additions from last 7 days (default)
curl -X GET http://localhost/api/dashboard/recent-additions \
  -H "Authorization: Bearer {token}"

# Get additions from last 30 days
curl -X GET http://localhost/api/dashboard/recent-additions?days=30 \
  -H "Authorization: Bearer {token}"

# Get only 5 most recent items per category
curl -X GET http://localhost/api/dashboard/recent-additions?days=7&limit=5 \
  -H "Authorization: Bearer {token}"
```

---

## 4. Visualization Data

**Endpoint:** `GET /api/dashboard/visualization`

Get data formatted for charts and visualizations.

### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `type` | string | overview | Visualization type (see below) |

### Visualization Types

| Type | Description |
|------|-------------|
| `overview` | High-level system overview |
| `expiry_timeline` | Batches grouped by expiry ranges |
| `product_categories` | Product count by category |
| `receiving_status` | Receiving records by status |
| `batch_status` | Batches by status |
| `inventory_by_location` | Inventory quantities by location |

---

### 4.1 Overview Visualization

**Type:** `overview`

```json
{
  "data": {
    "type": "overview",
    "data": {
      "products": 150,
      "active_batches": 320,
      "expiring_soon": 45,
      "pending_receivings": 12
    }
  }
}
```

---

### 4.2 Expiry Timeline

**Type:** `expiry_timeline`

Groups batches by expiration date ranges.

```json
{
  "data": {
    "type": "expiry_timeline",
    "data": {
      "expired": 15,
      "0-7_days": 8,
      "8-14_days": 12,
      "15-30_days": 25,
      "31-60_days": 40,
      "60+_days": 220
    }
  }
}
```

**Use Case:** Create bar charts or area charts showing expiration timeline.

---

### 4.3 Product Categories

**Type:** `product_categories`

Groups products by category.

```json
{
  "data": {
    "type": "product_categories",
    "data": [
      {
        "category": "Meat",
        "count": 45
      },
      {
        "category": "Dairy",
        "count": 38
      },
      {
        "category": "Vegetables",
        "count": 32
      },
      {
        "category": "Bakery",
        "count": 20
      }
    ]
  }
}
```

**Use Case:** Create pie charts or bar charts of product distribution.

---

### 4.4 Receiving Status

**Type:** `receiving_status`

Groups receiving records by status.

```json
{
  "data": {
    "type": "receiving_status",
    "data": [
      {
        "status": "accepted",
        "count": 400
      },
      {
        "status": "rejected",
        "count": 25
      },
      {
        "status": "quarantined",
        "count": 13
      },
      {
        "status": "pending",
        "count": 12
      }
    ]
  }
}
```

**Use Case:** Create pie charts showing receiving workflow status.

---

### 4.5 Batch Status

**Type:** `batch_status`

Groups batches by status.

```json
{
  "data": {
    "type": "batch_status",
    "data": [
      {
        "status": "in_storage",
        "count": 280
      },
      {
        "status": "received",
        "count": 40
      },
      {
        "status": "expired",
        "count": 15
      },
      {
        "status": "pending",
        "count": 10
      },
      {
        "status": "blocked",
        "count": 5
      },
      {
        "status": "recalled",
        "count": 2
      }
    ]
  }
}
```

**Use Case:** Create donut charts or stacked bar charts of batch lifecycle.

---

### 4.6 Inventory by Location

**Type:** `inventory_by_location`

Groups inventory quantities by warehouse location.

```json
{
  "data": {
    "type": "inventory_by_location",
    "data": [
      {
        "location_id": 5,
        "location_name": "Cold Storage A",
        "location_code": "CS-A-001",
        "total_quantity": 5000.5
      },
      {
        "location_id": 7,
        "location_name": "Dry Storage B",
        "location_code": "DS-B-002",
        "total_quantity": 3500.0
      }
    ]
  }
}
```

**Use Case:** Create bar charts showing inventory distribution across locations.

---

### Example Requests

```bash
# Overview data
curl -X GET http://localhost/api/dashboard/visualization?type=overview \
  -H "Authorization: Bearer {token}"

# Expiry timeline
curl -X GET http://localhost/api/dashboard/visualization?type=expiry_timeline \
  -H "Authorization: Bearer {token}"

# Product categories
curl -X GET http://localhost/api/dashboard/visualization?type=product_categories \
  -H "Authorization: Bearer {token}"

# Batch status distribution
curl -X GET http://localhost/api/dashboard/visualization?type=batch_status \
  -H "Authorization: Bearer {token}"
```

---

## Authorization

All dashboard endpoints require authentication and use the `viewDashboard` policy check.

### Required Permissions

A user must have **at least one** of the following permissions:
- `view_dashboard` (dedicated dashboard permission)
- `view_any_batch`
- `view_any_product`
- `view_any_manufacturer`
- `view_any_receiving`

### Response Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 401 | Unauthorized (missing or invalid token) |
| 403 | Forbidden (insufficient permissions) |
| 422 | Unprocessable Entity (validation error) |

---

## Integration Example

```javascript
// JavaScript example using fetch
const token = 'your_jwt_token';

// Get dashboard stats
const stats = await fetch('http://localhost/api/dashboard/stats', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
}).then(r => r.json());

console.log('Total Products:', stats.data.products.total);
console.log('Expiring Soon:', stats.data.batches.expiring_soon);

// Get expiring batches
const expiring = await fetch('http://localhost/api/dashboard/expiring-batches?days=7', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
}).then(r => r.json());

console.log('Batches expiring in 7 days:', expiring.data.length);

// Get visualization data for chart
const chartData = await fetch('http://localhost/api/dashboard/visualization?type=expiry_timeline', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
}).then(r => r.json());

// Use chartData.data.data for your chart library
console.log('Chart data:', chartData.data.data);
```

---

## Common Use Cases

### 1. Executive Dashboard

Display high-level metrics:

```bash
GET /api/dashboard/stats
GET /api/dashboard/visualization?type=overview
```

### 2. Operations Dashboard

Monitor critical items:

```bash
GET /api/dashboard/expiring-batches?days=7
GET /api/dashboard/visualization?type=batch_status
```

### 3. Quality Dashboard

Track receiving and quality metrics:

```bash
GET /api/dashboard/stats (focus on receivings data)
GET /api/dashboard/visualization?type=receiving_status
```

### 4. Inventory Dashboard

Monitor stock levels and distribution:

```bash
GET /api/dashboard/stats (focus on inventory data)
GET /api/dashboard/visualization?type=inventory_by_location
```

### 5. Activity Dashboard

Track recent system activity:

```bash
GET /api/dashboard/recent-additions?days=7
GET /api/dashboard/visualization?type=product_categories
```

---

## Performance Notes

### Caching Recommendations

Consider caching these endpoints:
- **Stats endpoint:** Cache for 5-15 minutes depending on update frequency
- **Visualization data:** Cache for 5-30 minutes
- **Expiring batches:** Cache for 1 hour (data doesn't change frequently)
- **Recent additions:** Cache for 5-15 minutes

### Query Optimization

All endpoints are optimized with:
- Indexed database queries
- Eager loading of relationships
- Aggregation queries where applicable
- Cursor pagination for large datasets

### Response Times

Expected response times (typical system):
- Stats: < 100ms
- Expiring Batches: < 200ms
- Recent Additions: < 150ms
- Visualization: < 200ms

---

## Frontend Integration Examples

### React Example

```jsx
import { useEffect, useState } from 'react';

function DashboardStats() {
  const [stats, setStats] = useState(null);
  
  useEffect(() => {
    fetch('/api/dashboard/stats', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
      .then(r => r.json())
      .then(data => setStats(data.data));
  }, []);
  
  if (!stats) return <div>Loading...</div>;
  
  return (
    <div className="grid grid-cols-4 gap-4">
      <StatCard 
        title="Total Products" 
        value={stats.products.total} 
      />
      <StatCard 
        title="Active Batches" 
        value={stats.batches.active} 
      />
      <StatCard 
        title="Expiring Soon" 
        value={stats.batches.expiring_soon}
        alert={stats.batches.expiring_soon > 50}
      />
      <StatCard 
        title="Pending Receivings" 
        value={stats.receivings.pending} 
      />
    </div>
  );
}
```

### Chart.js Example

```javascript
// Fetch data for expiry timeline chart
const response = await fetch('/api/dashboard/visualization?type=expiry_timeline', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const result = await response.json();
const data = result.data.data;

// Create chart
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: ['Expired', '0-7 days', '8-14 days', '15-30 days', '31-60 days', '60+ days'],
    datasets: [{
      label: 'Batch Count',
      data: [
        data.expired,
        data['0-7_days'],
        data['8-14_days'],
        data['15-30_days'],
        data['31-60_days'],
        data['60+_days']
      ],
      backgroundColor: [
        '#EF4444', // red for expired
        '#F59E0B', // amber for urgent
        '#F59E0B',
        '#10B981', // green for safe
        '#10B981',
        '#10B981'
      ]
    }]
  }
});
```

---

## Testing

Run dashboard tests:

```bash
# All dashboard tests
./vendor/bin/pest tests/Feature/Api/DashboardTest.php

# Specific test
./vendor/bin/pest tests/Feature/Api/DashboardTest.php --filter "can get dashboard stats"
```

---

## See Also

- [API Quick Reference](API_QUICK_REFERENCE.md)
- [Batch API Documentation](BATCH_API.md)
- [Product API Documentation](PRODUCT_API.md)
- [Receiving API Documentation](RECEIVING_API.md)
