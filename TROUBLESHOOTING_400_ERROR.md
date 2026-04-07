# Troubleshooting PayTabs 400 Error

## Problem

You're getting a 400 HTTP error from PayTabs API:
```
Failed to initiate payment: PayTabs API returned HTTP code: 400
```

## Common Causes & Solutions

### 1. Invalid Profile ID Format

**Issue**: Profile ID must be an integer, not a string.

**Solution**: Check your `.env` file:
```env
PAYTABS_PROFILE_ID=12345678
```
NOT:
```env
PAYTABS_PROFILE_ID="12345678"  # With quotes
PAYTABS_PROFILE_ID=ABC12345678 # With letters
```

### 2. Invalid Currency Code

**Issue**: Currency must be a valid 3-letter ISO code.

**Valid codes for Oman region**:
- `OMR` (Omani Rial) ✅
- `USD` (US Dollar) ✅
- `EUR` (Euro) ✅

**Check**: Ensure your booking currency matches one of PayTabs supported currencies.

### 3. Missing or Invalid Customer Details

**Required fields**:
- `name` - Must not be empty
- `email` - Must be valid email format
- `phone` - Must be valid phone number (digits and + only)
- `city` - Must not be empty
- `country` - Must be 2-letter ISO code (OM, SA, AE, etc.)
- `zip` - Can be "00000" if not available

**Solution**: The code now automatically fills empty fields with defaults.

### 4. Invalid Phone Number Format

**Issue**: Phone number should contain only digits and + sign.

**Example valid formats**:
- `+96899999999` ✅
- `96899999999` ✅
- `+1-555-0100` ❌ (contains dashes - will be cleaned automatically)

The code now automatically cleans phone numbers.

### 5. Invalid Country Code

**Issue**: Country code must be 2-letter ISO code.

**Valid codes**:
- `OM` (Oman) ✅
- `SA` (Saudi Arabia) ✅
- `AE` (UAE) ✅
- `EG` (Egypt) ✅

### 6. Invalid Server Key

**Issue**: Server key might be incorrect or expired.

**Solution**: 
1. Log into PayTabs merchant dashboard
2. Go to API Credentials
3. Copy the Server Key exactly (no extra spaces)
4. Update `.env` file

### 7. Invalid Cart ID Format

**Issue**: Cart ID might contain invalid characters or be too long.

**Current format**: `BK00000123` (BK + 8-digit booking ID)

**Solution**: This is automatically generated correctly.

## Test Your Configuration

Run the test script to see detailed error messages:

```bash
php test-paytabs-api.php
```

This will:
1. Test your configuration
2. Attempt a test payment
3. Show detailed error messages from PayTabs

## Debug Steps

### Step 1: Check Error Message

The code now shows the actual PayTabs error message. Look for:
- `message` field in the error
- Any field-specific errors

### Step 2: Verify Credentials

Run configuration test:
```bash
php test-paytabs-config.php
```

Verify:
- Profile ID is numeric
- Server Key is set
- Region matches your account

### Step 3: Test API Connection

Run API test:
```bash
php test-paytabs-api.php
```

This will show exactly what PayTabs is rejecting.

### Step 4: Check PayTabs Dashboard

1. Log into PayTabs merchant dashboard
2. Check API logs/transaction logs
3. Look for failed requests and error messages

### Step 5: Validate Request Payload

The updated code now:
- Cleans phone numbers automatically
- Provides default values for empty fields
- Validates email format
- Ensures all required fields are present

## Common PayTabs Error Messages

| Error Message | Cause | Solution |
|--------------|-------|----------|
| "Invalid profile_id" | Profile ID is wrong format or invalid | Check Profile ID in .env |
| "Invalid currency" | Currency code not supported | Use OMR, USD, EUR, etc. |
| "Invalid customer email" | Email format invalid | Check email format |
| "Invalid phone number" | Phone contains invalid characters | Phone will be cleaned automatically |
| "Invalid country code" | Country code wrong format | Use 2-letter ISO code (OM, SA, etc.) |
| "Missing required field" | A required field is missing | Code now fills defaults |

## Example Valid Request

```json
{
  "profile_id": 12345678,
  "tran_type": "sale",
  "tran_class": "ecom",
  "cart_id": "BK00000123",
  "cart_currency": "OMR",
  "cart_amount": 150.000,
  "cart_description": "Booking for Package Name",
  "customer_details": {
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+96899999999",
    "street1": "Street Address",
    "city": "Muscat",
    "state": "Muscat",
    "country": "OM",
    "zip": "12345"
  }
}
```

## Still Getting 400 Error?

1. **Run test script**: `php test-paytabs-api.php`
2. **Check the error message** - it now includes PayTabs' actual error
3. **Verify credentials** in PayTabs dashboard
4. **Check PayTabs documentation** for latest API requirements
5. **Contact PayTabs support** with the exact error message

## Recent Improvements

The code has been updated to:
- ✅ Show detailed error messages from PayTabs
- ✅ Clean phone numbers automatically
- ✅ Provide default values for missing fields
- ✅ Validate all required fields
- ✅ Format currency codes correctly
- ✅ Handle empty addresses gracefully

