# PayTabs Integration - Quick Start Guide

## Quick Setup Checklist

### 1. Database Migration
```bash
php add-paytabs-provider.php
```

### 2. Environment Configuration

Add to `.env` file:
```env
PAYTABS_PROFILE_ID=your_profile_id
PAYTABS_SERVER_KEY=your_server_key
PAYTABS_CLIENT_KEY=your_client_key
PAYTABS_REGION=OMN
```

### 3. API Endpoints Available

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/payments/paytabs/initiate` | POST | Required | Initiate payment and get payment URL |
| `/api/payments/paytabs/verify` | POST | Required | Verify payment status |
| `/api/payments/paytabs/callback` | POST | Public | Webhook handler (configure in PayTabs dashboard) |
| `/api/payments/paytabs/config` | GET | Public | Get client configuration |

### 4. Basic Frontend Flow

```javascript
// 1. Initiate Payment
const response = await fetch('/api/payments/paytabs/initiate', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    bookingId: 123,
    customerName: 'John Doe',
    customerEmail: 'john@example.com',
    customerPhone: '+968 9999 9999'
  })
});

const data = await response.json();
// Redirect to: data.data.payment_url

// 2. Verify Payment (on return)
const verifyResponse = await fetch('/api/payments/paytabs/verify', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    transactionRef: 'TST2106200012345',
    bookingId: 123
  })
});
```

### 5. PayTabs Dashboard Configuration

- **Webhook URL**: `https://yourdomain.com/api/payments/paytabs/callback`
- **Return URL**: `https://yourdomain.com/booking-confirmation?bookingId={bookingId}`

### 6. Files Created/Modified

- ✅ `database/add_paytabs_provider.sql` - Database migration
- ✅ `add-paytabs-provider.php` - Migration script
- ✅ `src/config/env.php` - Added PayTabs configuration
- ✅ `src/utils/PayTabsService.php` - PayTabs API service
- ✅ `src/controllers/paymentController.php` - Payment endpoints
- ✅ `src/routes/index.php` - Added payment routes

### 7. Testing

1. Use sandbox credentials for testing
2. Create a test booking
3. Initiate payment
4. Complete payment on PayTabs page
5. Verify payment status

For detailed documentation, see `PAYTABS_INTEGRATION.md`
