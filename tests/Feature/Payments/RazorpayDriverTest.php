<?php

use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Payments\Drivers\RazorpayDriver;
use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use ApiHub\Laravel\Payments\DTO\RefundRequest;
use Illuminate\Support\Facades\Http;

function razorpayDriver(): RazorpayDriver
{
    return new RazorpayDriver(new Connector(retries: 0), [
        'key' => 'rzp_key',
        'secret' => 'rzp_secret',
        'webhook_secret' => 'wh_secret',
    ]);
}

it('creates an order with basic auth', function () {
    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response(['id' => 'order_1', 'status' => 'created'], 200),
    ]);

    $result = razorpayDriver()->charge(
        ChargeRequest::make()->amount(50000, 'INR')->reference('rcpt-1')
    );

    expect($result->successful())->toBeTrue()
        ->and($result->id())->toBe('order_1')
        ->and($result->status())->toBe('created');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/orders')
        && $request->hasHeader('Authorization')
        && $request->data()['amount'] === 50000
        && $request->data()['currency'] === 'INR'
        && $request->data()['receipt'] === 'rcpt-1');
});

it('refunds a payment', function () {
    Http::fake([
        'api.razorpay.com/v1/payments/*/refund' => Http::response(['id' => 'rfnd_1', 'status' => 'processed'], 200),
    ]);

    $result = razorpayDriver()->refund(RefundRequest::make()->payment('pay_1')->amount(10000, 'INR'));

    expect($result->id())->toBe('rfnd_1');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/payments/pay_1/refund')
        && $request->data()['amount'] === 10000);
});

it('verifies a webhook signature', function () {
    $payload = '{"event":"payment.captured"}';
    $signature = hash_hmac('sha256', $payload, 'wh_secret');

    expect(razorpayDriver()->verifyWebhook($payload, ['X-Razorpay-Signature' => $signature]))->toBeTrue()
        ->and(razorpayDriver()->verifyWebhook($payload, ['X-Razorpay-Signature' => 'nope']))->toBeFalse();
});
