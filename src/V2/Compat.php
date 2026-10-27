<?php

namespace WaasApi\ApiSdkPhp\V2;

/** Runtime helpers shared by PHP 5.6 and current PHP releases. */
final class Compat
{
    public static function isList(array $value)
    {
        $index = 0;
        foreach ($value as $key => $unused) {
            if ($key !== $index++) {
                return false;
            }
        }
        return true;
    }
}
