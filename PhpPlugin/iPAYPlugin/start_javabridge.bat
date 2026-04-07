@echo off
set JAR_PATH="C:\wamp64\www\frontend\php-frontend\php-backend\PhpPlugin\iPAYPlugin\JavaBridge.jar"
set PORT=8085

echo Starting JavaBridge for OmanNet on port %PORT%...
java -cp "JavaBridge.jar;ipayPipe.jar" php.java.bridge.Standalone SERVLET_PORT:%PORT%
pause
