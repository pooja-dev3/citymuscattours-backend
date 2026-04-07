# Fix PayTabs Currency Not Available Error

## Problem

You're getting this error:
```
Failed to initiate payment: PayTabs API returned HTTP code: 400 - Currency not available
```

This means your PayTabs account/profile doesn't support the currency you're trying to use (e.g., OMR).

## Quick Fix Options

### Option 1: Use a Supported Currency (Recommended)

PayTabs typically supports these currencies:
- **USD** (US Dollar) ✅ - Most commonly available
- **EUR** (Euro) ✅
- **SAR** (Saudi Riyal) - For Saudi Arabia accounts
- **AED** (UAE Dirham) - For UAE accounts
- **EGP** (Egyptian Pound) - For Egypt accounts
- **OMR** (Omani Rial) - May not be available for all accounts

**Solution**: Update your booking currency to a supported one.

### Option 2: Check Your PayTabs Account

1. Log into your PayTabs merchant dashboard
2. Go to **Settings** → **Business Information** or **Account Settings**
3. Check which currencies are enabled for your account
4. Enable the currency you need if it's available

### Option 3: Update Code to Use Supported Currency

The code has been updated to automatically use USD as fallback if OMR is not available. However, **you need to handle currency conversion** if amounts differ.

## Recommended Solution

### Step 1: Check Available Currencies

Contact PayTabs support or check your dashboard to see which currencies are available for your account.

### Step 2: Update Your Configuration

If USD is available (most common), you have two options:

#### Option A: Convert at Booking Creation

Update your booking creation to store amounts in USD:

```php
// When creating booking, convert OMR to USD
$amountOMR = 150.000; // Original amount in OMR
$exchangeRate = 2.60; // 1 OMR = 2.60 USD (check current rate)
$amountUSD = $amountOMR * $exchangeRate;

// Store both for reference
$bookingData = [
    'total_amount' => $amountUSD,
    'currency' => 'USD',
    'original_amount' => $amountOMR,
    'original_currency' => 'OMR',
    'exchange_rate' => $exchangeRate,
];
```

#### Option B: Convert at Payment Time

The code now automatically uses USD if OMR is not available. However, you should convert the amount:

```php
// In paymentController.php, add currency conversion:
$requestedCurrency = strtoupper($booking['currency'] ?? 'OMR');
$paymentCurrency = 'USD'; // Use USD which is most commonly available

// Convert amount if needed
$amount = (float)$booking['total_amount'];
if ($requestedCurrency === 'OMR' && $paymentCurrency === 'USD') {
    $exchangeRate = 2.60; // 1 OMR = 2.60 USD (update with current rate)
    $amount = $amount * $exchangeRate;
}
```

## Implementation Guide

### For Immediate Fix (Use USD)

1. **Update bookings to use USD**:
   - Either convert amounts when creating bookings
   - Or update existing bookings to USD

2. **Update payment controller** (already done):
   - Code now automatically uses USD as fallback
   - But amount is not converted - you need to add conversion

### Proper Implementation with Currency Conversion

Here's a better approach - update the payment controller to convert currencies:

```php
// In paymentController.php, after getting booking:

// Get exchange rate (you can store this in config or fetch from API)
$exchangeRates = [
    'OMR_TO_USD' => 2.60,
    'INR_TO_USD' => 0.012,
    // Add more as needed
];

$requestedCurrency = strtoupper($booking['currency'] ?? 'OMR');
$paymentCurrency = 'USD'; // Use USD which is always available

// Convert amount if currency needs to change
$amount = (float)$booking['total_amount'];
if ($requestedCurrency !== $paymentCurrency) {
    $rateKey = $requestedCurrency . '_TO_' . $paymentCurrency;
    if (isset($exchangeRates[$rateKey])) {
        $amount = $amount * $exchangeRates[$rateKey];
        error_log("Currency conversion: {$booking['total_amount']} {$requestedCurrency} = {$amount} {$paymentCurrency}");
    }
}

$paymentData = [
    'cart_id' => 'BK' . str_pad($bookingId, 8, '0', STR_PAD_LEFT),
    'amount' => $amount,
    'currency' => $paymentCurrency,
    // ... rest of payment data
];
```

## Testing

After making changes, test with:

```bash
php test-paytabs-api.php
```

Make sure to use a currency that's available in your PayTabs account.

## Alternative: Enable OMR in PayTabs

If you need to use OMR specifically:

1. **Contact PayTabs Support**:
   - Email: support@paytabs.com
   - Request to enable OMR currency for your account
   - Provide your merchant account details

2. **Check Dashboard**:
   - Some accounts can enable currencies in the dashboard
   - Go to Settings → Currencies

## Current Code Behavior

The code has been updated to:
- ✅ Automatically use USD if OMR is not available
- ⚠️ Amount is NOT converted automatically (you need to add conversion)
- ✅ Error message shows which currency is not available

## Recommendation

**For immediate solution**: Use USD for all payments (most reliable)

**For production**: 
1. Check with PayTabs which currencies are available
2. Enable OMR if possible
3. If OMR not available, implement proper currency conversion
4. Display converted amounts to customers clearly

## Currency Conversion Service

For accurate conversion, consider using:
- **Exchange rate API** (e.g., exchangerate-api.com, fixer.io)
- **Manual rates** updated regularly
- **Bank rates** for accuracy

Remember to:
- Display both original and converted amounts to customers
- Store conversion rate for audit trail
- Update exchange rates regularly

