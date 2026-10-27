<?php

use WaasApi\ApiSdkPhp\V2\Json;
use WaasApi\ApiSdkPhp\V2\Request;
use WaasApi\ApiSdkPhp\V2\Response;
use WaasApi\ApiSdkPhp\V2\Crypto\CodeVasp;
use WaasApi\ApiSdkPhp\V2\Exception\ProtocolException;
use WaasApi\ApiSdkPhp\V2\TravelRule\EncryptedPayload;
use WaasApi\ApiSdkPhp\V2\TravelRule\Ivms101\IVMS101;
use WaasApi\ApiSdkPhp\V2\TravelRule\Ivms101\Beneficiary;
// Merchant subclasses outside the SDK namespace must keep validation and reply defaults.
class MerchantTransferRequest extends Request\TransferRequest
{
}
class MerchantSignatureResponse extends \WaasApi\ApiSdkPhp\V2\Callback\TrSignatureResponse
{
}
return ['DTOs preserve false, zero, explicit null, omissions and exact decimal strings' => static function () {
    same('{"status":0}', Json::encode(new Request\TransferListRequest(['status' => 0])));
    same('{"status":null}', Json::encode(new Request\TransferListRequest(['status' => null])));
    same('{}', Json::encode(new Request\TransferListRequest()));
    $values = fixture('request_data')->{'/transfer'};
    $r = new Request\TransferRequest($values);
    same(false, $r->isTrCompliant);
    same('2.00000000', $r->amount);
    truth(strpos(Json::encode($r), '"is_tr_compliant":false') !== false);
    $values->amount = 2.0;
    raises(ProtocolException::class, function () use ($values) {
        return new Request\TransferRequest($values);
    });
    raises(InvalidArgumentException::class, function () {
        return Json::encode(new Request\TransferRequest());
    });
    raises(InvalidArgumentException::class, function () {
        return new Request\TrQueryTransferRequest(['network' => 'TRON']);
    });
    raises(ProtocolException::class, function () {
        return new Request\TransferListRequest(['status' => false]);
    });
}, 'Threshold helpers clear the alternate form and preserve false in nested evidence' => static function () {
    $r = new Request\TrTransferRequest(fixture('request_data')->{'/travelRule/transfer'});
    $d = new Request\TrThresholdDeclaration(['exceeded' => false, 'jurisdiction' => 'SG', 'rule_version' => '2026', 'value' => '1500', 'currency' => 'SGD', 'fiat_amount' => '2.6', 'decided_at' => time()]);
    $r->setThreshold($d);
    $wire = Json::object(Json::encode($r));
    same(false, $wire->threshold->exceeded);
    truth(!property_exists($wire, 'is_exceeding_threshold'));
    $r->setThresholdExceeded(false);
    $wire = Json::object(Json::encode($r));
    same(false, $wire->is_exceeding_threshold);
    truth(!property_exists($wire, 'threshold'));
    $r->threshold = $d;
    raises(InvalidArgumentException::class, function () use ($r) {
        return Json::encode($r);
    });
    raises(InvalidArgumentException::class, function () {
        return Json::encode(new Request\TrThresholdDeclaration(['exceeded' => false]));
    });
}, 'Conditional request groups and compliant withdrawal identifiers fail locally' => static function () {
    $r = new Request\TrVerifyAddressRequest(['originator_vasp_id' => 'code:own', 'coin' => 'usdt_trc20', 'address' => 'TA']);
    Json::encode($r);
    $r->beneficiaryVaspId = 'code:target';
    raises(InvalidArgumentException::class, function () use ($r) {
        return Json::encode($r);
    });
    $r->payload = 'cipher';
    $r->beneficiaryPublicKey = 'public';
    Json::encode($r);
    $r = new Request\TransferRequest(fixture('request_data')->{'/transfer'});
    $r->isTrCompliant = true;
    raises(InvalidArgumentException::class, function () use ($r) {
        return Json::encode($r);
    });
    $r->trTransferId = 'tr1';
    Json::encode($r);
    $r->amount = '0.123456789';
    raises(InvalidArgumentException::class, function () use ($r) {
        return Json::encode($r);
    });
    $r = new MerchantTransferRequest(fixture('request_data')->{'/transfer'});
    $r->amount = '0.123456789';
    raises(InvalidArgumentException::class, function () use ($r) {
        return Json::encode($r);
    });
    same('{"signature":""}', Json::encode(new MerchantSignatureResponse()));
    raises(InvalidArgumentException::class, function () {
        return Json::encode(new Request\OuterAddressAddRequest(['chain' => 'trx', 'addresses' => array_fill(0, 101, 'TA')]));
    });
}, 'Response data shapes and huge integer IDs remain distinguishable' => static function () {
    $r = new Response\TrVaspListResponse('{"status":200,"data":{"items":[],"total":9223372036854775808}}');
    same('9223372036854775808', $r->data->total);
    same([], (new Response\AddressGetBatchResponse('{"status":200,"data":null}'))->data);
    same('eosaccount', (new Response\AddressCoinAccountResponse('{"status":200,"data":"eosaccount"}'))->data);
    same(null, (new Response\AddressVerifyAddressResponse('{"status":200}'))->data);
    raises(ProtocolException::class, function () {
        return new Response\BalanceResponse('{"status":200,"data":null}');
    });
    raises(ProtocolException::class, function () {
        return new Response\AddressCoinAccountResponse('{"status":200,"data":{}}');
    });
}, 'IVMS101 preserves case and nested natural/legal identities and emits beneficiaryPersons []' => static function () {
    $b = new Beneficiary(['accountNumber' => ['TA']]);
    same('{"beneficiaryPersons":[],"accountNumber":["TA"]}', Json::encode($b));
    same('{"beneficiaryPersons":[]}', Json::encode(new Beneficiary(['beneficiaryPersons' => null])));
    raises(InvalidArgumentException::class, function () {
        return Json::encode(new \WaasApi\ApiSdkPhp\V2\TravelRule\Ivms101\Person(['naturalPerson' => [], 'legalPerson' => []]));
    });
    $payload = new IVMS101(['Originator' => ['originatorPersons' => [['naturalPerson' => ['name' => ['nameIdentifier' => [['primaryIdentifier' => 'Doe', 'secondaryIdentifier' => 'Jane', 'nameIdentifierType' => 'LEGL']]], 'dateAndPlaceOfBirth' => ['dateOfBirth' => '1990-01-01', 'placeOfBirth' => 'SG'], 'customerIdentification' => 'customer1', 'nationalIdentification' => ['nationalIdentifier' => 'ID1'], 'geographicAddress' => [['country' => 'SG']]]]], 'accountNumber' => ['TB']], 'Beneficiary' => $b, 'OriginatingVASP' => ['originatingVASP' => ['legalPerson' => ['name' => ['nameIdentifier' => [['legalPersonName' => 'Merchant Ltd']]], 'nationalIdentification' => ['nationalIdentifier' => 'REG1', 'nationalIdentifierType' => 'RAID'], 'geographicAddress' => [['addressLine' => ['street1'], 'country' => 'SG']]]]]]);
    $wire = Json::encode($payload);
    truth(strpos($wire, '"Originator"') !== false);
    truth(strpos($wire, '"OriginatingVASP"') !== false);
    same($wire, Json::encode(IVMS101::fromJson($wire)));
    $f = fixture('codevasp');
    $sealed = EncryptedPayload::encrypt($payload, $f->fixtureSeedB64, $f->fixtureReceiverPubKeyB64);
    same($wire, Json::encode(EncryptedPayload::decrypt($sealed->ciphertext, $f->fixtureReceiverSeedB64, $f->fixturePubKeyB64)));
    foreach ([['applyToTransfer', Request\TrTransferRequest::class, 'beneficiaryPublicKey'], ['applyToVerifyAddress', Request\TrVerifyAddressRequest::class, 'beneficiaryPublicKey'], ['applyToPostTransfer', Request\TrPostTransferRequest::class, 'originatorPublicKey']] as list($method, $type, $field)) {
        $request = new $type();
        $sealed->{$method}($request);
        same($sealed->ciphertext, $request->payload);
        same($f->fixtureReceiverPubKeyB64, $request->{$field});
    }
}];
