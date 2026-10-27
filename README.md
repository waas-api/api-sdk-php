# api-sdk-php

PHP SDK for the platform API. The SDK signs every request and verifies every
response and notification for you. `V2\Client` covers the merchant APIs in the
public documentation, plus Travel Rule below; `V2\Callback` covers the result
notifications and the withdrawal and exchange reviews. See
[Coverage](#coverage) for the full list. Examples are in `examples/v2` and
`examples/riskserver`.

## Install
```bash
composer require waas-api/api-sdk-php
```

## Client

```php
use WaasApi\ApiSdkPhp\V2\Client;

$client = new Client([
    'appId'             => 'your-app-id',
    'baseURL'           => 'https://api.example.com/shopapi', // same as v1; the SDK appends /v2
    'privateKey'        => $merchantRsaPrivateKeyPem, // signs requests
    'platformPublicKey' => $platformRsaPublicKeyPem,  // verifies responses; required in production
]);
```

### Deposit

Allocate an address, bind it to your user, and show it to them. The platform
reports each deposit to that address through the deposit notification below.

```php
use WaasApi\ApiSdkPhp\V2\Request;

// a non-200 business status throws ApiException; $e->status gives it
$batch = $client->addressGetBatch(new Request\AddressGetBatchRequest(['coin' => 'trx']));
if (count($batch->data) === 0) {
    throw new RuntimeException('no address allocated');
}

$bound = $client->addressSyncStatus(new Request\AddressSyncStatusRequest([
    'address' => $batch->data[0],
    'coin'    => 'trx',
    'user_id' => '10086',
]));
echo 'deposit address: ', $bound->data->address, PHP_EOL;
```

### Withdrawal

Submit the withdrawal with your own unique `trade_id`. Acceptance is not payout:
the outcome arrives through the withdrawal notification below, and the
withdrawal review, if enabled, is called before the payout.

```php
use WaasApi\ApiSdkPhp\V2\Exception\ApiException;

try {
    $res = $client->transfer(new Request\TransferRequest([
        'user_id'  => '10086',
        'coin'     => 'usdt_trc20',
        'amount'   => '1.5',
        'address'  => 'TUCx9tabZSDRa4MxJzw9R3x5EiWNsvMdE7',
        'trade_id' => 'W202610200001',
    ]));
} catch (ApiException $e) {
    if (isset($e->data->trade_id)) {
        // rejected, but the platform still identifies the order, e.g. a
        // duplicate trade_id
        error_log("trade {$e->data->trade_id} rejected: status {$e->status}");
    }
    throw $e;
}
echo 'accepted: ', $res->data->tradeId, PHP_EOL;
```

A method returns only on business status 200, so `data` is always there to use.
A non-200 status throws `ApiException`, whose `data` is the raw error data and
usually null, except where the platform still identifies the order (a duplicate
`transfer` or `exchangeIndex` keeps `trade_id`).

A few points about the request and response types:

- Requests accept wire names (`trade_id`) or property names (`tradeId`);
  response objects use property names.
- Amounts are decimal strings. Never pass them through a float.
- `H5OrderCreateRequest` has no `app_id`; the SDK sends `appId` from the
  config in the `X-Cbc-App-Id` header.
- `TransferRequest` carries `is_tr_compliant` and `tr_transfer_id` for Travel
  Rule withdrawals.
- Optional fields are sent only when set, so a status filter of `0` is a real
  status, not an omission.
- `rateExchangeBatch`, `exchangeCoin` and `balanceGeneralAccount` take no request.
- `request($path, $body)` calls a path without a typed method; the response is
  an `ApiResponse` with the raw `data`.

## Result notifications and withdrawal review

```php
use WaasApi\ApiSdkPhp\V2\Callback;

$headers = Callback\Input::headers();
$body    = Callback\Input::read(fopen('php://input', 'rb')); // the raw body, never re-encoded
$method  = $_SERVER['REQUEST_METHOD'];

$notify = new Callback\NotifyServer([
    'platformPublicKey' => $platformRsaPublicKeyPem,
    'nonceStore'        => $myRedisNonceStore,
    'errorLog'          => function ($e) { error_log('notification rejected: ' . $e->getMessage()); },
]);
// /callback/deposit
$reply = $notify->handleDeposit($headers, $body, function (Callback\DepositNotify $n) {
    // credit once per $n->orderId
    return true;
}, $method);
// /callback/withdraw
$reply = $notify->handleWithdraw($headers, $body, function (Callback\WithdrawNotify $n) {
    // settle the local order found by $n->tradeId; $n->status is the outcome
    return true;
}, $method);

$risk = new Callback\WithdrawRiskServer([
    'riskPlatformPublicKey' => $platformRiskPublicKeyPem,  // verifies the request
    'riskPrivateKey'        => $merchantRiskPrivateKeyPem, // signs the reply
    'nonceStore'            => $myRedisNonceStore,
]);
// /withdraw/order/check
$reply = $risk->handle($headers, $body, function (Callback\WithdrawRiskRequest $req) {
    // compare $req with the local order found by $req->tradeId
    return new Callback\RiskResult(Callback\RiskStatus::APPROVE);
}, $method);

$exchangeRisk = new Callback\ExchangeRiskServer([
    'riskPlatformPublicKey' => $platformRiskPublicKeyPem,  // same keys as the withdrawal review
    'riskPrivateKey'        => $merchantRiskPrivateKeyPem,
    'nonceStore'            => $myRedisNonceStore,
]);
// /exchange/order/check
$reply = $exchangeRisk->handle($headers, $body, function (Callback\ExchangeRiskRequest $req) {
    // compare $req with the local exchange found by $req->tradeId;
    // compare $req->amount as a number ("2" for 2.00000000), e.g. with V2\Decimal::equals
    return new Callback\RiskResult(Callback\RiskStatus::APPROVE);
}, $method);

// every handler returns a Reply; send it back unchanged
http_response_code($reply->status);
foreach ($reply->headers as $name => $value) {
    header($name . ': ' . $value);
}
echo $reply->body;
```

- **Deposit has one handler.** The first notification and each confirmation
  update go to the same address; tell them apart by `status` and
  `confirmCount`.
- **The SDK writes the reply.** Returning `false`, or throwing, makes the
  platform retry. The reply satisfies every notifier's success check, including
  H5 orders, which look only at `status`.
- **The review reply is signed and echoes `trade_id`.** A failed verification
  answers 5400, which the platform retries, without calling your function. An
  exception in your function answers 2001. A status outside the `RiskStatus`
  constants is not sent: the SDK answers HTTP 500 instead, so the order stays
  pending rather than being closed. The exchange review has no 5004 (address
  mismatch).
- **Both reviews use the same risk key pairs.** The platform's `sign_version`
  switches callbacks, the withdrawal review and the exchange review to v2
  together.
- **`nonceStore` needs shared storage**, for example `Callback\RedisNonceStore`,
  which uses `SET nonce 1 NX EX <ttl>`. `MemoryNonceStore` lives in one process,
  so under PHP-FPM it deduplicates nothing across requests.

## Coverage

Interfaces of the public documentation and their SDK entry points:

| Chapter | v2 path (under `/shopapi/v2`) | SDK |
|---|---|---|
| Address | `address/getBatch` | `addressGetBatch` |
| | `address/list` | `addressList` |
| | `address/coinAccount` | `addressCoinAccount` |
| | `address/syncStatus` | `addressSyncStatus` |
| | `address/syncBatchStatus` | `addressSyncBatchStatus` |
| | `address/verifyAddress` | `addressVerifyAddress` |
| | `address/verifyAml` | `addressVerifyAml` |
| Deposit | `receive/list` | `receiveList` |
| | `balance` | `balance` |
| | deposit result notification | `NotifyServer::handleDeposit` |
| Withdrawal | `transfer` | `transfer` |
| | `transfer/list` | `transferList` |
| | withdrawal result notification | `NotifyServer::handleWithdraw` |
| Exchange | `exchange/index` | `exchangeIndex` |
| | `exchange/transfer` | `exchangeTransfer` |
| | `rate/exchange` | `rateExchange` |
| | `rate/exchangeBatch` | `rateExchangeBatch` |
| | `balance/exchange` | `balanceExchange` |
| | `exchange/coin` | `exchangeCoin` |
| | `exchange/list` | `exchangeList` |
| | exchange notification | `NotifyServer::handleExchange` |
| H5 deposit | `h5Order/create` | `h5OrderCreate` |
| | `h5Order/cancel` | `h5OrderCancel` |
| | `h5Order/detail` | `h5OrderDetail` |
| | order notification | `NotifyServer::handleH5Order` |
| Outer address | `outerAddress/add` | `outerAddressAdd` |
| | `outerAddress/delete` | `outerAddressDelete` |
| | transaction notification | `NotifyServer::handleOuterAddressTransaction` |
| Statistics | `balance/generalAccount` | `balanceGeneralAccount` |
| | `statistics/transfer` | `statisticsTransfer` |
| Other | `coin/list` | `coinList` |
| | `rate/index` | `rateIndex` |
| | `rate/fiatCurrency` | `rateFiatCurrency` |
| | `balance/transactionFee` | `balanceTransactionFee` |
| | `transfer/transactionFee` | `transferTransactionFee` |
| | `refund/list` | `refundList` |
| | `reconcile/balances` | `reconcileBalances` |
| | `reconcile/addressBalance` | `reconcileAddressBalance` |
| | `reconcile/withdrawal` | `reconcileWithdrawal` |
| Risk control | withdrawal review | `WithdrawRiskServer::handle` |
| | exchange review | `ExchangeRiskServer::handle` |

## Travel Rule (CODE)

Travel Rule uses the same `V2\Client` as the merchant APIs.

Only the CODE network is covered. A lookup that finds nothing means the address
is unknown to CODE, not that the transfer is exempt from the Travel Rule.

### Client

```php
$trClient = new Client([
    'appId'             => 'your-app-id',
    'baseURL'           => 'https://api.example.com/shopapi', // same as v1
    'privateKey'        => $merchantRsaPrivateKeyPem, // signs requests
    'platformPublicKey' => $platformRsaPublicKeyPem,  // verifies responses
]);
```

Five methods: `trVaspList`, `trVerifyAddress`, `trTransfer`, `trQueryTransfer`,
`trPostTransfer`. `trReceiveAudit`, `trRate` and `trHotWallet` are also
available.

`coin` is the platform coin name, which already identifies the chain (for example
`usdt_trc20`). Requests do not carry a separate `network` field. For inbound
callbacks, the platform sends `coin` when the provider supplied a network and
the platform resolved one exact match; otherwise it sends all usable candidates
in `possible_coins`. A currency with no usable mapping returns 612.

On any non-200 business status the platform sends no data object, and the
status arrives as an `ApiException`; a method that returns has `data`:

```php
try {
    $res = $trClient->trTransfer($request);
} catch (ApiException $e) {
    // $e->status gives the business status
    throw $e;
}
// $res->data is set here
```

### The directory is paged

`trVaspList` takes `vasp_id`, `keyword`, `page` and `page_size`, all optional,
and answers with `items` plus `page`, `page_size` and `total`. `total` counts
everything matching the query, so page until the collected items reach it:

```php
$res = $trClient->trVaspList(new Request\TrVaspListRequest(['keyword' => 'Upbit']));
foreach ($res->data->items as $vasp) {
    // $vasp->publicKeys[$i]->value is the base64 key; skip any whose expiresAt has passed
}
```

`page_size` defaults to 50; above 200 the platform answers 613.

`status` is the platform's local approval state, and only `ACTIVE` entries come
back. Provider service health is not part of the entry: it means something
different, this endpoint reads a local snapshot so the value would be stale, and
"reachable" reads too easily as "cleared to trade". Real availability shows up when
a request is made — `trTransfer` answers 617 with the provider's `errorType`.

### `result` is only ever verified or denied

`trTransfer` answers `Values::RESULT_VERIFIED` or `Values::RESULT_DENIED`
(`V2\TravelRule\Values`). A `PASS` route reports verified, and `MANUAL` reports
denied with `Values::REASON_LACK_OF_INFORMATION`. So branching on `result` alone
is correct:

```php
use WaasApi\ApiSdkPhp\V2\TravelRule\Values;

if ($res->data->result !== Values::RESULT_VERIFIED) {
    throw new RuntimeException("not cleared: {$res->data->reasonType} {$res->data->reasonMessage}");
}
```

`routeDecision` explains why, and is only needed to distinguish an actual refusal
by the counterparty from the platform holding the transfer for review. `MANUAL`
reads as denied because those transfers need a human before anything is released.

`reasonType` is always one of the `Values::REASON_*` constants. Platform internal
codes and non-standard provider codes are folded into `Values::REASON_UNKNOWN`
with the detail in `reasonMessage`, so there is no need to handle unknown values.

### Threshold declaration

Two forms are accepted, and `setThreshold`/`setThresholdExceeded` make sure only
one reaches the wire:

```php
$request->setThreshold(new Request\TrThresholdDeclaration([
    'exceeded' => true, 'jurisdiction' => 'EU',
    'rule_version' => '2026-01', 'value' => '1000', 'currency' => 'EUR',
    'fiat_amount' => '1.20', 'decided_at' => time(),
]));
```

`setThresholdExceeded(true)` is accepted too, but a bare boolean cannot answer
which jurisdiction and rule version the decision rested on, so the platform marks
those records as carrying incomplete evidence and an audit cannot reconstruct the
reasoning from platform data alone. Prefer the full form.

`beneficiary_type` omitted is treated as `UNKNOWN`, which routes to `MANUAL` rather
than releasing.

### Payload encryption

The IVMS101 payload is encrypted end to end. The platform never decrypts it, so
the completeness of the identity data is yours to get right. Required fields vary
by jurisdiction and by the counterparty's `alliance_name`: Europe, Hong Kong and
GTR require `dateOfBirth` for natural persons.

The ciphertext and the public key that produced it must always travel together.
The platform forwards that key to the counterparty as the identifier telling it
which private key to decrypt with, and a NaCl box ciphertext is bound to one key
pair. During a key rotation more than one key is valid at once, so sourcing them
independently is a real failure mode. `applyToTransfer` sets both:

```php
use WaasApi\ApiSdkPhp\V2\TravelRule\EncryptedPayload;

$encrypted = EncryptedPayload::encrypt($payload, $ownEd25519PrivateKey, $beneficiaryPubKey);
$encrypted->applyToTransfer($request); // sets payload and beneficiary_public_key together
```

Take `$beneficiaryPubKey` from `trVaspList` and pass it through unchanged: base64
is case sensitive.

### Status 618 is normal, not a fault

VASP lookups (`trVerifyAddress` without a beneficiary VASP, and
`trQueryTransfer`) are asynchronous underneath. The platform waits about 8
seconds inside the request, while the provider advises polling no more often than
every 10 seconds, so **the first call almost always returns 618**. Retry the same
call with the same parameters after 10 seconds or more; a background poll fills
in the result. Results are reused for 24 hours, so retrying does not burn quota.

```php
try {
    $res = $trClient->trVerifyAddress($request);
} catch (ApiException $e) {
    if ($e->isSearchProcessing()) {
        // no result yet, retry in >= 10s
    }
}
```

The SDK does not retry internally: a blocking wait of 10 seconds or more inside
your request handler is rarely what you want.

`trPostTransfer` needs a finished lookup, and never starts one itself — that would
put an asynchronous wait inside a data submission. Locate the deposit by `txid`
and `coin` (`beneficiary_address` optional). With no reusable result the platform
answers 615.

Encrypt the payload for `originatorPublicKey` from the lookup result.

### Callback server

Seven endpoints on your side. Signature verification runs inside each handler and
a failed verification never reaches your business function.

```php
use WaasApi\ApiSdkPhp\V2\Callback\TravelRuleServer;
use WaasApi\ApiSdkPhp\V2\TravelRule\MemorySigner;

$server = new TravelRuleServer([
    'platformPublicKey' => $platformRsaPublicKeyPem,
    'signer'            => new MemorySigner($ed25519PrivateKeyBase64),
    'signerPublicKey'   => $registeredEd25519PublicKeyBase64,
    'nonceStore'        => $myRedisNonceStore,
    'errorLog'          => function ($e) { error_log('callback rejected: ' . $e->getMessage()); },
]);

// TravelRuleServer::PATH_SIGNATURE
$reply = $server->handleSignature($headers, $body, $method);
// TravelRuleServer::PATH_TRANSFER
$reply = $server->handleTransfer($headers, $body, function (Callback\TrTransferCallbackRequest $req) {
    // your KYC and sanctions logic; return a Callback\TrTransferCallbackResponse
}, $method);
```

Points worth knowing:

- **`nonceStore` needs shared storage.** There is no default on purpose: a
  process local store silently stops deduplicating as soon as the endpoint runs
  more than one instance, or across PHP-FPM requests. A store outage causes the
  callback to be refused rather than accepted unchecked.
- **Callback responses are not signed.** The platform checks only that the body
  is valid JSON with an allowed `result`.
- **Decrypt with the key the platform supplies** (`originator_public_key`, or
  `beneficiary_public_key` on postTransfer). That is the key it actually verified
  the inbound request against. Never use the public key from a raw inbound
  header: it is attacker supplied, and verifying a signature with it proves
  nothing.
- **Inbound callbacks carry resolved coins or candidate sets.** `verifyAddress`
  supplies candidate main-chain names in `possible_coins`, which must each be
  checked against the decrypted address. `transfer` supplies either one exact
  platform `coin` when the counterparty sent a network, or configured deposit
  coin candidates in `possible_coins` when it did not. A `coin` in a postTransfer
  reply is mapped back before the counterparty sees it. When no usable candidate
  exists, the platform refuses with `NOT_SUPPORTED_SYMBOL` without calling the
  merchant callback.
- **`signature` sits on the critical path.** It is called before every outbound
  CodeVASP request, including each poll of an in-flight lookup. Roughly 100
  concurrent lookups produce about 10 calls per second, with a 3 second timeout.
- **`transferResult` must be idempotent.** It is retried more than once, and the
  same `transfer_id`, `status`, `txid` and `vout` must always produce the same
  answer.
- **`transferResult` only reports inbound on-chain outcomes.** It carries
  `status=confirmed` or `status=canceled`, together with the provider transfer
  identifier and applicable chain fields. Authorisation for your own withdrawal
  is returned synchronously by the platform `transfer` endpoint and is not sent
  through this callback.
- **The reason field is `reason_message` everywhere**, in both directions and on
  every callback. It matters most on responses: the platform reads only that name,
  so a body built by hand under any other one loses the detail with no error, and
  the counterparty receives an empty reason.
- **Health covers the signer.** Otherwise a broken signer is only discovered
  when a live compliance exchange needs a signature. `handleHealth` signs a probe
  and verifies it against `signerPublicKey`, which also catches a signer wired to
  the wrong key, and answers HTTP 503 when it fails.

### Private key handling

Ed25519 private keys must live in an approved HSM, KMS or key management system,
and must never appear in a config file, log, ticket or chat message. The platform
neither receives nor stores them. Implement `TravelRule\SignerInterface` against
your key system; `MemorySigner` is for tests and self managed storage.

One asymmetry to plan around: a **sign-only** HSM or KMS is sufficient for the
`signature` callback, but it **cannot decrypt payloads**. NaCl box needs the raw
private key for scalar multiplication, which is not a signing operation. If the
key must never leave the hardware, payload encryption and decryption have to
happen inside that boundary too, with only ciphertext crossing it.

> See more examples in `examples/v2/travelrule-outbound.php`,
> `examples/v2/travelrule-inbound.php` and `examples/v2/travelrule-post-transfer.php`.

## Existing integrations

Merchants already integrated on the earlier signature scheme: see
[README_v1.md](README_v1.md), which also covers switching over.
