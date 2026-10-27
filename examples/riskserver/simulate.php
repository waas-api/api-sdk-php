<?php

require dirname(__DIR__) . '/bootstrap.php';
use WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature;
use WaasApi\ApiSdkPhp\V2\Http\CurlTransport;
use WaasApi\ApiSdkPhp\V2\Json;
$kind = isset($argv[1]) ? $argv[1] : 'withdraw';
if (!in_array($kind, ['withdraw', 'exchange'], true)) {
    throw new InvalidArgumentException('Use withdraw or exchange');
}
$directory = getenv('RISK_KEY_DIR') ?: sys_get_temp_dir() . '/waas-v2-risk-keys';
$orders = json_decode(file_get_contents(__DIR__ . '/orders.example.json'), true, 512, 0);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new \Exception(json_last_error_msg());
}
$order = $orders[$kind][$kind === 'withdraw' ? 'T1' : 'E1'];
unset($order['review']);
$order['amount'] = '2';
// Deliberately differs from the stored "2.00000000" representation.
$body = Json::encode(['data' => $order]);
$headers = RsaSignature::signBody($body, RsaSignature::privateKey(file_get_contents($directory . '/platform-risk-private.pem')));
$response = (new CurlTransport())->post((isset($argv[2]) ? $argv[2] : 'http://127.0.0.1:8088') . '/' . $kind . '/review', ['Content-Type' => 'application/json'] + $headers, $body, 30, 1048576);
if ($response->status !== 200) {
    throw new RuntimeException('Review HTTP ' . $response->status);
}
RsaSignature::verifyBody($response->body, $response->headers, RsaSignature::publicKey(file_get_contents($directory . '/merchant-risk-public.pem')));
$reply = Json::object($response->body);
if ((isset($reply->data->trade_id) ? $reply->data->trade_id : null) !== $order['trade_id'] || $reply->status !== 200) {
    throw new RuntimeException('Unexpected review response: ' . $response->body);
}
echo $kind . ' request and merchant response signatures verified: ' . $response->body . PHP_EOL;
