<?php

require_once __DIR__ . '/log/logger.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$uri = $_SERVER['REQUEST_URI'] ?? '';
$headers = function_exists('getallheaders') ? getallheaders() : [];

$get = $_GET;
$post = $_POST;

$rawInput = file_get_contents('php://input');

$files = [];
foreach ($_FILES as $name => $file) {
    $files[$name] = [
        'name' => $file['name'],
        'type' => $file['type'],
        'size' => $file['size'],
        'error' => $file['error']
    ];
}

$log = [
    'time' => date('Y-m-d H:i:s'),
    'method' => $method,
    'uri' => $uri,
    'headers' => $headers,
    'get' => $get,
    'post' => $post,
    'raw_input' => $rawInput,
    'files' => $files
];

logMessage('Request main index.php', __DIR__ . '/log/request.txt', $log);

header('Content-Type: application/json');
echo json_encode(['status' => 'logged']);
