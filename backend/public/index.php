<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CryptoTax\Services\FileProcessor;
use CryptoTax\Services\Logger;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['file'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['file'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }

    // Initialize logger
    $logger = new Logger(__DIR__ . '/../logs');
    
    // Process the file
    $processor = new FileProcessor($logger);
    $result = $processor->processFile($file['tmp_name'], $file['name']);

    // Store in a temporary file for later retrieval
    $dataFile = __DIR__ . '/../logs/latest_transactions.json';
    file_put_contents($dataFile, json_encode([
        'transactions' => $result['transactions'],
        'summary' => $result['summary'],
        'red_flags' => $result['red_flags'] ?? [],
        'red_flag_summary' => $result['red_flag_summary'] ?? null,
        'has_critical_issues' => $result['has_critical_issues'] ?? false,
        'audit_risk_level' => $result['audit_risk_level'] ?? 'MINIMAL',
        'detected_format' => $result['detected_format'] ?? null,
        'timestamp' => time()
    ]));

    // Return success response with red flag data
    echo json_encode([
        'success' => true,
        'data' => [
            'transactions' => $result['transactions'],
            'summary' => $result['summary'],
            'red_flags' => $result['red_flags'] ?? [],
            'red_flag_summary' => $result['red_flag_summary'] ?? null,
            'has_critical_issues' => $result['has_critical_issues'] ?? false,
            'audit_risk_level' => $result['audit_risk_level'] ?? 'MINIMAL',
            'detected_format' => $result['detected_format'] ?? null
        ]
    ]);

} catch (Exception $e) {
    $logger->error('Processing failed: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'errors' => method_exists($e, 'getErrors') ? $e->getErrors() : []
    ]);
}
