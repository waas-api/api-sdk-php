<?php

namespace WaasApi\ApiSdkPhp\V2;

use WaasApi\ApiSdkPhp\V2\Exception\ApiException;
use WaasApi\ApiSdkPhp\V2\Exception\ProtocolException;
class ApiResponse
{
    const SHAPE = 'any';
    const DATA_TYPE = 'json';
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
    /**
     * @readonly
     * @var string|null
     */
    public $dateTime;
    /**
     * @readonly
     * @var int|string|null
     */
    public $timeStamp;
    /**
     * @readonly
     * @var \stdClass
     */
    public $raw;
    public function __construct($body)
    {
        $wire = Json::object($body);
        if (!isset($wire->status) || !is_int($wire->status) || isset($wire->msg) && !is_string($wire->msg)) {
            throw new ProtocolException('Invalid response envelope');
        }
        $this->raw = $wire;
        $this->status = $wire->status;
        $this->msg = isset($wire->msg) ? $wire->msg : '';
        $this->dateTime = isset($wire->date_time) ? Model::convert($wire->date_time, 'string', 'date_time') : null;
        $this->timeStamp = isset($wire->time_stamp) ? Model::convert($wire->time_stamp, 'int64', 'time_stamp') : null;
        $data = isset($wire->data) ? $wire->data : null;
        // Preserve error data irrespective of success shape; the business status wins.
        if ($this->status !== 200) {
            $this->data = $data;
            return;
        }
        switch (static::SHAPE) {
            case 'object':
                if (!$data instanceof \stdClass) {
                    throw new ProtocolException('Successful response requires a data object');
                }
                $this->data = Model::convert($data, static::DATA_TYPE, 'data');
                break;
            case 'list':
                $this->data = $data === null ? [] : Model::convert($data, 'list:' . static::DATA_TYPE, 'data');
                break;
            case 'string':
                $this->data = $data === null ? null : Model::convert($data, 'string', 'data');
                break;
            case 'none':
                $this->data = null;
                break;
            default:
                $this->data = $data;
                break;
        }
    }
    /**
     * @return static
     */
    public function throwIfError()
    {
        if ($this->status !== 200) {
            throw new ApiException($this);
        }
        return $this;
    }
}
