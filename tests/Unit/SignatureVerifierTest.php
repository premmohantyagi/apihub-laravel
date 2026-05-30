<?php

use ApiHub\Laravel\Webhooks\SignatureVerifier;

it('verifies a matching hmac signature', function () {
    $verifier = new SignatureVerifier;
    $payload = '{"event":"charge.succeeded"}';
    $secret = 'whsec_test';

    $signature = $verifier->hmac($payload, $secret);

    expect($verifier->verifyHmac($payload, $signature, $secret))->toBeTrue();
});

it('rejects a tampered payload', function () {
    $verifier = new SignatureVerifier;
    $secret = 'whsec_test';
    $signature = $verifier->hmac('{"amount":100}', $secret);

    expect($verifier->verifyHmac('{"amount":999}', $signature, $secret))->toBeFalse();
});
