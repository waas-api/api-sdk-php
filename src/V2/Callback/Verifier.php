<?php

namespace WaasApi\ApiSdkPhp\V2\Callback;

use WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature;
use WaasApi\ApiSdkPhp\V2\Exception\SignatureException;
final class Verifier
{
    /**
     * @readonly
     * @var resource|\OpenSSLAsymmetricKey
     */
    private $publicKey;
    /**
     * @readonly
     * @var int
     */
    private $window;
    /**
     * @readonly
     * @var \WaasApi\ApiSdkPhp\V2\Callback\NonceStoreInterface|null
     */
    private $nonceStore;
    /**
     * @readonly
     * @var callable|null
     */
    private $errorLog;
    public function __construct(array $config)
    {
        if (isset($config['errorLog']) && !is_callable($config['errorLog'])) {
            throw new \InvalidArgumentException('errorLog must be callable');
        }
        $this->publicKey = RsaSignature::publicKey(isset($config['platformPublicKey']) ? $config['platformPublicKey'] : '');
        $this->window = (isset($config['timestampWindow']) ? $config['timestampWindow'] : 0) > 0 ? $config['timestampWindow'] : 300;
        $this->nonceStore = isset($config['nonceStore']) ? $config['nonceStore'] : null;
        $this->errorLog = isset($config['errorLog']) ? $config['errorLog'] : null;
    }
    public function verify(array $headers, $body)
    {
        $nonce = RsaSignature::verifyBody($body, $headers, $this->publicKey, $this->window);
        if ($this->nonceStore !== null) {
            try {
                $this->nonceStore->checkAndMark($nonce, 2 * $this->window);
            } catch (\Exception $e) {
                throw new SignatureException('Callback nonce rejected', 0, $e);
            } catch (\Throwable $e) {
                throw new SignatureException('Callback nonce rejected', 0, $e);
            }
        }
    }
    public function log($error)
    {
        if ($this->errorLog !== null) {
            try {
                call_user_func($this->errorLog, $error);
            } catch (\Exception $exception) {
                // Logging must not change callback acknowledgement semantics.
            } catch (\Throwable $exception) {
                // Logging must not change callback acknowledgement semantics.
            }
        }
    }
}
