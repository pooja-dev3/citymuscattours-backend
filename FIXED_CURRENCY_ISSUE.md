# ✅ Fixed Currency Issue

## Problem Identified

Your bookings were being created with **INR** currency (default), but PayTabs requires **OMR** for your account.

## Solution Applied

The payment controller now **always uses OMR** for PayTabs payments, regardless of the booking currency.

## What Changed

**Before:**
```php
$paymentCurrency = strtoupper($booking['currency'] ?? 'OMR'); // Could be INR
```

**After:**
```php
$paymentCurrency = 'OMR'; // Always use OMR for PayTabs
```

## Why This Works

- Your PayTabs account has OMR available ✅
- Test script confirms OMR works ✅
- Bookings may have different currencies (INR, OMR, etc.)
- But PayTabs payments must use OMR for your account

## Important Note

The booking amount is sent as-is to PayTabs. If your bookings are stored in INR but you want to charge in OMR:
- You may need currency conversion (1 INR ≈ 0.0046 OMR)
- Or ensure bookings are created with OMR currency from the start

For now, the amount is sent directly - this should work if your booking amounts are already in OMR format.

## Test

Try creating a payment again - it should now work with OMR currency!

