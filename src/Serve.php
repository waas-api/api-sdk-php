<?php

/**
 * Date: 2023/6/21
 */

namespace WaasApi\ApiSdkPhp;


class Serve
{
    protected $privateKey;
    protected $platformPublicKey;

    public function __construct($config)
    {
        $this->privateKey = $config['privateKey'];
        $this->platformPublicKey = $config['platformPublicKey'];
    }

    /**
     * @param array $requestData [
        "data" => [
            "order_id" => "2020010211153423123456",
            "coin" => "eth",
            "chain" => "eth",
            "address" => "this is test address",
            "txid" => "this is txid",
            "total" => "5",
            "amount" => "10.0089",
            "fee" => "0",
            "status" => "0",
            "confirm_count" => "5",
            "time" => "1650011196",
            "type" => 1
        ],
        "sign" => "OTA8utI8y8G93p7RPCyb4qIilFQ0B4Aq4iUjhaXWK9m2kgektqlHOASDKXT2VE7NPNysrGycYlVfjDR2WGZn1G66phHo3qa9CcCNpG9klOBEuBEMjiVbb/d8AXcxEzvQr9OCwNsikyxonyzLiY/lsNHeGm9cC5eRvlNLdUSVBipH+ajPd0lDHCayZPs1eCMfbm/xnf8e2lfy6z0UPOpHGfyX/0+hz99Ir5Xnx+0sBBzyZZJxKm4ROid5aDv/9m9guILpURae+Yw/IrkYF4uAKGX+/44cBvtTRQSdX76CAOBSiywZa6BAgDYBLRtkAPM+i+vwNzFBovqHUvI+4Ponaw==",
    ]
     * @return mixed
     * @throws \Exception
     */
    public function getDepositCallbackData($requestData)
    {
        $data = $requestData["data"];
        $sign = $requestData["sign"];
        if (!Signature::checkSignature($sign, $data, $this->platformPublicKey)) {
            throw new \Exception("Check sign failed");
        }
        return $data;
    }

    /**
     * @param array $requestData [
        "data" => [
            "trade_id" => "this is trade_id",
            "coin" => "eth",
            "txid" => "this is txid",
            "address" => "this is address",
            "time" => "1591779242",
            "amount" => "10.1",
            "fee" => "0",
            "total" => "10.1",
            "status" => "1",
            "msg" => "Prompt information",
            "type" => 1
        ],
        "sign" => "dLtK+uiPcnxt2ACKMbBqQ6wI5ttAvesOHzK5ybVID1R6hqYPDTkagl7Tsjr5iLJafehcSLTZyJLtCRI8O2CtIWQUUroGXBneHZC486NUi/4FMOQs0FaAgm17pWlbhX5/96cWXXXMVeoe3IZFKaFNYSWaA14v3RcdDU6QDE/9ixGiSJ0DIxm9NKA0+RkbIFbyYeuFn8d63OcjmUhv7tsOE6rKCc3Q2yi7Qe9i6BNAQFYMFATztb18MsxsBHKUxNqklqyVnl0ofETAHmQhfOHLmungOJQnOqAAuwfmRtg50Qci5F+R2mXeqjmIXko/u3E+DLYW1ygDBp3afKZmU4PwmA=="
    ]
     * @return mixed
     * @throws \Exception
     */
    public function getWithdrawCallbackData($requestData)
    {
        $data = $requestData["data"];
        $sign = $requestData["sign"];
        if (!Signature::checkSignature($sign, $data, $this->platformPublicKey)) {
            throw new \Exception("Check sign failed");
        }
        return $data;
    }

    /**
     * Withdraw and deposit Callback Response Data
     * @return array
     */
    public function callbackResponseData()
    {
        $data = [
            "success_data" => "success"
        ];
        $sign = Signature::generate($data, $this->privateKey);

        return [
            "status" => 200,
            "data" => $data,
            "sign" => $sign,
        ];
    }
}