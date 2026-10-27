<?php

namespace WaasApi\ApiSdkPhp\V2\Callback;

use WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature;
use WaasApi\ApiSdkPhp\V2\Exception\TransportException;
use WaasApi\ApiSdkPhp\V2\Json;
abstract class RiskServer
{
    const REQUEST_TYPE = WithdrawRiskRequest::class;
    const EXCHANGE = false;
    /**
     * @readonly
     * @var \WaasApi\ApiSdkPhp\V2\Callback\Verifier
     */
    private $verifier;
    /**
     * @readonly
     * @var resource|\OpenSSLAsymmetricKey
     */
    private $privateKey;
    public function __construct(array $config)
    {
        // Dedicated risk keys: do not fall back to API keys.
        $verify = $config;
        $verify['platformPublicKey'] = isset($config['riskPlatformPublicKey']) ? $config['riskPlatformPublicKey'] : '';
        $this->verifier = new Verifier($verify);
        $this->privateKey = RsaSignature::privateKey(isset($config['riskPrivateKey']) ? $config['riskPrivateKey'] : '');
    }
    /**
     * @param mixed[] $headers
     * @param string $body
     * @param callable $business
     * @param string $method
     */
    public function handle($headers, $body, $business, $method = 'POST')
    {
        try {
            Input::check($body, $method);
        } catch (TransportException $e) {
            return Reply::json(['error' => 'Invalid callback request'], $e->httpStatus);
        }
        try {
            $this->verifier->verify($headers, $body);
        } catch (\Exception $e) {
            return $this->rejectUnverified($e, $body);
        } catch (\Throwable $e) {
            return $this->rejectUnverified($e, $body);
        }
        try {
            $type = static::REQUEST_TYPE;
            $request = new $type(Input::data($body));
        } catch (\Exception $e) {
            $this->verifier->log($e);
            return Reply::json(['error' => 'Invalid review data'], 500);
        } catch (\Throwable $e) {
            $this->verifier->log($e);
            return Reply::json(['error' => 'Invalid review data'], 500);
        }
        $tradeId = isset($request->tradeId) ? $request->tradeId : '';
        try {
            $result = $business($request);
        } catch (\Exception $e) {
            $this->verifier->log($e);
            return $this->reply(2001, 'review temporarily unavailable', $tradeId);
        } catch (\Throwable $e) {
            $this->verifier->log($e);
            return $this->reply(2001, 'review temporarily unavailable', $tradeId);
        }
        if (!$result instanceof RiskResult || !self::validStatus($result->status)) {
            return Reply::json(['error' => 'Invalid review status'], 500);
        }
        return $this->reply($result->status, $result->msg, $tradeId);
    }
    /**
     * @param \Exception|\Throwable $error
     */
    private function rejectUnverified($error, $body)
    {
        $this->verifier->log($error);
        // This unverified identifier is echoed only in the 5400 reply, never passed to business code.
        $tradeId = '';
        try {
            $data = Input::data($body);
            $tradeId = is_string(isset($data->trade_id) ? $data->trade_id : null) ? $data->trade_id : '';
        } catch (\Exception $exception) {
        } catch (\Throwable $exception) {
        }
        return $this->reply(5400, 'signature verification failed', $tradeId);
    }
    private static function validStatus($status)
    {
        return in_array($status, [200, 2001, 5400, 5401, 5402, 5001, 5002, 5003, 5005, 5006, 5007], true) || !static::EXCHANGE && $status === 5004;
    }
    private function reply($status, $msg, $tradeId)
    {
        try {
            $body = Json::encode(['status' => $status, 'msg' => $msg, 'data' => ['trade_id' => $tradeId]]);
            $headers = RsaSignature::signBody($body, $this->privateKey);
            return new Reply(200, ['Content-Type' => 'application/json; charset=utf-8'] + $headers, $body);
        } catch (\Exception $e) {
            $this->verifier->log($e);
            return Reply::json(['error' => 'Unable to sign review response'], 500);
        } catch (\Throwable $e) {
            $this->verifier->log($e);
            return Reply::json(['error' => 'Unable to sign review response'], 500);
        }
    }
}
