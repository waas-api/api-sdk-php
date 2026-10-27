<?php

namespace WaasApi\ApiSdkPhp\V2\Http;

use WaasApi\ApiSdkPhp\V2\Exception\TransportException;
final class CurlTransport implements TransportInterface
{
    /**
     * @param string $url
     * @param mixed[] $headers
     * @param string $body
     * @param float $timeout
     * @param int $maxBodyBytes
     */
    public function post($url, $headers, $body, $timeout, $maxBodyBytes)
    {
        if (!extension_loaded('curl')) {
            throw new TransportException('ext-curl is required');
        }
        $handle = curl_init($url);
        if ($handle === false) {
            throw new TransportException('Cannot initialize cURL');
        }
        $responseBody = '';
        $responseHeaders = [];
        $tooLarge = false;
        $headerBytes = 0;
        $wireHeaders = [];
        foreach ($headers as $key => $value) {
            $wireHeaders[] = $key . ': ' . $value;
        }
        $options = [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $wireHeaders, CURLOPT_TIMEOUT_MS => max(1, (int) ceil($timeout * 1000)), CURLOPT_CONNECTTIMEOUT_MS => max(1, (int) ceil($timeout * 1000)), CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS, CURLOPT_HEADERFUNCTION => static function ($ch, $line) use (&$responseHeaders, &$headerBytes) {
            $headerBytes += strlen($line);
            if ($headerBytes > 65536) {
                return 0;
            }
            if (preg_match('/^HTTP\/\S+ \d{3}/', $line)) {
                $responseHeaders = [];
            } elseif (strpos($line, ':') !== false) {
                list($name, $value) = explode(':', $line, 2);
                $name = strtolower(trim($name));
                $responseHeaders[$name][] = trim($value);
            }
            return strlen($line);
        }, CURLOPT_WRITEFUNCTION => static function ($ch, $chunk) use (&$responseBody, &$tooLarge, $maxBodyBytes) {
            if (strlen($responseBody) + strlen($chunk) > $maxBodyBytes) {
                $tooLarge = true;
                return 0;
            }
            $responseBody .= $chunk;
            return strlen($chunk);
        }];
        if (defined('CURLOPT_PATH_AS_IS')) {
            $options[constant('CURLOPT_PATH_AS_IS')] = true;
        }
        if (!curl_setopt_array($handle, $options)) {
            throw new TransportException('Cannot configure cURL transport');
        }
        try {
            $result = curl_exec($handle);
            if ($tooLarge) {
                throw new TransportException('Response exceeds ' . $maxBodyBytes . ' bytes');
            }
            if ($result === false) {
                throw new TransportException('HTTP transport failed: ' . curl_error($handle));
            }
            return new Response((int) curl_getinfo($handle, CURLINFO_HTTP_CODE), $responseHeaders, $responseBody);
        } finally {
            unset($handle);
        }
    }
}
