<?php

use WaasApi\ApiSdkPhp\V2\Client;
use WaasApi\ApiSdkPhp\V2\Exception\ProtocolException;
use WaasApi\ApiSdkPhp\V2\Exception\TransportException;
return ['Actual cURL sends exact signed bytes and escaped path, refuses redirects and caps streamed body' => static function () {
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    truth($socket !== false, $error);
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $log = tempnam(sys_get_temp_dir(), 'waas-http-');
    $process = proc_open('exec ' . implode(' ', array_map('escapeshellarg', [PHP_BINARY, '-S', $address, __DIR__ . '/../Fixtures/http-router.php'])), [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot start HTTP test server');
    }
    fclose($pipes[0]);
    try {
        $ready = false;
        for ($i = 0; $i < 200; $i++) {
            $sock = @stream_socket_client('tcp://' . $address, $errno, $error, 0.02);
            if ($sock) {
                fclose($sock);
                $ready = true;
                break;
            }
            usleep(10000);
        }
        truth($ready, 'HTTP server did not start: ' . file_get_contents($log));
        $client = new Client(config(['baseURL' => 'http://' . $address . '/shopapi']));
        $response = $client->request('/a%2Fb/../空 格', ['text' => '你好/世界', 'amount' => '2.00000000', 'flag' => false, 'status' => 0]);
        same('/shopapi/v2/a%2Fb/%2E%2E/%E7%A9%BA%20%E6%A0%BC', $response->data->path);
        same('{"text":"你好/世界","amount":"2.00000000","flag":false,"status":0}', base64_decode($response->data->body));
        same('{}', base64_decode($client->request('/empty')->data->body));
        $e = raises(TransportException::class, function () use ($client) {
            return $client->request('/test', ['mode' => 'redirect']);
        });
        same(302, $e->httpStatus);
        raises(TransportException::class, function () use ($client) {
            return $client->request('/test', ['mode' => 'oversize']);
        });
        raises(ProtocolException::class, function () use ($client) {
            return $client->request('/test', ['mode' => 'badjson']);
        });
        $e = raises(TransportException::class, function () use ($client) {
            return $client->request('/test', ['mode' => 'failure']);
        });
        same(503, $e->httpStatus);
        // V1 public methods still sign RSA-MD5 and use their original paths.
        $v1 = new \WaasApi\ApiSdkPhp\Client(config(['baseURL' => 'http://' . $address . '/v1']));
        foreach ([['getCoinList', [], '/coin/list'], ['addressGetBatch', ['trx'], '/address/getBatch'], ['addressSyncStatus', ['trx', 'TA', 'u1'], '/address/syncStatus'], ['addressVerifyAddress', ['trx', 'TA'], '/address/verifyAddress'], ['transfer', ['trx', 'TA', '2', 'T1'], '/transfer'], ['h5OrderCreate', ['M1', '2'], '/h5_order/create']] as list($method, $args, $path)) {
            same($path, $v1->{$method}(...$args)['data']['path'], $method . ' V1 path');
        }
        $client = new Client(config(['baseURL' => 'http://' . $address . '/shopapi', 'timeout' => 0.05]));
        raises(TransportException::class, function () use ($client) {
            return $client->request('/test', ['mode' => 'timeout']);
        });
    } catch (\Exception $e) {
        throw new RuntimeException($e->getMessage() . "\nHTTP server log:\n" . file_get_contents($log), 0, $e);
    } catch (Throwable $e) {
        throw new RuntimeException($e->getMessage() . "\nHTTP server log:\n" . file_get_contents($log), 0, $e);
    } finally {
        proc_terminate($process);
        proc_close($process);
        unlink($log);
    }
}];
