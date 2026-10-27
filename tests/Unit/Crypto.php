<?php

use WaasApi\ApiSdkPhp\V2\Crypto\RsaSignature as Rsa;
use WaasApi\ApiSdkPhp\V2\Crypto\CodeVasp;
use WaasApi\ApiSdkPhp\V2\Exception\SignatureException;
return ['RSA request and body match Go fixed signature vectors exactly' => static function () {
    $f = fixture('signature_v2_fixture');
    $key = Rsa::privateKey($f->private_key_pem);
    $public = Rsa::publicKey($f->public_key_pem);
    $r = $f->request;
    $canonical = Rsa::requestCanonical($r->method, $r->path, $r->app_id, $r->key_version, $r->timestamp, $r->nonce, $r->body);
    same($r->canonical, $canonical);
    same($r->signature, Rsa::sign($canonical, $key));
    Rsa::verify($canonical, $r->signature, $public);
    $r = $f->response;
    same($r->canonical, Rsa::bodyCanonical($r->timestamp, $r->nonce, $r->algorithm, $r->body));
    $headers = Rsa::signBody($r->body, $key, (int) $r->timestamp, $r->nonce);
    same($r->signature, $headers['X-Cbc-Signature']);
    Rsa::verifyBody($r->body, $headers, $public, 300, (int) $r->timestamp);
    raises(SignatureException::class, function () use ($r, $headers, $public) {
        return Rsa::verifyBody($r->body . ' ', $headers, $public, 300, (int) $r->timestamp);
    });
    raises(SignatureException::class, function () use ($r, $headers, $public) {
        return Rsa::verifyBody($r->body, $headers, $public, 300, (int) $r->timestamp + 301);
    });
    raises(SignatureException::class, function () use ($r, $headers, $public) {
        return Rsa::verifyBody($r->body, $headers, $public, 300, (int) $r->timestamp - 301);
    });
}, 'RSA validates PEM formats, algorithm, complete and case insensitive headers' => static function () {
    raises(SignatureException::class, function () {
        return Rsa::privateKey('bad');
    });
    raises(SignatureException::class, function () {
        return Rsa::publicKey('bad');
    });
    $weak = openssl_pkey_new(['private_key_bits' => 1024, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($weak, $pem);
    raises(SignatureException::class, function () use ($pem) {
        return Rsa::privateKey($pem);
    });
    $f = fixture('signature_v2_fixture');
    $key = Rsa::privateKey($f->private_key_pem);
    $pub = Rsa::publicKey($f->public_key_pem);
    // PKCS#1 (V1 keys, OpenSSL 1.0 exports): unwrap RSAPrivateKey from the PKCS#8 OCTET STRING.
    $der = base64_decode(preg_replace('/-----[^-]+-----|\s/', '', $f->private_key_pem));
    same("\x04\x82", substr($der, 22, 2));
    $pkcs1 = "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split(base64_encode(substr($der, 26)), 64, "\n") . "-----END RSA PRIVATE KEY-----\n";
    same(Rsa::sign('vector', $key), Rsa::sign('vector', Rsa::privateKey($pkcs1)));
    raises(SignatureException::class, function () use ($pkcs1) {
        return Rsa::privateKey(preg_replace('/KEY-----\n/', "KEY-----\nProc-Type: 4,ENCRYPTED\nDEK-Info: AES-128-CBC,00000000000000000000000000000000\n\n", $pkcs1, 1));
    });
    $headers = Rsa::signBody('{}', $key);
    $lower = array_change_key_case($headers, CASE_LOWER);
    Rsa::verifyBody('{}', $lower, $pub);
    $lower['X-Cbc-Nonce'] = 'duplicate';
    raises(SignatureException::class, function () use ($lower, $pub) {
        return Rsa::verifyBody('{}', $lower, $pub);
    });
    unset($headers['X-Cbc-Signature']);
    raises(SignatureException::class, function () use ($headers, $pub) {
        return Rsa::verifyBody('{}', $headers, $pub);
    });
    $headers['X-Cbc-Signature'] = 'invalid';
    $headers['X-Cbc-Sign-Alg'] = 'RSA-MD5';
    raises(SignatureException::class, function () use ($headers, $pub) {
        return Rsa::verifyBody('{}', $headers, $pub);
    });
    truth((bool) preg_match('/^[a-f0-9]{32}$/D', Rsa::nonce()));
    truth(Rsa::nonce() !== Rsa::nonce());
}, 'Ed25519 seed, full key and published signatures interoperate with Go' => static function () {
    $f = fixture('codevasp');
    $crypto = new CodeVasp($f->fixtureSeedB64);
    same($f->fixturePubKeyB64, $crypto->publicKey());
    $message = base64_decode($f->fixtureMessageB64, true);
    same($f->fixtureSignatureB64, $crypto->sign($message));
    CodeVasp::verify($message, $f->fixtureSignatureB64, $crypto->publicKey());
    $full = base64_encode(base64_decode($f->fixtureSeedB64) . base64_decode($f->fixturePubKeyB64));
    same($crypto->sign($message), (new CodeVasp($full))->sign($message));
    raises(SignatureException::class, function () use ($message, $f, $crypto) {
        return CodeVasp::verify($message . 'x', $f->fixtureSignatureB64, $crypto->publicKey());
    });
    raises(SignatureException::class, function () {
        return new CodeVasp(base64_encode('short'));
    });
    $bad = base64_encode(base64_decode($f->fixtureSeedB64) . base64_decode($f->fixtureReceiverPubKeyB64));
    raises(SignatureException::class, function () use ($bad) {
        return new CodeVasp($bad);
    });
}, 'NaCl box decrypts Go vector, round trips both ways, detects tampering and wrong key' => static function () {
    $f = fixture('codevasp');
    $sender = new CodeVasp($f->fixtureSeedB64);
    $receiver = new CodeVasp($f->fixtureReceiverSeedB64);
    same($f->fixturePlaintext, $receiver->decrypt($f->fixtureCiphertextB64, $sender->publicKey()));
    $cipher = $sender->encrypt($f->fixturePlaintext, $receiver->publicKey());
    same($f->fixturePlaintext, $receiver->decrypt($cipher, $sender->publicKey()));
    truth($cipher !== $sender->encrypt($f->fixturePlaintext, $receiver->publicKey()));
    same('reverse', $sender->decrypt($receiver->encrypt('reverse', $sender->publicKey()), $receiver->publicKey()));
    $raw = base64_decode($cipher);
    $raw[30] = chr(ord($raw[30]) ^ 1);
    raises(SignatureException::class, function () use ($receiver, $raw, $sender) {
        return $receiver->decrypt(base64_encode($raw), $sender->publicKey());
    });
    $other = new CodeVasp(base64_encode(random_bytes(32)));
    raises(SignatureException::class, function () use ($other, $cipher, $sender) {
        return $other->decrypt($cipher, $sender->publicKey());
    });
    raises(SignatureException::class, function () use ($receiver, $sender) {
        return $receiver->decrypt('bad', $sender->publicKey());
    });
}];
