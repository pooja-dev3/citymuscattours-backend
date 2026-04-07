# Quick Setup Guide - ngrok URLs for PayTabs

## Your URLs

- **Backend**: `https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend`
- **Frontend**: `https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/frontend/php-frontend`

## Quick Configuration Steps

### 1. Update `.env` file

Add this to your `php-backend/.env` file:

```env
CLIENT_URL=https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/frontend/php-frontend

PAYTABS_PROFILE_ID=your_profile_id
PAYTABS_SERVER_KEY=your_server_key
PAYTABS_CLIENT_KEY=your_client_key
PAYTABS_REGION=OMN
```

**Note**: The code automatically converts the frontend URL to backend URL for callbacks.

### 2. Configure PayTabs Webhook

In your PayTabs dashboard at `https://merchant-oman.paytabs.com/`:

1. Go to **Settings** → **Webhooks** (or Developer Settings)
2. Set webhook URL to:
   ```
   https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend/api/payments/paytabs/callback
   ```

### 3. Test Configuration

```bash
php test-paytabs-config.php
```

## What the Code Does Automatically

When you set `CLIENT_URL` to your frontend URL, the payment controller will:

1. **Frontend URL**: Used for return URL after payment
   - `https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/frontend/php-frontend/booking-confirmation`

2. **Backend URL**: Automatically constructed for webhook callback
   - `https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend/api/payments/paytabs/callback`

## Testing

### Test Payment Initiation

```bash
POST https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend/api/payments/paytabs/initiate
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "bookingId": 123,
  "customerName": "Test User",
  "customerEmail": "test@example.com",
  "customerPhone": "+968 9999 9999"
}
```

### Test Webhook Endpoint

```bash
curl -X POST https://ketogenetic-northeastwardly-kortney.ngrok-free.dev/php-backend/api/payments/paytabs/callback \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

## Important Reminders

⚠️ **When ngrok URL changes** (every restart):
1. Update `CLIENT_URL` in `.env`
2. Update webhook URL in PayTabs dashboard
3. Restart PHP server

## Full Documentation

See `PAYTABS_NGROK_SETUP.md` for complete details.
