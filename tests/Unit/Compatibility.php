<?php

use WaasApi\ApiSdkPhp\V2\Json;
use WaasApi\ApiSdkPhp\V2\Exception\ProtocolException;
use WaasApi\ApiSdkPhp\V2\TravelRule\EncryptedPayload;

return [
    'PHP 5.6 JSON errors remain protocol errors without JSON_THROW_ON_ERROR' => function () {
        raises(ProtocolException::class, function () { Json::encode(["invalid" => "\xb1\x31"]); });
        raises(ProtocolException::class, function () { Json::encode(INF); });
        raises(ProtocolException::class, function () { Json::decode('{broken'); });
        same(null, Json::decode('null'));
        same('9223372036854775808', Json::decode('{"id":9223372036854775808}')->id);
        same('{"status":0,"flag":false}', Json::encode(['status' => 0, 'flag' => false]));
    },
    'Compatibility libraries provide secure randomness and immutable encrypted key binding' => function () {
        $random = random_bytes(32);
        same(32, strlen($random));
        truth($random !== random_bytes(32));
        $f = fixture('codevasp');
        $payload = new EncryptedPayload($f->fixtureCiphertextB64, $f->fixtureReceiverPubKeyB64);
        same($f->fixtureReceiverPubKeyB64, $payload->remotePublicKey);
        raises(LogicException::class, function () use ($payload) { $payload->remotePublicKey = 'other'; });
        same($f->fixtureReceiverPubKeyB64, $payload->remotePublicKey);
        if (!extension_loaded('sodium')) {
            truth(class_exists('ParagonIE_Sodium_Compat'));
        }
    },
];
