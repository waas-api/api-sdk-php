<?php
namespace WaasApi\ApiSdkPhp\V2;

/** The 42 public V2 methods; all transport and signature handling lives in ClientBase. */
final class Client extends ClientBase
{

    /** @param Request\TrVaspListRequest|null $request @return Response\TrVaspListResponse */
    public function trVaspList($request = null)
    {
        if ($request === null) { $request = new Request\TrVaspListRequest(); }
        if (!$request instanceof Request\TrVaspListRequest) { throw new \InvalidArgumentException("Expected TrVaspListRequest"); }
        return $this->call('/travelRule/vaspList', $request, Response\TrVaspListResponse::class);
    }

    /** @param Request\TrVerifyAddressRequest|null $request @return Response\TrVerifyAddressResponse */
    public function trVerifyAddress(Request\TrVerifyAddressRequest $request)
    {
        return $this->call('/travelRule/verifyAddress', $request, Response\TrVerifyAddressResponse::class);
    }

    /** @param Request\TrTransferRequest|null $request @return Response\TrTransferResponse */
    public function trTransfer(Request\TrTransferRequest $request)
    {
        return $this->call('/travelRule/transfer', $request, Response\TrTransferResponse::class);
    }

    /** @param Request\TrQueryTransferRequest|null $request @return Response\TrQueryTransferResponse */
    public function trQueryTransfer(Request\TrQueryTransferRequest $request)
    {
        return $this->call('/travelRule/queryTransfer', $request, Response\TrQueryTransferResponse::class);
    }

    /** @param Request\TrPostTransferRequest|null $request @return Response\TrPostTransferResponse */
    public function trPostTransfer(Request\TrPostTransferRequest $request)
    {
        return $this->call('/travelRule/postTransfer', $request, Response\TrPostTransferResponse::class);
    }

    /** @param Request\TrReceiveAuditRequest|null $request @return Response\TrReceiveAuditResponse */
    public function trReceiveAudit(Request\TrReceiveAuditRequest $request)
    {
        return $this->call('/travelRule/receiveAudit', $request, Response\TrReceiveAuditResponse::class);
    }

    /** @param Request\TrRateRequest|null $request @return Response\TrRateResponse */
    public function trRate(Request\TrRateRequest $request)
    {
        return $this->call('/travelRule/rate', $request, Response\TrRateResponse::class);
    }

    /** @param Request\TrHotWalletRequest|null $request @return Response\TrHotWalletResponse */
    public function trHotWallet(Request\TrHotWalletRequest $request)
    {
        return $this->call('/address/hotWallet', $request, Response\TrHotWalletResponse::class);
    }

    /** @param Request\AddressGetBatchRequest|null $request @return Response\AddressGetBatchResponse */
    public function addressGetBatch(Request\AddressGetBatchRequest $request)
    {
        return $this->call('/address/getBatch', $request, Response\AddressGetBatchResponse::class);
    }

    /** @param Request\AddressListRequest|null $request @return Response\AddressListResponse */
    public function addressList(Request\AddressListRequest $request)
    {
        return $this->call('/address/list', $request, Response\AddressListResponse::class);
    }

    /** @param Request\AddressCoinAccountRequest|null $request @return Response\AddressCoinAccountResponse */
    public function addressCoinAccount(Request\AddressCoinAccountRequest $request)
    {
        return $this->call('/address/coinAccount', $request, Response\AddressCoinAccountResponse::class);
    }

    /** @param Request\AddressSyncStatusRequest|null $request @return Response\AddressSyncStatusResponse */
    public function addressSyncStatus(Request\AddressSyncStatusRequest $request)
    {
        return $this->call('/address/syncStatus', $request, Response\AddressSyncStatusResponse::class);
    }

    /** @param Request\AddressSyncBatchStatusRequest|null $request @return Response\AddressSyncBatchStatusResponse */
    public function addressSyncBatchStatus(Request\AddressSyncBatchStatusRequest $request)
    {
        return $this->call('/address/syncBatchStatus', $request, Response\AddressSyncBatchStatusResponse::class);
    }

    /** @param Request\AddressVerifyAddressRequest|null $request @return Response\AddressVerifyAddressResponse */
    public function addressVerifyAddress(Request\AddressVerifyAddressRequest $request)
    {
        return $this->call('/address/verifyAddress', $request, Response\AddressVerifyAddressResponse::class);
    }

    /** @param Request\AddressVerifyAmlRequest|null $request @return Response\AddressVerifyAmlResponse */
    public function addressVerifyAml(Request\AddressVerifyAmlRequest $request)
    {
        return $this->call('/address/verifyAml', $request, Response\AddressVerifyAmlResponse::class);
    }

