<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../src/hook_handler.php';
require_once __DIR__ . '/../src/logger.php';

function checkHook(): void
{
    $clientId = getenv('CLIENT_ID');
    $clientSecret = getenv('CLIENT_SECRET');

    if (empty($_GET['client_uuid']) || empty($_GET['signature']) || empty($_GET['account_id'])) {
        throw new \HttpInvalidParamException('Wrong hook format');
    }

    $hookClientId = $_GET['client_uuid'];
    $hookSignature = $_GET['signature'];
    $hookAccountId = (int)$_GET['account_id'];

    if ($clientId !== $hookClientId) {
        throw new \Exception('Invalid hook signature');
    }

    if (!hash_equals(
        hash_hmac('sha256', sprintf('%s|%s', $clientId, $hookAccountId), $clientSecret),
        $hookSignature
    )) {
        throw new \Exception('Invalid hook signature');
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

$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    checkHook();
    if ($method === 'POST') {
        $data = getRequestData();
        if (str_starts_with($requestUri, '/contact/edit')) {
            resourceEdited('contacts', $_POST);
        } elseif (str_starts_with($requestUri, '/contact')) {
            resourceCreated('contacts', $_POST);
        } elseif (str_starts_with($requestUri, '/lead/edit')) {
            resourceEdited('leads', $_POST);
        } elseif (str_starts_with($requestUri, '/lead')) {
            resourceCreated('leads', $_POST);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Route not found']);
        }
    } else {
        logToConsole('Request method error', [
            'uri' => $requestUri,
            'method' => $method,
        ]);

        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    logToConsole('Server error', [
        'message' => $e->getMessage(),
        'uri' => $requestUri,
        'method' => $method,
        'trace' => $e->getTraceAsString(),
    ]);

    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
}