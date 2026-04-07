<?php
// Temporary IP checker - DELETE after getting the IP
header('Content-Type: text/plain');
$ip = @file_get_contents('https://api.ipify.org');
echo "Outbound IP: " . ($ip ?: 'unknown') . "\n\n";

// Quick gateway test
$body = '<id>ipay431798190144</id><password>ahlibank@2024</password><action>1</action>'
    . '<amt>1.000</amt><currencycode>512</currencycode><trackid>TEST' . time() . '</trackid>'
    . '<udf1></udf1><udf2></udf2><udf3></udf3><udf4></udf4><udf5></udf5>'
    . '<responseURL>https://test/</responseURL><errorURL>https://test/</errorURL><transid></transid>';

$ch = curl_init('https://securepgtest.fssnet.co.in/pgway/servlet/TranPortalXMLServlet');
curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => ['Content-Type: text/xml']]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Gateway HTTP: $code\n";
echo "Gateway Response: " . ($res ?: curl_error($ch)) . "\n";
