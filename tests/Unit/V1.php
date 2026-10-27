<?php

use WaasApi\ApiSdkPhp\Signature;
use WaasApi\ApiSdkPhp\Serve;
use WaasApi\ApiSdkPhp\RiskControl;
return ['V1 callback and risk entry points retain RSA-MD5 behavior' => static function () {
    $config = config();
    $data = ['coin' => 'trx', 'amount' => '2', 'status' => 0, 'order_id' => 'R1'];
    $signature = Signature::generate($data, $config['privateKey']);
    truth(Signature::checkSignature($signature, $data, $config['platformPublicKey']));
    $s = new Serve($config);
    same($data, $s->getDepositCallbackData(['data' => $data, 'sign' => $signature]));
    same($data, $s->getWithdrawCallbackData(['data' => $data, 'sign' => $signature]));
    $r = new RiskControl(['riskPrivateKey' => $config['privateKey'], 'riskPlatformPublicKey' => $config['platformPublicKey']]);
    same($data, $r->getWithdrawOrderCheckData(['data' => $data, 'sign' => $signature]));
    $reply = $r->callbackResponseData(200, 'R1');
    same('R1', $reply['data']['order_id']);
    truth(Signature::checkSignature($reply['sign'], $reply['data'], $config['platformPublicKey']));
}, 'Risk order example compares decimals numerically and addresses byte for byte' => static function () {
    require_once dirname(dirname(__DIR__)) . '/examples/riskserver/JsonOrderStore.php';
    require_once dirname(dirname(__DIR__)) . '/examples/riskserver/OrderReviewer.php';
    $r = new \WaasApi\Examples\Risk\OrderReviewer(new \WaasApi\Examples\Risk\JsonOrderStore(dirname(dirname(__DIR__)) . '/examples/riskserver/orders.example.json'));
    $withdraw = new \WaasApi\ApiSdkPhp\V2\Callback\WithdrawRiskRequest(['trade_id' => 'T1', 'user_id' => 'u1', 'coin' => 'trx', 'address' => 'TCaseSensitiveAddress', 'amount' => '2']);
    same(200, $r->withdraw($withdraw)->status);
    $withdraw->address = strtolower($withdraw->address);
    same(5004, $r->withdraw($withdraw)->status);
    $exchange = new \WaasApi\ApiSdkPhp\V2\Callback\ExchangeRiskRequest(['trade_id' => 'E1', 'user_id' => 'u1', 'source_coin' => 'trx', 'target_coin' => 'usdt_trc20', 'amount' => '2.000']);
    same(200, $r->exchange($exchange)->status);
    $exchange->targetCoin = 'eth';
    same(5005, $r->exchange($exchange)->status);
    $exchange->tradeId = 'missing';
    same(5402, $r->exchange($exchange)->status);
    truth(\WaasApi\ApiSdkPhp\V2\Decimal::equals('0002.00000000', '2'));
    same(false, \WaasApi\ApiSdkPhp\V2\Decimal::equals('100000000000000000000000000000.00000001', '100000000000000000000000000000'));
}];
