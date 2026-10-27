<?php

require_once dirname(__DIR__) . '/bootstrap.php';
use WaasApi\ApiSdkPhp\V2\Client;
use WaasApi\ApiSdkPhp\V2\Request;
use WaasApi\ApiSdkPhp\V2\Response\TransferResponse;
use WaasApi\ApiSdkPhp\V2\TravelRule\EncryptedPayload;
use WaasApi\ApiSdkPhp\V2\TravelRule\Ivms101\IVMS101;
/** Call from merchant application after producing its KYC and threshold assessment. */
function sendCompliantWithdrawal(Client $client, $ownVaspId, $targetVaspId, $ownEd25519Key, IVMS101 $identity, Request\TrThresholdDeclaration $threshold, $coin, $amount, $fiatTotal, $fiatCurrency, $fromAddress, $toAddress, $tradeId, $tag = '', $userId = '')
{
    $directory = $client->trVaspList(new Request\TrVaspListRequest(['vasp_id' => $targetVaspId]));
    $target = isset($directory->data->items[0]) ? $directory->data->items[0] : null;
    $publicKey = null;
    // Skip expired keys; expires_at is RFC3339 and absent when the provider published no expiry.
    foreach ($target !== null && is_array($target->publicKeys) ? $target->publicKeys : [] as $key) {
        $expiry = isset($key->expiresAt) && $key->expiresAt !== '' ? date_create($key->expiresAt, new DateTimeZone('UTC')) : null;
        if (isset($key->value) && $key->value !== '' && ($expiry === null || $expiry !== false && $expiry->getTimestamp() > time())) {
            $publicKey = $key->value;
            break;
        }
    }
    if ($publicKey === null) {
        throw new RuntimeException('Target VASP has no usable public key');
    }
    $sealed = EncryptedPayload::encrypt($identity, $ownEd25519Key, $publicKey);
    $verify = new Request\TrVerifyAddressRequest(['originator_vasp_id' => $ownVaspId, 'beneficiary_vasp_id' => $targetVaspId, 'coin' => $coin, 'address' => $toAddress, 'tag' => $tag]);
    $sealed->applyToVerifyAddress($verify);
    if ($client->trVerifyAddress($verify)->data->result !== 'valid') {
        throw new RuntimeException('Address not verified');
    }
    $authorization = new Request\TrTransferRequest(['originator_vasp_id' => $ownVaspId, 'beneficiary_vasp_id' => $targetVaspId, 'shop_order_id' => $tradeId, 'coin' => $coin, 'amount' => $amount, 'trade_price' => $fiatTotal, 'trade_currency' => $fiatCurrency, 'originator_address' => $fromAddress, 'beneficiary_address' => $toAddress, 'tag' => $tag, 'beneficiary_type' => 'VASP']);
    $authorization->setThreshold($threshold);
    $sealed->applyToTransfer($authorization);
    $result = $client->trTransfer($authorization)->data;
    if ($result->result !== 'verified' || !in_array($result->routeDecision, ['TR_CODE', 'PASS'], true)) {
        throw new RuntimeException('Withdrawal held: ' . (isset($result->reasonMessage) ? $result->reasonMessage : 'manual review required'));
    }
    // PASS also reports verified and keeps a transfer record, so both routes bind tr_transfer_id.
    // trTransfer handles compliance only. This separate call initiates the actual withdrawal.
    // Reuse the exact coin, decimal amount, destination and memo of the authorization.
    return $client->transfer(new Request\TransferRequest(['coin' => $coin, 'amount' => $amount, 'address' => $toAddress, 'memo' => $tag, 'trade_id' => $tradeId, 'is_tr_compliant' => true, 'tr_transfer_id' => $result->transferId, 'user_id' => $userId]));
}
