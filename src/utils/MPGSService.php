<?php

require_once __DIR__ . '/../config/env.php';

/**
 * Mastercard MPGS Hosted Checkout Service
 * Handles integration with Mastercard Payment Gateway Services (MPGS)
 * Documentation: https://developer.mastercard.com/
 */
class MPGSService {
    private $merchantId;
    private $apiUsername;
    private $apiPassword;
    private $apiBaseUrl;
    private $isTestMode;

    public function __construct() {
        $config = Env::get();
        
        $this->merchantId = $config['mpgs']['merchantId'] ?? '';
        $this->apiUsername = $config['mpgs']['username'] ?? '';
        $this->apiPassword = $config['mpgs']['password'] ?? '';
        $this->isTestMode = ($config['nodeEnv'] ?? 'development') !== 'production';
        
        // Set API base URL based on environment
        $this->apiBaseUrl = $this->isTestMode 
            ? 'https://test-ahlibank.mtf.gateway.mastercard.com/api/rest' 
            : 'https://ahlibank.gateway.mastercard.com/api/rest';
        
        // SSL verification setting
        $this->verifySSL = !($config['nodeEnv'] === 'development') && 
                          !($config['mpgs']['verifySsl'] === false);
        
        // Validate configuration
        if (empty($this->merchantId)) {
            throw new Exception('MPGS configuration error: Merchant ID is missing. Please check your .env file.');
        }
        
        if (empty($this->apiUsername)) {
            throw new Exception('MPGS configuration error: API Username is missing. Please check your .env file.');
        }
        
        if (empty($this->apiPassword)) {
            throw new Exception('MPGS configuration error: API Password is missing. Please check your .env file.');
        }
        
        error_log('MPGS initialized - Merchant ID: ' . $this->merchantId . ', Test Mode: ' . ($this->isTestMode ? 'Yes' : 'No'));
    }

    /**
     * Create a hosted checkout session
     * 
     * @param array $sessionData Array containing:
     *   - amount: Payment amount (float)
     *   - currency: Currency code (e.g., 'USD', 'OMR')
     *   - orderId: Unique order identifier
     *   - description: Description of the order
     *   - customer: Array with customer information
     *   - returnUrl: Return URL after payment
     *   - notificationUrl: Webhook/notification URL
     * 
     * @return array Response with session details
     */
    public function createHostedCheckoutSession($sessionData) {
        $url = $this->apiBaseUrl . '/version/100/merchant/' . $this->merchantId . '/session';
        
        // Extract booking data
        $bookingId = $sessionData['bookingId'] ?? null;
        $amount = $sessionData['amount'] ?? '85.000';
        $currency = $sessionData['currency'] ?? 'OMR';
        
        // Create order ID
        $orderId = 'BK' . str_pad($bookingId ?? '00000000', 8, '0', STR_PAD_LEFT);
        
        // Build session payload with complete order and interaction details
        $payload = [
            'apiOperation' => 'INITIATE_CHECKOUT',
            'order' => [
                'amount' => $amount,
                'currency' => $currency,
                'id' => $orderId,
                'description' => 'City Muscat Tour Booking'
            ],
            'interaction' => [
                'operation' => 'PURCHASE',
                'merchant' => [
                    'name' => 'City Muscat Tours'
                ],
                'returnUrl' => 'http://localhost/frontend/php-frontend/booking-confirmation?bookingId=' . $bookingId
            ]
        ];

        $response = $this->makeRequest($url, $payload);
        
        if (isset($response['session'])) {
            return [
                'success' => true,
                'sessionId' => $response['session']['id'],
                'sessionVersion' => $response['session']['version'] ?? null,
                'merchantId' => $this->merchantId,
                'checkoutUrl' => $this->getCheckoutUrl($response['session']['id'])
            ];
        } else {
            $errorMessage = 'Unknown error';
            $errorExplanation = 'N/A';
            
            if (isset($response['error'])) {
                $errorMessage = $response['error']['cause'] ?? 'Unknown error';
                $errorExplanation = $response['error']['explanation'] ?? 'N/A';
            }
            
            throw new Exception('MPGS session creation failed: ' . $errorMessage . ' (Explanation: ' . $errorExplanation . ')');
        }
    }

