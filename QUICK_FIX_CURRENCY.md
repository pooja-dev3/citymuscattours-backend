# Quick Fix: Currency Not Available Error

## Problem
```
Failed to initiate payment: PayTabs API returned HTTP code: 400 - Currency not available
```

## Quick Solution

Your PayTabs account doesn't have OMR enabled. Use USD instead (most reliable).

### Option 1: Use USD (Recommended for Testing)

Add to your `.env` file:
```env
PAYTABS_PAYMENT_CURRENCY=USD
```

The code will now use USD for all PayTabs payments, regardless of booking currency.

**Note**: Amounts will be sent as-is. If you need currency conversion, you'll need to implement it separately.

### Option 2: Enable OMR in PayTabs (For Production)

To use OMR, you need to enable it in your PayTabs account:

1. **Contact PayTabs Support**:
   - Email: customercare@paytabs.com
   - Subject: Enable OMR Currency
   - Provide your Profile ID
   - Request: "Please enable OMR (Omani Rial) currency for my merchant account"

2. **Or check dashboard**:
   - Log into PayTabs merchant dashboard
   - Go to Settings → Currencies
   - Enable OMR if available

3. **Once enabled**, update `.env`:
   ```env
   PAYTABS_PAYMENT_CURRENCY=OMR
   ```

## Supported Currencies

According to PayTabs documentation, Oman accounts typically support:
- ✅ **USD** - US Dollar (always available)
- ✅ **EUR** - Euro
- ✅ **SAR** - Saudi Riyal
- ✅ **OMR** - Omani Rial (may need to be enabled)

## Current Code Behavior

The code now:
- Uses `PAYTABS_PAYMENT_CURRENCY` from `.env` (defaults to USD)
- Ignores booking currency to avoid "Currency not available" errors
- Sends amount as-is (no automatic conversion)

## For Production

If you need to support multiple currencies:

1. Enable all needed currencies in PayTabs dashboard
2. Remove or modify the currency override in code
3. Implement currency conversion if amounts need to change

## Test

After setting `PAYTABS_PAYMENT_CURRENCY=USD`, test again:

```bash
php test-paytabs-api.php
```

Payment should work now with USD.

