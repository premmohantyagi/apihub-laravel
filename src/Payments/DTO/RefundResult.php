<?php

namespace ApiHub\Laravel\Payments\DTO;

use ApiHub\Laravel\Http\Response;

/**
 * The provider-agnostic outcome of a refund.
 */
class RefundResult
{
    public function __construct(
        public bool $successful,
        public ?string $id,
        public ?string $status,
        public string $driver,
        public ?Response $response = null,
    ) {}

    public function successful(): bool
    {
        return $this->successful;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function status(): ?string
    {
        return $this->status;
    }
}
