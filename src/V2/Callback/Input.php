<?php

namespace WaasApi\ApiSdkPhp\V2\Callback;

use WaasApi\ApiSdkPhp\V2\ClientBase;
use WaasApi\ApiSdkPhp\V2\Exception\TransportException;
use WaasApi\ApiSdkPhp\V2\Json;
final class Input
{
    /** Headers for both traditional SAPIs and the PHP built-in server. */
    public static function headers()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (strncmp($name, 'HTTP_', 5) === 0) {
                $headers[str_replace('_', '-', substr($name, 5))] = $value;
            }
        }
        return $headers;
    }
    /** Limit at the stream boundary before holding a framework request body in memory. */
    public static function read($stream)
    {
        $body = stream_get_contents($stream, ClientBase::MAX_BODY_BYTES + 1);
        if ($body === false) {
            throw new TransportException('Cannot read callback body', 400);
        }
        self::check($body);
        return $body;
    }
    public static function check($body, $method = 'POST')
    {
        if ($method !== 'POST') {
            throw new TransportException('Only POST callbacks are accepted', 405);
        }
        if (strlen($body) > ClientBase::MAX_BODY_BYTES) {
            throw new TransportException('Callback body exceeds maximum size', 413);
        }
    }
    public static function data($body)
    {
        $envelope = Json::object($body);
        if (!isset($envelope->data) || !$envelope->data instanceof \stdClass) {
            throw new \WaasApi\ApiSdkPhp\V2\Exception\ProtocolException('Callback requires a data object');
        }
        return $envelope->data;
    }
}
