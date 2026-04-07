# Debug OMR Currency Issue

## Your Account Status

From your PayTabs dashboard:
- ✅ **OMR is AVAILABLE** in your account
- Profile ID: **170407**
- Mode: Test
- Payment Methods for OMR: Visa, Electron, MasterCard, Maestro

## Why You're Still Getting "Currency not available"

If OMR is available but you're getting the error, check these:

### 1. Profile ID Mismatch

**Most Common Issue**: Your `.env` file might have the wrong Profile ID.

**Check**: Run verification script:
```bash
php verify-paytabs-profile.php
```

**Fix**: Update your `.env` file:
```env
PAYTABS_PROFILE_ID=170407
```

### 2. Amount Format for OMR

OMR uses **3 decimal places**. The code has been updated to format amounts correctly.

Example:
- ✅ Correct: `150.000` (3 decimal places)
- ❌ Wrong: `150.00` (2 decimal places)
- ❌ Wrong: `150` (no decimals)

### 3. Server Key Mismatch

Ensure your Server Key matches Profile ID 170407.

**Check**: 
1. Log into PayTabs dashboard
2. Go to API Credentials
3. Verify the Server Key matches what's in your `.env`

### 4. API Endpoint

Since you're in Test mode with Profile ID 170407, make sure you're using:
- Region: `OMN` (Oman)
- API URL: `https://secure-oman.paytabs.com`

Verify in `.env`:
```env
PAYTABS_REGION=OMN
```

### 5. Test Mode vs Production

Your account is in **Test mode**. Make sure:
- You're using test credentials (not production)
- Test payments should work in test mode
- OMR should be available in both test and production

## Quick Diagnostic Steps

### Step 1: Verify Profile ID

```bash
php verify-paytabs-profile.php
```

This will check if your Profile ID matches 170407.

### Step 2: Check .env File

Make sure your `.env` has:
```env
PAYTABS_PROFILE_ID=170407
PAYTABS_SERVER_KEY=your_server_key_for_profile_170407
PAYTABS_REGION=OMN
```

### Step 3: Test API Directly

```bash
php test-paytabs-api.php
```

Check the error message - it should now show more details.

## Code Updates Made

The code has been updated to:
- ✅ Format OMR amounts with 3 decimal places (e.g., 150.000)
- ✅ Use Profile ID from configuration
- ✅ Show detailed error messages

## Expected Behavior

With Profile ID 170407 and OMR available:
- ✅ Payments should work with OMR currency
- ✅ Amounts should be formatted as 150.000 (3 decimals)
- ✅ No "Currency not available" error

## Still Having Issues?

1. **Run verification script**: `php verify-paytabs-profile.php`
2. **Check error message** - it should now be more detailed
3. **Verify credentials** match Profile ID 170407
4. **Check PayTabs dashboard** - ensure you're using the correct API credentials for Profile ID 170407

