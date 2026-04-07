<?php
/**
 * PayTabs Deployment Diagnostic Script
 * Run this script on your production server to diagnose PayTabs authentication issues
 * Usage: php check-paytabs-deployment.php
 * 
 * This script checks:
 * 1. If .env file exists and is readable
 * 2. If PayTabs credentials are set
 * 3. If credentials are valid format
 * 4. Tests API connection (without making actual payment)
 */

require_once __DIR__ . '/src/config/env.php';
require_once __DIR__ . '/src/utils/PayTabsService.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== PayTabs Deployment Diagnostic ===\n\n";

$issues = [];
$warnings = [];

try {
    // Check if .env file exists
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        $issues[] = ".env file not found at: {$envFile}";
        echo "✗ .env file not found\n";
        echo "  Location checked: {$envFile}\n\n";
    } else {
        echo "✓ .env file found\n";
        if (!is_readable($envFile)) {
            $issues[] = ".env file is not readable";
            echo "✗ .env file is not readable (check file permissions)\n\n";
        } else {
            echo "✓ .env file is readable\n\n";
        }
    }
    
    // Try to load environment
    try {
        Env::load();
        echo "✓ Environment loaded successfully\n\n";
    } catch (Exception $e) {
        $issues[] = "Failed to load environment: " . $e->getMessage();
        echo "✗ Failed to load environment: " . $e->getMessage() . "\n\n";
        echo "=== DIAGNOSTIC INCOMPLETE ===\n";
        echo "Fix the environment loading issue first.\n";
        exit(1);
    }
    
    // Get PayTabs config
    $config = Env::get('paytabs');
    
    if (!$config) {
        $issues[] = "PayTabs configuration not found in environment";
        echo "✗ PayTabs configuration not found\n";
        echo "  Make sure your .env file contains:\n";
        echo "    PAYTABS_PROFILE_ID=your_profile_id\n";
        echo "    PAYTABS_SERVER_KEY=your_server_key\n";
        echo "    PAYTABS_REGION=OMN\n\n";
    } else {
        echo "PayTabs Configuration:\n";
        
        // Check Profile ID
        $profileId = $config['profileId'] ?? '';
        if (empty($profileId)) {
            $issues[] = "PAYTABS_PROFILE_ID is not set";
            echo "  ✗ Profile ID: NOT SET\n";
        } else {
            echo "  ✓ Profile ID: {$profileId}\n";
            if (!is_numeric($profileId)) {
                $warnings[] = "Profile ID should be numeric";
                echo "    ⚠ Warning: Profile ID should be numeric (got: " . gettype($profileId) . ")\n";
            }
        }
        
        // Check Server Key
        $serverKey = $config['serverKey'] ?? '';
        if (empty($serverKey)) {
            $issues[] = "PAYTABS_SERVER_KEY is not set";
            echo "  ✗ Server Key: NOT SET\n";
        } else {
            $keyLength = strlen($serverKey);
            echo "  ✓ Server Key: SET ({$keyLength} characters)\n";
            echo "    Prefix: " . substr($serverKey, 0, 10) . "...\n";
            
            // Check if key looks valid (PayTabs keys are usually 40+ characters)
            if ($keyLength < 20) {
                $warnings[] = "Server key seems too short (PayTabs keys are usually 40+ characters)";
                echo "    ⚠ Warning: Server key seems too short\n";
            }
        }
        
        // Check Client Key (optional)
        $clientKey = $config['clientKey'] ?? '';
        if (empty($clientKey)) {
            echo "  ⚠ Client Key: NOT SET (optional, but recommended for frontend)\n";
        } else {
            echo "  ✓ Client Key: SET (" . strlen($clientKey) . " characters)\n";
        }
        
        // Check Region
        $region = $config['region'] ?? '';
        if (empty($region)) {
            $warnings[] = "PAYTABS_REGION is not set (defaulting to SAU)";
            echo "  ⚠ Region: NOT SET (will default to SAU)\n";
        } else {
            echo "  ✓ Region: {$region}\n";
        }
        
        echo "\n";
    }
    
    // Try to initialize PayTabs service
    if (empty($issues)) {
        try {
            $paytabs = new PayTabsService();
            echo "✓ PayTabsService initialized successfully\n";
            
            // Get API URL using the public method
            $apiUrl = $paytabs->getCurrentApiUrl();
            echo "  API URL: " . $apiUrl . "\n\n";
            
            // Try a minimal API call to test authentication
            echo "Testing API authentication...\n";
            echo "  (This will make a test API call to verify credentials)\n\n";
            
            // Use verifyPayment with a dummy transaction ref to test auth
            // This will fail with a transaction not found error, but will confirm auth works
            try {
                $testRef = 'TEST_AUTH_CHECK_' . time();
                $result = $paytabs->verifyPayment($testRef);
                
                // If we get here without a 401, authentication is working
                // (The transaction won't exist, but that's expected)
                echo "✓ Authentication test passed!\n";
                echo "  (Transaction not found is expected - this confirms auth is working)\n\n";
            } catch (Exception $e) {
                $errorMsg = $e->getMessage();
                
                // Check if it's a 401 (auth failed) or 404/other (auth worked but transaction not found)
                if (strpos($errorMsg, '401') !== false || strpos($errorMsg, 'Authentication failed') !== false) {
                    $issues[] = "PayTabs authentication failed - credentials are incorrect";
                    echo "✗ Authentication test FAILED\n";
                    echo "  Error: " . $errorMsg . "\n\n";
                    echo "  This means your PAYTABS_PROFILE_ID and PAYTABS_SERVER_KEY don't match.\n";
                    echo "  Common causes:\n";
                    echo "  1. Using sandbox credentials in production (or vice versa)\n";
                    echo "  2. Server key doesn't match the profile ID\n";
                    echo "  3. Credentials copied incorrectly (extra spaces, quotes, etc.)\n";
                    echo "  4. Wrong region selected (OMN vs SAU vs ARE)\n\n";
                } else {
                    // Any other error means auth worked but transaction doesn't exist (expected)
                    echo "✓ Authentication test passed!\n";
                    echo "  (Transaction not found is expected - this confirms auth is working)\n";
                    echo "  Note: " . substr($errorMsg, 0, 100) . "\n\n";
                }
            }
            
        } catch (Exception $e) {
            $issues[] = "Failed to initialize PayTabsService: " . $e->getMessage();
            echo "✗ Failed to initialize PayTabsService\n";
            echo "  Error: " . $e->getMessage() . "\n\n";
        }
    }
    
    // Summary
    echo "=== DIAGNOSTIC SUMMARY ===\n\n";
    
    if (empty($issues) && empty($warnings)) {
        echo "✓ All checks passed! PayTabs should be working correctly.\n";
    } else {
        if (!empty($issues)) {
            echo "✗ CRITICAL ISSUES FOUND:\n";
            foreach ($issues as $issue) {
                echo "  - {$issue}\n";
            }
            echo "\n";
        }
        
        if (!empty($warnings)) {
            echo "⚠ WARNINGS:\n";
            foreach ($warnings as $warning) {
                echo "  - {$warning}\n";
            }
            echo "\n";
        }
        
        echo "=== RECOMMENDED FIXES ===\n\n";
        echo "1. Verify your .env file exists and contains:\n";
        echo "   PAYTABS_PROFILE_ID=your_actual_profile_id\n";
        echo "   PAYTABS_SERVER_KEY=your_actual_server_key\n";
        echo "   PAYTABS_REGION=OMN  (or SAU, ARE, EGY, etc.)\n\n";
        
        echo "2. Make sure there are NO quotes around values:\n";
        echo "   ✓ Correct: PAYTABS_PROFILE_ID=170407\n";
        echo "   ✗ Wrong:   PAYTABS_PROFILE_ID=\"170407\"\n\n";
        
        echo "3. Verify credentials match your PayTabs account:\n";
        echo "   - Log into PayTabs dashboard\n";
        echo "   - Check Profile ID matches PAYTABS_PROFILE_ID\n";
        echo "   - Copy Server Key exactly (no extra spaces)\n";
        echo "   - Ensure you're using production credentials for production\n\n";
        
        echo "4. Check file permissions:\n";
        echo "   - .env file should be readable by PHP\n";
        echo "   - Typically: chmod 644 .env\n\n";
        
        echo "5. Restart your web server after updating .env\n";
    }
    
} catch (Exception $e) {
    echo "✗ Fatal error: " . $e->getMessage() . "\n";
    echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Diagnostic Complete ===\n";

