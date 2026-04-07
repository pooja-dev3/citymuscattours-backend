<?php
/**
 * OmanNet Payment Integrator (PI) Version 2.6 Service
 * Reference: /payment-project/ (Verified Standard JWS Fixed)
 */

require_once __DIR__ . '/../config/env.php';

class OmanNetServiceV26
{
    private $apiKey;
    private $secretKey;
    private $username;
    private $password;
    private $terminalId;
    private $acquirerId;
    private $merchantId;
    private $kid;
    private $baseUrl;
    private $privateKeyPath;
    private $logPath;

    public function __construct()
    {
        // Load env if not already loaded
        Env::load();
        
        $this->apiKey = $_ENV['OMANNET_V26_API_KEY'] ?? '';
        $this->secretKey = $_ENV['OMANNET_V26_SECRET_KEY'] ?? '';
        $this->username = $_ENV['OMANNET_V26_USERNAME'] ?? '';
        $this->password = $_ENV['OMANNET_V26_PASSWORD'] ?? '';
        $this->terminalId = $_ENV['OMANNET_V26_TERMINAL_ID'] ?? '';
        $this->acquirerId = $_ENV['OMANNET_V26_ACQUIRER_ID'] ?? '';
        $this->merchantId = $_ENV['OMANNET_V26_MERCHANT_ID'] ?? '';
        $this->kid = $this->merchantId;
        
        $isProduction = ($_ENV['OMANNET_V26_ENV'] ?? 'sandbox') === 'production';
        $this->baseUrl = $isProduction 
            ? 'https://ecomm.omannet.om' 
            : 'https://certecomm.omannet.om';
        
        $this->privateKeyPath = __DIR__ . '/../config/keys/omanNet_private.pem';
        $this->logPath = __DIR__ . '/../../logs/omanNet_v26.log';
        
        // Ensure log directory exists
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    /**
     * Get OAuth Access Token
     */
    public function getAccessToken()
    {
        $tokenFile = __DIR__ . '/../../logs/omanNet_token.cache';
        
        // Check cache (mirror logic from auth.php)
        if (file_exists($tokenFile)) {
            $cache = @json_decode(file_get_contents($tokenFile), true);
            if ($cache && isset($cache['access_token']) && isset($cache['expires_at']) && $cache['expires_at'] > time()) {
                return $cache['access_token'];
            }
        }

        $url = $this->baseUrl . '/authorization-server/oauth/token';
        
        // 🔥 CRITICAL: Match working prototype (auth.php)
        // Uses JSON body + Basic Auth (Username:Password)
        $payload = [
            "apiKey" => $this->apiKey,
            "secretKey" => $this->secretKey,
            "username" => $this->username,
            "password" => $this->password
        ];

        $headers = [
            "Content-Type: application/json",
            "Accept: application/json"
        ];
        
        $this->log("Requesting Access Token from $url (Standard Prototype Flow)");
        $result = $this->makeRequest($url, 'POST', $headers, $payload, $this->username . ":" . $this->password);
        
        if ($result['success'] && isset($result['data']['access_token'])) {
            $tokenData = $result['data'];
            // Cache the token
            $expiresIn = $tokenData['expires_in'] ?? 3600;
            $tokenData['expires_at'] = time() + ($expiresIn - 60);
            file_put_contents($tokenFile, json_encode($tokenData));
            
            return $tokenData['access_token'];
        }
        
        $this->log("Failed to get Access Token (Fixed Flow)", $result);
        return false;
    }

    /**
     * Generate JWS Signature (v2.6 Standard)
     */
    public function generateJWS($data)
    {
        // 🔥 FIXED ORDER (Per User Requirement/payment-project Logic)
        // merchantReference + currencyCode + amount + transactionType + language + merchantId + orderNumber + merchantUrl
        $source = 
            trim($data['merchantReference']) .
            trim($data['currencyCode']) .
            trim($data['amount']) .
            trim($data['transactionType']) .
            trim($data['language']) .
            trim($data['merchantId']) .
            trim($data['orderNumber']) .
            trim($data['merchantUrl']);

        $this->log("JWS Source String Created: " . $source);

        $header = [
            "alg" => "RS256",
            "kid" => $this->kid
        ];

        $payload = [
            "hashAttributes" => $source
        ];

        // Base64URL encoding with JSON_UNESCAPED_SLASHES
        $base64Header = $this->base64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $base64Payload = $this->base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $dataToSign = $base64Header . "." . $base64Payload;

        if (!file_exists($this->privateKeyPath)) {
            $this->log("Error: Private key not found at " . $this->privateKeyPath);
            return false;
        }

        $privateKeyContent = file_get_contents($this->privateKeyPath);
        $privateKey = openssl_pkey_get_private($privateKeyContent);

        if (!$privateKey) {
            $this->log("Error: Could not read private key for signing.");
            return false;
        }

        $signature = '';
        if (!openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            $this->log("Error: OpenSSL signing failed.");
            return false;
        }

        $base64Signature = $this->base64url_encode($signature);

        return $dataToSign . "." . $base64Signature;
    }

    /**
     * Initiate Payment Request
     */
    public function initiatePayment($booking, $returnUrl)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return ['success' => false, 'error' => 'Authentication failed'];

        $bookingId = $booking['id'];
        $amount = number_format((float)$booking['total_amount'], 3, '.', '');
        
        // Min 20 characters reference
        $merchantReference = 'BK-' . $bookingId . '-' . time() . strtoupper(substr(md5(uniqid()), 0, 4));
        $orderNumber = $merchantReference;
        $currentTimestamp = date("Y-m-d H:i:s");
        $transactionDate = date("YmdHis");

        $merchantUrl = $_ENV['OMANNET_V26_MERCHANT_URL'] ?? $returnUrl;

        $dataBlock = [
            "terminalId" => $this->terminalId,
            "acquirerId" => $this->acquirerId,
            "merchantId" => $this->username, 
            "merchantReference" => $merchantReference,
            "amount" => $amount,
            "currencyCode" => "512", // OMR
            "transactionType" => "PURCHASE",
            "language" => "EN",
            "orderNumber" => $orderNumber,
            "merchantUrl" => $merchantUrl,
            "transactionDate" => $transactionDate
        ];

        // Sort keys for consistent hashing
        ksort($dataBlock);

        $jws = $this->generateJWS($dataBlock);
        if (!$jws) return ['success' => false, 'error' => 'Signing failed'];

        $url = $this->baseUrl . '/orchestrator/3ds/v/1/initiate';
        $payload = [
            "header" => [
                "version" => "2.6",
                "requestId" => $this->generateUuid(),
                "time" => $currentTimestamp
            ],
            "data" => array_merge($dataBlock, ["transactionHash" => $jws])
        ];

        $this->log("Initiating Payment for Booking: $bookingId, Ref: $merchantReference");
        
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Bearer $accessToken"
        ];
        
