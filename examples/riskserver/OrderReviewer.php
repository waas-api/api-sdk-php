<?php

namespace WaasApi\Examples\Risk;

require_once __DIR__ . '/OrderStoreInterface.php';
use WaasApi\ApiSdkPhp\V2\Callback\WithdrawRiskRequest;
use WaasApi\ApiSdkPhp\V2\Callback\ExchangeRiskRequest;
use WaasApi\ApiSdkPhp\V2\Callback\RiskResult;
use WaasApi\ApiSdkPhp\V2\Callback\RiskStatus;
use WaasApi\ApiSdkPhp\V2\Decimal;
final class OrderReviewer
{
    /**
     * @readonly
     * @var \WaasApi\Examples\Risk\OrderStoreInterface
     */
    private $orders;
    public function __construct(OrderStoreInterface $orders)
    {
        $this->orders = $orders;
    }
    public function withdraw(WithdrawRiskRequest $request)
    {
        return $this->review('withdraw', $request, ['coin' => RiskStatus::COIN_MISMATCH, 'address' => RiskStatus::ADDRESS_MISMATCH]);
    }
    public function exchange(ExchangeRiskRequest $request)
    {
        return $this->review('exchange', $request, ['sourceCoin' => RiskStatus::COIN_MISMATCH, 'targetCoin' => RiskStatus::COIN_MISMATCH]);
    }
    /**
     * @param \WaasApi\ApiSdkPhp\V2\Callback\WithdrawRiskRequest|\WaasApi\ApiSdkPhp\V2\Callback\ExchangeRiskRequest $request
     */
    private function review($kind, $request, array $fields)
    {
        $order = $this->orders->find($kind, isset($request->tradeId) ? $request->tradeId : '');
        if ($order === null) {
            return new RiskResult(RiskStatus::ORDER_NOT_FOUND);
        }
        if ($request->tradeId !== $order['trade_id']) {
            return new RiskResult(RiskStatus::TRADE_ID_MISMATCH);
        }
        if (!Decimal::equals(isset($request->amount) ? $request->amount : '', $order['amount'])) {
            return new RiskResult(RiskStatus::AMOUNT_MISMATCH);
        }
        if ($request->userId !== $order['user_id']) {
            return new RiskResult(RiskStatus::USER_ID_MISMATCH);
        }
        foreach ($fields as $property => $status) {
            $wire = preg_replace('/[A-Z]/', '_$0', $property);
            if ($request->{$property} !== $order[strtolower($wire)]) {
                return new RiskResult($status);
            }
        }
        // The merchant order state decides; matching fields alone must not approve canceled orders.
        return new RiskResult(call_user_func(function () use ($order) {
            switch (isset($order['review']) ? $order['review'] : 'pending') {
                case 'approve':
                    return RiskStatus::APPROVE;
                case 'reject':
                    return RiskStatus::REJECT;
                default:
                    return RiskStatus::PENDING;
            }
        }), 'merchant order review');
    }
}
