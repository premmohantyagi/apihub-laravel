<?php

use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Payments\Drivers\SquareDriver;
use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use Illuminate\Support\Facades\Http;

function squareDriver(): SquareDriver
{
    return new SquareDriver(new Connector(retries: 0), [
        'access_token' => 'sq_token',
        'environment' => 'sandbox',
        'version' => '2024-10-17',
        'signature_key' => 'sig_key',
        'notification_url' => 'https://acme.test/webhooks/square',
    ]);
}

it('creates a payment from a source id', function () {
    Http::fake([
        'connect.squareupsandbox.com/v2/payments' => Http::response([
            'payment' => ['id' => 'pay_1', 'status' => 'COMPLETED'],
        ], 200),
    ]);

    $result = squareDriver()->charge(
        ChargeRequest::make()->amount(1050, 'USD')->source('cnon:card-nonce')->reference('idem-1')
    );

    expect($result->successful())->toBeTrue()
        ->and($result->id())->toBe('pay_1')
        ->and($result->status())->toBe('COMPLETED');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), '/v2/payments')
            && $request->hasHeader('Authorization', 'Bearer sq_token')
            && $request->hasHeader('Square-Version', '2024-10-17')
            && $body['source_id'] === 'cnon:card-nonce'
            && $body['idempotency_key'] === 'idem-1'
            && $body['amount_money'] === ['amount' => 1050, 'currency' => 'USD'];
    });
});

it('verifies a webhook over notification url + body', function () {
    $payload = '{"type":"payment.updated"}';
    $signature = base64_encode(hash_hmac('sha256', 'https://acme.test/webhooks/square'.$payload, 'sig_key', true));

    expect(squareDriver()->verifyWebhook($payload, ['x-square-hmacsha256-signature' => $signature]))->toBeTrue()
        ->and(squareDriver()->verifyWebhook($payload, ['x-square-hmacsha256-signature' => 'bad']))->toBeFalse();
});
