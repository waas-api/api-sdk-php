<?php

namespace WaasApi\ApiSdkPhp;

class Signature
{
    /**
     * 私钥签名
     *
     * @param $data
     * @param $privateKey
     */
    public static function generate($data, $privateKey)
    {
        if (is_array($data))
            $signString = self::getSignString($data);
        else
            $signString = $data;
        $privKeyId = openssl_pkey_get_private($privateKey);
        $signature = '';
        openssl_sign($signString, $signature, $privKeyId, OPENSSL_ALGO_MD5);
        openssl_free_key($privKeyId);
        return base64_encode($signature);
    }


    /**
     * check signature by public key
     *
     * @param $sign
     * @param $data
     * @param $publicKey
     * @return bool
     */
    public static function checkSignature($sign, $data, $publicKey)
    {
        $toSign = self::getSignString($data);
        $publicKeyId = openssl_pkey_get_public($publicKey);
        $result = openssl_verify($toSign, base64_decode($sign), $publicKeyId, OPENSSL_ALGO_MD5);
        openssl_free_key($publicKeyId);
        return $result === 1 ? true : false;
    }

    public static function getSignString($data)
    {
        unset($data['sign']);
        ksort($data);
        reset($data);
        $pairs = array();
        foreach ($data as $k => $v) {
            if (is_array($v)) $v = self::arrayToString($v);
            $pairs[] = "$k=$v";
        }

        return implode('&', $pairs);
    }

    private static function arrayToString($data)
    {
        $str = '';
        foreach ($data as $list) {
            if (is_array($list)) {
                $str .= self::arrayToString($list);
            } else {
                $str .= $list;
            }
        }

        return $str;
    }
}