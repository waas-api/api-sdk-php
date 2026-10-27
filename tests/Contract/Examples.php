<?php

use WaasApi\ApiSdkPhp\V2\Client;
use WaasApi\ApiSdkPhp\V2\Http\Response;
use WaasApi\ApiSdkPhp\V2\Http\TransportInterface;
use WaasApi\ApiSdkPhp\V2\Json;
use WaasApi\ApiSdkPhp\V2\Request\TrThresholdDeclaration;
use WaasApi\ApiSdkPhp\V2\TravelRule\Ivms101\IVMS101;

class OutboundExampleTransport implements TransportInterface
{
    public $calls = [];
    private $publicKey;
    private $route;

    public function __construct($publicKey, $route)
    {
        $this->publicKey = $publicKey;
        $this->route = $route;
    }

    public function post($url, $headers, $body, $timeout, $maxBodyBytes)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $this->calls[$path] = Json::object($body);
        switch ($path) {
            case '/shopapi/v2/travelRule/vaspList':
                $data = ['items' => [['vasp_id' => 'code:target', 'public_keys' => [['value' => 'ZXhwaXJlZA==', 'expires_at' => '2020-01-01T00:00:00Z'], ['value' => $this->publicKey]]]]];
                break;
            case '/shopapi/v2/travelRule/verifyAddress':
                $data = ['result' => 'valid'];
                break;
            case '/shopapi/v2/travelRule/transfer':
                $data = ['transfer_id' => 'tr1', 'route_decision' => $this->route, 'result' => $this->route === 'MANUAL' ? 'denied' : 'verified'];
                break;
            case '/shopapi/v2/transfer':
                $data = ['trade_id' => 'T1'];
                break;
            default:
                throw new RuntimeException('Unexpected endpoint ' . $path);
        }
        $body = Json::encode(['status' => 200, 'data' => $data]);
        return new Response(200, \WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature::signBody($body, \WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature::privateKey(fixture('signature_v2_fixture')->private_key_pem)), $body);
    }
}

return [
    'Travel Rule outbound example binds authorization and withdrawal; MANUAL never initiates payout' => function () {
        require_once dirname(dirname(__DIR__)) . '/examples/v2/travelrule-outbound.php';
        $f = fixture('codevasp');
        foreach (['TR_CODE', 'PASS', 'MANUAL'] as $route) {
            $transport = new OutboundExampleTransport($f->fixtureReceiverPubKeyB64, $route);
            $call = function () use ($transport, $f) {
                return sendCompliantWithdrawal(new Client(config(), $transport), 'code:own', 'code:target', $f->fixtureSeedB64,
                    new IVMS101(['Beneficiary' => ['accountNumber' => ['TA']]]),
                    new TrThresholdDeclaration(['exceeded' => true, 'jurisdiction' => 'SG', 'rule_version' => '2026', 'value' => '1500', 'currency' => 'SGD', 'fiat_amount' => '2600', 'decided_at' => time()]),
                    'usdt_trc20', '2000.00000000', '2600', 'SGD', 'TB', 'TA', 'T1', 'memo', 'u1');
            };
            if ($route === 'MANUAL') {
                raises(RuntimeException::class, $call);
                truth(!isset($transport->calls['/shopapi/v2/transfer']));
                continue;
            }
            same('T1', $call()->data->tradeId);
            $auth = $transport->calls['/shopapi/v2/travelRule/transfer'];
            $withdraw = $transport->calls['/shopapi/v2/transfer'];
            same($auth->coin, $withdraw->coin);
            same($auth->amount, $withdraw->amount);
            same($auth->beneficiary_address, $withdraw->address);
            same($auth->tag, $withdraw->memo);
            same(true, $withdraw->is_tr_compliant);
            same('tr1', $withdraw->tr_transfer_id);
            same('u1', $withdraw->user_id);
            same($f->fixtureReceiverPubKeyB64, $auth->beneficiary_public_key);
        }
    },
    'Inbound result example stores business effect and idempotent response in one transaction' => function () {
        require_once dirname(dirname(__DIR__)) . '/examples/v2/travelrule-inbound.php';
        $db = new PDO('sqlite::memory:');
        $db->exec('CREATE TABLE applied (id TEXT PRIMARY KEY)');
        $store = new EventStore($db);
        $calls = 0;
        $handler = inboundTransferResultHandler($store, function (PDO $db, $event) use (&$calls) {
            $calls++;
            $db->exec("INSERT INTO applied VALUES ('1')");
        });
        $event = new \WaasApi\ApiSdkPhp\V2\Callback\TrTransferResultCallbackRequest(['transfer_id' => 'tr1', 'status' => 'confirmed', 'txid' => 'tx1', 'vout' => '0']);
        same('normal', $handler($event)->result);
        same('normal', $handler($event)->result);
        same(1, $calls);
        same(1, (int) $db->query('SELECT count(*) FROM applied')->fetchColumn());
        raises(RuntimeException::class, function () use ($store) {
            return $store->once('failure', function (PDO $db) {
                $db->exec("INSERT INTO applied VALUES ('2')");
                throw new RuntimeException('failure');
            });
        });
        same(1, (int) $db->query('SELECT count(*) FROM applied')->fetchColumn());
        same(['result' => 'normal'], $store->once('failure', function () { return ['result' => 'normal']; }));
    },
    'Standalone risk simulator completes both signed request/reply flows with generated keys' => function () {
        $root = dirname(dirname(__DIR__));
        $directory = sys_get_temp_dir() . '/waas-risk-' . bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $run = function (array $command, array $environment = []) {
            $proc = proc_open('exec ' . implode(' ', array_map('escapeshellarg', $command)), [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment + $_ENV);
            if (!is_resource($proc)) { throw new RuntimeException('Cannot start subprocess'); }
            fclose($pipes[0]);
            $out = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            same(0, proc_close($proc), $out . $error);
            return $out;
        };
        $server = null;
        $log = $directory . '/server.log';
        try {
            $run([PHP_BINARY, $root . '/examples/riskserver/generate-keys.php', $directory]);
            same(0600, fileperms($directory . '/merchant-risk-private.pem') & 0777);
            $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
            $address = stream_socket_get_name($socket, false); fclose($socket);
            $server = proc_open('exec ' . implode(' ', array_map('escapeshellarg', [PHP_BINARY, '-S', $address, $root . '/examples/riskserver/router.php'])), [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, null, ['RISK_KEY_DIR' => $directory] + $_ENV);
            truth(is_resource($server)); fclose($pipes[0]);
            $ready = false;
            for ($i = 0; $i < 200; $i++) {
                $socket = @stream_socket_client('tcp://' . $address, $errno, $error, 0.02);
                if ($socket) { fclose($socket); $ready = true; break; }
                usleep(10000);
            }
            truth($ready, 'Risk example server unavailable');
            foreach (['withdraw', 'exchange'] as $kind) {
                $out = $run([PHP_BINARY, $root . '/examples/riskserver/simulate.php', $kind, 'http://' . $address], ['RISK_KEY_DIR' => $directory]);
                truth(strpos($out, 'request and merchant response signatures verified') !== false);
            }
        } finally {
            if (is_resource($server)) { proc_terminate($server); proc_close($server); }
            foreach (glob($directory . '/*') as $file) { unlink($file); }
            rmdir($directory);
        }
    },
];
