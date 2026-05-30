<?php

namespace ApiHub\Laravel\Facades;

use ApiHub\Laravel\Messaging\MessagingManager;
use Illuminate\Support\Facades\Facade;

/**
 * Entry point for the SMS & Messaging category (Twilio, Telegram, WhatsApp …).
 *
 * @method static mixed driver(string|null $driver = null)
 *
 * @see MessagingManager
 */
class Sms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MessagingManager::class;
    }
}
