# Enable OMR Currency in PayTabs

## Current Status

Your code is now configured to use **OMR (Omani Rial)** currency. However, you're getting "Currency not available" error because OMR is not enabled in your PayTabs account.

## Solution: Enable OMR in PayTabs Account

You need to contact PayTabs to enable OMR currency for your merchant account.

### Step 1: Contact PayTabs Support

**Email**: customercare@paytabs.com

**Subject**: Enable OMR Currency for Merchant Account

**Email Template**:
```
Subject: Enable OMR Currency for Merchant Account

Dear PayTabs Support,

I need to enable OMR (Omani Rial) currency for my merchant account.

Account Details:
- Profile ID: [YOUR_PROFILE_ID]
- Merchant Account: [YOUR_ACCOUNT_NAME]
- Region: Oman

Please enable OMR currency so I can process payments in Omani Rial.

Thank you.
```

### Step 2: Check PayTabs Dashboard

1. Log into your PayTabs merchant dashboard: https://merchant-oman.paytabs.com/
2. Go to **Settings** → **Business Information** or **Account Settings**
3. Look for **Currency Settings** or **Supported Currencies**
4. Check if OMR is listed and enabled
5. If OMR is listed but disabled, enable it
6. If OMR is not listed, you need to contact support

### Step 3: Verify Currency is Enabled

After PayTabs enables OMR, test it:

```bash
php test-paytabs-api.php
```

Or try creating a payment - it should work with OMR now.

## Alternative: Check Dashboard First

Some PayTabs accounts allow enabling currencies directly in the dashboard:

1. Login to PayTabs merchant dashboard
2. Navigate to: **Settings** → **Currency Settings** or **Payment Settings**
3. Look for **Available Currencies** or **Supported Currencies**
4. Enable **OMR (Omani Rial)** if available
5. Save changes

## Why OMR Might Not Be Enabled

- New merchant accounts may need to request currency activation
- Some currencies require approval
- Account verification status might affect currency availability

## Timeline

After requesting OMR enablement:
- **Response time**: Usually 24-48 hours
- **Activation time**: May be immediate or require account verification

## Testing After Enablement

Once OMR is enabled, test with:

```bash
php test-paytabs-api.php
```

You should see successful payment creation with OMR currency.

## Current Code Configuration

The code is now set to use OMR:
- Booking currency (defaults to OMR)
- Payment requests use booking currency
- No currency conversion needed

## Support Contact Information

- **Email**: customercare@paytabs.com
- **Support Portal**: https://support.paytabs.com/
- **Phone**: Check PayTabs website for your region's support number

## Important Notes

1. **Don't change currency in code** - OMR should work once enabled in PayTabs
2. **Keep using OMR** - The code is already configured correctly
3. **Contact support** - This is the only way to enable currencies in PayTabs
4. **Wait for confirmation** - PayTabs will confirm when OMR is enabled

## Verification

After PayTabs enables OMR, you can verify by:

1. **API Test**: Run `php test-paytabs-api.php`
2. **Dashboard**: Check transaction logs show OMR
3. **Payment Flow**: Create a test payment - should work with OMR

---

**Status**: Code is ready for OMR. Waiting for PayTabs to enable OMR in your account.

