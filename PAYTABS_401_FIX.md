# Fixing PayTabs 401 Authentication Error

## Your Current Situation

- **Profile ID**: 110749
- **Server Key**: 32 characters (with hyphen - this is valid)
- **Region**: OMN
- **Error**: HTTP 401 - "Authentication failed. Check profile ID and authentication header."

## Most Common Causes

### 1. Profile ID and Server Key Don't Match ⚠️ MOST LIKELY

**Problem**: The Server Key you're using doesn't belong to Profile ID 110749.

**Solution**:
1. Log into PayTabs dashboard: https://my.paytabs.com
2. Go to **Developer** → **API Credentials**
3. Verify the Profile ID shown matches **110749**
4. Copy the **Server Key** that is associated with Profile ID **110749**
5. Make sure you're copying the **Server Key**, not the Client Key
6. Update your `.env` file with the exact Server Key (no spaces, no quotes)

### 2. Test vs Production Environment Mismatch

**Problem**: Using test/sandbox credentials in production, or vice versa.

**Solution**:
1. Check your PayTabs dashboard - are you in **Test Mode** or **Live Mode**?
2. If in **Test Mode**, make sure your `.env` has test credentials
3. If in **Live Mode**, make sure your `.env` has production credentials
4. **You cannot mix them** - test credentials only work in test mode, production only in live mode

### 3. Wrong Region/API Endpoint

**Problem**: Your account might be registered in a different region.

**Solution**:
1. Check your PayTabs dashboard - what region is your account in?
2. Verify the API endpoint matches:
   - **OMN/OMAN** → `https://secure-oman.paytabs.com`
   - **SAU** → `https://secure.paytabs.sa`
   - **ARE** → `https://secure.paytabs.com`
   - **EGY** → `https://secure-egypt.paytabs.com`
3. Update `PAYTABS_REGION` in `.env` if needed

### 4. IP Restrictions

**Problem**: Your PayTabs account might have IP restrictions enabled.

**Solution**:
1. In PayTabs dashboard, go to **Settings** → **Security** or **API Settings**
2. Check if IP whitelist is enabled
3. If yes, add your server's IP address to the whitelist
4. Or temporarily disable IP restrictions for testing

### 5. Account Status

**Problem**: Your PayTabs account might be suspended or restricted.

**Solution**:
1. Check your PayTabs dashboard for any account warnings
2. Contact PayTabs support if account appears restricted
3. Verify your account is active and in good standing

## Step-by-Step Fix

### Step 1: Verify Credentials in Dashboard

1. Log into https://my.paytabs.com
2. Navigate to **Developer** → **API Credentials**
3. Note down:
   - **Profile ID**: Should be `110749`
   - **Server Key**: Copy it exactly
   - **Environment**: Test or Live?
   - **Region**: What region is shown?

### Step 2: Update .env File

```env
PAYTABS_PROFILE_ID=110749
PAYTABS_SERVER_KEY=your_exact_server_key_from_dashboard
PAYTABS_REGION=OMN
```

**Important**:
- No quotes around values
- No extra spaces before/after
- Copy Server Key exactly as shown (including hyphens)

### Step 3: Verify Key Types

Make sure you're using:
- ✅ **Server Key** for `PAYTABS_SERVER_KEY` (backend API calls)
- ✅ **Client Key** for `PAYTABS_CLIENT_KEY` (frontend integration - optional)

**Do NOT**:
- ❌ Use Client Key as Server Key
- ❌ Use Server Key from a different Profile ID

### Step 4: Test Again

```bash
php verify-paytabs-credentials.php
```

This will test the authentication and show you if it's working.

### Step 5: Check Server Logs

If still failing, check your PHP error logs for detailed PayTabs error messages:
- Look for "PayTabs 401 Authentication Error Details"
- This will show what credentials are being sent

## Still Not Working?

If you've verified all of the above and it's still not working:

1. **Contact PayTabs Support**:
   - Provide your Profile ID: 110749
   - Provide the error trace code from the response
   - Ask them to verify your Server Key is correct

2. **Double-check in Dashboard**:
   - Sometimes credentials are regenerated
   - Make sure you're looking at the current/active credentials
   - Check if there are multiple profiles/accounts

3. **Test with PayTabs API directly**:
   - Use a tool like Postman or curl
   - Make a direct API call to verify credentials work
   - This will confirm if it's a code issue or credential issue

## Quick Checklist

- [ ] Profile ID in dashboard matches 110749
- [ ] Server Key copied exactly (no spaces, no quotes)
- [ ] Using Server Key, not Client Key
- [ ] Test credentials for test mode, production for live mode
- [ ] Region matches account region (OMN)
- [ ] No IP restrictions blocking your server
- [ ] Account is active and not suspended
- [ ] Restarted web server after updating .env

