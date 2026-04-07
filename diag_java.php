<?php
header('Content-Type: text/plain');
define("JAVA_HOSTS", "127.0.0.1:8085");
define("JAVA_SERVLET", false);

require_once __DIR__ . "/PhpPlugin/JavaBridge/java/Java.inc";

echo "Extension 'java' loaded: " . (extension_loaded('java') ? 'YES' : 'NO') . "\n";
echo "Function 'java_create_client' exists: " . (function_exists('java_create_client') ? 'YES' : 'NO') . "\n";
echo "JAVA_SERVLET: " . (defined('JAVA_SERVLET') ? (JAVA_SERVLET === false ? 'false' : JAVA_SERVLET) : 'not defined') . "\n";
echo "JAVA_HOSTS: " . (defined('JAVA_HOSTS') ? JAVA_HOSTS : 'not defined') . "\n";

$client = __javaproxy_Client_getClient();
echo "Client type: " . gettype($client) . "\n";
if (is_object($client)) {
    echo "Client class: " . get_class($client) . "\n";
    echo "methodCache is array: " . (is_array($client->methodCache) ? 'YES' : 'NO') . "\n";
    echo "methodCache type: " . gettype($client->methodCache) . "\n";
}

try {
    echo "Attempting to instantiate Java object...\n";
    $test = new Java("java.lang.String", "test");
    echo "Success: " . $test . "\n";
}
catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
