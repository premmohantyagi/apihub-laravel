<?php

namespace ApiHub\Laravel\Facades;

use ApiHub\Laravel\Email\EmailManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed driver(string|null $driver = null)
 *
 * @see EmailManager
 */
class Email extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EmailManager::class;
    }
}
