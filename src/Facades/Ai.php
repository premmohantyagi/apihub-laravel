<?php

namespace ApiHub\Laravel\Facades;

use ApiHub\Laravel\Ai\AiManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed driver(string|null $driver = null)
 *
 * @see AiManager
 */
class Ai extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiManager::class;
    }
}
