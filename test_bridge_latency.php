<?php
error_log("LATENCY TEST: Setting up constants");
define("JAVA_HOSTS", "127.0.0.1:8085");
define("JAVA_SERVLET", false);
error_log("LATENCY TEST: Requiring Java.inc");
require_once __DIR__ . "/PhpPlugin/JavaBridge/java/Java.inc";
error_log("LATENCY TEST: Instantiating test object");
$str = new Java("java.lang.String", "JavaBridge is working perfectly in PHP 8!");
error_log("LATENCY TEST: Object instantiated, dumping string");
echo $str;
error_log("LATENCY TEST: FINISHED");
