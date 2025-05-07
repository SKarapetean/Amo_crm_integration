<?php

function logMessage(string $message, string $filePath, array $data = []): void
{
    if (!empty($data)) {
        $data = json_encode($data);
        $message = $message . ' => ' . $data;
    }

    $log = '[' . date('Y-m-d H:i:s') . '] ' . $message .  PHP_EOL;

    file_put_contents($filePath, $log, FILE_APPEND);
}