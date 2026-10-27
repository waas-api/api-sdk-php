<?php

namespace WaasApi\ApiSdkPhp\V2\Http;

interface TransportInterface
{
    /** Send the exact supplied body, without redirects, within timeout and response size limits.
     * @param string $url
     * @param mixed[] $headers
     * @param string $body
     * @param float $timeout
     * @param int $maxBodyBytes */
    public function post($url, $headers, $body, $timeout, $maxBodyBytes);
}
