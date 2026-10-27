<?php

namespace WaasApi\ApiSdkPhp\V2;

use WaasApi\ApiSdkPhp\V2\Exception\ProtocolException;
/** Wire names are explicit; false, zero, null and omitted fields stay distinct. */
abstract class Model implements \JsonSerializable
{
    const FIELDS = [];
    /**
     * @var mixed[]
     */
    protected $present = [];
    /**
     * @var mixed[]
     */
    private $extra = [];
    /**
     * @param mixed[]|\stdClass $values
     */
    public function __construct($values = [])
    {
        if (!is_array($values) && !$values instanceof \stdClass) {
            throw new \InvalidArgumentException('DTO values must be an array or object');
        }
        $fields = static::FIELDS;
        foreach ((array) $values as $key => $value) {
            $wire = $key;
            if (!isset($fields[$wire])) {
                $wire = null;
                foreach ($fields as $candidate => list($property)) {
                    if ($property === $key) {
                        $wire = $candidate;
                        break;
                    }
                }
            }
            if ($wire === null || !isset($fields[$wire])) {
                if ($this instanceof RequestDto) {
                    throw new \InvalidArgumentException('Unknown request field: ' . $key);
                }
                $this->extra[$key] = $value;
                continue;
            }
            list($property, $type) = $fields[$wire];
            $this->{$property} = $value === null ? null : self::convert($value, $type, $wire);
            $this->present[$wire] = true;
        }
    }
    /** Decode without ever converting oversized JSON integers to float.
     * @param string $body
     * @return static */
    public static function fromJson($body)
    {
        return new static(Json::object($body));
    }
    /**
     * @param mixed $value
     * @param string $type
     * @param string $field
     * @return mixed
     */
    public static function convert($value, $type, $field)
    {
        if (strncmp($type, 'list:', strlen('list:')) === 0 || strncmp($type, 'map:', strlen('map:')) === 0) {
            $list = strncmp($type, 'list:', strlen('list:')) === 0;
            if ($list && (!is_array($value) || !Compat::isList($value))) {
                throw new ProtocolException($field . ' must be a JSON array');
            }
            if (!$list && !is_array($value) && !$value instanceof \stdClass) {
                throw new ProtocolException($field . ' must be a JSON object');
            }
            $out = [];
            foreach ((array) $value as $key => $item) {
                $out[$key] = self::convert($item, (string) substr($type, $list ? 5 : 4), $field . '[' . $key . ']');
            }
            return $list ? $out : (object) $out;
        }
        switch ($type) {
            case 'string':
                $ok = is_string($value);
                break;
            case 'bool':
                $ok = is_bool($value);
                break;
            case 'int':
                $ok = is_int($value);
                break;
            case 'int64':
                $ok = is_int($value) || is_string($value) && preg_match('/^-?\d+$/D', $value);
                break;
            case 'number':
                $ok = is_int($value) || is_float($value);
                break;
            case 'json':
                $ok = true;
                break;
            default:
                $ok = $value instanceof $type || $value instanceof \stdClass || is_array($value);
                break;
        }
        if (!$ok) {
            throw new ProtocolException($field . ' has invalid type; expected ' . $type);
        }
        if (class_exists($type) && !$value instanceof $type) {
            if (is_array($value) && $value !== [] && Compat::isList($value)) {
                throw new ProtocolException($field . ' must be an object');
            }
            return new $type($value);
        }
        return $value;
    }
    /**
     * @param string $wire
     */
    protected function omit($wire)
    {
        $fields = static::FIELDS;
        $property = $fields[$wire][0];
        $this->{$property} = null;
        unset($this->present[$wire]);
    }
    /**
     * @return object
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $fields = static::FIELDS;
        $data = $this->extra;
        foreach ($fields as $wire => list($property, $type, $required)) {
            $value = $this->{$property};
            if ($value === null) {
                if ($this instanceof RequestDto && $required) {
                    throw new \InvalidArgumentException($wire . ' is required');
                }
                if (!$required && !isset($this->present[$wire])) {
                    continue;
                }
                // Callback reply schemas require these keys even when empty.
                if ($required && $this instanceof Callback\CallbackResponse) {
                    switch ($type) {
                        case 'string':
                            $value = '';
                            break;
                        case 'bool':
                            $value = false;
                            break;
                        case 'int':
                        case 'int64':
                        case 'number':
                            $value = 0;
                            break;
                        default:
                            $value = strncmp($type, 'list:', strlen('list:')) === 0 ? [] : null;
                            break;
                    }
                }
            } else {
                $value = self::convert($value, $type, $wire);
            }
            if ($this instanceof RequestDto && $required && $value === '') {
                throw new \InvalidArgumentException($wire . ' must not be empty');
            }
            $data[$wire] = $value;
        }
        if ($this instanceof RequestDto) {
            RequestValidator::validate($this, $data);
        }
        if ($this instanceof TravelRule\Ivms101\Person && isset($data['naturalPerson'], $data['legalPerson'])) {
            throw new \InvalidArgumentException('IVMS101 Person must be naturalPerson or legalPerson, not both');
        }
        return (object) $data;
    }
    public function toArray()
    {
        return (array) $this->jsonSerialize();
    }
}
