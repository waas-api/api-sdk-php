<?php

namespace WaasApi\ApiSdkPhp\V2\TravelRule;

use WaasApi\ApiSdkPhp\V2\Crypto\CodeVasp;
final class MemorySigner implements SignerInterface
{
    /**
     * @readonly
     * @var \WaasApi\ApiSdkPhp\V2\Crypto\CodeVasp
     */
    private $crypto;
    public function __construct($privateKeyBase64)
    {
        $this->crypto = new CodeVasp($privateKeyBase64);
    }
    /**
     * @param string $message
     */
    public function sign($message)
    {
        return $this->crypto->sign($message);
    }
    public function publicKey()
    {
        return $this->crypto->publicKey();
    }
}
