<?php
// Suppress HTML error output - we always return JSON
ini_set('display_errors', 0);

require_once __DIR__ . '/../utils/ApiError.php';
require_once __DIR__ . '/../utils/logger.php';
require_once __DIR__ . '/../config/env.php';

function errorHandler($err, $req, $res) {
    // Suppress any HTML error output - we always return JSON
    if (ob_get_level()) {
        ob_clean();
    }
    
    $error = $err instanceof ApiError ? $err : new ApiError(500, $err->getMessage());
    $statusCode = $error->statusCode;
    
    $response = $error->toArray();

    Logger::error('Request failed', [
        'method' => $req['method'] ?? 'UNKNOWN',
        'url' => $req['uri'] ?? 'UNKNOWN',
        'statusCode' => $statusCode,
        'message' => $error->getMessage(),
    ]);

    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

