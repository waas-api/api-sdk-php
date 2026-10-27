<?php

require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/JsonOrderStore.php';
require __DIR__ . '/OrderReviewer.php';
use WaasApi\ApiSdkPhp\V2\Callback\Input;
use WaasApi\ApiSdkPhp\V2\Callback\Reply;
use WaasApi\ApiSdkPhp\V2\Callback\RedisNonceStore;
use WaasApi\ApiSdkPhp\V2\Callback\WithdrawRiskServer;
use WaasApi\ApiSdkPhp\V2\Callback\ExchangeRiskServer;
use WaasApi\ApiSdkPhp\V2\Exception\TransportException;
use WaasApi\Examples\Risk\JsonOrderStore;
use WaasApi\Examples\Risk\OrderReviewer;
try {
    $directory = getenv('RISK_KEY_DIR') ?: sys_get_temp_dir() . '/waas-v2-risk-keys';
    $config = ['riskPrivateKey' => file_get_contents($directory . '/merchant-risk-private.pem'), 'riskPlatformPublicKey' => file_get_contents($directory . '/platform-risk-public.pem'), 'errorLog' => static function ($e) {
        return error_log($e->getMessage());
    }];
    // Without this setting the demo has clock-window protection only. phpredis is optional.
    if (getenv('REDIS_HOST')) {
        $redis = new Redis();
        $redis->connect(getenv('REDIS_HOST'), (int) (getenv('REDIS_PORT') ?: 6379), 2.0);
        $config['nonceStore'] = new RedisNonceStore($redis, 'waas:risk:' . hash('sha256', $config['riskPlatformPublicKey']) . ':');
    }
    $review = new OrderReviewer(new JsonOrderStore(getenv('RISK_ORDER_FILE') ?: __DIR__ . '/orders.example.json'));
    $body = Input::read(fopen('php://input', 'rb'));
    $headers = \WaasApi\ApiSdkPhp\V2\Callback\Input::headers();
    $method = $_SERVER['REQUEST_METHOD'];
    switch (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
        case '/withdraw/review':
            $reply = (new WithdrawRiskServer($config))->handle($headers, $body, [$review, 'withdraw'], $method);
            break;
        case '/exchange/review':
            $reply = (new ExchangeRiskServer($config))->handle($headers, $body, [$review, 'exchange'], $method);
            break;
        default:
            $reply = Reply::json(['error' => 'Unknown endpoint'], 404);
            break;
    }
} catch (TransportException $e) {
    $reply = Reply::json(['error' => 'Invalid request'], isset($e->httpStatus) ? $e->httpStatus : 500);
} catch (\Exception $e) {
    error_log($e->getMessage());
    $reply = Reply::json(['error' => 'Review unavailable'], 500);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $reply = Reply::json(['error' => 'Review unavailable'], 500);
}
http_response_code($reply->status);
foreach ($reply->headers as $name => $value) {
    header($name . ': ' . $value);
}
echo $reply->body;
