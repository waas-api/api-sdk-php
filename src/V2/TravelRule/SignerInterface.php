<?php

namespace WaasApi\ApiSdkPhp\V2\TravelRule;

interface SignerInterface
{
    /** Sign exact raw bytes and return standard Base64 Ed25519 signature.
     * @param string $message */
    public function sign($message);
}
