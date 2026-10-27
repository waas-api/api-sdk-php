<?php

namespace WaasApi\Examples\Risk;

require_once __DIR__ . '/OrderStoreInterface.php';
final class JsonOrderStore implements OrderStoreInterface
{
    /**
     * @readonly
     * @var string
     */
    private $path;
    public function __construct($path)
    {
        $this->path = $path;
    }
    /**
     * @param string $kind
     * @param string $tradeId
     */
    public function find($kind, $tradeId)
    {
        $json = file_get_contents($this->path);
        if ($json === false) {
            throw new \RuntimeException('Order store unavailable');
        }
        $orders = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(json_last_error_msg());
        }
        return isset($orders[$kind][$tradeId]) ? $orders[$kind][$tradeId] : null;
    }
}
