<?php

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

$dataFile = __DIR__ . '/../logs/latest_transactions.json';

try {
    // Delete the cache file if it exists
    if (file_exists($dataFile)) {
        unlink($dataFile);
        echo json_encode([
            'success' => true,
            'message' => 'Transaction cache cleared successfully'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'No cache file to clear'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to clear cache: ' . $e->getMessage()
    ]);
}
