<?php

namespace WaasApi\ApiSdkPhp\V2\Callback;

/** For tests and a single long-lived process only; use shared storage across workers. */
final class MemoryNonceStore implements NonceStoreInterface
{
    /**
     * @var mixed[]
     */
    private $nonces = [];
    /**
     * @param string $nonce
     * @param int $ttl
     */
    public function checkAndMark($nonce, $ttl)
    {
        $now = time();
        foreach ($this->nonces as $key => $expiry) {
            if ($expiry <= $now) {
                unset($this->nonces[$key]);
            }
        }
        if (isset($this->nonces[$nonce])) {
            throw new \RuntimeException('Nonce already consumed');
        }
        $this->nonces[$nonce] = $now + $ttl;
    }
}
