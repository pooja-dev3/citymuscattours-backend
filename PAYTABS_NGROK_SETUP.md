# PayTabs Integration with ngrok URLs

## Your ngrok URLs

- **Backend**: `https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend`
- **Frontend**: `https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/frontend/php-frontend`

## Step 1: Update .env File

Update your `.env` file in `php-backend` directory:

```env
# Server Configuration
APP_ENV=development
PORT=5000

# Use your ngrok frontend URL
CLIENT_URL=https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/frontend/php-frontend

# PayTabs Configuration
PAYTABS_PROFILE_ID=your_profile_id
PAYTABS_SERVER_KEY=your_server_key
PAYTABS_CLIENT_KEY=your_client_key
PAYTABS_REGION=OMN
```

## Step 2: PayTabs Webhook Configuration

In your PayTabs merchant dashboard (`https://merchant-oman.paytabs.com/`):

1. Go to **Settings** → **Webhooks** or **Developer Settings** → **Webhooks**
2. Set the **Webhook/Callback URL** to:
   ```
   https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend/api/payments/paytabs/callback
   ```

## Step 3: PayTabs Return URL Configuration

The return URL will be dynamically set by the backend, but make sure your frontend has a booking confirmation page at:
```
https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/frontend/php-frontend/booking-confirmation
```

Or you can configure a default return URL in PayTabs dashboard if needed.

## Step 4: Test Your Setup

### Test 1: Verify Configuration
```bash
cd php-backend
php test-paytabs-config.php
```

### Test 2: Test Payment Initiation

Make a POST request to your backend:

```bash
POST https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend/api/payments/paytabs/initiate
Headers:
  Authorization: Bearer YOUR_JWT_TOKEN
  Content-Type: application/json

Body:
{
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

The response will include a `payment_url` that you can redirect the user to.

### Test 3: Complete Payment Flow

1. Create a booking via your frontend
2. Initiate payment (user will be redirected to PayTabs)
3. Complete payment on PayTabs page
4. User will be redirected back to your frontend with transaction reference
5. Verify payment status

## Frontend Integration Example

Update your frontend payment initiation code to use the ngrok backend URL:

```javascript
// Update your API base URL
const API_BASE_URL = 'https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend';

// Initiate payment
async function initiatePayment(bookingId, customerDetails) {
  try {
    const response = await fetch(`${API_BASE_URL}/api/payments/paytabs/initiate`, {
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
      // Redirect to PayTabs payment page
      window.location.href = data.data.payment_url;
    }
  } catch (error) {
    console.error('Payment initiation error:', error);
  }
}

// On booking confirmation page (after redirect from PayTabs)
window.addEventListener('DOMContentLoaded', async () => {
  const urlParams = new URLSearchParams(window.location.search);
  const transactionRef = urlParams.get('tran_ref');
  const bookingId = urlParams.get('bookingId');

  if (transactionRef && bookingId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/payments/paytabs/verify`, {
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
        showSuccessMessage('Payment successful! Booking confirmed.');
      } else {
        showErrorMessage('Payment verification failed.');
      }
    } catch (error) {
      console.error('Verification error:', error);
    }
  }
});
```

## Important Notes

### ngrok URL Changes

⚠️ **Free ngrok URLs change every time you restart ngrok!**

When your ngrok URL changes:

1. **Update `.env` file** with new frontend URL:
   ```env
   CLIENT_URL=https://NEW-URL.ngrok-free.dev/frontend/php-frontend
   ```

2. **Update webhook URL in PayTabs dashboard**:
   ```
   https://NEW-URL.ngrok-free.dev/php-backend/api/payments/paytabs/callback
   ```

3. **Restart your PHP server** to load new environment variables

### ngrok Free Tier Limitations

- URLs change on each restart
- May have connection limits
- Consider ngrok paid plan for reserved domains if testing extensively

### Alternative: Reserved ngrok Domain

For consistent testing, consider getting a reserved domain from ngrok:

```bash
ngrok http 80 --domain=your-reserved-domain.ngrok-free.app
```

Then use the reserved domain in all configurations.

## Troubleshooting

### Issue: Webhook not received

**Check**:
1. Verify webhook URL in PayTabs dashboard matches your ngrok backend URL
2. Ensure ngrok is running
3. Check ngrok web interface at `http://127.0.0.1:4040` to see incoming requests
4. Verify the callback endpoint is accessible:
   ```bash
   curl -X POST https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend/api/payments/paytabs/callback \
     -H "Content-Type: application/json" \
     -d '{"test": "data"}'
   ```

### Issue: CORS errors

**Solution**: Ensure your backend CORS configuration allows requests from your ngrok frontend URL.

### Issue: 404 on API endpoints

**Check**:
1. Verify the backend URL path includes `/php-backend`
2. Check your `.htaccess` or routing configuration
3. Test endpoint directly:
   ```bash
   curl https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend/api/payments/paytabs/config
   ```

## Quick Checklist

- [ ] `.env` file updated with ngrok frontend URL
- [ ] PayTabs webhook URL configured in dashboard
- [ ] ngrok running and accessible
- [ ] Backend accessible via ngrok URL
- [ ] Frontend accessible via ngrok URL
- [ ] Payment initiation endpoint working
- [ ] Webhook callback receiving requests (check ngrok dashboard)

## Production Deployment

When moving to production:

1. Replace ngrok URLs with your actual domain
2. Update `.env` with production URLs
3. Update PayTabs webhook URL to production URL
4. Use production PayTabs credentials
5. Ensure SSL certificate is valid

---

**Current ngrok URLs** (update when URL changes):
- Backend: `https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend`
- Frontend: `https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/frontend/php-frontend`
- Webhook: `https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend/api/payments/paytabs/callback`
