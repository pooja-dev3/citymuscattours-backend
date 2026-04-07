# PayTabs Deployment Troubleshooting Guide

## Error: "Authentication failed. Check authentication header." (HTTP 401)

This error occurs when PayTabs API rejects your authentication credentials. This is the most common issue after deploying to production.

### Quick Diagnosis

Run the diagnostic script on your production server:

```bash
php check-paytabs-deployment.php
```

This will check:
- ✅ If `.env` file exists and is readable
- ✅ If PayTabs credentials are set
- ✅ If credentials are valid format
- ✅ Tests API authentication

### Common Causes & Solutions

#### 1. Missing or Empty Credentials in Production

**Problem**: The `.env` file wasn't deployed or credentials are missing.

**Solution**:
1. Verify `.env` file exists in `php-backend/` directory
2. Check file contains:
   ```env
   PAYTABS_PROFILE_ID=your_profile_id
   PAYTABS_SERVER_KEY=your_server_key
   PAYTABS_REGION=OMN
   ```
3. Ensure no quotes around values:
   ```env
   # ✅ Correct
   PAYTABS_PROFILE_ID=170407
   
   # ❌ Wrong
   PAYTABS_PROFILE_ID="170407"
   ```

#### 2. Wrong Credentials (Sandbox vs Production)

**Problem**: Using sandbox/test credentials in production environment, or vice versa.

**Solution**:
1. Log into your PayTabs dashboard
2. Check if you're in **Test Mode** or **Live Mode**
3. Use the correct credentials:
   - **Test Mode** → Use sandbox credentials
   - **Live Mode** → Use production credentials
4. Verify Profile ID matches between dashboard and `.env`

#### 3. Profile ID and Server Key Mismatch

**Problem**: The Server Key doesn't belong to the Profile ID you're using.

**Solution**:
1. In PayTabs dashboard, go to your profile settings
2. Copy the **exact** Server Key for that specific Profile ID
3. Update `.env` with the correct Server Key
4. Ensure no extra spaces or characters when copying

#### 4. Wrong Region Configuration

**Problem**: Using wrong API endpoint for your account region.

**Solution**:
Check your PayTabs account region and set `PAYTABS_REGION` accordingly:

```env
# Oman
PAYTABS_REGION=OMN

# Saudi Arabia
PAYTABS_REGION=SAU

# UAE
PAYTABS_REGION=ARE

# Egypt
PAYTABS_REGION=EGY

# Global
PAYTABS_REGION=GLOBAL
```

#### 5. File Permissions Issue

**Problem**: PHP cannot read the `.env` file.

**Solution**:
```bash
# Set correct permissions
chmod 644 .env

# Or if using specific user/group
chown www-data:www-data .env
chmod 640 .env
```

#### 6. Environment Variables Not Loading

**Problem**: `.env` file exists but variables aren't being loaded.

**Solution**:
1. Check if `vendor/autoload.php` exists (Composer dependencies)
2. Verify `src/config/env.php` is being loaded
3. Check PHP error logs for environment loading errors
4. Try manually setting environment variables in your hosting panel

### Step-by-Step Fix

1. **Verify `.env` file exists**:
   ```bash
   ls -la php-backend/.env
   ```

2. **Check file contents** (without exposing keys):
   ```bash
   grep PAYTABS php-backend/.env
   ```

3. **Run diagnostic script**:
   ```bash
   php php-backend/check-paytabs-deployment.php
   ```

4. **Check server error logs**:
   - Look for "PayTabs 401 Authentication Error Details" entries
   - These will show what credentials are being used

5. **Verify in PayTabs Dashboard**:
   - Log into https://my.paytabs.com
   - Go to Developer → API Credentials
   - Verify Profile ID and Server Key match your `.env`

6. **Test with diagnostic script**:
   ```bash
   php php-backend/check-paytabs-deployment.php
   ```

### Production Deployment Checklist

- [ ] `.env` file is deployed to production server
- [ ] `.env` file has correct file permissions (readable by PHP)
- [ ] `PAYTABS_PROFILE_ID` matches your PayTabs account
- [ ] `PAYTABS_SERVER_KEY` is correct for that Profile ID
- [ ] `PAYTABS_REGION` matches your account region
- [ ] Using **production** credentials (not sandbox) for live site
- [ ] No quotes around values in `.env`
- [ ] No extra spaces in credentials
- [ ] Web server restarted after updating `.env`
- [ ] Diagnostic script passes all checks

### Testing After Fix

1. Run diagnostic: `php check-paytabs-deployment.php`
2. Try initiating a test payment
3. Check error logs for any remaining issues

### Still Having Issues?

1. **Check error logs** for detailed PayTabs error messages
2. **Contact PayTabs support** with:
   - Your Profile ID
   - Error message and trace code
   - API endpoint you're using
3. **Verify account status** in PayTabs dashboard (not suspended/restricted)

### Security Notes

- ⚠️ **Never commit `.env` file to version control**
- ⚠️ **Never expose Server Key in error messages to users**
- ⚠️ **Use environment variables in production** instead of `.env` if possible
- ⚠️ **Rotate credentials** if they may have been exposed