        $result = $this->makeRequest($url, 'POST', $headers, $payload);
        $this->log("Initiation API Response:", $result);

        if ($result['success']) {
            $apiData = $result['data'];
            $header = $apiData['header'] ?? [];
            $responseData = $apiData['data'] ?? [];
            
            if (isset($header['errorCode']) && !in_array($header['errorCode']['code'], ['0000', 'E000'])) {
                $err = $header['errorCode']['description'] ?? 'Business Error';
                $this->log("Business Error: $err (" . $header['errorCode']['code'] . ")");
                return ['success' => false, 'error' => $err . " (" . $header['errorCode']['code'] . ")"];
            }
            
            $acsPayload = $responseData['acsPayload'] ?? $responseData['acsHtmlPayload'] ?? null;
            if ($acsPayload) {
                return [
                    'success' => true,
                    'acsPayload' => $acsPayload,
                    'merchantReference' => $merchantReference,
                    'order_id' => $merchantReference
                ];
            }
            
            return ['success' => false, 'error' => 'Redirection payload missing'];
        }

        return ['success' => false, 'error' => $result['error'] ?? 'API request failed'];
    }

    /**
     * Inquire Payment Status
     */
    public function inquirePaymentStatus($transactionId)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return ['success' => false, 'error' => 'Authentication failed'];

        $url = $this->baseUrl . '/orchestrator/3ds/v/1/inquire';
        $payload = [
            "header" => [
                "version" => "2.6",
                "requestId" => $this->generateUuid(),
                "time" => date("Y-m-d H:i:s")
            ],
            "data" => [
                "id" => $transactionId,
                "merchantId" => $this->username,
                "terminalId" => $this->terminalId,
                "acquirerId" => $this->acquirerId
            ]
        ];

        $this->log("Inquiring Transaction: $transactionId");
        
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Bearer $accessToken"
        ];
        
        $result = $this->makeRequest($url, 'POST', $headers, $payload);

        if ($result['success']) {
            $apiData = $result['data'];
            $header = $apiData['header'] ?? [];
            $responseData = $apiData['data'] ?? [];
            
            if (isset($header['errorCode']) && $header['errorCode']['code'] !== '0000') {
                return [
                    'success' => false, 
                    'error' => $header['errorCode']['description'] ?? 'Inquiry failed',
                    'code' => $header['errorCode']['code']
                ];
            }
            
            return [
                'success' => true,
                'status' => $responseData['status'] ?? 'UNKNOWN',
                'tranid' => $transactionId,
                'trackid' => $responseData['merchantReference'] ?? '',
                'amt' => $responseData['amount'] ?? '',
                'result' => $responseData['status'] ?? '',
                'raw_response' => $apiData
            ];
        }

        return ['success' => false, 'error' => $result['error'] ?? 'Inquiry API failed'];
    }

    /**
     * Helper: Make API Request
     */
    private function makeRequest($url, $method, $headers = [], $payload = null, $auth = null)
    {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($auth) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth);
        }

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($payload) {
                // OmanNet requires JSON_UNESCAPED_SLASHES
                $data = is_array($payload) ? json_encode($payload, JSON_UNESCAPED_SLASHES) : $payload;
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        $decoded = json_decode($response, true);
        return [
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'data' => $decoded,
            'http_code' => $httpCode,
            'raw' => $response
        ];
    }

    private function base64url_encode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function generateUuid() 
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function log($message, $data = null)
    {
        $timestamp = date('Y-m-d H:i:s');
        $content = "[$timestamp] $message";
        if ($data) {
            $content .= "\nData: " . (is_string($data) ? $data : json_encode($data, JSON_PRETTY_PRINT));
        }
        file_put_contents($this->logPath, $content . "\n" . str_repeat('-', 40) . "\n", FILE_APPEND);
    }
}