    /** @param Request\ReceiveListRequest|null $request @return Response\ReceiveListResponse */
    public function receiveList($request = null)
    {
        if ($request === null) { $request = new Request\ReceiveListRequest(); }
        if (!$request instanceof Request\ReceiveListRequest) { throw new \InvalidArgumentException("Expected ReceiveListRequest"); }
        return $this->call('/receive/list', $request, Response\ReceiveListResponse::class);
    }

    /** @param Request\BalanceRequest|null $request @return Response\BalanceResponse */
    public function balance(Request\BalanceRequest $request)
    {
        return $this->call('/balance', $request, Response\BalanceResponse::class);
    }

    /** @param Request\ExchangeIndexRequest|null $request @return Response\ExchangeIndexResponse */
    public function exchangeIndex(Request\ExchangeIndexRequest $request)
    {
        return $this->call('/exchange/index', $request, Response\ExchangeIndexResponse::class);
    }

    /** @param Request\ExchangeTransferRequest|null $request @return Response\ExchangeTransferResponse */
    public function exchangeTransfer(Request\ExchangeTransferRequest $request)
    {
        return $this->call('/exchange/transfer', $request, Response\ExchangeTransferResponse::class);
    }

    /** @param Request\RateExchangeRequest|null $request @return Response\RateExchangeResponse */
    public function rateExchange(Request\RateExchangeRequest $request)
    {
        return $this->call('/rate/exchange', $request, Response\RateExchangeResponse::class);
    }

    /** @param Request\EmptyRequest|null $request @return Response\RateExchangeBatchResponse */
    public function rateExchangeBatch($request = null)
    {
        if ($request === null) { $request = new Request\EmptyRequest(); }
        if (!$request instanceof Request\EmptyRequest) { throw new \InvalidArgumentException("Expected EmptyRequest"); }
        return $this->call('/rate/exchangeBatch', $request, Response\RateExchangeBatchResponse::class);
    }

    /** @param Request\BalanceExchangeRequest|null $request @return Response\BalanceExchangeResponse */
    public function balanceExchange($request = null)
    {
        if ($request === null) { $request = new Request\BalanceExchangeRequest(); }
        if (!$request instanceof Request\BalanceExchangeRequest) { throw new \InvalidArgumentException("Expected BalanceExchangeRequest"); }
        return $this->call('/balance/exchange', $request, Response\BalanceExchangeResponse::class);
    }

    /** @param Request\EmptyRequest|null $request @return Response\ExchangeCoinResponse */
    public function exchangeCoin($request = null)
    {
        if ($request === null) { $request = new Request\EmptyRequest(); }
        if (!$request instanceof Request\EmptyRequest) { throw new \InvalidArgumentException("Expected EmptyRequest"); }
        return $this->call('/exchange/coin', $request, Response\ExchangeCoinResponse::class);
    }

    /** @param Request\ExchangeListRequest|null $request @return Response\ExchangeListResponse */
    public function exchangeList($request = null)
    {
        if ($request === null) { $request = new Request\ExchangeListRequest(); }
        if (!$request instanceof Request\ExchangeListRequest) { throw new \InvalidArgumentException("Expected ExchangeListRequest"); }
        return $this->call('/exchange/list', $request, Response\ExchangeListResponse::class);
    }

    /** @param Request\H5OrderCreateRequest|null $request @return Response\H5OrderCreateResponse */
    public function h5OrderCreate(Request\H5OrderCreateRequest $request)
    {
        return $this->call('/h5Order/create', $request, Response\H5OrderCreateResponse::class);
    }

    /** @param Request\H5OrderCancelRequest|null $request @return Response\H5OrderCancelResponse */
    public function h5OrderCancel(Request\H5OrderCancelRequest $request)
    {
        return $this->call('/h5Order/cancel', $request, Response\H5OrderCancelResponse::class);
    }

    /** @param Request\H5OrderDetailRequest|null $request @return Response\H5OrderDetailResponse */
    public function h5OrderDetail(Request\H5OrderDetailRequest $request)
    {
        return $this->call('/h5Order/detail', $request, Response\H5OrderDetailResponse::class);
    }

    /** @param Request\CoinListRequest|null $request @return Response\CoinListResponse */
    public function coinList($request = null)
    {
        if ($request === null) { $request = new Request\CoinListRequest(); }
        if (!$request instanceof Request\CoinListRequest) { throw new \InvalidArgumentException("Expected CoinListRequest"); }
        return $this->call('/coin/list', $request, Response\CoinListResponse::class);
    }

    /** @param Request\RateIndexRequest|null $request @return Response\RateIndexResponse */
    public function rateIndex(Request\RateIndexRequest $request)
    {
        return $this->call('/rate/index', $request, Response\RateIndexResponse::class);
    }

