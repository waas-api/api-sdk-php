<?php

namespace WaasApi\ApiSdkPhp\V2;

/** Rules match by instanceof so merchant subclasses of a DTO are validated too. */
final class RequestValidator
{
    public static function validate(RequestDto $request, array $data)
    {
        foreach (['amount', 'ledger_amount', 'ledger_balance', 'limit_price', 'trade_price', 'value', 'fiat_amount'] as $field) {
            if (isset($data[$field]) && !Decimal::isValid($data[$field])) {
                throw new \InvalidArgumentException($field . ' must be a decimal string');
            }
        }
        if (($request instanceof Request\TransferRequest || $request instanceof Request\TrTransferRequest) && !preg_match('/^\d{1,30}(?:\.\d{1,8})?$/D', $data['amount'])) {
            throw new \InvalidArgumentException('amount allows at most 30 integer and 8 fractional digits');
        }
        if ($request instanceof Request\TransferRequest) {
            if (!preg_match('/^[A-Za-z0-9]{1,32}$/D', $data['trade_id'])) {
                throw new \InvalidArgumentException('trade_id must be 1–32 letters or digits');
            }
            if ((isset($data['is_tr_compliant']) ? $data['is_tr_compliant'] : false) && (isset($data['tr_transfer_id']) ? $data['tr_transfer_id'] : '') === '') {
                throw new \InvalidArgumentException('tr_transfer_id required for compliant withdrawal');
            }
        }
        if ($request instanceof Request\TrVerifyAddressRequest) {
            $count = 0;
            foreach (['beneficiary_vasp_id', 'payload', 'beneficiary_public_key'] as $field) {
                $count += isset($data[$field]) && $data[$field] !== '' ? 1 : 0;
            }
            if ($count !== 0 && $count !== 3) {
                throw new \InvalidArgumentException('beneficiary_vasp_id, payload and beneficiary_public_key must be supplied together');
            }
        }
        if ($request instanceof Request\TrTransferRequest) {
            if (isset($data['threshold'], $data['is_exceeding_threshold'])) {
                throw new \InvalidArgumentException('threshold and is_exceeding_threshold are mutually exclusive');
            }
            if (isset($data['beneficiary_type']) && !in_array($data['beneficiary_type'], ['VASP', 'SELF_HOSTED', 'UNKNOWN'], true)) {
                throw new \InvalidArgumentException('Invalid beneficiary_type');
            }
        }
        if ($request instanceof Request\TrReceiveAuditRequest) {
            if (!in_array($data['result'], ['PASS', 'REJECT'], true) || $data['result'] === 'REJECT' && (isset($data['reason_type']) ? $data['reason_type'] : '') === '') {
                throw new \InvalidArgumentException('Audit requires PASS or REJECT with reason_type');
            }
        }
        if (($request instanceof Request\OuterAddressAddRequest || $request instanceof Request\OuterAddressDeleteRequest) && (count($data['addresses']) < 1 || count($data['addresses']) > 100)) {
            throw new \InvalidArgumentException('addresses must contain 1–100 addresses');
        }
        if ($request instanceof Request\ExchangeIndexRequest && (isset($data['type']) ? $data['type'] : 1) === 0 && (isset($data['limit_price']) ? $data['limit_price'] : '') === '') {
            throw new \InvalidArgumentException('limit_price required for limit orders');
        }
    }
}
