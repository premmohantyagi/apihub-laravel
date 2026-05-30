<?php

namespace ApiHub\Laravel\Payments\DTO;

use ApiHub\Laravel\Http\Response;

/**
 * The provider-agnostic outcome of a charge.
 */
class ChargeResult
{
    public function __construct(
        public bool $successful,
        public ?string $id,
        public ?string $status,
        public ?Money $money,
        public string $driver,
        public ?Response $response = null,
    ) {}

    public function successful(): bool
    {
        return $this->successful;
    }

    /**
     * The gateway id — a payment intent, order, payment, or transaction id
     * depending on the provider.
     */
    public function id(): ?string
    {
        return $this->id;
    }

    public function status(): ?string
    {
        return $this->status;
    }
}
