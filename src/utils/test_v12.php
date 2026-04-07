<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing OmanNetBridgeService_v12...\n";

try {
    require_once __DIR__ . '/OmanNetBridgeService_v12.php';
    echo "Required OmanNetBridgeService_v12.php successfully.\n";

    $service = new OmanNetBridgeService();
    echo "Instantiated OmanNetBridgeService successfully.\n";

    $trackId = $service->generateTrackId(MT_RAND(100, 999));
    echo "Generated Track ID: $trackId\n";

    echo "Attempting to create a mock payment request (this tests JavaBridge connection)...\n";
    $result = $service->createPaymentRequest([
        'amount' => '1.000',
        'currency' => 'OMR',
        'orderId' => $trackId,
        'returnUrl' => 'http://localhost/success',
        'errorUrl' => 'http://localhost/error'
    ]);

    echo "Result from createPaymentRequest:\n";
    print_r($result);

    if ($result['success']) {
        echo "\nSUCCESS: JavaBridge is alive and iPayPipe initialized correctly.\n";
    }
    else {
        echo "\nFAILURE: " . ($result['error'] ?? 'Unknown error') . "\n";
    }

}
catch (Exception $e) {
    echo "\nFATAL EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
