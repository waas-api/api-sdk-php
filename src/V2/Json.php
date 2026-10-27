<?php

namespace WaasApi\ApiSdkPhp\V2;

use WaasApi\ApiSdkPhp\V2\Exception\ProtocolException;

final class Json
{
    public static function encode($value)
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if (defined('JSON_PRESERVE_ZERO_FRACTION')) {
            $flags |= constant('JSON_PRESERVE_ZERO_FRACTION');
        }
        $body = json_encode($value, $flags);
        if ($body === false || json_last_error() !== JSON_ERROR_NONE) {
            throw new ProtocolException('Cannot encode JSON: ' . json_last_error_msg());
        }
        return $body;
    }

    public static function decode($body)
    {
        $value = json_decode($body, false, 512, JSON_BIGINT_AS_STRING);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ProtocolException('Invalid JSON body: ' . json_last_error_msg());
        }
        return $value;
    }

    public static function object($body)
    {
        $value = self::decode($body);
        if (!$value instanceof \stdClass) {
            throw new ProtocolException('Expected a JSON object');
        }
        return $value;
    }
}
