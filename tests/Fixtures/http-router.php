<?php

require dirname(__DIR__) . '/bootstrap.php';
use WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature as Rsa;
use WaasApi\ApiSdkPhp\V2\Json;
$f = fixture('signature_v2_fixture');
$body = file_get_contents('php://input');
$request = json_decode($body, true);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strncmp($path, '/v1/', strlen('/v1/')) === 0) {
    $signature = $request['sign'];
    if (!\WaasApi\ApiSdkPhp\Signature::checkSignature($signature, $request, $f->public_key_pem)) {
        http_response_code(401);
        exit;
    }
    $response = ['status' => 200, 'msg' => 'success', 'data' => ['path' => (string) substr($path, 3)]];
    $response['sign'] = \WaasApi\ApiSdkPhp\Signature::generate($response, $f->private_key_pem);
    echo json_encode($response);
    return;
}
$headers = array_change_key_case(\WaasApi\ApiSdkPhp\V2\Callback\Input::headers(), CASE_LOWER);
$canonical = implode("\n", ['POST', $path, isset($headers['x-cbc-app-id']) ? $headers['x-cbc-app-id'] : '', isset($headers['x-cbc-key-version']) ? $headers['x-cbc-key-version'] : '', isset($headers['x-cbc-timestamp']) ? $headers['x-cbc-timestamp'] : '', isset($headers['x-cbc-nonce']) ? $headers['x-cbc-nonce'] : '', hash('sha256', $body)]);
if (openssl_verify($canonical, base64_decode(isset($headers['x-cbc-signature']) ? $headers['x-cbc-signature'] : ''), $f->public_key_pem, OPENSSL_ALGO_SHA256) !== 1) {
    http_response_code(401);
    echo 'signature mismatch';
    return;
}
switch (isset($request['mode']) ? $request['mode'] : '') {
    case 'redirect':
        header('Location: /redirected', true, 302);
        echo '{}';
        return;
    case 'oversize':
        echo str_repeat('x', 1048577);
        return;
    case 'timeout':
        usleep(300000);
        echo '{"status":200}';
        return;
    case 'badjson':
        echo 'bad json';
        return;
    case 'failure':
        http_response_code(503);
        echo 'temporarily unavailable';
        return;
}
$response = Json::encode(['status' => 200, 'data' => ['body' => base64_encode($body), 'path' => $path]]);
foreach (Rsa::signBody($response, Rsa::privateKey($f->private_key_pem)) as $name => $value) {
    header(strtolower($name) . ': ' . $value);
}
header('Content-Type: application/json');
echo $response;
