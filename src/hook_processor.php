<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/resource_handler.php';

$resourceName = $argv[1] ?? '';
$requestData = json_decode($argv[2] ?? '{}', true);
$mode = $argv[3] ?? 'create';

if ($mode === 'create') {
    handleCreate($resourceName, $requestData);
} elseif ($mode === 'edit') {
    handleEdit($resourceName, $requestData);
}

function handleCreate(string $resourceName, array $requestData): void
{
    logToConsole($resourceName . ' create request:', $requestData);

    $data = [];
    $resourceType = preg_replace('/s$/', '', $resourceName);

    if (isset($requestData[$resourceName]['add'])) {
        $requestData = $requestData[$resourceName]['add'];
        if ($requestData['type'] === $resourceType) {
            saveResourceState($resourceName, $requestData['id'], $requestData);
            $data['id'] = $requestData['id'];
            $textData = 'Name: ' . $requestData['name'] . PHP_EOL
                . ' Responsible user id: ' . $requestData['responsible_user_id'] . PHP_EOL
                . ' Created At: ' . date("Y-m-d H:i:s", $requestData['created_at']);

            $data['custom_data']['text'] = $textData;
        }
    }

    $token = getToken();
    if (time() >= $token['expires']) {
        refreshToken();
        $token = getToken();
    }

    sendEditRequest($data, $token['access_token'], $resourceName, $data['id']);
    logToConsole($resourceName . ' create success.');
}

function handleEdit(string $resourceName, array $requestData): void
{
    logToConsole($resourceName . ' edit request:', $requestData);

    $data = [];
    $resourceType = preg_replace('/s$/', '', $resourceName);
    if (isset($requestData[$resourceName]['edit'])) {
        $requestData = $requestData[$resourceName]['edit'];
        if ($requestData['type'] === $resourceType) {
            $dataDiff = getResourceStateDiff($resourceName, $requestData['id'], $requestData);
            $data['id'] = $requestData['id'];
            $textData = '';
            foreach ($dataDiff as $diff) {
                foreach ($diff as $key => $value) {
                    if (is_array($value)) {
                        $textData .= $key . ': ' . json_encode($value) . PHP_EOL;
                    } else {
                        $textData .= $key . ': ' . $value . PHP_EOL;
                    }
                }
            }
            $textData .= 'Updated At: ' . date("Y-m-d H:i:s", $requestData['updated_at']);
            $data['custom_data']['text'] = $textData;

            saveResourceState($resourceName, $requestData['id'], $requestData);
        }
    }

    $token = getToken();
    if (time() >= $token['expires']) {
        refreshToken();
        $token = getToken();
    }

    sendEditRequest($data, $token['access_token'], $resourceName, $data['id']);
    logToConsole($resourceName . ' edit success.');
}

function sendEditRequest(array $data, string $accessToken, string $resourceUri, ?int $resourceId = null)
{
    $uri = 'api/v4/' . $resourceUri;
    if ($resourceId) {
        $uri .= '/' . $resourceId;
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
    curl_setopt($curl, CURLOPT_URL, getenv('ACCOUNT_DOMAIN') . $uri);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type:application/json',
        'Authorization: Bearer ' . $accessToken,
    ]);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

    $out = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($code < 200 || $code > 204) {
        logToConsole("ASYNC ERROR [$code]", json_decode($out, true));
    } else {
        logToConsole("ASYNC SUCCESS [$code]", json_decode($out, true));
    }
}
