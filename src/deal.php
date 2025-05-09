<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../log/logger.php';

function dealCreate(array $requestData = []): void
{
    logMessage('Deal create request:', HOOK_LOG_FILE, $requestData);

    $data = [];
    if (isset($requestData['deal']['add'])) {
        $requestData = $requestData['deal']['add'];
        if ($requestData['type'] === 'deal') {
            $data['id'] = $requestData['id'];
            $textData = 'Name: ' . $requestData['name']
                . ' Responsible user id: ' . $requestData['responsible_user_id']
                . ' Created At: ' . date("Y-m-d H:i:s", $requestData['created_at']);

            $data['custom_data']['text'] = $textData;
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Wrong data']);
        exit();
    }

    $token = getToken();

    if (time() >= $token['expires']) {
        refreshToken();
        $token = getToken();
    }

    $out = sendDealEditRequest($data, $token['access_token'], $data['id']);

    logMessage('Deal create success:', HOOK_LOG_FILE, $out);
}

function dealEdit(array $requestData = []): void
{
    logMessage('Deal edit request:', HOOK_LOG_FILE, $requestData);

    $data = [];
    if (isset($requestData['deal']['edit'])) {
        $requestData = $requestData['deal']['edit'];
        if ($requestData['type'] === 'deal') {
            $data['id'] = $requestData['id'];
            $textData = 'Name: ' . $requestData['name']
                . ' Responsible user id: ' . $requestData['responsible_user_id']
                . ' Created At: ' . date("Y-m-d H:i:s", $requestData['created_at']);

            $data['custom_data']['text'] = $textData;
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Wrong data']);
        exit();
    }

    $token = getToken();

    if (time() >= $token['expires']) {
        refreshToken();
        $token = getToken();
    }

    $out = sendDealEditRequest($data, $token['access_token'], $data['id']);

    logMessage('Deal edit success:', HOOK_LOG_FILE, $out);
}

function sendDealEditRequest(array $data, string $accessToken, ?int $dealId = null)
{
    $uri = 'api/v4/leads/';
    if ($dealId) {
        $uri .= '/' . $dealId;
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
    curl_setopt($curl, CURLOPT_URL, getenv('ACCOUNT_DOMAIN') . $uri);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type:application/json',
        'Authorization: Bearer ' . $accessToken,
    ]);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    $out = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $code = (int)$code;
    $errors = [
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal server error',
        502 => 'Bad gateway',
        503 => 'Service unavailable',
    ];

    try {
        if ($code < 200 || $code > 204) {
            throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
        }

        return json_decode($out);
    } catch (\Exception $e) {
        logMessage('DEAL REQUEST ERR:', HOOK_LOG_FILE, [
            'message' => $e->getMessage(),
            'code' => $code,
            'response' => json_encode($out),
        ]);
        die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
    }
}