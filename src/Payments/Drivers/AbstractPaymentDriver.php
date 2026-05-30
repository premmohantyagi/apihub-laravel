<?php

namespace ApiHub\Laravel\Payments\Drivers;

use ApiHub\Laravel\Contracts\PaymentGateway;
use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Webhooks\SignatureVerifier;

/**
 * Shared base for payment gateways: holds the HTTP connector and config, names
 * itself, and exposes a signature verifier for webhook checks. Header lookups
 * are case-insensitive, since webhook headers arrive in varied casing.
 */
abstract class AbstractPaymentDriver implements PaymentGateway
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

    protected function verifier(): SignatureVerifier
    {
        return new SignatureVerifier;
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function header(array $headers, string $name): ?string
    {
        $headers = array_change_key_case($headers, CASE_LOWER);

        return $headers[strtolower($name)] ?? null;
    }
}
