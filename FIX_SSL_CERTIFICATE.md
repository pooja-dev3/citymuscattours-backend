# Fix SSL Certificate Error for PayTabs API

## Problem

You're getting this error:
```
Failed to initiate payment: PayTabs API request failed: SSL certificate problem: unable to get local issuer certificate
```

This happens because PHP's cURL can't verify PayTabs SSL certificate.

## Solution Options

### Option 1: Download CA Certificate Bundle (Recommended for Production)

1. **Download the CA certificate bundle**:
   - Go to: https://curl.se/ca/cacert.pem
   - Or download directly: https://curl.se/ca/cacert.pem
   - Save it as `php-backend/cacert.pem`

2. **The code will automatically use it** if it exists in the php-backend directory.

### Option 2: Disable SSL Verification (Development Only)

For localhost/development testing only, you can temporarily disable SSL verification:

1. **Add to your `.env` file**:
   ```env
   PAYTABS_VERIFY_SSL=false
   ```

2. **Restart your PHP server** after updating `.env`

**⚠️ Warning**: Never use this in production! It makes your connection insecure.

### Option 3: Configure PHP php.ini (Permanent Fix)

1. **Find your php.ini file**:
   ```bash
   php --ini
   ```

2. **Edit php.ini** and set:
   ```ini
   curl.cainfo = "C:/path/to/cacert.pem"
   openssl.cafile = "C:/path/to/cacert.pem"
   ```

3. **Download cacert.pem** from https://curl.se/ca/cacert.pem

4. **Place it** in a permanent location (e.g., `C:/wamp64/bin/php/php8.x.x/extras/ssl/cacert.pem`)

5. **Update php.ini**:
   ```ini
   curl.cainfo = "C:/wamp64/bin/php/php8.x.x/extras/ssl/cacert.pem"
   openssl.cafile = "C:/wamp64/bin/php/php8.x.x/extras/ssl/cacert.pem"
   ```

6. **Restart Apache/WAMP**

### Option 4: Quick Fix for WAMP (Windows)

1. **Download cacert.pem**:
   ```bash
   # Using PowerShell
   Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile "c:\wamp64\bin\php\php8.x.x\extras\ssl\cacert.pem"
   ```

2. **Edit php.ini** (usually in `C:\wamp64\bin\php\php8.x.x\php.ini`):
   ```ini
   curl.cainfo = "C:/wamp64/bin/php/php8.x.x/extras/ssl/cacert.pem"
   openssl.cafile = "C:/wamp64/bin/php/php8.x.x/extras/ssl/cacert.pem"
   ```

3. **Restart WAMP**

## Quick Test

After applying a fix, test it:

```bash
php test-paytabs-config.php
```

Or try creating a payment to see if the SSL error is resolved.

## For Production

**Always use Option 1 or Option 3** in production. Never disable SSL verification in production environments.

## Automatic Fallback

The PayTabsService now includes automatic fallback:
1. First tries with SSL verification enabled
2. Checks for `cacert.pem` in php-backend directory
3. Falls back to disabled SSL only in development mode if explicitly set

## Verification

To verify SSL is working:

```php
<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://secure-oman.paytabs.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$result = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "SSL Error: " . $error . "\n";
} else {
    echo "SSL connection successful!\n";
}
```

