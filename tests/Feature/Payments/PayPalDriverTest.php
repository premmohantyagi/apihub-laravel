<?php

use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Payments\Drivers\PayPalDriver;
use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use Illuminate\Support\Facades\Http;

function paypalDriver(): PayPalDriver
{
    return new PayPalDriver(new Connector(retries: 0), [
        'client_id' => 'cid',
        'client_secret' => 'csecret',
        'mode' => 'sandbox',
        'webhook_id' => 'WH-1',
    ]);
}

it('fetches an oauth token then creates an order', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'A21AA'], 200),
        'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response(['id' => 'ORDER-1', 'status' => 'CREATED'], 201),
    ]);

    $result = paypalDriver()->charge(ChargeRequest::make()->amount(1050, 'USD')->description('Order #42'));

    expect($result->successful())->toBeTrue()
        ->and($result->id())->toBe('ORDER-1')
        ->and($result->status())->toBe('CREATED');

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/v2/checkout/orders')) {
            return false;
        }

        $body = $request->data();

        return $request->hasHeader('Authorization', 'Bearer A21AA')
            && $body['intent'] === 'CAPTURE'
            && $body['purchase_units'][0]['amount'] === ['currency_code' => 'USD', 'value' => '10.50'];
    });
});

it('verifies a webhook via the paypal api', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'A21AA'], 200),
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
    ]);

    $valid = paypalDriver()->verifyWebhook('{"id":"evt"}', [
        'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        'PAYPAL-CERT-URL' => 'https://api.paypal.com/cert',
        'PAYPAL-TRANSMISSION-ID' => 'tid',
        'PAYPAL-TRANSMISSION-SIG' => 'sig',
        'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
    ]);

    expect($valid)->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'verify-webhook-signature')
        && $request->data()['webhook_id'] === 'WH-1'
        && $request->data()['transmission_id'] === 'tid');
});
