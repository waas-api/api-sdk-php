<?php

require __DIR__ . '/config.php';
use WaasApi\ApiSdkPhp\V2\Client;
use WaasApi\ApiSdkPhp\V2\Request\BalanceRequest;
$client = new Client(apiConfig());
$response = $client->balance(new BalanceRequest(['coin' => isset($argv[1]) ? $argv[1] : 'trx']));
echo json_encode($response->data, JSON_PRETTY_PRINT) . PHP_EOL;
