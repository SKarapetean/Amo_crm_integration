<?php

header('Content-Type: application/json');
define('HOOK_LOG_FILE', __DIR__ . '/../log/hook.txt');
define('HOOK_ERR_FILE', __DIR__ . '/../log/hook_err.txt');

require_once __DIR__ . '/../src/deal.php';
require_once __DIR__ . '/../src/contact.php';
require_once __DIR__ . '/../log/logger.php';

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        throw new Exception('.env file not found: ' . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \"'");

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function getRequestData(): array
{
    $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents("php://input");
            return json_decode($raw, true) ?? [];
        }

        if (
            str_contains($contentType, 'application/x-www-form-urlencoded') ||
            str_contains($contentType, 'multipart/form-data')
        ) {
            return $_POST;
        }
    }

    return [];
}

$envPath = __DIR__ . '/../.env';
$envSecondPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
    logToConsole('Env first path');
}

if (file_exists($envSecondPath)) {
    loadEnv($envSecondPath);
    logToConsole('Env second path');
}
//loadEnv(__DIR__ . '/../.env');

$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $data = getRequestData();
        if (str_starts_with($requestUri, '/contact/edit')) {
            contactEdit($_POST);
        } elseif (str_starts_with($requestUri, '/contact')) {
            contactCreate($_POST);
        } elseif (str_starts_with($requestUri, '/deal/edit')) {
            dealEdit($_POST);
        } elseif (str_starts_with($requestUri, '/deal')) {
            dealCreate($_POST);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Route not found']);
        }
    } else {
        logToConsole('Request method error', [
            'uri' => $requestUri,
            'method' => $method,
        ]);
//        logMessage('Request method error', HOOK_ERR_FILE, [
//            'uri' => $requestUri,
//            'method' => $method,
//        ]);
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    logToConsole('Server error', [
        'message' => $e->getMessage(),
        'uri' => 'contact/',
        'method' => $method,
        'trace' => $e->getTraceAsString(),
    ]);
//    logMessage('Server error', HOOK_ERR_FILE, [
//        'message' => $e->getMessage(),
//        'uri' => 'contact/',
//        'method' => $method,
//        'trace' => $e->getTraceAsString(),
//    ]);
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
}