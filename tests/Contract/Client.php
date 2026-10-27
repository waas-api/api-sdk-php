<?php

use WaasApi\ApiSdkPhp\V2\Client;
use WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature as Rsa;
use WaasApi\ApiSdkPhp\V2\Http\TransportInterface;
use WaasApi\ApiSdkPhp\V2\Http\Response;
use WaasApi\ApiSdkPhp\V2\Json;
use WaasApi\ApiSdkPhp\V2\Exception\ApiException;
use WaasApi\ApiSdkPhp\V2\Exception\ProtocolException;
use WaasApi\ApiSdkPhp\V2\Exception\SignatureException;
use WaasApi\ApiSdkPhp\V2\Exception\TransportException;
final class MockTransport implements TransportInterface
{
    /**
     * @var \WaasApi\ApiSdkPhp\V2\Http\Response
     */
    public $response;
    /**
     * @var mixed[]
     */
    public $calls = [];
    public function __construct(Response $response)
    {
        $this->response = $response;
    }
    /**
     * @param string $url
     * @param mixed[] $headers
     * @param string $body
     * @param float $timeout
     * @param int $maxBodyBytes
     */
    public function post($url, $headers, $body, $timeout, $maxBodyBytes)
    {
        $this->calls[] = compact('url', 'headers', 'body', 'timeout', 'maxBodyBytes');
        return $this->response;
    }
}
return ['All 42 POST endpoints match literal paths, JSON fields and signed response contracts' => static function () {
    $endpoints = fixture('endpoints');
    $requests = fixture('request_data');
    $data = (object) ((array) fixture('response_data') + (array) fixture('tr_response_data'));
    same(42, count((array) $endpoints));
    $f = fixture('signature_v2_fixture');
    $private = Rsa::privateKey($f->private_key_pem);
    $public = Rsa::publicKey($f->public_key_pem);
    foreach ($endpoints as $method => $endpoint) {
        $path = $endpoint->path;
        $body = Json::encode(['status' => 200, 'msg' => 'success', 'data' => $data->{$path}]);
        $transport = new MockTransport(new Response(200, Rsa::signBody($body, $private), $body));
        $client = new Client(config(), $transport);
        $class = 'WaasApi\ApiSdkPhp\V2\Request\\' . $endpoint->request;
        $response = $client->{$method}(new $class($requests->{$path}));
        $call = $transport->calls[0];
        same('https://example.test/shopapi/v2' . $path, $call['url'], $method);
        same(normalizedJson(Json::encode($requests->{$path})), normalizedJson($call['body']), $method . ' request fields');
        same(30.0, $call['timeout']);
        same(1048576, $call['maxBodyBytes']);
        $h = $call['headers'];
        same('admin', $h['X-Cbc-Key-Version']);
        same('RSA-SHA256', $h['X-Cbc-Sign-Alg']);
        truth((bool) preg_match('/^[a-f0-9]{32}$/D', $h['X-Cbc-Nonce']));
        // Assemble independently, not through the production canonical builder.
        $canonical = implode("\n", ['POST', '/shopapi/v2' . $path, 'demo-app', 'admin', $h['X-Cbc-Timestamp'], $h['X-Cbc-Nonce'], hash('sha256', $call['body'])]);
        truth(openssl_verify($canonical, base64_decode($h['X-Cbc-Signature']), $public, OPENSSL_ALGO_SHA256) === 1);
        same('WaasApi\ApiSdkPhp\V2\Response\\' . $endpoint->response, get_class($response));
        // Every original response field survives typed hydration and re-serialization.
        if ($data->{$path} !== null) {
            $actual = json_decode(Json::encode($response->data));
            $check = static function ($expected, $actual, $field) use (&$check) {
                if (is_object($expected) || is_array($expected)) {
                    foreach ($expected as $key => $value) {
                        $check($value, is_object($actual) ? $actual->{$key} : $actual[$key], $field . '.' . $key);
                    }
                } else {
                    same($expected, $actual, $field);
                }
            };
            $check($data->{$path}, $actual, $method);
        }
    }
}, 'Unsigned gateway errors retain business codes and raw error data' => static function () {
    foreach ([null, [], (object) ['trade_id' => 'T1'], 'diagnostic'] as $data) {
        $transport = new MockTransport(new Response(200, [], Json::encode(['status' => 618, 'msg' => 'processing', 'data' => $data])));
        $client = new Client(config(), $transport);
        $e = raises(ApiException::class, function () use ($client) {
            return $client->balance(new \WaasApi\ApiSdkPhp\V2\Request\BalanceRequest(['coin' => 'trx']));
        });
        same(618, $e->status);
        truth($e->isSearchProcessing());
        truth($e->isRetryable());
        same(Json::encode($data), Json::encode($e->data));
    }
    $transport = new MockTransport(new Response(200, [], '{"status":617,"msg":"provider","data":[]}'));
    $e = raises(ApiException::class, function () use ($transport) {
        return (new Client(config(), $transport))->request('/future');
    });
    same(false, $e->isRetryable());
}, 'Response signatures cannot be bypassed by partial headers, invalid JSON, or HTTP errors' => static function () {
    $f = fixture('signature_v2_fixture');
    $key = Rsa::privateKey($f->private_key_pem);
    foreach ([['X-Cbc-Nonce' => 'partial'], Rsa::signBody('different', $key)] as $headers) {
        $transport = new MockTransport(new Response(500, $headers, 'invalid json'));
        raises(SignatureException::class, function () use ($transport) {
            return (new Client(config(), $transport))->request('/future');
        });
    }
    $transport = new MockTransport(new Response(503, [], 'gateway unavailable'));
    $e = raises(TransportException::class, function () use ($transport) {
        return (new Client(config(), $transport))->request('/future');
    });
    same(503, $e->httpStatus);
    $transport->response = new Response(200, [], 'not json');
    raises(ProtocolException::class, function () use ($transport) {
        return (new Client(config(), $transport))->request('/future');
    });
    $transport->response = new Response(200, [], str_repeat('a', 1048577));
    raises(TransportException::class, function () use ($transport) {
        return (new Client(config(), $transport))->request('/future');
    });
    $transport->response = new Response(200, Rsa::signBody('{"status":200,"data":[]}', $key), '{"status":200,"data":[]}');
    raises(ProtocolException::class, function () use ($transport) {
        return (new Client(config(), $transport))->balance(new \WaasApi\ApiSdkPhp\V2\Request\BalanceRequest(['coin' => 'trx']));
    });
    // Stripping every signature header must not turn a forged success into a trusted reply.
    $transport->response = new Response(200, [], '{"status":200,"data":{"coin":"trx"}}');
    raises(SignatureException::class, function () use ($transport) {
        return (new Client(config(), $transport))->balance(new \WaasApi\ApiSdkPhp\V2\Request\BalanceRequest(['coin' => 'trx']));
    });
    $transport->response = new Response(200, Rsa::signBody('{"status":200,"data":{}}', $key), '{"status":200,"data":{}}');
    raises(ProtocolException::class, function () use ($transport) {
        return (new Client(config(), $transport))->rateExchangeBatch();
    });
    $transport->response = new Response(200, ['X-Cbc-Nonce' => 'partial'], '{"status":200}');
    (new Client(config(['platformPublicKey' => '']), $transport))->request('/future');
}, 'Generic entry serializes empty object, escapes path once and renews nonce on each call' => static function () {
    $transport = new MockTransport(new Response(200, Rsa::signBody('{"status":200}', Rsa::privateKey(fixture('signature_v2_fixture')->private_key_pem)), '{"status":200}'));
    $client = new Client(config(), $transport);
    $client->request('/未来/%2f/a b');
    $client->request('/future');
    same('{}', $transport->calls[0]['body']);
    same('https://example.test/shopapi/v2/%E6%9C%AA%E6%9D%A5/%2f/a%20b', $transport->calls[0]['url']);
    truth($transport->calls[0]['headers']['X-Cbc-Nonce'] !== $transport->calls[1]['headers']['X-Cbc-Nonce']);
    raises(InvalidArgumentException::class, function () use ($client) {
        return $client->request('/future?x=1');
    });
    raises(InvalidArgumentException::class, function () use ($client) {
        return $client->request('//elsewhere');
    });
    raises(InvalidArgumentException::class, function () use ($client) {
        return $client->request('/future', ['a', 'b']);
    });
    raises(InvalidArgumentException::class, function () use ($transport) {
        return new Client(config(['baseURL' => 'https://example.test/shopapi/v2/']), $transport);
    });
}];
