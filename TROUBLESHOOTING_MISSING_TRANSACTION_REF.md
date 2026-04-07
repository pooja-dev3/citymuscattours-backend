# Troubleshooting: Missing Transaction Reference

## Problem

After payment redirect from PayTabs, you're getting:
```
Missing transaction reference. Please contact support.
```

## Why This Happens

1. **PayTabs doesn't include `tran_ref` in redirect URL** - Sometimes PayTabs redirects without including the transaction reference
2. **SessionStorage cleared** - Browser cleared session storage or user used different browser/tab
3. **Different origin** - PayTabs redirect might go to different domain/subdomain

## Solutions Applied

### 1. Check SessionStorage FIRST

The code now checks sessionStorage BEFORE URL parameters, since we store it before redirecting:

```javascript
// We store before redirect:
sessionStorage.setItem("paytabs_booking_id", bookingId);
sessionStorage.setItem("paytabs_transaction_ref", transactionRef);

// We check sessionStorage first:
const storedTransactionRef = sessionStorage.getItem("paytabs_transaction_ref");
const transactionRef = storedTransactionRef || searchParams.get("tran_ref") || ...;
```

### 2. Multiple Fallbacks

The code checks multiple sources:
1. ✅ SessionStorage (stored before redirect)
2. ✅ URL parameter `tran_ref`
3. ✅ URL parameter `tranRef` (alternative)
4. ✅ Extract from cart_id if available

### 3. Debug Logging

Check browser console for detailed debug info:
```javascript
console.log("Payment verification debug:", {
  transactionRef,
  bookingId,
  storedTransactionRef,
  storedBookingId,
  urlParams: {...},
  fullUrl: "..."
});
```

## Manual Recovery

If transaction reference is missing:

### Option 1: Check PayTabs Dashboard

1. Go to your PayTabs merchant dashboard
2. Find the transaction by:
   - Customer email
   - Booking date/time
   - Amount
3. Get the `tran_ref` from the transaction details
4. Manually verify using the API:
   ```
   POST /api/payments/paytabs/verify
   {
     "transactionRef": "TST1234567890",
     "bookingId": 123
   }
   ```

### Option 2: Query Database

If you have bookingId, you can find the transaction reference in the database:

```sql
SELECT p.provider_reference, p.metadata, p.status
FROM payments p
WHERE p.booking_id = YOUR_BOOKING_ID
AND p.provider = 'paytabs'
ORDER BY p.created_at DESC
LIMIT 1;
```

The `provider_reference` field contains the `tran_ref`.

### Option 3: Use Booking ID Only (Future Enhancement)

We could add an endpoint to verify payment using just bookingId:

```
POST /api/payments/paytabs/verify-by-booking
{
  "bookingId": 123
}
```

This would:
1. Find payment record by booking_id
2. Get transaction_ref from payment record
3. Verify with PayTabs
4. Update booking status

## Prevention

### Ensure SessionStorage Persists

Make sure sessionStorage is set BEFORE redirecting:

```javascript
// ✅ CORRECT: Set before redirect
sessionStorage.setItem("paytabs_transaction_ref", transactionRef);
window.location.href = paymentUrl;

// ❌ WRONG: Setting after redirect won't work
window.location.href = paymentUrl;
sessionStorage.setItem("paytabs_transaction_ref", transactionRef);
```

### Verify Return URL

Make sure your return URL includes bookingId:

```php
$returnUrl = $baseUrl . '/booking-confirmation?bookingId=' . $bookingId;
```

This helps with recovery even if tran_ref is missing.

## Test Checklist

1. ✅ Complete a payment on PayTabs
2. ✅ Check browser console for debug logs
3. ✅ Verify sessionStorage has both values:
   - `paytabs_booking_id`
   - `paytabs_transaction_ref`
4. ✅ Check URL has parameters (at least `bookingId`)
5. ✅ Check if `tran_ref` is in URL

## Next Steps

If the issue persists:

1. **Check browser console** - Look for debug logs
2. **Check URL** - What parameters does PayTabs include?
3. **Check sessionStorage** - Is data still there after redirect?
4. **Contact PayTabs support** - Ask if they can include `tran_ref` in redirect URL

## Temporary Workaround

If you have the bookingId from URL, you can manually verify:

1. Go to PayTabs dashboard
2. Find transaction by booking details
3. Get `tran_ref`
4. Call verify API manually with both values

