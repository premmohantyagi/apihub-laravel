<?php

namespace ApiHub\Laravel\Messaging\Drivers;

use ApiHub\Laravel\Contracts\MessageSender;
use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Messaging\DTO\TextMessage;
use InvalidArgumentException;

/**
 * Shared base for messaging drivers: holds the HTTP connector and config, names
 * itself, and guards the destination where one is required.
 */
abstract class AbstractMessagingDriver implements MessageSender
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected Connector $connector,
        protected array $config,
    ) {}

    abstract protected function driverName(): string;

    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    protected function requireTo(TextMessage $message): string
    {
        if ($message->to === null || $message->to === '') {
            throw new InvalidArgumentException("The {$this->driverName()} driver requires a 'to' destination.");
        }

        return $message->to;
    }
}
