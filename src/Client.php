<?php

namespace WaasApi\ApiSdkPhp;

class Client
{
    protected $baseURL;
    protected $privateKey;
    protected $platformPublicKey;
    protected $appId;
    protected $version = "1.0";
    protected $keyVersion = "admin";

    /**
     * set configuration
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->baseURL = $config['baseURL'];
        $this->privateKey = $config['privateKey'];
        $this->platformPublicKey = $config['platformPublicKey'];
        $this->appId = $config['appId'];
        $this->version = isset($config['version']) && !empty($config['version']) ? $config['version'] : "1.0";
        $this->keyVersion = isset($config['keyVersion']) && !empty($config['keyVersion']) ? $config['keyVersion'] : "admin";
    }

    private function getPublicData()
    {
        $data['app_id'] = $this->appId;
        $data['version'] = $this->version;
        $data['key_version'] = $this->keyVersion;
        $data['time'] = time();
        return $data;
    }

    /**
     * Supported currencies
     * @param string $chain Main chain name (query main chain and its tokens, such as: eth)
     * @param string $coin coin name (eg: eth)
     * @return mixed
     * @throws \Exception
     */
    public function getCoinList($chain = "", $coin = "")
    {
        $data = $this->getPublicData();
        if (!empty($chain)) {
            $data["chain"] = $chain;
        }
        if (!empty($coin)) {
            $data["coin"] = $coin;
        }
        $data['sign'] = Signature::generate($data, $this->privateKey);
        $res = Http::post($this->baseURL . '/coin/list', $data);
        $resp = json_decode($res, true);
        // verify signature
        if (!Signature::checkSignature($resp["sign"], $resp, $this->platformPublicKey)) {
            throw new \Exception("Check sign failed");
        }
        return $resp;
    }

    /**
     * Get New Address
     * @param string $coin the coin name platform shall prevail (if bnb_bsc please use eth)
     * @return mixed
     * @throws \Exception
     */
    public function addressGetBatch($coin)
    {
        $data = $this->getPublicData();
        $data["coin"] = $coin;
        $data['sign'] = Signature::generate($data, $this->privateKey);
        $res = Http::post($this->baseURL . '/address/getBatch', $data);
        $resp = json_decode($res, true);
        // verify signature
        if (!Signature::checkSignature($resp["sign"], $resp, $this->platformPublicKey)) {
            throw new \Exception("Check sign failed");
        }
        return $resp;
    }

    /**
     * After the merchant assigns the address to the user, it must notify the platform to update the address usage status through the "Status Synchronization Interface"
     * @param string $coin the main chain currency of the address (if bnb_bsc, please pass eth)
     * @param string $address address from platform
     * @param string $userId user UID assigned by address
     * @return mixed
     * @throws \Exception
     */
    public function addressSyncStatus($coin, $address, $userId)
    {
        $data = $this->getPublicData();
        $data["coin"] = $coin;
        $data["address"] = $address;
        $data["user_id"] = $userId;
        $data['sign'] = Signature::generate($data, $this->privateKey);
        $res = Http::post($this->baseURL . '/address/syncStatus', $data);
        $resp = json_decode($res, true);
        // verify signature
        if (!Signature::checkSignature($resp["sign"], $resp, $this->platformPublicKey)) {
            throw new \Exception("Check sign failed");
        }
        return $resp;
    }

    /**
     * Address validity verification, currently only supports eth, trx, btc main chain address verification, and will continue to expand other main chain address verification in the future
     * @param string $coin
     * @param string $address
     * @return mixed
     * @throws \Exception
     */
    public function addressVerifyAddress($coin, $address)
    {
        $data = $this->getPublicData();
        $data["coin"] = $coin;
        $data["address"] = $address;
        $data['sign'] = Signature::generate($data, $this->privateKey);
        $res = Http::post($this->baseURL . '/address/verifyAddress', $data);
        $resp = json_decode($res, true);
        // verify signature
        if (!Signature::checkSignature($resp["sign"], $resp, $this->platformPublicKey)) {
            throw new \Exception("Check sign failed");
        }
        return $resp;
    }

    /**
     * The merchant initial a on-chain withdrawal request will use this API. In order to complete the withdrawl request, merchant need to prepare the callback API for risk control callback. ( Detail please refer to the Risk Control callback -> The second review of the withdrawal order)
     * @param string $coin currency abbreviation - platform agreement shall prevail
     * @param string $address payment address, an error will occur if it exceeds the range
     * @param string $amount withdrawal amount, an error will occur if it exceeds the range
     * @param string $tradeId The unique ID of the merchant's transaction (recommended format: year, month, day, hour, minute, second + 6-digit random number case: 20200311202903000001)
     * @param string $userId user ID, out of range error
     * @param string $memo memo/tag needs to be filled in when the current currency is eos and its tokens
     * @return mixed
     * @throws \Exception
     */
    public function transfer($coin, $address, $amount, $tradeId, $userId = "", $memo = "")
    {
        $data = $this->getPublicData();
        $data["coin"] = $coin;
        $data["address"] = $address;
        $data["amount"] = $amount;
        $data["trade_id"] = $tradeId;
        $data["user_id"] = $userId;
        $data["memo"] = $memo;
        $data['sign'] = Signature::generate($data, $this->privateKey);
        $res = Http::post($this->baseURL . '/transfer', $data);
        $resp = json_decode($res, true);
        // verify signature
        if (!Signature::checkSignature($resp["sign"], $resp, $this->platformPublicKey)) {
            throw new \Exception("Check sign failed");
        }
        return $resp;
    }

    /**
     * Create H5 Order
     * @param string $merchantOrderId merchant trade order ID
     * @param float $amount order amount
     * @param string $currency currency, default usd
     * @param string $returnUrl page jump return URL
     * @param string $lang page language
     * @return mixed
     * @throws \Exception
     */
    public function h5OrderCreate($merchantOrderId, $amount, $currency = "usd", $returnUrl = "", $lang = "en")
    {
        $data = $this->getPublicData();
        $data["merchant_order_id"] = $merchantOrderId;
        $data["amount"] = $amount;
        $data["currency"] = $currency;
        $data["lang"] = $lang;
        $data["return_url"] = $returnUrl;
        $data['sign'] = Signature::generate($data, $this->privateKey);
        $res = Http::post($this->baseURL . '/h5_order/create', $data);
        $resp = json_decode($res, true);
        // verify signature
        if (!Signature::checkSignature($resp["sign"], $resp, $this->platformPublicKey)) {
            throw new \Exception("Check sign failed");
        }
        return $resp;
    }
}