<?php

if (is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}
spl_autoload_register(static function ($class) {
    $prefix = 'WaasApi\ApiSdkPhp\\';
    if (strncmp($class, $prefix, strlen($prefix)) === 0) {
        $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', (string) substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});
/**
 * @return mixed
 */
function fixture($name)
{
    return json_decode(file_get_contents(__DIR__ . '/Fixtures/' . $name . '.json'), false, 512, JSON_BIGINT_AS_STRING);
}
/**
 * @param mixed $want
 * @param mixed $got
 */
function same($want, $got, $context = '')
{
    if ($want !== $got) {
        throw new RuntimeException($context . ' expected ' . var_export($want, true) . ', got ' . var_export($got, true));
    }
}
function truth($condition, $context = '')
{
    same(true, $condition, $context);
}
function raises($type, callable $call)
{
    try {
        $call();
    } catch (\Exception $e) {
        if (!$e instanceof $type) {
            throw new RuntimeException('Expected ' . $type . ', got ' . get_class($e) . ': ' . $e->getMessage(), 0, $e);
        }
        return $e;
    } catch (Throwable $e) {
        if (!$e instanceof $type) {
            throw new RuntimeException('Expected ' . $type . ', got ' . get_class($e) . ': ' . $e->getMessage(), 0, $e);
        }
        return $e;
    }
    throw new RuntimeException('Expected ' . $type . ', no exception was thrown');
}
function config(array $overrides = [])
{
    $f = fixture('signature_v2_fixture');
    return $overrides + ['baseURL' => 'https://example.test/shopapi', 'appId' => 'demo-app', 'privateKey' => $f->private_key_pem, 'platformPublicKey' => $f->public_key_pem];
}
function normalizedJson($json)
{
    $sort = static function ($value) use (&$sort) {
        if (is_object($value)) {
            $fields = (array) $value;
            ksort($fields);
            return (object) array_map($sort, $fields);
        }
        return is_array($value) ? array_map($sort, $value) : $value;
    };
    return \WaasApi\ApiSdkPhp\V2\Json::encode($sort(json_decode($json, false, 512, JSON_BIGINT_AS_STRING)));
}
