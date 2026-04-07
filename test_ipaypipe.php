<?php
define("JAVA_HOSTS", "127.0.0.1:8085");
define("JAVA_SERVLET", false);
require_once __DIR__ . "/PhpPlugin/JavaBridge/java/Java.inc";

header('Content-Type: text/plain');

try {
    echo "Instantiating iPayPipe...\n";
    $myObj = new Java("com.fss.plugin.iPayPipe");
    echo "Object instantiated!\n";

    $path = "c:/wamp64/www/frontend/php-frontend/php-backend/PhpPlugin/iPAYPlugin/";
    echo "Setting resource path to $path...\n";
    $myObj->setResourcePath($path);
    echo "Resource path set.\n";

    echo "Setting Keystore Path...\n";
    $myObj->setKeystorePath($path);
    echo "Keystore path set.\n";

    echo "Setting Alias...\n";
    $myObj->setAlias("HALADOC");
    echo "Alias set.\n";

    $myObj->setCurrency("512");
    $myObj->setLanguage("USA");
    $myObj->setResponseURL("http://localhost/success");
    $myObj->setErrorURL("http://localhost/error");
    $myObj->setAmt("10.000");
    $myObj->setTrackId("1234567890");
    $myObj->setAction("1");

    echo "Calling performPaymentInitializationHTTP...\n";
    $val = java_values($myObj->performPaymentInitializationHTTP());

    echo "Result code: $val\n";
    if ($val !== 0 && $val !== "0") {
        $error = "UNKNOWN";
        try {
            $error = $myObj->getError();
        }
        catch (\Throwable $e) {
            $error = "Could not get getError()";
        }
        echo "Error message: " . $error . "\n";
    }
    else {
        echo "Redirect URL: " . $myObj->getWebAddress() . "\n";
        echo "Payment ID: " . $myObj->getPaymentId() . "\n";
    }

}
catch (\Throwable $e) {
    echo "FATAL ERROR CAUGHT:\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
