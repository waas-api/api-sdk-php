<?php

require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/EventStore.php';
use WaasApi\ApiSdkPhp\V2\Callback;
use WaasApi\ApiSdkPhp\V2\TravelRule\EncryptedPayload;
use WaasApi\ApiSdkPhp\V2\TravelRule\Ivms101\IVMS101;
/** The server invokes this closure only after platform RSA verification. */
function inboundTransferHandler($ownKey, callable $merchantAudit)
{
    return static function (Callback\TrTransferCallbackRequest $request) use ($ownKey, $merchantAudit) {
        // Use the verified platform body key, never a public key from arbitrary inbound headers.
        $identity = EncryptedPayload::decrypt($request->payload, $ownKey, $request->originatorPublicKey);
        // Application resolves coin/address, compares KYC and checks sanctions.
        // Audit returns a typed response and the merchant's own IVMS101 for encryption.
        list($reply, $ownIdentity) = $merchantAudit($request, $identity);
        if (!$reply instanceof Callback\TrTransferCallbackResponse || !$ownIdentity instanceof IVMS101) {
            throw new RuntimeException('Invalid merchant audit response');
        }
        $reply->payload = EncryptedPayload::encrypt($ownIdentity, $ownKey, $request->originatorPublicKey)->ciphertext;
        return $reply;
    };
}
function inboundTransferResultHandler(EventStore $events, callable $recordInboundResult)
{
    return static function (Callback\TrTransferResultCallbackRequest $request) use ($events, $recordInboundResult) {
        $key = 'tr-result:' . hash('sha256', json_encode([$request->transferId, $request->status, $request->txid, $request->vout], 0));
        $reply = $events->once($key, static function (PDO $db) use ($request, $recordInboundResult) {
            $recordInboundResult($db, $request);
            return ['result' => 'normal', 'reason_type' => '', 'reason_message' => ''];
        });
        return new Callback\TrTransferResultCallbackResponse($reply);
    };
}
function inboundPostTransferHandler($ownKey, callable $merchantAudit)
{
    return static function (Callback\TrPostTransferCallbackRequest $request) use ($ownKey, $merchantAudit) {
        $identity = EncryptedPayload::decrypt($request->payload, $ownKey, $request->beneficiaryPublicKey);
        list($reply, $ownIdentity) = $merchantAudit($request, $identity);
        if (!$reply instanceof Callback\TrPostTransferCallbackResponse || !$ownIdentity instanceof IVMS101) {
            throw new RuntimeException('Invalid supplementary audit response');
        }
        $reply->payload = EncryptedPayload::encrypt($ownIdentity, $ownKey, $request->beneficiaryPublicKey)->ciphertext;
        return $reply;
    };
}
