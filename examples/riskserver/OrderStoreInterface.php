<?php

namespace WaasApi\Examples\Risk;

interface OrderStoreInterface
{
    /** Return the merchant's durable order record. Throw on temporary storage failure.
     * @param string $kind
     * @param string $tradeId */
    public function find($kind, $tradeId);
}
