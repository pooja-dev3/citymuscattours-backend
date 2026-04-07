<?php
define("JAVA_HOSTS", "127.0.0.1:8085");
define("JAVA_SERVLET", false);
require_once "c:/wamp64/www/frontend/php-frontend/php-backend/PhpPlugin/JavaBridge/java/Java.inc";

try {
    $class = new JavaClass("java.lang.Class");
    $prClass = $class->forName("com.fss.plugin.ParseResouce");
    $method = $prClass->getDeclaredMethod("getKeystorePwd", []);
    $method->setAccessible(true);
    $pwd = $method->invoke(null, []);
    echo "KS PWD: " . (string)$pwd . "\n";

    $method2 = $prClass->getDeclaredMethod("getKeyPwd", []);
    $method2->setAccessible(true);
    $pwd2 = $method2->invoke(null, []);
    echo "KEY PWD: " . (string)$pwd2 . "\n";
}
catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
