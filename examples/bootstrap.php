<?php

// Supports both a Composer checkout and running these repository examples directly.
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
} else {
    spl_autoload_register(static function ($class) {
        $prefix = 'WaasApi\ApiSdkPhp\\';
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', (string) substr($class, strlen($prefix))) . '.php';
            if (is_file($path)) {
                require_once $path;
            }
        }
    });
}
