<?php

namespace WaasApi\ApiSdkPhp\V2\Callback;

use WaasApi\ApiSdkPhp\V2\Crypto\CodeVasp;
use WaasApi\ApiSdkPhp\V2\Exception\SignatureException;
use WaasApi\ApiSdkPhp\V2\Exception\TransportException;
use WaasApi\ApiSdkPhp\V2\Json;
use WaasApi\ApiSdkPhp\V2\TravelRule\SignerInterface;
final class TravelRuleServer
{
    const PATH_SIGNATURE = '/v2/travelRule/signature';
    const PATH_VERIFY_ADDRESS = '/v2/travelRule/callback/verifyAddress';
    const PATH_TRANSFER = '/v2/travelRule/callback/transfer';
    const PATH_TRANSFER_RESULT = '/v2/travelRule/callback/transferResult';
    const PATH_POST_TRANSFER = '/v2/travelRule/callback/postTransfer';
    const PATH_ADDRESS_REGION = '/v2/travelRule/callback/addressRegion';
    const PATH_HEALTH = '/v2/travelRule/callback/health';
    /**
     * @readonly
     * @var \WaasApi\ApiSdkPhp\V2\Callback\Verifier
     */
    private $verifier;
    /**
     * @readonly
     * @var \WaasApi\ApiSdkPhp\V2\TravelRule\SignerInterface|null
     */
    private $signer;
    /**
     * @readonly
     * @var string
     */
    private $expectedPublicKey;
    public function __construct(array $config)
    {
        $this->verifier = new Verifier($config);
        $this->signer = isset($config['signer']) ? $config['signer'] : null;
        $this->expectedPublicKey = isset($config['signerPublicKey']) ? $config['signerPublicKey'] : '';
    }
    public function handleSignature(array $headers, $body, $method = 'POST')
    {
        return $this->dispatch('Signature', $headers, $body, function (TrSignatureRequest $request) {
            if ($this->signer === null) {
                throw new \RuntimeException('No SignerInterface configured');
            }
            $message = CodeVasp::decode(isset($request->message) ? $request->message : '');
            $signature = $this->signer->sign($message);
            CodeVasp::decode($signature, 64);
            return new TrSignatureResponse(['signature' => $signature]);
        }, [], $method);
    }
    public function handleVerifyAddress(array $headers, $body, callable $business, $method = 'POST')
    {
        return $this->dispatch('VerifyAddressCallback', $headers, $body, $business, ['valid', 'invalid'], $method);
    }
    public function handleTransfer(array $headers, $body, callable $business, $method = 'POST')
    {
        return $this->dispatch('TransferCallback', $headers, $body, $business, ['verified', 'denied'], $method);
    }
    public function handleTransferResult(array $headers, $body, callable $business, $method = 'POST')
    {
        return $this->dispatch('TransferResultCallback', $headers, $body, $business, ['normal', 'error'], $method);
    }
    public function handlePostTransfer(array $headers, $body, callable $business, $method = 'POST')
    {
        return $this->dispatch('PostTransferCallback', $headers, $body, $business, ['normal', 'error'], $method);
    }
    public function handleAddressRegion(array $headers, $body, callable $business, $method = 'POST')
    {
        return $this->dispatch('AddressRegionCallback', $headers, $body, $business, [], $method);
    }
    public function handleHealth(array $headers, $body, $business = null, $method = 'POST')
    {
        if ($business !== null && !is_callable($business)) {
            throw new \InvalidArgumentException('business must be callable');
        }
        $business = isset($business) ? $business : static function (TrHealthCallbackRequest $request) {
            $reply = new TrHealthCallbackResponse();
            $reply->healthy = true;
            return $reply;
        };
        return $this->dispatch('HealthCallback', $headers, $body, $business, [], $method);
    }
    public function healthCheckSigner($expectedPublicKey = null)
    {
        if ($this->signer === null) {
            throw new \RuntimeException('No SignerInterface configured');
        }
        $probe = 'travel-rule signer health probe';
        $signature = $this->signer->sign($probe);
        CodeVasp::decode($signature, 64);
        $key = isset($expectedPublicKey) ? $expectedPublicKey : $this->expectedPublicKey;
        if ($key !== '') {
            CodeVasp::verify($probe, $signature, $key);
        }
    }
    private function dispatch($kind, array $headers, $body, callable $business, array $allowed, $method)
    {
        try {
            Input::check($body, $method);
            $this->verifier->verify($headers, $body);
        } catch (\Exception $e) {
            $this->verifier->log($e);
            return self::reject($e instanceof SignatureException ? 401 : ($e instanceof TransportException ? $e->httpStatus : 400));
        } catch (\Throwable $e) {
            $this->verifier->log($e);
            return self::reject($e instanceof SignatureException ? 401 : ($e instanceof TransportException ? $e->httpStatus : 400));
        }
        try {
            $requestType = __NAMESPACE__ . '\Tr' . $kind . 'Request';
            $request = new $requestType(Json::object($body));
            if ($kind === 'Signature') {
                CodeVasp::decode(isset($request->message) ? $request->message : '');
            }
        } catch (\Exception $e) {
            $this->verifier->log($e);
            return self::reject(400);
        } catch (\Throwable $e) {
            $this->verifier->log($e);
            return self::reject(400);
        }
        try {
            $response = $business($request);
            $responseType = __NAMESPACE__ . '\Tr' . $kind . 'Response';
            if (!$response instanceof $responseType) {
                throw new \UnexpectedValueException('Handler must return ' . $responseType);
            }
            if ($allowed !== [] && !in_array($response->result, $allowed, true)) {
                throw new \UnexpectedValueException('Invalid Travel Rule callback result');
            }
            if ($kind === 'HealthCallback') {
                // Include the signer dependency even if business reports healthy.
                try {
                    $this->healthCheckSigner();
                } catch (\Exception $e) {
                    $response->healthy = false;
                    $response->status = 'ERROR';
                    $this->verifier->log($e);
                } catch (\Throwable $e) {
                    $response->healthy = false;
                    $response->status = 'ERROR';
                    $this->verifier->log($e);
                }
                if ($response->status === null || $response->status === '') {
                    $response->status = $response->healthy ? 'OK' : 'ERROR';
                }
                return Reply::json($response, $response->healthy ? 200 : 503);
            }
            return Reply::json($response);
        } catch (\Exception $e) {
            $this->verifier->log($e);
            return self::reject($kind === 'HealthCallback' ? 503 : 500);
        } catch (\Throwable $e) {
            $this->verifier->log($e);
            return self::reject($kind === 'HealthCallback' ? 503 : 500);
        }
    }
    private static function reject($status)
    {
        return Reply::json(['result' => 'error', 'reason_type' => 'UNKNOWN'], $status);
    }
}
