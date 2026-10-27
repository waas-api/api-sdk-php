<?php

namespace WaasApi\ApiSdkPhp\V2\Exception;

use WaasApi\ApiSdkPhp\V2\ApiResponse;
class ApiException extends \RuntimeException
{
    /**
     * @readonly
     * @var \WaasApi\ApiSdkPhp\V2\ApiResponse
     */
    public $response;
    /**
     * @readonly
     * @var int
     */
    public $status;
    /**
     * @readonly
     * @var string
     */
    public $msg;
    /**
     * @readonly
     * @var mixed
     */
    public $data;
    public function __construct(ApiResponse $response)
    {
        $this->response = $response;
        $this->status = $response->status;
        $this->msg = $response->msg;
        $this->data = $response->data;
        parent::__construct('API status ' . $this->status . ': ' . $this->msg, $this->status);
    }
    public function isSearchProcessing()
    {
        return $this->status === 618;
    }
    public function isRetryable()
    {
        return in_array($this->status, [606, 616, 618], true);
    }
}
