<?php

namespace WaasApi\ApiSdkPhp\V2\Crypto;

use WaasApi\ApiSdkPhp\V2\Exception\SignatureException;
/** Ed25519 signatures and NaCl box (X25519 + XSalsa20-Poly1305), compatible with Go. */
final class CodeVasp
{
    /**
     * @readonly
     * @var string
     */
    private $secret;
    public function __construct($privateKeyBase64)
    {
        self::requireSodium();
        $raw = self::decode($privateKeyBase64);
        if (strlen($raw) !== 32 && strlen($raw) !== 64) {
            throw new SignatureException('Ed25519 private key must be a 32-byte seed or 64-byte full key');
        }
        $derived = sodium_crypto_sign_secretkey(sodium_crypto_sign_seed_keypair(substr($raw, 0, 32)));
        if (strlen($raw) === 64 && !hash_equals($derived, $raw)) {
            throw new SignatureException('Ed25519 full key has an inconsistent public half');
        }
        $this->secret = $derived;
    }
    private static function requireSodium()
    {
        if (!function_exists('sodium_crypto_sign_detached')) {
            throw new SignatureException('Travel Rule cryptography requires ext-sodium or Composer sodium_compat');
        }
    }
    public static function decode($base64, $length = null)
    {
        $raw = base64_decode($base64, true);
        if ($raw === false || $length !== null && strlen($raw) !== $length) {
            throw new SignatureException('Invalid Base64 value or key length');
        }
        return $raw;
    }
    public function publicKey()
    {
        return base64_encode(sodium_crypto_sign_publickey_from_secretkey($this->secret));
    }
    public function sign($message)
    {
        return base64_encode(sodium_crypto_sign_detached($message, $this->secret));
    }
    public static function verify($message, $signature, $publicKey)
    {
        self::requireSodium();
        if (!sodium_crypto_sign_verify_detached(self::decode($signature, 64), $message, self::decode($publicKey, 32))) {
            throw new SignatureException('Ed25519 signature verification failed');
        }
    }
    private function boxKeypair($remotePublicKey)
    {
        try {
            $secret = sodium_crypto_sign_ed25519_sk_to_curve25519($this->secret);
            $public = sodium_crypto_sign_ed25519_pk_to_curve25519(self::decode($remotePublicKey, 32));
            return sodium_crypto_box_keypair_from_secretkey_and_publickey($secret, $public);
        } catch (\SodiumException $e) {
            throw new SignatureException('Cannot convert Ed25519 keys to X25519', 0, $e);
        }
    }
    /** Return Base64(24-byte random nonce || authenticated ciphertext). */
    public function encrypt($plaintext, $remotePublicKey)
    {
        $nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
        return base64_encode($nonce . sodium_crypto_box($plaintext, $nonce, $this->boxKeypair($remotePublicKey)));
    }
    public function decrypt($ciphertext, $remotePublicKey)
    {
        $raw = self::decode($ciphertext);
        if (strlen($raw) < SODIUM_CRYPTO_BOX_NONCEBYTES + SODIUM_CRYPTO_BOX_MACBYTES) {
            throw new SignatureException('NaCl box ciphertext is too short');
        }
        $nonce = substr($raw, 0, SODIUM_CRYPTO_BOX_NONCEBYTES);
        $plain = sodium_crypto_box_open((string) substr($raw, SODIUM_CRYPTO_BOX_NONCEBYTES), $nonce, $this->boxKeypair($remotePublicKey));
        if ($plain === false) {
            throw new SignatureException('NaCl box authentication failed');
        }
        return $plain;
    }
}
