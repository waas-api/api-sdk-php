<?php

namespace WaasApi\ApiSdkPhp\V2\Callback;

interface NonceStoreInterface
{
    /** Atomically consume a nonce for TTL seconds; throw on reuse OR storage failure.
     * @param string $nonce
     * @param int $ttl */
    public function checkAndMark($nonce, $ttl);
}
