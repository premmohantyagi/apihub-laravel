<?php

use ApiHub\Laravel\Payments\DTO\Money;

it('keeps minor units and renders a two-decimal string for normal currencies', function () {
    $money = Money::of(1050, 'usd');

    expect($money->minorUnits)->toBe(1050)
        ->and($money->currency)->toBe('USD')
        ->and($money->decimal())->toBe('10.50');
});

it('does not divide zero-decimal currencies', function () {
    $money = Money::of(1050, 'JPY');

    expect($money->isZeroDecimal())->toBeTrue()
        ->and($money->decimal())->toBe('1050');
});

it('pads whole amounts to two decimals', function () {
    expect(Money::of(1000, 'EUR')->decimal())->toBe('10.00');
});
