<?php

// Router for `php -S` in CurlHttpClientTest: answers in the API's envelope with what it received.
$path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/api/v3/slow') {
    sleep(3);
}

header('Content-Type: application/json');
header('X-RateLimit-Remaining: 238');

if ($path === '/api/v3/denied') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => ['code' => '601', 'message' => 'Authentication Failed']]);

    return true;
}

$headers = [];
foreach ($_SERVER as $k => $v) {
    if (strpos($k, 'HTTP_') === 0) {
        $headers[strtolower(str_replace('_', '-', substr($k, 5)))] = $v;
    }
}
if (isset($_SERVER['CONTENT_TYPE'])) {
    $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
}

echo json_encode(['success' => true, 'data' => [
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => $path,
    'query' => $_GET,
    'headers' => $headers,
    'body' => file_get_contents('php://input'),
]]);

return true;
