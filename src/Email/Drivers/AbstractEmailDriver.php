<?php

namespace ApiHub\Laravel\Email\Drivers;

use ApiHub\Laravel\Contracts\Mailer;
use ApiHub\Laravel\Http\Connector;

/**
 * Shared base for email drivers: holds the HTTP connector and the driver's
 * slice of config, and names itself for results and error messages.
 */
abstract class AbstractEmailDriver implements Mailer
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
}
