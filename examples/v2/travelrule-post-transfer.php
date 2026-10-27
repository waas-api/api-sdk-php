<?php

require_once dirname(__DIR__) . '/bootstrap.php';
use WaasApi\ApiSdkPhp\V2\Client;
use WaasApi\ApiSdkPhp\V2\Exception\ApiException;
use WaasApi\ApiSdkPhp\V2\Request\TrQueryTransferRequest;
use WaasApi\ApiSdkPhp\V2\Request\TrPostTransferRequest;
use WaasApi\ApiSdkPhp\V2\Response\TrPostTransferResponse;
use WaasApi\ApiSdkPhp\V2\TravelRule\EncryptedPayload;
use WaasApi\ApiSdkPhp\V2\TravelRule\Ivms101\IVMS101;
function submitDepositIdentity(Client $client, $coin, $txid, $address, IVMS101 $identity, $ownKey, $maxPolls = 6, $tag = '')
{
    $query = new TrQueryTransferRequest(['coin' => $coin, 'txid' => $txid, 'beneficiary_address' => $address, 'tag' => $tag]);
    for ($attempt = 0; $attempt < $maxPolls; $attempt++) {
        try {
            $result = $client->trQueryTransfer($query)->data;
            break;
        } catch (ApiException $e) {
            if (!$e->isSearchProcessing() || $attempt === $maxPolls - 1) {
                throw $e;
            }
            // Each read poll is a new signed HTTP call; reuse the business parameters only.
            sleep(10);
        }
    }
    if (!isset($result) || $result->result !== 'valid' || !$result->originatorPublicKey) {
        throw new RuntimeException('Completed deposit lookup with originator public key required');
    }
    $request = new TrPostTransferRequest(['coin' => $coin, 'txid' => $txid, 'beneficiary_address' => $address, 'tag' => $tag]);
    EncryptedPayload::encrypt($identity, $ownKey, $result->originatorPublicKey)->applyToPostTransfer($request);
    // Locate by completed query's txid + coin; never pass its request_id.
    return $client->trPostTransfer($request);
}
