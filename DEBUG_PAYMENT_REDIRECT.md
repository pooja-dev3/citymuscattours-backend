# Debug Payment Redirect Issue

## Problem

After payment, you're getting:
```
Payment Verification Failed
Missing payment information. Please contact support with your booking details.
```

## What PayTabs Redirects

When PayTabs redirects back after payment, it typically includes:
- `tran_ref` - Transaction reference (always included)
- `cart_id` - Cart/order ID (may be included)
- `payment_result` - Payment result status (may be included)
- Other PayTabs-specific parameters

**Note**: PayTabs does NOT automatically include your `bookingId` parameter in the redirect URL.

## Solution Applied

The code has been updated to:

1. **Extract bookingId from cart_id** if available:
   - Cart ID format: `BK00000123` (where `00000123` is the booking ID)
   - Code extracts booking ID from cart_id

2. **Check multiple sources** for booking ID:
   - URL parameter: `?bookingId=123`
   - Session storage: `paytabs_booking_id`
   - Cart ID extraction: `BK00000123` → `123`

3. **Better error messages** showing what data is available

## Debug Steps

### Check Browser Console (Next.js Frontend)

Open browser console and look for:
```
Payment verification debug: {
  transactionRef: "...",
  bookingId: "...",
  urlParams: {...},
  sessionStorage: {...}
}
```

This shows exactly what data is available.

### Check URL After PayTabs Redirect

After completing payment, check the URL:
```
https://your-frontend-url/booking-confirmation?tran_ref=TST...&cart_id=BK00000123&bookingId=123
```

**What to look for:**
- ✅ `tran_ref` should be present
- ✅ `bookingId` should be in URL (we include it in return_url)
- ✅ `cart_id` might be present

### Check Session Storage

Before redirecting to PayTabs, the code stores:
```javascript
sessionStorage.setItem('paytabs_booking_id', bookingId);
sessionStorage.setItem('paytabs_transaction_ref', transactionRef);
```

**Check if these exist:**
1. Open browser DevTools
2. Go to Application/Storage tab
3. Check Session Storage
4. Look for `paytabs_booking_id` and `paytabs_transaction_ref`

## Why bookingId Might Be Missing

1. **Session Storage cleared** - User cleared browser data
2. **Different browser/tab** - Session storage is per-tab
3. **URL parameter lost** - PayTabs might not preserve all URL parameters
4. **Cart ID not available** - PayTabs might not include cart_id in redirect

## Fallback Solution

If bookingId is missing, you can:

1. **Use transaction reference only** - Query database by transaction_ref
2. **Contact support** - User provides transaction reference
3. **Extract from cart_id** - If PayTabs includes it (already implemented)

## Updated Code Behavior

The code now:
- ✅ Checks URL for `bookingId`
- ✅ Checks sessionStorage for `paytabs_booking_id`
- ✅ Extracts booking ID from `cart_id` if available
- ✅ Shows detailed error with available data
- ✅ Logs debug information to console

## Test

After the fix, test the payment flow:

1. Create booking
2. Initiate payment
3. Complete payment on PayTabs
4. Check browser console for debug logs
5. Verify booking ID is found from one of the sources

If it still fails, check the console logs to see which data is available.

