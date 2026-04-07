# PayTabs Region Configuration Guide

## Important: Choose the Correct Region

PayTabs uses different API endpoints for different regions. You **must** use the correct region that matches your merchant account.

## How to Determine Your Region

Check your PayTabs merchant dashboard URL:
- **Oman**: `https://merchant-oman.paytabs.com/` → Use `PAYTABS_REGION=OMN`
- **Saudi Arabia**: `https://merchant-sa.paytabs.com/` or `https://secure.paytabs.sa` → Use `PAYTABS_REGION=SAU`
- **UAE**: `https://merchant.paytabs.com/` or `https://merchant-uae.paytabs.com/` → Use `PAYTABS_REGION=ARE`
- **Egypt**: `https://merchant-egypt.paytabs.com/` → Use `PAYTABS_REGION=EGY`
- **Global**: `https://merchant-global.paytabs.com/` → Use `PAYTABS_REGION=GLOBAL`

## Region Configuration in .env

```env
# For Oman (if your account is on merchant-oman.paytabs.com)
PAYTABS_REGION=OMN

# For Saudi Arabia
PAYTABS_REGION=SAU

# For UAE
PAYTABS_REGION=ARE

# For Egypt
PAYTABS_REGION=EGY

# For Global
PAYTABS_REGION=GLOBAL
```

## API Endpoints by Region

The service automatically selects the correct API endpoint based on your region:

| Region Code | API Endpoint |
|-------------|--------------|
| OMN / OMAN | https://secure-oman.paytabs.com |
| SAU | https://secure.paytabs.sa |
| ARE | https://secure.paytabs.com |
| EGY | https://secure-egypt.paytabs.com |
| GLOBAL | https://secure-global.paytabs.com |

## Common Issue: Wrong Region

**Problem**: You get API errors or "Payment creation failed"

**Cause**: Your `.env` region doesn't match your PayTabs account region

**Solution**: 
1. Check your merchant dashboard URL
2. Update `PAYTABS_REGION` in `.env` to match
3. Restart your PHP server

## Example: Oman Account Setup

If you created your account on `https://merchant-oman.paytabs.com/`:

```env
PAYTABS_PROFILE_ID=12345678
PAYTABS_SERVER_KEY=your_server_key_here
PAYTABS_CLIENT_KEY=your_client_key_here
PAYTABS_REGION=OMN
```

The service will automatically use: `https://secure-oman.paytabs.com` for all API requests.

## Verify Your Configuration

Run the test script to verify:

```bash
php test-paytabs-config.php
```

This will show you which API endpoint is being used based on your region setting.
