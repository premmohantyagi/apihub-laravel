<?php

use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Payments\Drivers\StripeDriver;
use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use ApiHub\Laravel\Payments\DTO\RefundRequest;
use Illuminate\Support\Facades\Http;

function stripeDriver(): StripeDriver
{
    return new StripeDriver(new Connector(retries: 0), [
        'secret' => 'sk_test_123',
        'webhook_secret' => 'whsec_test',
    ]);
}

it('creates and confirms a payment intent', function () {
    Http::fake([
        'api.stripe.com/v1/payment_intents' => Http::response(['id' => 'pi_1', 'status' => 'succeeded'], 200),
    ]);

    $result = stripeDriver()->charge(
        ChargeRequest::make()->amount(1050, 'USD')->source('pm_card_visa')->reference('order-42')->metadata(['order' => '42'])
    );

    expect($result->successful())->toBeTrue()
        ->and($result->id())->toBe('pi_1')
        ->and($result->status())->toBe('succeeded');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), '/v1/payment_intents')
            && $request->hasHeader('Authorization', 'Bearer sk_test_123')
            && $request->hasHeader('Idempotency-Key', 'order-42')
            && $body['amount'] === 1050
            && $body['currency'] === 'usd'
            && $body['payment_method'] === 'pm_card_visa'
            && $body['confirm'] === 'true'
            && $body['metadata[order]'] === '42';
    });
});

it('refunds a payment intent', function () {
    Http::fake([
        'api.stripe.com/v1/refunds' => Http::response(['id' => 're_1', 'status' => 'succeeded'], 200),
    ]);

    $result = stripeDriver()->refund(RefundRequest::make()->payment('pi_1')->amount(500, 'USD'));

    expect($result->successful())->toBeTrue()
        ->and($result->id())->toBe('re_1');

    Http::assertSent(fn ($request) => $request->data()['payment_intent'] === 'pi_1'
        && $request->data()['amount'] === 500);
});

it('verifies a webhook signature', function () {
    $payload = '{"id":"evt_1"}';
    $timestamp = '1700000000';
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test');

    $valid = stripeDriver()->verifyWebhook($payload, ['Stripe-Signature' => "t={$timestamp},v1={$signature}"]);
    $invalid = stripeDriver()->verifyWebhook($payload, ['Stripe-Signature' => "t={$timestamp},v1=deadbeef"]);

    expect($valid)->toBeTrue()
        ->and($invalid)->toBeFalse();
});
