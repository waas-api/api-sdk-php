<?php

namespace WaasApi\ApiSdkPhp;


class RiskControl
{
    protected $riskPrivateKey;
    protected $riskPlatformPublicKey;

    public function __construct($config)
    {
        $this->riskPrivateKey = $config['riskPrivateKey'];
        $this->riskPlatformPublicKey = $config['riskPlatformPublicKey'];
    }

    /**
     * @param $rawData [
     * "data" => [
     * "amount" => 2.00000000,
     * "coin_symbol" => "USDT_TRC20",
     * "address" => "TLhdZuFU1fDPnzxPoXfJ6WZZMpKzY15DUi",
     * "user_id" => "1",
     * "order_id" => "1394934189494173697",
     * "timestamp" => 1621493658
     * ],
     * "sign" => "dLtK+uiPcnxt2ACKMbBqQ6wI5ttAvesOHzK5ybVID1R6hqYPDTkagl7Tsjr5iLJafehcSLTZyJLtCRI8O2CtIWQUUroGXBneHZC486NUi/4FMOQs0FaAgm17pWlbhX5/96cWXXXMVeoe3IZFKaFNYSWaA14v3RcdDU6QDE/9ixGiSJ0DIxm9NKA0+RkbIFbyYeuFn8d63OcjmUhv7tsOE6rKCc3Q2yi7Qe9i6BNAQFYMFATztb18MsxsBHKUxNqklqyVnl0ofETAHmQhfOHLmungOJQnOqAAuwfmRtg50Qci5F+R2mXeqjmIXko/u3E+DLYW1ygDBp3afKZmU4PwmA=="
     * ]
     * @return mixed
     * @throws \Exception
     */
    public function getWithdrawOrderCheckData($rawData)
    {
        $data = $rawData["data"];
        $sign = $rawData["sign"];
        if (!Signature::checkSignature($sign, $data, $this->riskPlatformPublicKey)) {
            throw new \Exception("Check sign failed");
        }
        return $data;
    }

    /**
     * @param $statusCode int 200 passed, 2001 awaiting review, 5400 failed signature verification, 5401 time is less than 30s, 5402 order number does not exist, 5001 platform order_id is different, 5002 amount is wrong, 5003 user uid is wrong, 5004 address is wrong, 5005 currency The symbol is wrong, 5006 order rejection, 5007 other, the description information can be expanded by yourself
     * @param string $orderId Withdraw Order's OrderId
     * @return array
     */
    public function callbackResponseData($statusCode, $orderId)
    {
        $data = [
            "status_code" => $statusCode,
            "timestamp" => time(),
            "order_id" => $orderId,
        ];
        $sign = Signature::generate($data, $this->riskPrivateKey);

        return [
            "status" => $statusCode,
            "data" => $data,
            "sign" => $sign,
        ];
    }

}