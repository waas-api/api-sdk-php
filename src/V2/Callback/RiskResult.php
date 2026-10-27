<?php

namespace WaasApi\ApiSdkPhp\V2\Callback;

final class RiskResult
{
    /**
     * @readonly
     * @var int
     */
    public $status;
    /**
     * @readonly
     * @var string
     */
    public $msg = '';
    public function __construct($status, $msg = '')
    {
        $this->status = $status;
        $this->msg = $msg;
    }
}
