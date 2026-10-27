<?php

namespace WaasApi\ApiSdkPhp\V2;

/** Exact nonnegative decimal comparison for order review; never converts through float. */
final class Decimal
{
    public static function isValid($amount)
    {
        return is_string($amount) && preg_match('/^\d+(?:\.\d+)?$/D', $amount) === 1;
    }
    public static function normalize($amount)
    {
        if (!self::isValid($amount)) {
            throw new \InvalidArgumentException('Invalid nonnegative decimal string');
        }
        list($integer, $fraction) = array_pad(explode('.', $amount, 2), 2, '');
        $integer = ltrim($integer, '0');
        $fraction = rtrim($fraction, '0');
        return ($integer === '' ? '0' : $integer) . ($fraction === '' ? '' : '.' . $fraction);
    }
    public static function equals($left, $right)
    {
        return self::normalize($left) === self::normalize($right);
    }
}
