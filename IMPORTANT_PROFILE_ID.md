# IMPORTANT: Verify Your Profile ID

## From Your PayTabs Dashboard

Your PayTabs profile shows:
- **Profile ID: 170407** ✅
- **OMR Currency: Available** ✅

## Critical Check

**Your `.env` file MUST have the correct Profile ID:**

```env
PAYTABS_PROFILE_ID=170407
```

**Not**:
- ❌ `PAYTABS_PROFILE_ID=12345678` (wrong ID)
- ❌ `PAYTABS_PROFILE_ID="170407"` (with quotes - remove them)

## Verify Your Configuration

Run this script to check:
```bash
php verify-paytabs-profile.php
```

This will:
1. Show your configured Profile ID
2. Compare it with 170407
3. Test if OMR works with your current configuration

## Why Profile ID Matters

PayTabs uses Profile ID to:
- Identify which merchant account to use
- Check which currencies are available
- Validate API requests

**If Profile ID is wrong, you'll get "Currency not available" even though OMR is enabled on your account!**

## Fix Steps

1. **Check your `.env` file**:
   ```env
   PAYTABS_PROFILE_ID=170407
   ```

2. **Remove quotes if present**:
   ```env
   # Wrong:
   PAYTABS_PROFILE_ID="170407"
   
   # Correct:
   PAYTABS_PROFILE_ID=170407
   ```

3. **Restart PHP server** after updating `.env`

4. **Test again**:
   ```bash
   php verify-paytabs-profile.php
   ```

## Other Requirements

Make sure your `.env` also has:
```env
PAYTABS_PROFILE_ID=170407
PAYTABS_SERVER_KEY=your_server_key_from_profile_170407
PAYTABS_REGION=OMN
```

**Important**: The Server Key must match Profile ID 170407!

