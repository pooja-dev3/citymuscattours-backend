<?php

require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../utils/MPGSService.php';

require_once __DIR__ . '/../utils/OmanNetServiceV26.php';
require_once __DIR__ . '/../utils/ApiError.php';
require_once __DIR__ . '/../config/env.php';

/**
 * Initiate MPGS Hosted Checkout payment (Public endpoint for guest bookings)
 * POST /api/payments/mpgs/initiate-public
 */
function initiateMPGSPaymentPublic($req, $res)
{
    $data = json_decode($req['body'], true);

    $bookingId = $data['bookingId'] ?? null;
    if (!$bookingId) {
        throw new ApiError(400, 'Booking ID is required');
    }

    // Get booking details
    $bookingModel = new Booking();
    $booking = $bookingModel->findWithPackage($bookingId);

    if (!$booking) {
        throw new ApiError(404, 'Booking not found');
    }

    // Check if booking is already paid
    if ($booking['payment_status'] === 'paid') {
        throw new ApiError(400, 'Booking is already paid');
    }

    try {
        $mpgsService = new MPGSService();
        $env = Env::get();

        // Prepare customer details
        $customerEmail = $data['customerEmail'] ?? $booking['contact_email'] ?? '';
        $customerPhone = $data['customerPhone'] ?? $booking['contact_phone'] ?? '';
        $customerName = $data['customerName'] ?? 'Customer';

        if (empty($customerEmail)) {
            throw new ApiError(400, 'Customer email is required');
        }

        // Prepare callback and return URLs
        $baseUrl = rtrim($env['clientUrl'] ?? 'http://localhost:3000', '/');

        // Construct backend URL from frontend URL
        $backendUrl = $baseUrl;
        if (strpos($baseUrl, '/frontend/php-frontend') !== false) {
            $backendUrl = str_replace('/frontend/php-frontend', '/php-backend', $baseUrl);
        }
        elseif (strpos($baseUrl, '/frontend') !== false) {
            $backendUrl = str_replace('/frontend', '/php-backend', $baseUrl);
        }
        else {
            $backendUrl = rtrim($baseUrl, '/') . '/php-backend';
        }

        $returnUrl = $baseUrl . '/booking-confirmation?bookingId=' . $bookingId;

        // Clean and format phone number
        $customerPhone = preg_replace('/[^\d+]/', '', $customerPhone);

        // Create unique order ID
        $orderId = 'BK' . str_pad($bookingId, 8, '0', STR_PAD_LEFT);

        // Create payment session with minimal payload
        $sessionData = [
            'bookingId' => $bookingId,
            'amount' => number_format($booking['total_amount'], 3, '.', ''),
            'currency' => $booking['currency'] ?? 'OMR',
            'description' => 'City Muscat Tours - ' . ($booking['package_name'] ?? 'Package Booking') . ' - Booking #' . str_pad($bookingId, 4, '0', STR_PAD_LEFT)
        ];
        $sessionResponse = $mpgsService->createHostedCheckoutSession($sessionData);

        // Create payment record in database
        $paymentModel = new Payment();
        $paymentRecord = [
            'booking_id' => $bookingId,
            'provider' => 'mpgs',
            'amount' => $booking['total_amount'],
            'currency' => $booking['currency'] ?? 'OMR',
            'status' => 'created',
            'provider_reference' => $sessionResponse['sessionId'],
            'metadata' => json_encode([
                'session_id' => $sessionResponse['sessionId'],
                'session_version' => $sessionResponse['sessionVersion'],
                'checkout_url' => $sessionResponse['checkoutUrl'],
                'merchant_id' => $sessionResponse['merchantId'],
                'order_id' => $orderId
            ]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $paymentId = $paymentModel->createPayment($paymentRecord);

        // Update booking with session reference
        $bookingModel->updateBooking($bookingId, [
            'transaction_id' => $sessionResponse['sessionId'],
            'payment_intent_id' => $orderId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'payment_id' => $paymentId,
                'session_id' => $sessionResponse['sessionId'],
                'checkout_url' => $sessionResponse['checkoutUrl'],
                'order_id' => $orderId,
                'booking_id' => $bookingId,
            ],
        ]);
    }
    catch (Exception $e) {
        throw new ApiError(500, 'Failed to initiate payment: ' . $e->getMessage());
    }
}

/**
 * Verify MPGS payment
 * POST /api/payments/mpgs/verify
 */
function verifyMPGSPayment($req, $res)
{
    $data = json_decode($req['body'], true);
    $userId = $req['user']['sub'] ?? null;
    $isAuthenticated = !empty($userId);

    // Log incoming request for debugging
    error_log('MPGS Verification Request: ' . json_encode([
        'data' => $data,
        'userId' => $userId,
        'isAuthenticated' => $isAuthenticated
    ]));

    $resultIndicator = $data['sessionId'] ?? null; // This is actually resultIndicator from Ahli Bank
    $bookingId = $data['bookingId'] ?? null;

    if (!$bookingId) {
        throw new ApiError(400, 'Booking ID is required');
    }

    // For Ahli Bank MPGS, if we have resultIndicator, consider payment successful
    // The resultIndicator is only returned when payment is successful
    $isPaymentSuccessful = !empty($resultIndicator);

    error_log('Payment verification status: ' . json_encode([
        'resultIndicator' => $resultIndicator,
        'bookingId' => $bookingId,
        'isPaymentSuccessful' => $isPaymentSuccessful
    ]));

    try {
        // Update booking status if payment was successful
        if ($isPaymentSuccessful) {
            try {
                $bookingModel = new Booking();
                $booking = $bookingModel->findById($bookingId);

                if ($booking) {
                    // Update booking status regardless of authentication
                    // resultIndicator is proof of successful payment
                    try {
                        $updateResult = $bookingModel->updateBooking($bookingId, [
                            'status' => 'confirmed', // Update booking status
                            'payment_status' => 'paid',
                            'transaction_id' => $resultIndicator,
                        ]);
                        error_log('Booking update successful for ID: ' . $bookingId);
                    }
                    catch (Exception $updateError) {
                        error_log('Booking update failed: ' . $updateError->getMessage());
                    // Don't throw error, continue with payment record update
                    }

                    // Update payment record
                    try {
                        $paymentModel = new Payment();
                        $paymentRecord = $paymentModel->findByBooking($bookingId);
                        if ($paymentRecord) {
                            $paymentModel->updatePayment($paymentRecord['id'], [
                                'status' => 'completed',
                                'transaction_reference' => $resultIndicator,
                                'completed_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                    catch (Exception $paymentError) {
                        error_log('Payment record update failed: ' . $paymentError->getMessage());
                    // Continue even if payment record update fails
                    }

                    // Send confirmation email after successful payment
                    try {
                        sendPaymentConfirmationEmail($bookingId, $resultIndicator);
                    }
                    catch (Exception $emailError) {
                        error_log('Failed to send payment confirmation email: ' . $emailError->getMessage());
                    // Continue even if email fails
                    }
                }
                else {
                    error_log('Booking not found for ID: ' . $bookingId);
                }
            }
            catch (Exception $bookingError) {
                error_log('Booking update failed: ' . $bookingError->getMessage());
                throw new Exception('Failed to update booking: ' . $bookingError->getMessage());
            }
        }

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'verified' => $isPaymentSuccessful,
                'bookingId' => $bookingId,
                'transactionReference' => $resultIndicator,
                'message' => $isPaymentSuccessful ? 'Payment verified successfully' : 'Payment verification pending'
            ],
        ]);
    }
    catch (ApiError $e) {
        throw $e;
    }
    catch (Exception $e) {
        throw new ApiError(500, 'Payment verification failed: ' . $e->getMessage());
    }
}

function sendPaymentConfirmationEmail($bookingId, $transactionReference)
{
    require_once __DIR__ . '/../config/env.php';
    require_once __DIR__ . '/../models/Booking.php';

    // Load Composer autoloader
    $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        throw new Exception('PHPMailer autoloader not found. Please run: composer require phpmailer/phpmailer');
    }
    require_once $autoloadPath;

    // Get booking details
    $bookingModel = new Booking();
    $booking = $bookingModel->findWithPackage($bookingId);

    if (!$booking) {
        throw new Exception('Booking not found for email sending');
    }

    $recipientEmail = $booking['contact_email'];
    $recipientName = $booking['traveler_name'] ?? 'Customer';

    // Validate email
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address: ' . $recipientEmail);
    }

    // Get email configuration from env
    $emailConfig = Env::get('email');

    // Check if email is configured
    if (empty($emailConfig['host']) || empty($emailConfig['user']) || empty($emailConfig['pass'])) {
        throw new Exception('Email service is not configured. Please configure email settings in .env file');
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = $emailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['user'];
        $mail->Password = $emailConfig['pass'];
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $emailConfig['port'] ?? 587;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $fromAddress = $emailConfig['from'] ?? 'Travelalshaheed2016@gmail.com';
        $fromName = 'Travel Al Shaheed';
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($recipientEmail, $recipientName);

        // Send copy to company email (BCC)
        $companyEmail = $emailConfig['from'] ?? 'Travelalshaheed2016@gmail.com';
        $mail->addBCC($companyEmail, 'Travel Al Shaheed');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Confirmation - Booking #' . $bookingId;

        // Generate email body
        $emailBody = generatePaymentConfirmationEmail($booking, $transactionReference);
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags($emailBody);

        $mail->send();

        error_log("Payment confirmation email sent successfully to customer {$recipientEmail} and company {$companyEmail} for booking {$bookingId}");

    }
    catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        throw new Exception('Failed to send email: ' . $mail->ErrorInfo);
    }
}

