# Testing PayTabs Integration on Localhost

This guide will help you test the PayTabs payment integration on your local development environment.

## Prerequisites

1. **PayTabs Sandbox Account**: Sign up for a PayTabs sandbox/test account at [https://www.paytabs.com](https://www.paytabs.com)
2. **Local Development Server**: WAMP/XAMPP running on `localhost`
3. **Webhook Testing Tool**: Use ngrok or similar for webhook callbacks (see below)

## Step 1: Get PayTabs Sandbox Credentials

1. Sign up for PayTabs account
2. Navigate to your merchant dashboard
3. Go to **Settings** > **API Credentials** or **Developer Settings**
4. Generate sandbox/test credentials:
   - **Profile ID** (numeric)
   - **Server Key** (for backend API calls)
   - **Client Key** (optional, for frontend)
   - Note your **Region** (SAU, ARE, EGY, etc.)

## Step 2: Configure .env File for Localhost

Update your `.env` file in `php-backend` directory:

```env
# Server Configuration
APP_ENV=development
PORT=5000
CLIENT_URL=http://localhost:3000

# PayTabs Sandbox Configuration
# Region options: OMN (Oman), SAU (Saudi Arabia), ARE (UAE), EGY (Egypt), GLOBAL
PAYTABS_PROFILE_ID=your_sandbox_profile_id
PAYTABS_SERVER_KEY=your_sandbox_server_key
PAYTABS_CLIENT_KEY=your_sandbox_client_key
PAYTABS_REGION=OMN
```

**Important**: Use **sandbox/test credentials** for localhost testing.

## Step 3: Run Database Migration

Make sure the database is updated:

```bash
cd php-backend
php add-paytabs-provider.php
```

## Step 4: Start Your Local Server

### Option A: Using PHP Built-in Server

```bash
cd php-backend
php -S localhost:5000
```

### Option B: Using WAMP/XAMPP

- Ensure Apache is running
- Access via: `http://localhost/php-backend`

## Step 5: Handle Webhook Callbacks on Localhost

Since PayTabs needs to send webhooks to your server, and `localhost` isn't accessible from the internet, you have several options:

### Option 1: Use ngrok (Recommended)

**ngrok** creates a secure tunnel to your localhost.

1. **Install ngrok**: Download from [https://ngrok.com](https://ngrok.com)

2. **Start your local server** (e.g., `php -S localhost:5000`)

3. **Start ngrok tunnel**:
   ```bash
   ngrok http 5000
   ```
   Or if using WAMP on port 80:
   ```bash
   ngrok http 80
   ```

4. **Copy the ngrok HTTPS URL** (e.g., `https://abc123.ngrok.io`)

5. **Configure PayTabs webhook** in your PayTabs dashboard:
   - Webhook URL: `https://abc123.ngrok.io/api/payments/paytabs/callback`
   - Return URL: `https://abc123.ngrok.io/booking-confirmation?bookingId={bookingId}`

6. **Update your .env**:
   ```env
   CLIENT_URL=https://abc123.ngrok.io
   ```

**Note**: Free ngrok URLs change each time you restart. For consistent testing, consider:
- Using ngrok authtoken for reserved domains
- Updating webhook URL in PayTabs dashboard when URL changes

### Option 2: Use localtunnel

**localtunnel** is a free alternative to ngrok.

1. **Install localtunnel**:
   ```bash
   npm install -g localtunnel
   ```

2. **Start tunnel**:
   ```bash
   lt --port 5000
   ```
   Or for port 80:
   ```bash
   lt --port 80
   ```

3. **Use the provided URL** (e.g., `https://random-name.loca.lt`)

4. **Configure webhook in PayTabs dashboard** with the localtunnel URL

### Option 3: Skip Webhooks (Manual Verification)

For initial testing, you can skip webhook configuration and manually verify payments:

1. Complete payment on PayTabs page
2. After redirect, manually call the verify endpoint:
   ```javascript
   fetch('/api/payments/paytabs/verify', {
     method: 'POST',
     headers: {
       'Content-Type': 'application/json',
       'Authorization': `Bearer ${token}`
     },
     body: JSON.stringify({
       transactionRef: 'TST2106200012345', // from URL params
       bookingId: 123
     })
   })
   ```

## Step 6: Test the Payment Flow

### Test Scenario 1: Complete Payment Flow

1. **Create a test booking**:
   ```bash
   POST http://localhost:5000/api/bookings
   Headers: Authorization: Bearer YOUR_JWT_TOKEN
   Body: {
     "packageId": 1,
     "date": "2024-12-25",
     "adults": 2,
     "children": 0,
     "totalAmount": 150.00,
     "currency": "OMR",
     "contactEmail": "test@example.com",
     "contactPhone": "+968 9999 9999"
   }
   ```

2. **Initiate PayTabs payment**:
   ```bash
   POST http://localhost:5000/api/payments/paytabs/initiate
   Headers: Authorization: Bearer YOUR_JWT_TOKEN
   Body: {
     "bookingId": 123,
     "customerName": "Test User",
     "customerEmail": "test@example.com",
     "customerPhone": "+968 9999 9999",
     "address": {
       "city": "Muscat",
       "country": "OM",
       "zip": "12345"
     }
   }
   ```

3. **Response** will contain `payment_url`:
   ```json
   {
     "success": true,
     "data": {
       "payment_url": "https://secure.paytabs.sa/payment/page/XXXXX",
       "transaction_ref": "TST2106200012345",
       "booking_id": 123
     }
   }
   ```

4. **Open payment_url in browser** and complete payment using PayTabs test card

5. **After payment**, you'll be redirected back to your return URL with transaction reference

6. **Verify payment** (if not using webhooks):
   ```bash
   POST http://localhost:5000/api/payments/paytabs/verify
   Headers: Authorization: Bearer YOUR_JWT_TOKEN
   Body: {
     "transactionRef": "TST2106200012345",
     "bookingId": 123
   }
   ```

### Test Scenario 2: Using Postman/Thunder Client

1. **Set up collection** with authentication token
2. **Test initiate endpoint**:
   - Method: POST
   - URL: `http://localhost:5000/api/payments/paytabs/initiate`
   - Headers: `Authorization: Bearer YOUR_TOKEN`
   - Body: JSON with booking details
3. **Copy payment_url** from response
4. **Open in browser** and complete payment
5. **Test verify endpoint** with transaction reference

### Test Scenario 3: Frontend Integration Test

If you have a frontend running:

```javascript
// In your checkout component
async function handlePayTabsPayment(bookingId, customerDetails) {
  try {
    // 1. Initiate payment
    const response = await fetch('http://localhost:5000/api/payments/paytabs/initiate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${yourAuthToken}`
      },
      body: JSON.stringify({
        bookingId: bookingId,
        customerName: customerDetails.name,
        customerEmail: customerDetails.email,
        customerPhone: customerDetails.phone,
        address: {
          city: 'Muscat',
          country: 'OM'
        }
      })
    });

    const data = await response.json();
    
    if (data.success) {
      // 2. Redirect to PayTabs payment page
      window.location.href = data.data.payment_url;
    } else {
      console.error('Payment initiation failed:', data);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// On return page (booking-confirmation)
window.addEventListener('DOMContentLoaded', async () => {
  const urlParams = new URLSearchParams(window.location.search);
  const transactionRef = urlParams.get('tran_ref');
  const bookingId = urlParams.get('bookingId');

  if (transactionRef && bookingId) {
    try {
      const response = await fetch('http://localhost:5000/api/payments/paytabs/verify', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${yourAuthToken}`
        },
        body: JSON.stringify({
          transactionRef: transactionRef,
          bookingId: bookingId
        })
      });

      const result = await response.json();
      
      if (result.success && result.data.verified) {
        // Show success message
        alert('Payment successful! Booking confirmed.');
      } else {
        alert('Payment verification failed.');
      }
    } catch (error) {
      console.error('Verification error:', error);
    }
  }
});
```

## Step 7: Test with PayTabs Test Cards

PayTabs provides test card numbers for sandbox testing. Check PayTabs documentation for current test cards, typically:

- **Success**: Use valid card numbers with future expiry dates
- **Failure**: Use specific declined card numbers (check PayTabs docs)

Common test cards (verify with PayTabs documentation):
- Card Number: `4111111111111111` (Visa test card)
- Expiry: Any future date (e.g., `12/25`)
- CVV: Any 3 digits (e.g., `123`)

## Step 8: Verify Database Updates

After successful payment, verify the database:

```sql
-- Check payment record
SELECT * FROM payments WHERE booking_id = 123;

-- Check booking status
SELECT id, status, payment_status, transaction_id, payment_intent_id 
FROM bookings 
WHERE id = 123;
```

Expected results:
- `payments.status` should be `'captured'`
- `bookings.payment_status` should be `'paid'`
- `bookings.status` should be `'Confirmed'`
- `bookings.transaction_id` should contain transaction reference

## Troubleshooting Common Issues

### Issue: "PayTabs configuration is missing"

**Solution**: 
- Check `.env` file has all PayTabs credentials
- Restart PHP server after updating `.env`
- Verify credentials are correct (no extra spaces)

### Issue: "Payment creation failed" or API errors

**Solution**:
- Verify you're using sandbox credentials (not production)
- Check region matches your PayTabs account region
  - If account is on merchant-oman.paytabs.com, use `PAYTABS_REGION=OMN`
  - API endpoint will be: https://secure-oman.paytabs.com
- Test API directly with cURL (replace URL based on your region):
  ```bash
  # For Oman
  curl -X POST https://secure-oman.paytabs.com/payment/request \
    -H "Content-Type: application/json" \
    -H "Authorization: YOUR_SERVER_KEY" \
    -d '{"profile_id": YOUR_PROFILE_ID, ...}'
  
  # For Saudi Arabia
  curl -X POST https://secure.paytabs.sa/payment/request \
    -H "Content-Type: application/json" \
    -H "Authorization: YOUR_SERVER_KEY" \
    -d '{"profile_id": YOUR_PROFILE_ID, ...}'
  ```

### Issue: Webhook not being received

**Solution**:
- Ensure ngrok/localtunnel is running
- Verify webhook URL in PayTabs dashboard matches ngrok URL
- Check PHP error logs for callback requests
- Test callback endpoint manually:
  ```bash
  curl -X POST http://localhost:5000/api/payments/paytabs/callback \
    -H "Content-Type: application/json" \
    -d '{"tran_ref": "TEST123", "cart_id": "BK00000123", ...}'
  ```

### Issue: "Invalid callback signature"

**Solution**:
- Check server key is correct
- Verify callback data format matches PayTabs documentation
- Review callback handler logs

### Issue: CORS errors in browser

**Solution**:
- Ensure `CLIENT_URL` in `.env` matches your frontend URL
- Check CORS configuration in your PHP backend
- Use same origin if testing from same domain

### Issue: Payment succeeds but booking not updated

**Solution**:
- Check PHP error logs
- Verify webhook callback is being received
- Manually call verify endpoint as fallback
- Check database foreign key constraints

## Testing Checklist

- [ ] Database migration completed
- [ ] PayTabs sandbox credentials configured in `.env`
- [ ] Local server running
- [ ] ngrok/localtunnel running (if testing webhooks)
- [ ] Webhook URL configured in PayTabs dashboard
- [ ] Payment initiation endpoint working
- [ ] Can redirect to PayTabs payment page
- [ ] Payment completes successfully
- [ ] Redirects back to return URL
- [ ] Payment verification endpoint working
- [ ] Booking status updates to "Confirmed"
- [ ] Payment record created in database
- [ ] Webhook callback received (if configured)

## Quick Test Script

Create a simple test script `test-paytabs.php`:

```php
<?php
require_once __DIR__ . '/src/config/env.php';
require_once __DIR__ . '/src/utils/PayTabsService.php';

try {
    Env::load();
    $paytabs = new PayTabsService();
    
    echo "✓ PayTabs service initialized\n";
    echo "Profile ID: " . $paytabs->getProfileId() . "\n";
    echo "Client Key: " . $paytabs->getClientKey() . "\n";
    echo "✓ Configuration looks good!\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
```

Run it:
```bash
php test-paytabs.php
```

## Next Steps

Once localhost testing is successful:

1. Test all payment scenarios (success, failure, cancellation)
2. Verify webhook handling
3. Test error cases
4. Prepare for production deployment
5. Switch to production credentials when ready

## Additional Resources

- **PayTabs API Documentation**: https://docs.paytabs.com
- **ngrok Documentation**: https://ngrok.com/docs
- **PayTabs Support**: Contact PayTabs support for sandbox/test account assistance
