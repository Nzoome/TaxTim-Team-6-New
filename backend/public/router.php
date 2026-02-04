<?php
// Router for PHP built-in server
// Routes requests based on URI path

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Route to appropriate handler
if ($uri === '/' || $uri === '/upload') {
    require_once __DIR__ . '/index.php';
} elseif ($uri === '/transactions' || $uri === '/api/transactions') {
    require_once __DIR__ . '/transactions.php';
} else {
    // If the request is for a real file, serve it
    if (file_exists(__DIR__ . $uri)) {
        return false;
    }
    
    // 404 for unknown routes
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not found']);
}
