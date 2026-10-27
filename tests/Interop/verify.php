<?php

require dirname(__DIR__) . '/bootstrap.php';
use WaasApi\ApiSdkPhp\V2\Crypto\CodeVasp;
use WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature;
$f = fixture('codevasp');
$rsa = fixture('signature_v2_fixture');
$in = json_decode(file_get_contents($argv[1]), true, 512, 0);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new \Exception(json_last_error_msg());
}
$message = base64_decode($in['message']);
same($message, (new CodeVasp($f->fixtureSeedB64))->decrypt($in['ciphertext'], $f->fixtureReceiverPubKeyB64));
CodeVasp::verify($message, $in['ed_signature'], $f->fixtureReceiverPubKeyB64);
RsaSignature::verify($in['message'], $in['rsa_signature'], RsaSignature::publicKey($rsa->public_key_pem));
echo "PASS Go -> PHP RSA, Ed25519 and NaCl box\n";
