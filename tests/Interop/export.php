<?php

require dirname(__DIR__) . '/bootstrap.php';
use WaasApi\ApiSdkPhp\V2\Crypto\CodeVasp;
use WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature;
$f = fixture('codevasp');
$rsa = fixture('signature_v2_fixture');
$body = base64_encode(random_bytes(80));
$message = base64_decode($body);
$key = new CodeVasp($f->fixtureSeedB64);
echo json_encode(['message' => $body, 'ciphertext' => $key->encrypt($message, $f->fixtureReceiverPubKeyB64), 'ed_signature' => $key->sign($message), 'rsa_signature' => RsaSignature::sign($body, RsaSignature::privateKey($rsa->private_key_pem))], 0);
