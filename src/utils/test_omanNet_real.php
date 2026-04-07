<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$url = 'http://localhost/frontend/php-frontend/php-backend/api/payments/omanNet/initiate';

// We need a valid booking ID. Let's try to find one.
require_once 'c:/wamp64/www/frontend/php-frontend/php-backend/src/config/db.php';
$db = getDB();
$stmt = $db->query("SELECT id FROM bookings ORDER BY id DESC LIMIT 1");
$row = $stmt->fetch();
$bookingId = $row ? $row['id'] : 1;

echo "Simulating request for Booking ID: $bookingId\n";

$data = [
    'bookingId' => $bookingId,
    'amount' => '1.000',
    'currency' => 'OMR',
    'customerName' => 'Test User',
    'customerEmail' => 'test@example.com',
    'customerPhone' => '12345678',
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
// Skip SSL verification if needed
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response Body:\n";
echo $response . "\n";
if ($error) {
    echo "Curl Error: $error\n";
}
