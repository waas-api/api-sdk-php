<?php

require dirname(__DIR__) . '/bootstrap.php';
function requiredEnv($name)
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        throw new RuntimeException('Missing environment variable: ' . $name);
    }
    return $value;
}
function apiConfig()
{
    return ['baseURL' => requiredEnv('WAAS_BASE_URL'), 'appId' => requiredEnv('WAAS_APP_ID'), 'privateKey' => file_get_contents(requiredEnv('WAAS_PRIVATE_KEY_FILE')), 'platformPublicKey' => file_get_contents(requiredEnv('WAAS_PLATFORM_PUBLIC_KEY_FILE')), 'keyVersion' => getenv('WAAS_KEY_VERSION') ?: 'admin'];
}