    /**
     * Retrieve payment details after checkout
     * 
     * @param string $sessionId MPGS session ID
     * @return array Payment details
     */
    public function retrievePayment($sessionId) {
        $url = $this->apiBaseUrl . '/version/58/merchant/' . $this->merchantId . '/session/' . $sessionId;
        
        $payload = [
            'apiOperation' => 'RETRIEVE_PAYMENT'
        ];

        $response = $this->makeRequest($url, $payload);
        
        return [
            'success' => true,
            'sessionId' => $sessionId,
            'orderId' => $response['order']['id'] ?? null,
            'transactionId' => $response['transaction']['id'] ?? null,
            'amount' => $response['order']['amount'] ?? null,
            'currency' => $response['order']['currency'] ?? null,
            'status' => $response['transaction']['status'] ?? null,
            'responseCode' => $response['response']['gatewayCode'] ?? null,
            'responseMessage' => $response['response']['description'] ?? null,
            'customerEmail' => $response['customer']['email'] ?? null,
            'paymentMethod' => $response['sourceOfFunds']['type'] ?? null,
            'raw_response' => $response
        ];
    }

    /**
     * Get the hosted checkout URL for a session
     * 
     * @param string $sessionId MPGS session ID
     * @return string Hosted checkout URL
     */
    private function getCheckoutUrl($sessionId) {
        $baseUrl = $this->isTestMode 
            ? 'https://test-ahlibank.mtf.gateway.mastercard.com' 
            : 'https://ahlibank.gateway.mastercard.com';
        
        // Ahli Bank requires POST to /api/page/version/100/pay with sessionId in form
        return $baseUrl . '/api/page/version/100/pay';
    }

    /**
     * Format amount based on currency (MPGS expects decimal format)
     * 
     * @param float $amount Original amount
     * @param string $currency Currency code
     * @return float Amount formatted for MPGS (with correct decimal places)
     */
    private function formatAmount($amount, $currency) {
        // Currencies with 3 decimal places
        $threeDecimalCurrencies = ['OMR', 'BHD', 'KWD', 'IQD', 'JOD', 'LYD', 'TND'];
        
        // Format based on currency decimal places
        if (in_array(strtoupper($currency), $threeDecimalCurrencies)) {
            // 3 decimal places (e.g., OMR: 10.500)
            return round((float)$amount, 3);
        } else {
            // 2 decimal places for most currencies (e.g., USD: 10.50)
            return round((float)$amount, 2);
        }
    }

    /**
     * Make HTTP request to MPGS API
     * 
     * @param string $url API endpoint URL
     * @param array $payload Request payload
     * @param string $method HTTP method (POST, PUT)
     * @return array API response
     */
    private function makeRequest($url, $payload, $method = 'POST') {
        $ch = curl_init();
        
        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->apiUsername . ':' . $this->apiPassword),
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => $this->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->verifySSL ? 2 : 0,
        ];
        
        // Set method based on parameter
        if ($method === 'PUT') {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = 'PUT';
        } else {
            $curlOptions[CURLOPT_POST] = true;
        }
        
        curl_setopt_array($ch, $curlOptions);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Log request details for debugging
        error_log('MPGS API Request - URL: ' . $url . ', HTTP Code: ' . $httpCode);
        error_log('MPGS Request Payload: ' . json_encode($payload));
        
        if ($error) {
            throw new Exception('MPGS API request failed: ' . $error);
        }
        
        if ($response === false || $response === '') {
            throw new Exception('MPGS API returned empty response (HTTP ' . $httpCode . ')');
        }
        
        $data = json_decode($response, true);
        $jsonError = json_last_error();
        
        if ($jsonError !== JSON_ERROR_NONE) {
            $jsonErrorMsg = json_last_error_msg();
            error_log('MPGS API JSON Parse Error: ' . $jsonErrorMsg);
            error_log('MPGS API Raw Response: ' . $response);
            throw new Exception('MPGS API returned invalid JSON: ' . $jsonErrorMsg);
        }
        
        if ($httpCode !== 200 && $httpCode !== 201) {
            $errorMsg = 'MPGS API returned HTTP code: ' . $httpCode;
            if ($data && isset($data['error'])) {
                $errorMsg .= ' - ' . $data['error']['cause'] ?? 'Unknown error';
                if (isset($data['error']['explanation'])) {
                    $errorMsg .= ' (' . $data['error']['explanation'] . ')';
                }
            } elseif ($data) {
                $errorMsg .= ' - ' . json_encode($data);
            }
            error_log('MPGS API Error Details: ' . json_encode($data));
            throw new Exception($errorMsg);
        }
        
        return $data;
    }

    /**
     * Get merchant ID
     * 
     * @return string Merchant ID
     */
    public function getMerchantId() {
        return $this->merchantId;
    }

    /**
     * Get API base URL
     * 
     * @return string API base URL
     */
    public function getApiBaseUrl() {
        return $this->apiBaseUrl;
    }

    /**
     * Check if in test mode
     * 
     * @return bool True if in test mode
     */
    public function isTestMode() {
        return $this->isTestMode;
    }
}
