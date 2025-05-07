<?php
header('Content-Type: application/json');

require_once "../../src/contact.php";
require_once "../../src/logger.php";

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

    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents("php://input");
        return json_decode($raw, true) ?? [];
    }

    return $_POST;
}

loadEnv('../../.env');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = getRequestData();
    contactEdit($data);
} else {
    logMessage('Request method error', 'hook_err.log', [
        'uri' => 'contact/edit/',
        'method' => $method,
    ]);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

