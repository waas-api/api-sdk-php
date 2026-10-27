<?php

namespace WaasApi\ApiSdkPhp\V2\Callback;

final class RiskStatus
{
    const APPROVE = 200;
    const PENDING = 2001;
    const SIGNATURE_INVALID = 5400;
    const TIME_INVALID = 5401;
    const ORDER_NOT_FOUND = 5402;
    const TRADE_ID_MISMATCH = 5001;
    const AMOUNT_MISMATCH = 5002;
    const USER_ID_MISMATCH = 5003;
    const ADDRESS_MISMATCH = 5004;
    const COIN_MISMATCH = 5005;
    const REJECT = 5006;
    const OTHER = 5007;
}
