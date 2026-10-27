<?php

require dirname(__DIR__) . '/bootstrap.php';
$directory = isset($argv[1]) ? $argv[1] : sys_get_temp_dir() . '/waas-v2-risk-keys';
if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
    throw new RuntimeException('Cannot create key directory');
}
foreach (['platform', 'merchant'] as $name) {
    $privatePath = $directory . '/' . $name . '-risk-private.pem';
    $publicPath = $directory . '/' . $name . '-risk-public.pem';
    if (file_exists($privatePath) || file_exists($publicPath)) {
        throw new RuntimeException('Refusing to overwrite existing keys: ' . $name);
    }
    $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
    if ($key === false || !openssl_pkey_export($key, $private)) {
        throw new RuntimeException('Key generation failed');
    }
    file_put_contents($privatePath, $private, LOCK_EX);
    chmod($privatePath, 0600);
    file_put_contents($publicPath, openssl_pkey_get_details($key)['key'], LOCK_EX);
}
echo 'Local test keys created in ' . $directory . PHP_EOL;
