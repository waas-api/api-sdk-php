<?php

namespace WaasApi\ApiSdkPhp\V2\Http;

final class Response
{
    /**
     * @readonly
     * @var int
     */
    public $status;
    /**
     * @readonly
     * @var mixed[]
     */
    public $headers;
    /**
     * @readonly
     * @var string
     */
    public $body;
    public function __construct($status, array $headers, $body)
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }
}
