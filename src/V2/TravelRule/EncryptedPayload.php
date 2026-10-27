<?php

namespace WaasApi\ApiSdkPhp\V2\TravelRule;

use WaasApi\ApiSdkPhp\V2\Crypto\CodeVasp;
use WaasApi\ApiSdkPhp\V2\Json;
use WaasApi\ApiSdkPhp\V2\Request\TrPostTransferRequest;
use WaasApi\ApiSdkPhp\V2\Request\TrTransferRequest;
use WaasApi\ApiSdkPhp\V2\Request\TrVerifyAddressRequest;
use WaasApi\ApiSdkPhp\V2\TravelRule\Ivms101\IVMS101;
/** A ciphertext and the exact counterparty public key used to encrypt it. */
final class EncryptedPayload
{
    /**
     * @readonly
     * @var string
     */
    private $ciphertext;
    /**
     * @readonly
     * @var string
     */
    private $remotePublicKey;
    public function __get($name)
    {
        if ($name === 'ciphertext' || $name === 'remotePublicKey') {
            return $this->{$name};
        }
        throw new \OutOfBoundsException('Unknown encrypted payload property: ' . $name);
    }
    public function __isset($name)
    {
        return $name === 'ciphertext' || $name === 'remotePublicKey';
    }
    public function __set($name, $value)
    {
        throw new \LogicException('Encrypted payload and key are immutable');
    }
    /** Also accepts externally encrypted payloads from an HSM/KMS implementation. */
    public function __construct($ciphertext, $remotePublicKey)
    {
        $this->ciphertext = $ciphertext;
        $this->remotePublicKey = $remotePublicKey;
        if (strlen(CodeVasp::decode($ciphertext)) < 40) {
            throw new \InvalidArgumentException('Ciphertext must include nonce and MAC');
        }
        CodeVasp::decode($remotePublicKey, 32);
    }
    public static function encrypt(IVMS101 $payload, $ownPrivateKey, $remotePublicKey)
    {
        return self::encryptBytes(Json::encode($payload), $ownPrivateKey, $remotePublicKey);
    }
    public static function encryptBytes($plaintext, $ownPrivateKey, $remotePublicKey)
    {
        return new self((new CodeVasp($ownPrivateKey))->encrypt($plaintext, $remotePublicKey), $remotePublicKey);
    }
    public function applyToTransfer(TrTransferRequest $request)
    {
        $request->payload = $this->ciphertext;
        $request->beneficiaryPublicKey = $this->remotePublicKey;
    }
    public function applyToVerifyAddress(TrVerifyAddressRequest $request)
    {
        $request->payload = $this->ciphertext;
        $request->beneficiaryPublicKey = $this->remotePublicKey;
    }
    public function applyToPostTransfer(TrPostTransferRequest $request)
    {
        $request->payload = $this->ciphertext;
        $request->originatorPublicKey = $this->remotePublicKey;
    }
    public static function decrypt($ciphertext, $ownPrivateKey, $remotePublicKey)
    {
        return IVMS101::fromJson((new CodeVasp($ownPrivateKey))->decrypt($ciphertext, $remotePublicKey));
    }
}
