<?php

use WaasApi\ApiSdkPhp\V2\Callback;
use WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature as Rsa;
use WaasApi\ApiSdkPhp\V2\Crypto\CodeVasp;
use WaasApi\ApiSdkPhp\V2\Exception\SignatureException;
use WaasApi\ApiSdkPhp\V2\TravelRule\MemorySigner;
use WaasApi\ApiSdkPhp\V2\TravelRule\SignerInterface;
use WaasApi\ApiSdkPhp\V2\Json;
class RecordingNonceStore implements Callback\NonceStoreInterface
{
    /**
     * @var mixed[]
     */
    public $calls = [];
    /**
     * @param string $nonce
     * @param int $ttl
     */
    public function checkAndMark($nonce, $ttl)
    {
        if (isset($this->calls[$nonce])) {
            throw new RuntimeException('replay');
        }
        $this->calls[$nonce] = $ttl;
    }
}
class FailingNonceStore implements Callback\NonceStoreInterface
{
    /**
     * @param string $nonce
     * @param int $ttl
     */
    public function checkAndMark($nonce, $ttl)
    {
        throw new RuntimeException('Redis unavailable');
    }
}
class AtomicRedisStub
{
    /**
     * @var mixed[]
     */
    public $args = [];
    /**
     * @var bool
     */
    public $result = true;
    public function set(...$args)
    {
        $this->args = $args;
        return $this->result;
    }
}
function signedCallback($body, $timestamp = null)
{
    return Rsa::signBody($body, Rsa::privateKey(fixture('signature_v2_fixture')->private_key_pem), $timestamp);
}
return ['Verifier authenticates before clock and nonce, rejects replay, future/stale time and storage failure' => static function () {
    $store = new RecordingNonceStore();
    $v = new Callback\Verifier(config(['nonceStore' => $store]));
    $body = '{"data":{}}';
    $headers = signedCallback($body);
    raises(SignatureException::class, function () use ($v, $headers, $body) {
        return $v->verify($headers, $body . ' ');
    });
    same([], $store->calls);
    foreach ([time() - 301, time() + 301] as $t) {
        raises(SignatureException::class, function () use ($v, $body, $t) {
            return $v->verify(signedCallback($body, $t), $body);
        });
    }
    same([], $store->calls);
    $v->verify($headers, $body);
    same(600, $store->calls[$headers['X-Cbc-Nonce']]);
    raises(SignatureException::class, function () use ($v, $headers, $body) {
        return $v->verify($headers, $body);
    });
    raises(SignatureException::class, function () use ($v, $body) {
        return $v->verify([], $body);
    });
    $down = new FailingNonceStore();
    raises(SignatureException::class, function () use ($down, $body) {
        return (new Callback\Verifier(config(['nonceStore' => $down])))->verify(signedCallback($body), $body);
    });
}, 'Redis nonce adapter uses atomic SET NX EX, isolates key space and fails closed' => static function () {
    $redis = new AtomicRedisStub();
    $store = new Callback\RedisNonceStore($redis, 'merchant:key:');
    $store->checkAndMark('nonce', 600);
    same(['merchant:key:' . hash('sha256', 'nonce'), '1', ['nx', 'ex' => 600]], $redis->args);
    $redis->result = false;
    raises(RuntimeException::class, function () use ($store) {
        return $store->checkAndMark('nonce', 600);
    });
}, 'Five notifications hydrate complete fields, acknowledge failures and never dispatch unverified payloads' => static function () {
    $server = new Callback\NotifyServer(config());
    $cases = [['handleDeposit', ['order_id' => 'R1', 'coin' => 'trx', 'amount' => '2.0', 'status' => 0, 'is_frozen_by_tr' => false, 'confirm_count' => 0], Callback\DepositNotify::class], ['handleWithdraw', ['trade_id' => 'T1', 'amount' => '2.0', 'time' => '1785297600', 'status' => 1], Callback\WithdrawNotify::class], ['handleExchange', ['trade_id' => 'E1', 'source_amount' => '2', 'type' => '0', 'status' => 1], Callback\ExchangeNotify::class], ['handleH5Order', ['order_no' => 'H1', 'status' => 1, 'payments' => [['coin' => 'trx', 'amount' => '2', 'check_code' => 0]], 'within_tolerance' => 0], Callback\H5OrderNotify::class], ['handleOuterAddressTransaction', ['address' => 'TA', 'transaction_type' => 1, 'status' => 0, 'amount' => '2'], Callback\OuterAddressTransactionNotify::class]];
    foreach ($cases as list($method, $data, $type)) {
        $body = Json::encode(['data' => $data]);
        $count = 0;
        $business = static function ($request) use ($type, &$count) {
            truth($request instanceof $type);
            $count++;
            return true;
        };
        $reply = $server->{$method}(signedCallback($body), $body, $business);
        same(200, $reply->status);
        same(1, $count);
        same('{"status":200,"msg":"success","data":{"success_data":"success"}}', $reply->body);
        truth(!Rsa::hasSignature($reply->headers));
        same(401, $server->{$method}([], $body, $business)->status);
        same(1, $count);
        same(400, $server->{$method}(signedCallback('{}'), '{}', $business)->status);
        same(1, $count);
        $reply = $server->{$method}(signedCallback($body), $body, static function () {
            return false;
        });
        same('{"status":500,"msg":"fail","data":{"success_data":"fail"}}', $reply->body);
        $reply = $server->{$method}(signedCallback($body), $body, static function () {
            throw new RuntimeException('DB down');
        });
        same(500, Json::object($reply->body)->status);
        same(405, $server->{$method}([], '', $business, 'GET')->status);
        same(413, $server->{$method}([], str_repeat('a', 1048577), $business)->status);
    }
}, 'Both risk reviews echo immutable trade_id and sign approve, pending and verification failure responses' => static function () {
    $f = fixture('signature_v2_fixture');
    $riskConfig = ['riskPlatformPublicKey' => $f->public_key_pem, 'riskPrivateKey' => $f->private_key_pem];
    foreach ([Callback\WithdrawRiskServer::class, Callback\ExchangeRiskServer::class] as $type) {
        $s = new $type($riskConfig);
        $body = '{"data":{"trade_id":"T1","user_id":"u1","coin":"trx","source_coin":"trx","target_coin":"usdt_trc20","address":"TA","amount":"2"}}';
        $count = 0;
        $business = static function ($request) use (&$count) {
            $count++;
            $request->tradeId = 'mutated';
            return new Callback\RiskResult(200, 'success');
        };
        $reply = $s->handle(signedCallback($body), $body, $business);
        same(1, $count);
        same(200, $reply->status);
        same(200, Json::object($reply->body)->status);
        same('T1', Json::object($reply->body)->data->trade_id);
        Rsa::verifyBody($reply->body, $reply->headers, Rsa::publicKey($f->public_key_pem));
        foreach ([[], signedCallback($body, time() - 301), signedCallback($body, time() + 301)] as $headers) {
            $reply = $s->handle($headers, $body, $business);
            same(5400, Json::object($reply->body)->status);
            same(1, $count);
            same('T1', Json::object($reply->body)->data->trade_id);
            Rsa::verifyBody($reply->body, $reply->headers, Rsa::publicKey($f->public_key_pem));
        }
        $reply = $s->handle(signedCallback($body), $body, static function () {
            throw new RuntimeException('database down');
        });
        same(2001, Json::object($reply->body)->status);
        same(500, $s->handle(signedCallback($body), $body, static function () {
            return new Callback\RiskResult(9999);
        })->status);
        same(500, $s->handle(signedCallback('{}'), '{}', $business)->status);
        same(500, $s->handle(signedCallback('{'), '{', $business)->status);
        same(413, $s->handle([], str_repeat('a', 1048577), $business)->status);
        $reply = $s->handle(signedCallback($body), $body, static function () {
            return new Callback\RiskResult(5004);
        });
        same($type === Callback\ExchangeRiskServer::class ? 500 : 200, $reply->status);
    }
    raises(SignatureException::class, function () {
        return new Callback\WithdrawRiskServer(config());
    });
}, 'Seven Travel Rule callbacks follow independent schemas and allowed result sets' => static function () {
    $f = fixture('codevasp');
    $s = new Callback\TravelRuleServer(config(['signer' => new MemorySigner($f->fixtureSeedB64), 'signerPublicKey' => $f->fixturePubKeyB64]));
    $body = Json::encode(['vasp_id' => 'code:own', 'message' => $f->fixtureMessageB64, 'operation' => 'transfer']);
    $reply = $s->handleSignature(signedCallback($body), $body);
    same($f->fixtureSignatureB64, Json::object($reply->body)->signature);
    truth(!Rsa::hasSignature($reply->headers));
    $badBody = '{"message":"?invalid-base64"}';
    same(400, $s->handleSignature(signedCallback($badBody), $badBody)->status);
    foreach ([['handleVerifyAddress', 'TrVerifyAddressCallbackResponse', 'valid'], ['handleTransfer', 'TrTransferCallbackResponse', 'verified'], ['handleTransferResult', 'TrTransferResultCallbackResponse', 'normal'], ['handlePostTransfer', 'TrPostTransferCallbackResponse', 'normal']] as list($method, $class, $result)) {
        $class = 'WaasApi\ApiSdkPhp\V2\Callback\\' . $class;
        $body = '{"vasp_id":"code:own","transfer_id":"tr1","status":"confirmed","txid":"tx1","vout":"0","is_exceeding_threshold":false}';
        $reply = $s->{$method}(signedCallback($body), $body, static function ($req) use ($class, $result) {
            return new $class(['result' => $result]);
        });
        same(200, $reply->status);
        same($result, Json::object($reply->body)->result);
        truth(property_exists(Json::object($reply->body), 'reason_message'));
        truth(!Rsa::hasSignature($reply->headers));
        same(500, $s->{$method}(signedCallback($body), $body, static function () use ($class) {
            return new $class(['result' => 'unexpected']);
        })->status);
        $invoked = false;
        same(401, $s->{$method}([], $body, static function () use (&$invoked, $class) {
            $invoked = true;
            return new $class();
        })->status);
        same(false, $invoked);
    }
    $body = '{"vasp_id":"code:own","address":"TA","contract":"trx"}';
    $reply = $s->handleAddressRegion(signedCallback($body), $body, static function () {
        return new Callback\TrAddressRegionCallbackResponse(['region' => '']);
    });
    same('{"region":"","reason_message":""}', $reply->body);
    $body = '{"vasp_id":"code:own"}';
    $reply = $s->handleHealth(signedCallback($body), $body);
    same(200, $reply->status);
    same('{"status":"OK"}', $reply->body);
    $s = new Callback\TravelRuleServer(config());
    same(503, $s->handleHealth(signedCallback($body), $body)->status);
    same(500, $s->handleSignature(signedCallback($body), $body)->status);
    $s = new Callback\TravelRuleServer(config(['signer' => new MemorySigner($f->fixtureSeedB64), 'signerPublicKey' => $f->fixtureReceiverPubKeyB64]));
    same(503, $s->handleHealth(signedCallback($body), $body)->status);
    same(401, $s->handleHealth([], $body)->status);
    same(400, $s->handleHealth(signedCallback('{'), '{')->status);
}];
