<?php

namespace WaasApi\ApiSdkPhp\V2;

use WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature;
use WaasApi\ApiSdkPhp\V2\Exception\SignatureException;
use WaasApi\ApiSdkPhp\V2\Exception\TransportException;
use WaasApi\ApiSdkPhp\V2\Http\CurlTransport;
use WaasApi\ApiSdkPhp\V2\Http\TransportInterface;
abstract class ClientBase
{
    const MAX_BODY_BYTES = 1048576;
    /**
     * @readonly
     * @var \WaasApi\ApiSdkPhp\V2\Config
     */
    private $config;
    /**
     * @readonly
     * @var \WaasApi\ApiSdkPhp\V2\Http\TransportInterface
     */
    private $transport;
    /**
     * @param \WaasApi\ApiSdkPhp\V2\Config|mixed[] $config
     */
    public function __construct($config, $transport = null)
    {
        if ($transport !== null && !$transport instanceof TransportInterface) {
            throw new \InvalidArgumentException('transport must implement TransportInterface');
        }
        $this->config = is_array($config) ? new Config($config) : $config;
        $this->transport = isset($transport) ? $transport : new CurlTransport();
    }
    /**
     * @param string $path
     * @param \WaasApi\ApiSdkPhp\V2\RequestDto|mixed[]|\stdClass $request
     */
    public function request($path, $request = null)
    {
        $request = isset($request) ? $request : new \stdClass();
        return $this->call($path, $request, ApiResponse::class);
    }
    /** @template T of ApiResponse @param class-string<T> $responseClass @return T
     * @param string $path
     * @param \WaasApi\ApiSdkPhp\V2\RequestDto|mixed[]|\stdClass $request
     * @param string $responseClass */
    protected function call($path, $request, $responseClass)
    {
        if (strncmp($path, '/', strlen('/')) !== 0 || strncmp($path, '//', strlen('//')) === 0 || strpbrk($path, "?#\\") !== false || preg_match('/[\x00-\x1f\x7f]/', $path)) {
            throw new \InvalidArgumentException('Path must be relative to V2 root and cannot contain query or fragment');
        }
        if (is_array($request)) {
            if ($request !== [] && Compat::isList($request)) {
                throw new \InvalidArgumentException('Request body must be a JSON object');
            }
            $request = (object) $request;
        }
        $body = Json::encode($request);
        $url = $this->config->baseURL . '/v2' . $path;
        // Encode each raw byte once, preserving already escaped path octets.
        $parts = parse_url($url);
        $escapedPath = preg_replace_callback("#%[0-9a-fA-F]{2}|[A-Za-z0-9/._!\$&'()*+,;=:@~-]|.#s", static function (array $m) {
            return strlen($m[0]) > 1 || preg_match("#^[A-Za-z0-9/._!\$&'()*+,;=:@~-]\$#D", $m[0]) ? $m[0] : rawurlencode($m[0]);
        }, $parts['path']);
        // Older PHP cURL bindings cannot disable dot-segment normalization.
        // Escape these segments before signing so every runtime sends the signed path.
        $escapedPath = preg_replace_callback('#(?<=/)\.{1,2}(?=/|$)#', static function ($match) {
            return str_replace('.', '%2E', $match[0]);
        }, $escapedPath);
        $origin = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $url = $origin . $escapedPath;
        $timestamp = (string) time();
        $nonce = RsaSignature::nonce();
        $canonical = RsaSignature::requestCanonical('POST', $escapedPath, $this->config->appId, $this->config->keyVersion, $timestamp, $nonce, $body);
        $headers = ['Content-Type' => 'application/json', 'X-Cbc-App-Id' => $this->config->appId, 'X-Cbc-Key-Version' => $this->config->keyVersion, 'X-Cbc-Timestamp' => $timestamp, 'X-Cbc-Nonce' => $nonce, 'X-Cbc-Sign-Alg' => RsaSignature::ALGORITHM, 'X-Cbc-Signature' => RsaSignature::sign($canonical, $this->config->privateKey)];
        $response = $this->transport->post($url, $headers, $body, $this->config->timeout, self::MAX_BODY_BYTES);
        if (strlen($response->body) > self::MAX_BODY_BYTES) {
            throw new TransportException('Response exceeds maximum body size');
        }
        $signed = RsaSignature::hasSignature($response->headers);
        if ($this->config->platformPublicKey !== null && $signed) {
            RsaSignature::verifyBody($response->body, $response->headers, $this->config->platformPublicKey);
        }
        if ($response->status < 200 || $response->status > 299) {
            throw new TransportException('Unexpected HTTP status ' . $response->status, $response->status, substr($response->body, 0, 512));
        }
        // Unsigned bodies are tolerated only for early gateway rejections, never for business success.
        if ($this->config->platformPublicKey !== null && !$signed && (new ApiResponse($response->body))->status === 200) {
            throw new SignatureException('Successful response is missing signature headers');
        }
        return (new $responseClass($response->body))->throwIfError();
    }
}
