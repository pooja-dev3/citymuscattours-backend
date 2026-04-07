<?php
/**
 * Download CA Certificate Bundle
 * Run this script to download the CA certificate bundle for SSL verification
 * Usage: php download-cacert.php
 */

$certUrl = 'https://curl.se/ca/cacert.pem';
$certPath = __DIR__ . '/cacert.pem';

echo "Downloading CA certificate bundle from $certUrl...\n";

// Use file_get_contents with allow_url_fopen, or curl as fallback
$certData = false;

// Try file_get_contents first (requires allow_url_fopen)
if (ini_get('allow_url_fopen')) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'PHP CA Cert Downloader'
        ]
    ]);
    $certData = @file_get_contents($certUrl, false, $context);
}

// Fallback to curl if file_get_contents fails
if (!$certData && function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $certUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // We're downloading the cert file itself
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $certData = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "Error: " . $error . "\n";
    }
}

if ($certData && strlen($certData) > 1000) {
    // Write to file
    $written = file_put_contents($certPath, $certData);
    if ($written) {
        echo "✓ Successfully downloaded CA certificate bundle to: $certPath\n";
        echo "  File size: " . number_format(strlen($certData)) . " bytes\n";
        echo "\nThe PayTabsService will now use this certificate bundle for SSL verification.\n";
        echo "You can now test PayTabs API connections.\n";
    } else {
        echo "✗ Failed to write certificate file. Check permissions.\n";
    }
} else {
    echo "✗ Failed to download certificate bundle.\n";
    echo "\nManual download instructions:\n";
    echo "1. Visit: $certUrl\n";
    echo "2. Save the file as: $certPath\n";
    echo "3. Or set PAYTABS_VERIFY_SSL=false in .env for development (not recommended for production)\n";
}