function generatePaymentConfirmationEmail($booking, $transactionReference)
{
    $packageName = $booking['package_name'] ?? 'Package';
    $date = $booking['date'] ?? 'TBD';
    $formattedDate = $date !== 'TBD' ? date('F j, Y', strtotime($date)) : 'TBD';
    $adults = $booking['adults'] ?? 0;
    $children = $booking['children'] ?? 0;
    $totalAmount = $booking['total_amount'] ?? 0;
    $currency = $booking['currency'] ?? 'OMR';

    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4b76f6; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .booking-details { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            .success { color: #28a745; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Payment Confirmation</h1>
                <p>Travel Al Shaheed</p>
            </div>
            <div class='content'>
                <h2>Thank you for your payment!</h2>
                <p class='success'>Your payment has been successfully processed.</p>
                
                <div class='booking-details'>
                    <h3>Booking Details</h3>
                    <p><strong>Booking ID:</strong> #{$booking['id']}</p>
                    <p><strong>Package:</strong> {$packageName}</p>
                    <p><strong>Date:</strong> {$formattedDate}</p>
                    <p><strong>Travelers:</strong> {$adults} adults" . ($children > 0 ? ", {$children} children" : "") . "</p>
                    <p><strong>Total Amount:</strong> {$currency} {$totalAmount}</p>
                    <p><strong>Transaction ID:</strong> {$transactionReference}</p>
                    <p><strong>Payment Method:</strong> Mastercard MPGS</p>
                </div>
                
                <p>Your booking is now confirmed and payment has been received. Please keep this email for your records.</p>
                
                <p>We look forward to serving you. If you have any questions, please don't hesitate to contact us.</p>
                
                <p>Best regards,<br>Travel Al Shaheed Team</p>
            </div>
            <div class='footer'>
                <p>This is an automated confirmation email. Please do not reply to this email.</p>
                <p>Contact: +968 9949 8697 | Travelalshaheed2016@gmail.com</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * MPGS notification/webhook handler
 * POST /api/payments/mpgs/notification
 */
function mpgsNotification($req, $res)
{
    // Get raw POST data
    $input = file_get_contents('php://input');
    $notificationData = json_decode($input, true);

    if (empty($notificationData)) {
        throw new ApiError(400, 'No notification data received');
    }

    try {
        $sessionId = $notificationData['sessionId'] ?? null;

        if (!$sessionId) {
            throw new ApiError(400, 'Session ID missing in notification');
        }

        // Retrieve payment details using session ID
        $mpgsService = new MPGSService();
        $paymentDetails = $mpgsService->retrievePayment($sessionId);

        if (!$paymentDetails['success']) {
            error_log('MPGS payment retrieval failed: ' . json_encode($paymentDetails));
            http_response_code(200); // Return 200 to MPGS even if retrieval fails
            echo json_encode(['status' => 'retrieval_failed']);
            return;
        }

        // Extract booking ID from order ID (format: BK00000001)
        $bookingId = null;
        if (!empty($paymentDetails['orderId'])) {
            $orderId = $paymentDetails['orderId'];
            if (preg_match('/BK(\d+)/', $orderId, $matches)) {
                $bookingId = (int)$matches[1];
            }
        }

        if ($bookingId) {
            $bookingModel = new Booking();
            $booking = $bookingModel->findWithPackage($bookingId);

            if ($booking) {
                // Determine payment status
                $paymentStatus = 'paid';
                $bookingStatus = 'Confirmed';
                $dbPaymentStatus = 'captured';

                // Check payment result
                if ($paymentDetails['status'] !== 'SUCCESS' && $paymentDetails['status'] !== 'APPROVED') {
                    $paymentStatus = 'failed';
                    $bookingStatus = 'Pending';
                    $dbPaymentStatus = 'failed';
                }

                // Update payment record
                $paymentModel = new Payment();
                $payment = $paymentModel->findByBooking($bookingId);

                if ($payment && $payment['provider'] === 'mpgs') {
                    $paymentModel->updatePayment($payment['id'], [
                        'status' => $dbPaymentStatus,
                        'provider_reference' => $paymentDetails['transactionId'],
                        'metadata' => json_encode($paymentDetails),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                else {
                    // Create payment record if it doesn't exist
                    $paymentModel->createPayment([
                        'booking_id' => $bookingId,
                        'provider' => 'mpgs',
                        'amount' => $booking['total_amount'],
                        'currency' => $booking['currency'] ?? 'OMR',
                        'status' => $dbPaymentStatus,
                        'provider_reference' => $paymentDetails['transactionId'],
                        'metadata' => json_encode($paymentDetails),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                // Update booking
                $bookingModel->updateBooking($bookingId, [
                    'payment_status' => $paymentStatus,
                    'status' => $bookingStatus,
                    'transaction_id' => $paymentDetails['transactionId'],
                    'payment_intent_id' => $paymentDetails['orderId'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                error_log("MPGS notification processed successfully for booking {$bookingId}, transaction {$paymentDetails['transactionId']}");
            }
        }

        // Always return 200 to MPGS
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
    }
    catch (Exception $e) {
        error_log('MPGS notification error: ' . $e->getMessage());
        // Still return 200 to MPGS
        http_response_code(200);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

/**
 * Get MPGS configuration for frontend
 * GET /api/payments/mpgs/config
 */
function getMPGSConfig($req, $res)
{
    try {
        $mpgsService = new MPGSService();

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'merchantId' => $mpgsService->getMerchantId(),
                'apiBaseUrl' => $mpgsService->getApiBaseUrl(),
                'testMode' => $mpgsService->isTestMode(),
            ],
        ]);
    }
    catch (Exception $e) {
        throw new ApiError(500, 'Failed to get MPGS configuration: ' . $e->getMessage());
    }
}

/**
 * Initiate OmanNet payment
 * POST /api/payments/omanNet/initiate
 */
function initiateOmanNetPayment($req, $res)
{
    $data = json_decode($req['body'], true);

    $bookingId = $data['bookingId'] ?? null;
    if (!$bookingId) {
        throw new ApiError(400, 'Booking ID is required');
    }

    // Get booking details
    $bookingModel = new Booking();
    $booking = $bookingModel->findWithPackage($bookingId);

    if (!$booking) {
        throw new ApiError(404, 'Booking not found');
    }

    // Use amount and currency from request if provided, otherwise from booking
    $amount = $data['amount'] ?? $booking['total_amount'];
    $currency = $data['currency'] ?? $booking['currency'] ?? 'OMR';

    // Skip user permission check since no authentication system
    // Allow anyone with booking ID to initiate payment

    // Check if booking is already paid
    if ($booking['payment_status'] === 'paid') {
        throw new ApiError(400, 'Booking is already paid');
    }

    try {
        $omanNetService = new OmanNetServiceV26();
        $env = Env::get();

        // Base URL for callback
        $clientUrl = rtrim($env['clientUrl'] ?? 'http://localhost/frontend/php-frontend/', '/');
        
        // This is where OmanNet will redirect the user after payment (POST request)
        // Ensure this URL is accessible and registered with OmanNet if required
        $returnUrl = $clientUrl . '/php-backend/api/payments/omanNet/response';

        // Initiate payment
        $paymentRequest = $omanNetService->initiatePayment($booking, $returnUrl);

        if (!$paymentRequest['success']) {
            throw new Exception($paymentRequest['error']);
        }

        $acsPayload = $paymentRequest['acsPayload'];
        $merchantReference = $paymentRequest['merchantReference'];

        // Create payment record in database
        $paymentModel = new Payment();
        $paymentRecord = [
            'booking_id' => $bookingId,
            'provider' => 'omanNet',
            'amount' => $booking['total_amount'],
            'currency' => $booking['currency'] ?? 'OMR',
            'status' => 'created',
            'provider_reference' => $merchantReference,
            'metadata' => json_encode([
                'merchantReference' => $merchantReference,
                'acsPayload' => $acsPayload,
                'version' => '2.6'
            ]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $paymentId = $paymentModel->createPayment($paymentRecord);

        // Update booking with track ID reference
        $bookingModel->updateBooking($bookingId, [
            'transaction_id' => $merchantReference,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        error_log('OmanNet PI V2.6 initiated – BookingID: ' . $bookingId . ', Ref: ' . $merchantReference);

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'payment_id' => $paymentId,
                'acsPayload' => $acsPayload, // Frontend will render this HTML
                'merchantReference' => $merchantReference,
                'booking_id' => $bookingId,
                'amount' => $amount,
                'currency' => $currency,
            ],
        ]);
    }
    catch (Exception $e) {
        error_log('OmanNet initiate (V2.6) failed: ' . $e->getMessage());
        throw new ApiError(500, 'Failed to initiate OmanNet payment: ' . $e->getMessage());
    }
}

/**
 * Verify OmanNet payment
 * POST /api/payments/omanNet/verify
 */
function verifyOmanNetPayment($req, $res)
{
    $data = json_decode($req['body'], true);

    // Log incoming request for debugging
    error_log('OmanNet Verification Request: ' . json_encode([
        'data' => $data
    ]));

    $encryptedResponse = $data['encryptedResponse'] ?? null;
    $bookingId = $data['bookingId'] ?? null;

    if (!$bookingId) {
        throw new ApiError(400, 'Booking ID is required');
    }

    if (!$encryptedResponse) {
        throw new ApiError(400, 'Encrypted response is required');
    }

    try {
        $omanNetService = new OmanNetServiceV26();
        
        // Find the merchant reference stored for this booking
        $paymentModel = new Payment();
        $payment = $paymentModel->findOne(['booking_id' => $bookingId, 'provider' => 'omanNet']);
        $merchantRef = $payment['provider_reference'] ?? null;
        
        if (!$merchantRef) {
            throw new Exception("Merchant reference not found for booking ID: $bookingId");
        }

        // Perform real-time status inquiry using REST API using the merchant reference
        $response = $omanNetService->inquirePaymentStatus($merchantRef);

        error_log('OmanNet PI V2.6 verification result for ID: ' . $bookingId . '. Status: ' . ($response['success'] ? 'SUCCESS' : 'FAILED'));

        // Update booking and payment status based on response
        if ($response['success']) {
            try {
                $bookingModel = new Booking();
                $booking = $bookingModel->findById($bookingId);

                if ($booking) {
                    // Update booking status
                    try {
                        $updateResult = $bookingModel->updateBooking($bookingId, [
                            'status' => 'confirmed',
                            'payment_status' => 'paid',
                            'transaction_id' => $response['tranid'] ?? $response['trackid'],
                        ]);
                        error_log('Booking update successful for ID: ' . $bookingId);
                    }
                    catch (Exception $updateError) {
                        error_log('Booking update failed: ' . $updateError->getMessage());
                    // Don't throw error, continue with payment record update
                    }

                    // Update payment record
                    try {
                        $paymentModel = new Payment();
                        $paymentRecord = $paymentModel->findByBooking($bookingId);
                        if ($paymentRecord) {
                            $paymentModel->updatePayment($paymentRecord['id'], [
                                'status' => 'completed',
                                'transaction_reference' => $response['tranid'] ?? $response['trackid'],
                                'completed_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                                'metadata' => json_encode($response['raw_response'])
                            ]);
                        }
                    }
                    catch (Exception $paymentError) {
                        error_log('Payment record update failed: ' . $paymentError->getMessage());
                    // Continue even if payment record update fails
                    }

                    // Send confirmation email after successful payment
                    try {
                        sendOmanNetPaymentConfirmationEmail($bookingId, $response['tranid'] ?? $response['trackid']);
                    }
                    catch (Exception $emailError) {
                        error_log('Failed to send OmanNet payment confirmation email: ' . $emailError->getMessage());
                    // Continue even if email fails
                    }
                }
                else {
                    error_log('Booking not found for ID: ' . $bookingId);
                }
            }
            catch (Exception $bookingError) {
                error_log('Booking update failed: ' . $bookingError->getMessage());
                throw new Exception('Failed to update booking: ' . $bookingError->getMessage());
            }
        }
        else {
            // Handle failed payment
            try {
                $bookingModel = new Booking();
                $paymentModel = new Payment();

                // Update booking status to failed
                $bookingModel->updateBooking($bookingId, [
                    'payment_status' => 'failed',
                    'status' => 'pending',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                // Update payment record
                $paymentRecord = $paymentModel->findByBooking($bookingId);
                if ($paymentRecord) {
                    $paymentModel->updatePayment($paymentRecord['id'], [
                        'status' => 'failed',
                        'updated_at' => date('Y-m-d H:i:s'),
                        'metadata' => json_encode($response['raw_response'])
                    ]);
                }
            }
            catch (Exception $updateError) {
                error_log('Failed to update failed payment status: ' . $updateError->getMessage());
            }
        }

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'verified' => $response['success'],
                'bookingId' => $bookingId,
                'trackid' => $response['trackid'],
                'result' => $response['result'],
                'transactionReference' => $response['tranid'] ?? $response['trackid'],
                'message' => $response['success'] ? 'Payment verified successfully' : 'Payment failed: ' . ($response['error_text'] ?? 'Unknown error'),
                'raw_response' => $response['raw_response']
            ],
        ]);
    }
    catch (ApiError $e) {
        throw $e;
    }
    catch (Exception $e) {
        throw new ApiError(500, 'OmanNet payment verification failed: ' . $e->getMessage());
    }
}


/**
 * Get OmanNet configuration for frontend
 * GET /api/payments/omanNet/config
 */
function getOmanNetConfig($req, $res)
{
    try {
        require_once __DIR__ . '/../utils/OmanNetService_v12.php';
        $omanNetService = new OmanNetService();
        $cfg = $omanNetService->getConfig();

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $cfg,
        ]);
    }
    catch (Exception $e) {
        throw new ApiError(500, 'Failed to get OmanNet configuration: ' . $e->getMessage());
    }
}

function sendOmanNetPaymentConfirmationEmail($bookingId, $transactionReference)
{
    require_once __DIR__ . '/../config/env.php';
    require_once __DIR__ . '/../models/Booking.php';

    // Load Composer autoloader
    $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        throw new Exception('PHPMailer autoloader not found. Please run: composer require phpmailer/phpmailer');
    }
    require_once $autoloadPath;

    // Get booking details
    $bookingModel = new Booking();
    $booking = $bookingModel->findWithPackage($bookingId);

    if (!$booking) {
        throw new Exception('Booking not found for email sending');
    }

    $recipientEmail = $booking['contact_email'];
    $recipientName = $booking['traveler_name'] ?? 'Customer';

    // Validate email
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address: ' . $recipientEmail);
    }

    // Get email configuration from env
    $emailConfig = Env::get('email');

    // Check if email is configured
    if (empty($emailConfig['host']) || empty($emailConfig['user']) || empty($emailConfig['pass'])) {
        throw new Exception('Email service is not configured. Please configure email settings in .env file');
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = $emailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['user'];
        $mail->Password = $emailConfig['pass'];
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $emailConfig['port'] ?? 587;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $fromAddress = $emailConfig['from'] ?? 'Travelalshaheed2016@gmail.com';
        $fromName = 'Travel Al Shaheed';
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($recipientEmail, $recipientName);

        // Send copy to company email (BCC)
        $companyEmail = $emailConfig['from'] ?? 'Travelalshaheed2016@gmail.com';
        $mail->addBCC($companyEmail, 'Travel Al Shaheed');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Confirmation - Booking #' . $bookingId;

        // Generate email body
        $emailBody = generateOmanNetPaymentConfirmationEmail($booking, $transactionReference);
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags($emailBody);

        $mail->send();

        error_log("OmanNet payment confirmation email sent successfully to customer {$recipientEmail} and company {$companyEmail} for booking {$bookingId}");

    }
    catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        throw new Exception('Failed to send email: ' . $mail->ErrorInfo);
    }
}

function generateOmanNetPaymentConfirmationEmail($booking, $transactionReference)
{
    $packageName = $booking['package_name'] ?? 'Package';
    $date = $booking['date'] ?? 'TBD';
    $formattedDate = $date !== 'TBD' ? date('F j, Y', strtotime($date)) : 'TBD';
    $adults = $booking['adults'] ?? 0;
    $children = $booking['children'] ?? 0;
    $totalAmount = $booking['total_amount'] ?? 0;
    $currency = $booking['currency'] ?? 'OMR';

    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4b76f6; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .booking-details { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            .success { color: #28a745; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Payment Confirmation</h1>
                <p>Travel Al Shaheed</p>
            </div>
            <div class='content'>
                <h2>Thank you for your payment!</h2>
                <p class='success'>Your payment has been successfully processed.</p>
                
                <div class='booking-details'>
                    <h3>Booking Details</h3>
                    <p><strong>Booking ID:</strong> #{$booking['id']}</p>
                    <p><strong>Package:</strong> {$packageName}</p>
                    <p><strong>Date:</strong> {$formattedDate}</p>
                    <p><strong>Travelers:</strong> {$adults} adults" . ($children > 0 ? ", {$children} children" : "") . "</p>
                    <p><strong>Total Amount:</strong> {$currency} {$totalAmount}</p>
                    <p><strong>Transaction ID:</strong> {$transactionReference}</p>
                    <p><strong>Payment Method:</strong> OmanNet (Local Oman Debit Cards)</p>
                </div>
                
                <p>Your booking is now confirmed and payment has been received. Please keep this email for your records.</p>
                
                <p>We look forward to serving you. If you have any questions, please don't hesitate to contact us.</p>
                
                <p>Best regards,<br>Travel Al Shaheed Team</p>
            </div>
            <div class='footer'>
                <p>This is an automated confirmation email. Please do not reply to this email.</p>
                <p>Contact: +968 9949 8697 | Travelalshaheed2016@gmail.com</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Handle the callback (Response) from OmanNet Payment Gateway PI V2.6
 * POST /api/payments/omanNet/response
 */
function omanNetResponse($req, $res)
{
    // OmanNet PI V2.6 sends back 'trandata' or 'merchantReference'
    $data = $req['bodyData'] ?? $req['query'] ?? [];
    $merchantReference = $data['trackid'] ?? $data['merchantReference'] ?? null;
    
    // In many cases we just need the merchantReference to perform an inquiry
    error_log('OmanNet PI V2.6 Callback received. Reference: ' . ($merchantReference ?? 'MISSING'));
    
    try {
        $paymentModel = new Payment();
        $bookingModel = new Booking();
        $omanNetService = new OmanNetServiceV26();
        
        // Find payment by provider reference if merchantReference is provided
        $payment = null;
        if ($merchantReference) {
            $payment = $paymentModel->findOne(['provider_reference' => $merchantReference]);
        }
        
        if (!$payment) {
            // Check if it's sent as a URL parameter maybe?
            error_log('OmanNet PI V2.6 Callback: Payment record not found for reference. Attempting default lookup.');
        }

        $bookingId = $payment['booking_id'] ?? null;
        
        // If we still don't have a booking ID, we can't update anything
        if (!$bookingId) {
            error_log('OmanNet PI V2.6 Callback: No booking context found.');
            $env = Env::get();
            header('Location: ' . rtrim($env['clientUrl'], '/') . '/booking-confirmation?status=error&error=session_lost');
            exit;
        }

        // Always perform a secure server-to-server inquiry to verify status
        // Use the merchant reference we found (stored in payment record)
        $inquiryResult = $omanNetService->inquirePaymentStatus($payment['provider_reference']);
        
        $env = Env::get();
        $redirectBase = rtrim($env['clientUrl'], '/');

        if ($inquiryResult['success']) {
            error_log("OmanNet PI V2.6 SUCCESS inquiry for Booking $bookingId");

            // Update booking status
            $bookingModel->updateBooking($bookingId, [
                'payment_status' => 'paid',
                'transaction_id' => $inquiryResult['tranid'] ?? $merchantReference,
                'status' => 'Confirmed'
            ]);

            // Update payment records
            $paymentModel->updatePayment($payment['id'], [
                'status' => 'captured',
                'transaction_id' => $inquiryResult['tranid'] ?? $merchantReference,
                'updated_at' => date('Y-m-d H:i:s'),
                'metadata' => json_encode(array_merge(json_decode($payment['metadata'] ?? '{}', true), $inquiryResult))
            ]);

            // Send confirmation email
            try {
                sendOmanNetPaymentConfirmationEmail($bookingId, $inquiryResult['tranid'] ?? $merchantReference);
            } catch (Exception $e) {
                error_log('OmanNet PI V2.6 Email Error: ' . $e->getMessage());
            }

            header("Location: $redirectBase/booking-confirmation?bookingId=$bookingId&gateway=omanNet&status=success");
        } else {
            error_log("OmanNet PI V2.6 FAILED inquiry for Booking $bookingId. Reason: " . ($inquiryResult['error'] ?? 'Inquiry failed'));

            // Update payment status as failed
            $paymentModel->updatePayment($payment['id'], [
                'status' => 'failed',
                'updated_at' => date('Y-m-d H:i:s'),
                'metadata' => json_encode(array_merge(json_decode($payment['metadata'] ?? '{}', true), $inquiryResult))
            ]);

            $errorMsg = urlencode($inquiryResult['error'] ?? 'payment_failed');
            header("Location: $redirectBase/booking-confirmation?bookingId=$bookingId&gateway=omanNet&status=failed&error=$errorMsg");
        }
    } catch (Exception $e) {
        error_log('OmanNet PI V2.6 Callback Processing Error: ' . $e->getMessage());
        $env = Env::get();
        $redirectBase = rtrim($env['clientUrl'], '/');
        header("Location: $redirectBase/booking-confirmation?status=failed&error=processing_error");
    }
    exit;
}
