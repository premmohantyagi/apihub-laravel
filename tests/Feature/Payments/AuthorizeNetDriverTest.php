<?php

use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Payments\Drivers\AuthorizeNetDriver;
use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use Illuminate\Support\Facades\Http;

function authorizeNetDriver(): AuthorizeNetDriver
{
    return new AuthorizeNetDriver(new Connector(retries: 0), [
        'login_id' => 'login',
        'transaction_key' => 'txnkey',
        'environment' => 'sandbox',
        'signature_key' => 'ABC123',
    ]);
}

it('runs an auth-capture and parses a bom-prefixed json response', function () {
    // Authorize.Net prefixes its JSON with a UTF-8 BOM.
    $json = "\xEF\xBB\xBF".json_encode([
        'transactionResponse' => ['transId' => '60160000001', 'responseCode' => '1'],
        'messages' => ['resultCode' => 'Ok'],
    ]);

    Http::fake([
        'apitest.authorize.net/*' => Http::response($json, 200),
    ]);

    $result = authorizeNetDriver()->charge(
        ChargeRequest::make()->amount(1050, 'USD')->source('opaque-token-value')->reference('inv-1')
    );

    expect($result->successful())->toBeTrue()
        ->and($result->id())->toBe('60160000001');

    Http::assertSent(function ($request) {
        $body = $request->data();
        $transaction = $body['createTransactionRequest']['transactionRequest'];

        return $body['createTransactionRequest']['merchantAuthentication']['name'] === 'login'
            && $transaction['transactionType'] === 'authCaptureTransaction'
            && $transaction['amount'] === '10.50'
            && $transaction['payment']['opaqueData']['dataValue'] === 'opaque-token-value';
    });
});

it('verifies a webhook with an hmac-sha512 hex digest', function () {
    $payload = '{"notificationId":"abc"}';
    $digest = hash_hmac('sha512', $payload, 'ABC123');

    expect(authorizeNetDriver()->verifyWebhook($payload, ['X-ANET-Signature' => 'sha512='.strtoupper($digest)]))->toBeTrue()
        ->and(authorizeNetDriver()->verifyWebhook($payload, ['X-ANET-Signature' => 'sha512='.strtolower($digest)]))->toBeTrue()
        ->and(authorizeNetDriver()->verifyWebhook($payload, ['X-ANET-Signature' => 'sha512=00']))->toBeFalse();
});
