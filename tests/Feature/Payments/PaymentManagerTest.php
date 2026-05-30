<?php

use ApiHub\Laravel\Facades\Payments;
use ApiHub\Laravel\Payments\Drivers\AuthorizeNetDriver;
use ApiHub\Laravel\Payments\Drivers\PayPalDriver;
use ApiHub\Laravel\Payments\Drivers\RazorpayDriver;
use ApiHub\Laravel\Payments\Drivers\SquareDriver;
use ApiHub\Laravel\Payments\Drivers\StripeDriver;
use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use ApiHub\Laravel\Payments\FakePayments;
use ApiHub\Laravel\Payments\PaymentManager;

it('resolves the configured default and named drivers', function () {
    $manager = app(PaymentManager::class);

    expect($manager->driver())->toBeInstanceOf(StripeDriver::class)
        ->and($manager->driver('razorpay'))->toBeInstanceOf(RazorpayDriver::class)
        ->and($manager->driver('paypal'))->toBeInstanceOf(PayPalDriver::class)
        ->and($manager->driver('square'))->toBeInstanceOf(SquareDriver::class)
        ->and($manager->driver('authorizenet'))->toBeInstanceOf(AuthorizeNetDriver::class);
});

it('records charges through the facade when faked', function () {
    $fake = Payments::fake();

    $result = Payments::charge(ChargeRequest::make()->amount(1050, 'USD')->source('tok'));

    expect($fake)->toBeInstanceOf(FakePayments::class)
        ->and($result->successful())->toBeTrue()
        ->and($result->id())->toBe('fake-charge-1');

    $fake->assertCharged(fn (ChargeRequest $request) => $request->money?->minorUnits === 1050);
});

it('lets the fake control webhook validity', function () {
    $fake = Payments::fake();
    $fake->webhookValid = false;

    expect(Payments::verifyWebhook('{}', []))->toBeFalse();
});
