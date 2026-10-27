# api-sdk-php: v1 integration

The v1 signature scheme puts a `sign` field in the JSON body. New integrations
should use v2; see [README.md](README.md). v1 stays supported and unchanged.

## Install
```bash
composer require waas-api/api-sdk-php
```

## Generate Rsa Key Pair

```shell
mkdir rsa_pem && cd rsa_pem
openssl
OpenSSL> genrsa -out app_private_key.pem 2048 # generate private key
OpenSSL> pkcs8 -topk8 -inform PEM -in app_private_key.pem -outform PEM -nocrypt -out app_private_key_pkcs8.pem # convert private key to PKCS8 format
OpenSSL> rsa -in app_private_key.pem -pubout -out app_public_key.pem # generate public key
OpenSSL> exit
```

## Client Request Methods

```php
use WaasApi\ApiSdkPhp\Client;

$config = [
    "baseURL" => "http://api.***.com/shopapi",
    "appId" => "your-app-id",
    "privateKey" => $merchantRsaPrivateKeyPem, // app_private_key_pkcs8.pem content
    "platformPublicKey" => $platformRsaPublicKeyPem, // platform public key
];
$client = new Client($config);

try {
    $resp = $client->addressGetBatch("eth"); // Get New Addresses
//    $resp = $client->addressSyncStatus("eth", "0x6828449A5b01F7c3D281A0c06216eA9f4f188B1c", "10"); // Sync Address Status
//    $resp = $client->transfer("eth", "0xb6dEd378Ad9Aa871c6ec5599049B1DFb7C9866a3", 0.23, "20230621164438434765", "10"); // initial a on-chain withdrawal request
//    ...
    var_dump($resp);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
```

## Deposit And Withdraw Callback

```php
use WaasApi\ApiSdkPhp\Serve;

$config = [
    "privateKey" => $merchantRsaPrivateKeyPem, // app_private_key_pkcs8.pem content
    "platformPublicKey" => $platformRsaPublicKeyPem, // platform public key
];

$serve = new Serve($config);

try {
    $postJson = file_get_contents('php://input');
    $request = json_decode($postJson, true);

    // get request data
    $data = $serve->getDepositCallbackData($request); // Get Deposit Callback data
//    $data = $serve->getWithdrawCallbackData($request); // Get Withdraw Callback data

    // todo : processing business logic
    var_dump($data);

    // response
    $responseData = $serve->callbackResponseData();
    echo json_encode($responseData);
    exit();
} catch (Exception $e) {
    var_dump($e->getMessage());
}
```

## Withdraw Order Risk Control Callback

```php
use WaasApi\ApiSdkPhp\RiskControl;

$config = [
    "riskPrivateKey" => $merchantRiskPrivateKeyPem, // a new risk control app_private_key_pkcs8.pem content
    "riskPlatformPublicKey" => $platformRiskPublicKeyPem, // risk platform public key
];

$riskControl = new RiskControl($config);

try {
    $postJson = file_get_contents('php://input');
    $request = json_decode($postJson, true);

    // get request data
    $data = $riskControl->getWithdrawOrderCheckData($request);

    // todo : processing business logic
    var_dump($data);
    $statusCode = 200;
    $orderId = $data["order_id"];

    // response
    $responseData = $riskControl->callbackResponseData($statusCode, $orderId);
    echo json_encode($responseData);
    exit();
} catch (Exception $e) {
    var_dump($e->getMessage());
}
```

## Switching to v2

The platform switches a merchant's signature version for all notifications and
the withdrawal review at once, and keeps the same addresses. Until it confirms
the switch, the same address can receive both formats. The v2 handlers reject
anything without v2 headers, so route in front of them:

```php
use WaasApi\ApiSdkPhp\V2\Callback\Input;

$headers = array_change_key_case(Input::headers(), CASE_LOWER);
if (isset($headers['x-cbc-signature'])) {
    // v2: NotifyServer, WithdrawRiskServer, ExchangeRiskServer
} else {
    // your existing v1 handler
}
```

Remove the v1 branch once the platform confirms the switch.

The v2 client uses the same RSA key pair and the same `baseURL` as v1; it
appends `/v2` itself. It lives in its own namespace, `WaasApi\ApiSdkPhp\V2`,
independent of `Client`, `Serve` and `RiskControl`, so both versions can run in
the same process during the switch.
