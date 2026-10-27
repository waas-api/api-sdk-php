<?php

namespace WaasApi\ApiSdkPhp\V2\Exception;

class TransportException extends \RuntimeException
{
    /**
     * @readonly
     * @var int|null
     */
    public $httpStatus;
    /**
     * @readonly
     * @var string|null
     */
    public $responseBody;
    public function __construct($message, $httpStatus = null, $responseBody = null, $previous = null)
    {
        $this->httpStatus = $httpStatus;
        $this->responseBody = $responseBody;
        parent::__construct($message, 0, $previous);
    }
}
