# PayTabs Payment Gateway Integration Guide

This guide provides step-by-step instructions for integrating PayTabs payment gateway into your Tours and Travels application.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Database Setup](#database-setup)
4. [Environment Configuration](#environment-configuration)
5. [API Endpoints](#api-endpoints)
6. [Frontend Integration](#frontend-integration)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

## Overview

PayTabs integration has been added to handle payment processing for bookings. The integration includes:

- Payment initiation endpoint
- Payment verification endpoint
- Webhook/callback handler for payment status updates
- Automatic booking status updates based on payment status

## Prerequisites

1. **PayTabs Account**: Sign up for a PayTabs merchant account at [https://www.paytabs.com](https://www.paytabs.com)
2. **API Credentials**: Obtain your PayTabs API credentials:
   - Profile ID
   - Server Key (for backend API calls)
   - Client Key (for frontend integration, optional)
3. **PHP Requirements**: 
   - PHP >= 7.4
   - cURL extension enabled
   - JSON extension enabled

## Database Setup

### Step 1: Run the Migration

Run the migration script to add 'paytabs' as a payment provider option:

```bash
# Option 1: Using PHP script
php add-paytabs-provider.php

# Option 2: Using SQL file directly
mysql -u root -p tour_travels < database/add_paytabs_provider.sql
```

Or execute the SQL in phpMyAdmin:

```sql
ALTER TABLE payments MODIFY COLUMN provider ENUM('razorpay', 'stripe', 'paytabs') NOT NULL;
```

**Verification**: Verify the migration by checking the payments table schema:
```sql
SHOW COLUMNS FROM payments WHERE Field = 'provider';
```

## Environment Configuration

### Step 2: Add PayTabs Credentials to .env File

Add the following variables to your `.env` file in the `php-backend` directory:

```env
# PayTabs Configuration
PAYTABS_PROFILE_ID=your_profile_id_here
PAYTABS_SERVER_KEY=your_server_key_here
PAYTABS_CLIENT_KEY=your_client_key_here
PAYTABS_REGION=SAU
```

**Configuration Details**:

- `PAYTABS_PROFILE_ID`: Your PayTabs merchant profile ID (numeric)
- `PAYTABS_SERVER_KEY`: Server key for backend API authentication
- `PAYTABS_CLIENT_KEY`: Client key for frontend integration (optional)
- `PAYTABS_REGION`: Region code:
  - `OMN` or `OMAN` - Oman (API: https://secure-oman.paytabs.com)
  - `SAU` - Saudi Arabia (API: https://secure.paytabs.sa)
  - `ARE` - UAE (API: https://secure.paytabs.com)
  - `EGY` - Egypt (API: https://secure-egypt.paytabs.com)
  - `GLOBAL` - Global (API: https://secure-global.paytabs.com)

**Important**: 
- Use sandbox credentials for testing
- Switch to production credentials for live environment
- Never commit your `.env` file to version control

## API Endpoints

The following API endpoints are available for PayTabs integration:

### 1. Initiate Payment

**Endpoint**: `POST /api/payments/paytabs/initiate`

**Authentication**: Required (JWT token)

**Request Body**:
```json
{
  "bookingId": 123,
  "customerName": "John Doe",
  "customerEmail": "john@example.com",
  "customerPhone": "+968 9999 9999",
  "address": {
    "street": "Street Address",
    "city": "Muscat",
    "state": "Muscat",
    "country": "OM",
    "zip": "12345"
  }
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "payment_id": 1,
    "payment_url": "https://secure.paytabs.sa/payment/page/XXXXX",
    "transaction_ref": "TST2106200012345",
    "cart_id": "BK00000123",
    "booking_id": 123
  }
}
```

**Usage**: Redirect the user to `payment_url` to complete the payment.

### 2. Verify Payment

**Endpoint**: `POST /api/payments/paytabs/verify`

**Authentication**: Required (JWT token)

**Request Body**:
```json
{
  "transactionRef": "TST2106200012345",
  "bookingId": 123
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "verified": true,
    "transaction_ref": "TST2106200012345",
    "status": "success",
    "amount": 150.000,
    "currency": "OMR",
    "payment_info": {
      "payment_method": "CreditCard",
      "card_scheme": "VISA"
    }
  }
}
```

### 3. Payment Callback/Webhook

**Endpoint**: `POST /api/payments/paytabs/callback`

**Authentication**: Not required (public endpoint for PayTabs webhook)

This endpoint is automatically called by PayTabs when payment status changes. It:
- Validates the callback signature
- Verifies the payment via PayTabs API
- Updates the booking and payment records
- Updates booking status to "Confirmed" if payment is successful

**Note**: Configure this URL in your PayTabs merchant dashboard:
- Dashboard URL: `https://yourdomain.com/api/payments/paytabs/callback`

### 4. Get PayTabs Config

**Endpoint**: `GET /api/payments/paytabs/config`

**Authentication**: Not required (public endpoint)

**Response**:
```json
{
  "success": true,
  "data": {
    "clientKey": "your_client_key",
    "profileId": "your_profile_id"
  }
}
```

**Usage**: Use this endpoint to get configuration for frontend integration (if using PayTabs JS SDK).

## Frontend Integration

### Step 3: Frontend Payment Flow

Here's a typical payment flow in your frontend:

```javascript
// 1. Initiate payment
async function initiatePayment(bookingId, customerDetails) {
  const response = await fetch('/api/payments/paytabs/initiate', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${accessToken}`
    },
    body: JSON.stringify({
      bookingId: bookingId,
      customerName: customerDetails.name,
      customerEmail: customerDetails.email,
      customerPhone: customerDetails.phone,
      address: customerDetails.address
    })
  });
  
  const data = await response.json();
  
  if (data.success) {
    // Redirect user to PayTabs payment page
    window.location.href = data.data.payment_url;
  }
}

// 2. After payment (on return URL page)
// Check payment status when user returns from PayTabs
async function verifyPayment(transactionRef, bookingId) {
  const response = await fetch('/api/payments/paytabs/verify', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${accessToken}`
    },
    body: JSON.stringify({
      transactionRef: transactionRef,
      bookingId: bookingId
    })
  });
  
  const data = await response.json();
  
  if (data.success && data.data.verified) {
    // Payment successful - show success message
    // Redirect to booking confirmation page
  } else {
    // Payment failed - show error message
  }
}

// 3. Handle URL parameters after redirect
const urlParams = new URLSearchParams(window.location.search);
const transactionRef = urlParams.get('tran_ref');
const bookingId = urlParams.get('bookingId');

if (transactionRef && bookingId) {
  verifyPayment(transactionRef, bookingId);
}
```

### Complete Frontend Example

```javascript
// checkout.js or similar file

// Step 1: User completes booking form
async function proceedToPayment(bookingData) {
  try {
    // Create booking first
    const bookingResponse = await fetch('/api/bookings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(bookingData)
    });
    
    const bookingResult = await bookingResponse.json();
    const bookingId = bookingResult.data.id;
    
    // Initiate PayTabs payment
    const paymentResponse = await fetch('/api/payments/paytabs/initiate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        bookingId: bookingId,
        customerName: bookingData.contactName,
        customerEmail: bookingData.contactEmail,
        customerPhone: bookingData.contactPhone,
        address: {
          city: bookingData.city || 'Muscat',
          country: 'OM',
          zip: bookingData.zip || ''
        }
      })
    });
    
    const paymentResult = await paymentResponse.json();
    
    if (paymentResult.success) {
      // Store transaction reference in session/localStorage
      localStorage.setItem('paytabs_transaction_ref', paymentResult.data.transaction_ref);
      localStorage.setItem('paytabs_booking_id', bookingId);
      
      // Redirect to PayTabs payment page
      window.location.href = paymentResult.data.payment_url;
    } else {
      alert('Failed to initiate payment. Please try again.');
    }
  } catch (error) {
    console.error('Payment initiation error:', error);
    alert('An error occurred. Please try again.');
  }
}

// Step 2: Handle return from PayTabs (on booking-confirmation page)
window.addEventListener('DOMContentLoaded', async () => {
  const urlParams = new URLSearchParams(window.location.search);
  const transactionRef = urlParams.get('tran_ref') || localStorage.getItem('paytabs_transaction_ref');
  const bookingId = urlParams.get('bookingId') || localStorage.getItem('paytabs_booking_id');
  
  if (transactionRef && bookingId) {
    try {
      const response = await fetch('/api/payments/paytabs/verify', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          transactionRef: transactionRef,
          bookingId: bookingId
        })
      });
      
      const result = await response.json();
      
      if (result.success && result.data.verified) {
        // Show success message
        showSuccessMessage('Payment successful! Your booking is confirmed.');
        // Clear stored data
        localStorage.removeItem('paytabs_transaction_ref');
        localStorage.removeItem('paytabs_booking_id');
        
        // Optionally fetch and display booking details
        fetchBookingDetails(bookingId);
      } else {
        showErrorMessage('Payment verification failed. Please contact support.');
      }
    } catch (error) {
      console.error('Payment verification error:', error);
      showErrorMessage('An error occurred while verifying payment.');
    }
  }
});
```

## Testing

### Test Mode / Sandbox

1. Use PayTabs sandbox credentials in your `.env` file
2. Test payments using PayTabs test cards:
   - **Success**: Use any valid card number with future expiry date
   - **Failure**: Use declined card numbers (check PayTabs documentation)

### Test Payment Flow

1. Create a test booking
2. Initiate payment using the `/api/payments/paytabs/initiate` endpoint
3. Complete payment on PayTabs payment page
4. Verify payment status using `/api/payments/paytabs/verify`
5. Check booking status in database (should be "Confirmed" with payment_status "paid")

### Common Test Scenarios

- **Successful Payment**: Verify booking status updates correctly
- **Failed Payment**: Verify booking remains "Pending"
- **Webhook Callback**: Test that callback endpoint receives and processes PayTabs notifications
- **Payment Verification**: Test manual verification endpoint

## Troubleshooting

### Issue: "PayTabs configuration is missing"

**Solution**: Ensure all PayTabs credentials are set in `.env` file:
- `PAYTABS_PROFILE_ID`
- `PAYTABS_SERVER_KEY`
- `PAYTABS_CLIENT_KEY` (optional but recommended)
- `PAYTABS_REGION`

### Issue: "Payment creation failed"

**Possible Causes**:
- Invalid credentials
- Incorrect API URL for your region
- Network connectivity issues
- Invalid request data

**Solution**:
- Verify credentials in PayTabs dashboard
- Check `.env` file configuration
- Review error logs for detailed error messages
- Test API connection using cURL or Postman

### Issue: "Callback not being received"

**Possible Causes**:
- Webhook URL not configured in PayTabs dashboard
- SSL certificate issues
- Firewall blocking PayTabs requests

**Solution**:
- Configure callback URL in PayTabs merchant dashboard: `https://yourdomain.com/api/payments/paytabs/callback`
- Ensure your server has valid SSL certificate
- Check server logs for incoming requests
- Test callback endpoint manually using webhook testing tools

### Issue: "Payment verification fails"

**Possible Causes**:
- Invalid transaction reference
- Transaction not found in PayTabs system
- API credentials mismatch

**Solution**:
- Verify transaction reference format
- Check PayTabs dashboard for transaction status
- Ensure you're using the correct API credentials (sandbox vs production)

### Debugging Tips

1. **Enable Error Logging**: Check PHP error logs for detailed error messages
2. **Test API Directly**: Use Postman or cURL to test PayTabs API directly
3. **Check Database**: Verify payment and booking records are created/updated correctly
4. **PayTabs Dashboard**: Use PayTabs merchant dashboard to monitor transactions

## Security Considerations

1. **Never expose Server Key**: Server key should only be used on the backend
2. **Validate Callbacks**: Always validate PayTabs callbacks before processing
3. **HTTPS Only**: Use HTTPS for all payment-related endpoints in production
4. **Environment Variables**: Store credentials in `.env` file, never in code
5. **Input Validation**: Always validate and sanitize user input

## Support

- **PayTabs Documentation**: [https://docs.paytabs.com](https://docs.paytabs.com)
- **PayTabs Support**: Contact PayTabs support for API-related issues
- **Application Issues**: Check application logs and error messages

## Next Steps

After successful integration:

1. Switch to production credentials
2. Configure webhook URL in PayTabs dashboard
3. Test complete payment flow end-to-end
4. Monitor payment transactions in PayTabs dashboard
5. Set up payment reconciliation process
