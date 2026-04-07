<?php

class Env {
    private static $config = null;

    public static function load() {
        if (self::$config !== null) {
            return self::$config;
        }

        $envFile = __DIR__ . '/../../.env';
        
        if (file_exists($envFile)) {
            // Check if Dotenv class is available (Composer autoloader must be loaded first)
            if (class_exists('Dotenv\Dotenv')) {
                $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
                $dotenv->load();
            } else {
                // Fallback: manually parse .env file if Dotenv is not available
                self::parseEnvFile($envFile);
            }
        }

        $requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'JWT_SECRET', 'CLIENT_URL'];
        foreach ($requiredVars as $key) {
            if (!isset($_ENV[$key]) && !getenv($key)) {
                throw new Exception("Missing required environment variable: {$key}");
            }
        }

        self::$config = [
            'nodeEnv' => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'development',
            'port' => (int)($_ENV['PORT'] ?? getenv('PORT') ?? 5000),
            'db' => [
                'host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST'),
                'port' => (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 3306),
                'name' => $_ENV['DB_NAME'] ?? getenv('DB_NAME'),
                'user' => $_ENV['DB_USER'] ?? getenv('DB_USER'),
                'pass' => $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '',
            ],
            'jwt' => [
                'secret' => $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET'),
                // Use JWT_REFRESH_SECRET if set and not empty, otherwise fall back to JWT_SECRET
                'refreshSecret' => (!empty($_ENV['JWT_REFRESH_SECRET']) ? $_ENV['JWT_REFRESH_SECRET'] : null) 
                    ?? (!empty(getenv('JWT_REFRESH_SECRET')) ? getenv('JWT_REFRESH_SECRET') : null)
                    ?? ($_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET')),
                'accessExpiry' => $_ENV['JWT_EXPIRY'] ?? getenv('JWT_EXPIRY') ?? '24h',
                'refreshExpiry' => $_ENV['JWT_REFRESH_EXPIRY'] ?? getenv('JWT_REFRESH_EXPIRY') ?? '30d',
            ],
            'clientUrl' => $_ENV['CLIENT_URL'] ?? getenv('CLIENT_URL'),
            'email' => [
                'from' => $_ENV['EMAIL_FROM'] ?? getenv('EMAIL_FROM') ?? 'no-reply@travelapp.com',
                'host' => $_ENV['EMAIL_HOST'] ?? getenv('EMAIL_HOST') ?? '',
                'port' => (int)($_ENV['EMAIL_PORT'] ?? getenv('EMAIL_PORT') ?? 587),
                'user' => $_ENV['EMAIL_USER'] ?? getenv('EMAIL_USER') ?? '',
                'pass' => $_ENV['EMAIL_PASS'] ?? getenv('EMAIL_PASS') ?? '',
            ],
            'razorpay' => [
                'keyId' => $_ENV['RAZORPAY_KEY_ID'] ?? getenv('RAZORPAY_KEY_ID') ?? '',
                'keySecret' => $_ENV['RAZORPAY_KEY_SECRET'] ?? getenv('RAZORPAY_KEY_SECRET') ?? '',
            ],
            'stripe' => [
                'secretKey' => $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY') ?? '',
                'webhookSecret' => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? getenv('STRIPE_WEBHOOK_SECRET') ?? '',
            ],
            'mpgs' => [
                'merchantId' => $_ENV['MPGS_MERCHANT_ID'] ?? getenv('MPGS_MERCHANT_ID') ?? 'TEST10000000',
                'username' => $_ENV['MPGS_USERNAME'] ?? getenv('MPGS_USERNAME') ?? 'merchant.TEST10000000',
                'password' => $_ENV['MPGS_PASSWORD'] ?? getenv('MPGS_PASSWORD') ?? 'dbe7cc389792f58c7e5850ab31673abe',
                'verifySsl' => $_ENV['MPGS_VERIFY_SSL'] ?? getenv('MPGS_VERIFY_SSL') ?? true,
            ],
            'omanNet' => [
                'resourcePath' => $_ENV['OMANNET_RESOURCE_PATH'] ?? getenv('OMANNET_RESOURCE_PATH') ?? '',
                'alias' => $_ENV['OMANNET_MERCHANT_ALIAS'] ?? getenv('OMANNET_MERCHANT_ALIAS') ?? '',
                'tranportalId' => $_ENV['OMANNET_TRANPORTAL_ID'] ?? getenv('OMANNET_TRANPORTAL_ID') ?? '',
                'tranportalPassword' => $_ENV['OMANNET_TRANPORTAL_PASSWORD'] ?? getenv('OMANNET_TRANPORTAL_PASSWORD') ?? '',
                'currency' => $_ENV['OMANNET_CURRENCY'] ?? getenv('OMANNET_CURRENCY') ?? '512',
                'language' => $_ENV['OMANNET_LANGUAGE'] ?? getenv('OMANNET_LANGUAGE') ?? 'USA',
                'action' => $_ENV['OMANNET_ACTION'] ?? getenv('OMANNET_ACTION') ?? '1',
                'receiptURL' => $_ENV['OMANNET_RECEIPT_URL'] ?? getenv('OMANNET_RECEIPT_URL') ?? '',
                'errorURL' => $_ENV['OMANNET_ERROR_URL'] ?? getenv('OMANNET_ERROR_URL') ?? '',
                'phpJavaBridgeUrl' => $_ENV['OMANNET_PHP_JAVA_BRIDGE_URL'] ?? getenv('OMANNET_PHP_JAVA_BRIDGE_URL') ?? '',
            ],
        ];

        return self::$config;
    }

    public static function get($key = null) {
        $config = self::load();
        if ($key === null) {
            return $config;
        }
        return $config[$key] ?? null;
    }

    /**
     * Fallback method to parse .env file manually if Dotenv is not available
     */
    private static function parseEnvFile($filePath) {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                // Set environment variable
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

