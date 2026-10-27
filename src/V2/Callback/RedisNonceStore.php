<?php

namespace WaasApi\ApiSdkPhp\V2\Callback;

/** phpredis-compatible SET NX EX. Prefix should isolate merchant and verification key. */
final class RedisNonceStore implements NonceStoreInterface
{
    /**
     * @readonly
     * @var object
     */
    private $redis;
    /**
     * @readonly
     * @var string
     */
    private $prefix;
    /**
     * @param object $redis
     */
    public function __construct($redis, $prefix)
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }
    /**
     * @param string $nonce
     * @param int $ttl
     */
    public function checkAndMark($nonce, $ttl)
    {
        if ($this->redis->set($this->prefix . hash('sha256', $nonce), '1', ['nx', 'ex' => $ttl]) !== true) {
            throw new \RuntimeException('Nonce reused or nonce storage unavailable');
        }
    }
}
