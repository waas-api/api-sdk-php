<?php

namespace WaasApi\ApiSdkPhp\V2\Crypto;

use WaasApi\ApiSdkPhp\V2\Exception\SignatureException;
final class RsaSignature
{
    const ALGORITHM = 'RSA-SHA256';
    const SIGNED_HEADERS = ['X-Cbc-Timestamp', 'X-Cbc-Nonce', 'X-Cbc-Sign-Alg', 'X-Cbc-Signature'];
    /** Accepts unencrypted PKCS#8 and, for V1 keys and OpenSSL 1.0 exports, unencrypted PKCS#1 PEM. */
    public static function privateKey($pem)
    {
        $pem = trim($pem);
        $pkcs1 = strncmp($pem, '-----BEGIN RSA PRIVATE KEY-----', strlen('-----BEGIN RSA PRIVATE KEY-----')) === 0;
        if (!$pkcs1 && strncmp($pem, '-----BEGIN PRIVATE KEY-----', strlen('-----BEGIN PRIVATE KEY-----')) !== 0 || $pkcs1 && strpos($pem, 'Proc-Type:') !== false) {
            throw new SignatureException('RSA private key must use unencrypted PKCS#8 or PKCS#1 PEM');
        }
        $key = openssl_pkey_get_private($pem);
        return self::checkKey($key);
    }
    public static function publicKey($pem)
    {
        if (strncmp(trim($pem), '-----BEGIN PUBLIC KEY-----', strlen('-----BEGIN PUBLIC KEY-----')) !== 0) {
            throw new SignatureException('RSA public key must use PKIX PEM');
        }
        return self::checkKey(openssl_pkey_get_public($pem));
    }
    /**
     * @param resource|\OpenSSLAsymmetricKey|false $key
     */
    private static function checkKey($key)
    {
        $details = $key ? openssl_pkey_get_details($key) : false;
        if (!$details || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 2048) {
            throw new SignatureException('A valid RSA key of at least 2048 bits is required');
        }
        return $key;
    }
    public static function nonce()
    {
        return bin2hex(random_bytes(16));
    }
    public static function requestCanonical($method, $escapedPath, $appId, $keyVersion, $timestamp, $nonce, $body)
    {
        return implode("\n", [$method, $escapedPath, $appId, $keyVersion, $timestamp, $nonce, hash('sha256', $body)]);
    }
    public static function bodyCanonical($timestamp, $nonce, $algorithm, $body)
    {
        return implode("\n", [$timestamp, $nonce, $algorithm, hash('sha256', $body)]);
    }
    public static function sign($canonical, $key)
    {
        if (!openssl_sign($canonical, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new SignatureException('RSA signing failed');
        }
        return base64_encode($signature);
    }
    public static function verify($canonical, $signature, $key)
    {
        $raw = base64_decode($signature, true);
        if ($raw === false || openssl_verify($canonical, $raw, $key, OPENSSL_ALGO_SHA256) !== 1) {
            throw new SignatureException('RSA signature verification failed');
        }
    }
    /** Normalize names and whitespace and reject ambiguous duplicate signature headers. */
    public static function readHeaders(array $headers)
    {
        $normalized = [];
        $signed = array_map('strtolower', self::SIGNED_HEADERS);
        foreach ($headers as $key => $value) {
            $key = strtolower((string) $key);
            if (!in_array($key, $signed, true)) {
                continue;
            }
            if (isset($normalized[$key]) || is_array($value) && count($value) !== 1) {
                throw new SignatureException('Duplicate signature header');
            }
            $normalized[$key] = trim(is_array($value) ? (string) reset($value) : (string) $value);
        }
        return array_map(static function ($name) use ($normalized) {
            return isset($normalized[strtolower($name)]) ? $normalized[strtolower($name)] : '';
        }, self::SIGNED_HEADERS);
    }
    public static function hasSignature(array $headers)
    {
        return count(array_filter(self::readHeaders($headers), static function ($value) {
            return $value !== '';
        })) > 0;
    }
    public static function signBody($body, $key, $timestamp = null, $nonce = null)
    {
        $timestamp = (string) (isset($timestamp) ? $timestamp : time());
        $nonce = isset($nonce) ? $nonce : self::nonce();
        return array_combine(self::SIGNED_HEADERS, [$timestamp, $nonce, self::ALGORITHM, self::sign(self::bodyCanonical($timestamp, $nonce, self::ALGORITHM, $body), $key)]);
    }
    /** Signature first, clock window second; nonce consumption belongs to the caller. */
    public static function verifyBody($body, array $headers, $key, $window = 300, $now = null)
    {
        list($timestamp, $nonce, $algorithm, $signature) = self::readHeaders($headers);
        if (in_array('', [$timestamp, $nonce, $algorithm, $signature], true)) {
            throw new SignatureException('Incomplete signature headers');
        }
        if ($algorithm !== self::ALGORITHM) {
            throw new SignatureException('Unsupported signature algorithm');
        }
        self::verify(self::bodyCanonical($timestamp, $nonce, $algorithm, $body), $signature, $key);
        if (!preg_match('/^-?\d{1,19}$/D', $timestamp) || filter_var($timestamp, FILTER_VALIDATE_INT) === false || abs((isset($now) ? $now : time()) - (int) $timestamp) > $window) {
            throw new SignatureException('Timestamp outside accepted clock window');
        }
        return $nonce;
    }
}
