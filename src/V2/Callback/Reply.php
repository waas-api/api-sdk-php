<?php

namespace WaasApi\ApiSdkPhp\V2\Callback;

use WaasApi\ApiSdkPhp\V2\Json;
final class Reply
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
    /**
     * @param mixed $payload
     */
    public static function json($payload, $status = 200, array $headers = [])
    {
        return new self($status, ['Content-Type' => 'application/json; charset=utf-8'] + $headers, Json::encode($payload));
    }
}