    /** @param Request\RateFiatCurrencyRequest|null $request @return Response\RateFiatCurrencyResponse */
    public function rateFiatCurrency(Request\RateFiatCurrencyRequest $request)
    {
        return $this->call('/rate/fiatCurrency', $request, Response\RateFiatCurrencyResponse::class);
    }

    /** @param Request\BalanceTransactionFeeRequest|null $request @return Response\BalanceTransactionFeeResponse */
    public function balanceTransactionFee(Request\BalanceTransactionFeeRequest $request)
    {
        return $this->call('/balance/transactionFee', $request, Response\BalanceTransactionFeeResponse::class);
    }

    /** @param Request\TransferTransactionFeeRequest|null $request @return Response\TransferTransactionFeeResponse */
    public function transferTransactionFee($request = null)
    {
        if ($request === null) { $request = new Request\TransferTransactionFeeRequest(); }
        if (!$request instanceof Request\TransferTransactionFeeRequest) { throw new \InvalidArgumentException("Expected TransferTransactionFeeRequest"); }
        return $this->call('/transfer/transactionFee', $request, Response\TransferTransactionFeeResponse::class);
    }

    /** @param Request\RefundListRequest|null $request @return Response\RefundListResponse */
    public function refundList($request = null)
    {
        if ($request === null) { $request = new Request\RefundListRequest(); }
        if (!$request instanceof Request\RefundListRequest) { throw new \InvalidArgumentException("Expected RefundListRequest"); }
        return $this->call('/refund/list', $request, Response\RefundListResponse::class);
    }

    /** @param Request\ReconcileBalancesRequest|null $request @return Response\ReconcileBalancesResponse */
    public function reconcileBalances(Request\ReconcileBalancesRequest $request)
    {
        return $this->call('/reconcile/balances', $request, Response\ReconcileBalancesResponse::class);
    }

    /** @param Request\ReconcileAddressBalanceRequest|null $request @return Response\ReconcileAddressBalanceResponse */
    public function reconcileAddressBalance(Request\ReconcileAddressBalanceRequest $request)
    {
        return $this->call('/reconcile/addressBalance', $request, Response\ReconcileAddressBalanceResponse::class);
    }

    /** @param Request\ReconcileWithdrawalRequest|null $request @return Response\ReconcileWithdrawalResponse */
    public function reconcileWithdrawal(Request\ReconcileWithdrawalRequest $request)
    {
        return $this->call('/reconcile/withdrawal', $request, Response\ReconcileWithdrawalResponse::class);
    }

    /** @param Request\OuterAddressAddRequest|null $request @return Response\OuterAddressAddResponse */
    public function outerAddressAdd(Request\OuterAddressAddRequest $request)
    {
        return $this->call('/outerAddress/add', $request, Response\OuterAddressAddResponse::class);
    }

    /** @param Request\OuterAddressDeleteRequest|null $request @return Response\OuterAddressDeleteResponse */
    public function outerAddressDelete(Request\OuterAddressDeleteRequest $request)
    {
        return $this->call('/outerAddress/delete', $request, Response\OuterAddressDeleteResponse::class);
    }

    /** @param Request\EmptyRequest|null $request @return Response\BalanceGeneralAccountResponse */
    public function balanceGeneralAccount($request = null)
    {
        if ($request === null) { $request = new Request\EmptyRequest(); }
        if (!$request instanceof Request\EmptyRequest) { throw new \InvalidArgumentException("Expected EmptyRequest"); }
        return $this->call('/balance/generalAccount', $request, Response\BalanceGeneralAccountResponse::class);
    }

    /** @param Request\StatisticsTransferRequest|null $request @return Response\StatisticsTransferResponse */
    public function statisticsTransfer($request = null)
    {
        if ($request === null) { $request = new Request\StatisticsTransferRequest(); }
        if (!$request instanceof Request\StatisticsTransferRequest) { throw new \InvalidArgumentException("Expected StatisticsTransferRequest"); }
        return $this->call('/statistics/transfer', $request, Response\StatisticsTransferResponse::class);
    }

    /** @param Request\TransferRequest|null $request @return Response\TransferResponse */
    public function transfer(Request\TransferRequest $request)
    {
        return $this->call('/transfer', $request, Response\TransferResponse::class);
    }

    /** @param Request\TransferListRequest|null $request @return Response\TransferListResponse */
    public function transferList($request = null)
    {
        if ($request === null) { $request = new Request\TransferListRequest(); }
        if (!$request instanceof Request\TransferListRequest) { throw new \InvalidArgumentException("Expected TransferListRequest"); }
        return $this->call('/transfer/list', $request, Response\TransferListResponse::class);
    }
}
