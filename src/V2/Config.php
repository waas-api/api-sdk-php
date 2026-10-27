<?php

namespace WaasApi\ApiSdkPhp\V2;

use WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature;
final class Config
{
    /**
     * @readonly
     * @var string
     */
    public $baseURL;
    /**
     * @readonly
     * @var string
     */
    public $appId;
    /**
     * @readonly
     * @var string
     */
    public $keyVersion;
    /**
     * @readonly
     * @var float
     */
    public $timeout;
    /**
     * @readonly
     * @var resource|\OpenSSLAsymmetricKey
     */
    public $privateKey;
    /**
     * @readonly
     * @var resource|\OpenSSLAsymmetricKey|null
     */
    public $platformPublicKey;
    public function __construct(array $config)
    {
        $base = rtrim(isset($config['baseURL']) ? $config['baseURL'] : '', '/');
        $parts = parse_url($base);
        if (!$parts || !in_array(isset($parts['scheme']) ? $parts['scheme'] : '', ['http', 'https'], true) || empty($parts['host']) || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user']) || preg_match('/[\x00-\x20\\\\]/', $base)) {
            throw new \InvalidArgumentException('baseURL must be an absolute HTTP(S) URL without query, fragment or credentials');
        }
        if (substr(rawurldecode(isset($parts['path']) ? $parts['path'] : ''), -3) === '/v2') {
            throw new \InvalidArgumentException('baseURL must be the /shopapi root, without /v2');
        }
        foreach (['appId', 'keyVersion'] as $field) {
            $value = isset($config[$field]) ? $config[$field] : ($field === 'keyVersion' ? 'admin' : '');
            if ($value === '' && $field === 'keyVersion') {
                $value = 'admin';
            }
            if (!is_string($value) || $value === '' || preg_match('/[\x00-\x20\x7f]/', $value)) {
                throw new \InvalidArgumentException($field . ' must be a nonempty header value');
            }
            $this->{$field} = $value;
        }
        $this->baseURL = $base;
        $timeout = isset($config['timeout']) ? $config['timeout'] : 30;
        if (!is_int($timeout) && !is_float($timeout) || !is_finite((float) $timeout)) {
            throw new \InvalidArgumentException('timeout must be a finite number of seconds');
        }
        $this->timeout = $timeout > 0 ? (float) $timeout : 30.0;
        $this->privateKey = RsaSignature::privateKey(isset($config['privateKey']) ? $config['privateKey'] : '');
        $publicKey = isset($config['platformPublicKey']) ? $config['platformPublicKey'] : '';
        $this->platformPublicKey = $publicKey === '' ? null : RsaSignature::publicKey($publicKey);
    }
}
