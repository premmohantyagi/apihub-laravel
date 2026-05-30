<?php

namespace ApiHub\Laravel\Facades;

use ApiHub\Laravel\Payments\PaymentManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed driver(string|null $driver = null)
 *
 * @see PaymentManager
 */
class Payments extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentManager::class;
    }
}
