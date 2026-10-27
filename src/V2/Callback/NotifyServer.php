<?php

namespace WaasApi\ApiSdkPhp\V2\Callback;

use WaasApi\ApiSdkPhp\V2\Exception\SignatureException;
use WaasApi\ApiSdkPhp\V2\Exception\TransportException;
final class NotifyServer
{
    /**
     * @readonly
     * @var \WaasApi\ApiSdkPhp\V2\Callback\Verifier
     */
    private $verifier;
    public function __construct(array $config)
    {
        $this->verifier = new Verifier($config);
    }
    public function handleDeposit(array $headers, $body, callable $business, $method = 'POST')
    {
        return $this->handle(DepositNotify::class, $headers, $body, $business, $method);
    }
    public function handleWithdraw(array $headers, $body, callable $business, $method = 'POST')
    {
        return $this->handle(WithdrawNotify::class, $headers, $body, $business, $method);
    }
    public function handleExchange(array $headers, $body, callable $business, $method = 'POST')
    {
        return $this->handle(ExchangeNotify::class, $headers, $body, $business, $method);
    }
    public function handleH5Order(array $headers, $body, callable $business, $method = 'POST')
    {
        return $this->handle(H5OrderNotify::class, $headers, $body, $business, $method);
    }
    public function handleOuterAddressTransaction(array $headers, $body, callable $business, $method = 'POST')
    {
        return $this->handle(OuterAddressTransactionNotify::class, $headers, $body, $business, $method);
    }
    private function handle($type, array $headers, $body, callable $business, $method)
    {
        try {
            Input::check($body, $method);
            $this->verifier->verify($headers, $body);
            $request = new $type(Input::data($body));
        } catch (\Exception $e) {
            $this->verifier->log($e);
            return self::ack(false, $e instanceof SignatureException ? 401 : ($e instanceof TransportException ? $e->httpStatus : 400));
        } catch (\Throwable $e) {
            $this->verifier->log($e);
            return self::ack(false, $e instanceof SignatureException ? 401 : ($e instanceof TransportException ? $e->httpStatus : 400));
        }
        try {
            $success = $business($request);
            if (!is_bool($success)) {
                throw new \UnexpectedValueException('Notification handler must return bool');
            }
            return self::ack($success);
        } catch (\Exception $e) {
            $this->verifier->log($e);
            return self::ack(false);
        } catch (\Throwable $e) {
            $this->verifier->log($e);
            return self::ack(false);
        }
    }
    private static function ack($success, $httpStatus = 200)
    {
        return Reply::json(['status' => $success ? 200 : 500, 'msg' => $success ? 'success' : 'fail', 'data' => ['success_data' => $success ? 'success' : 'fail']], $httpStatus);
    }
}
