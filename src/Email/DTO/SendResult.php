<?php

namespace ApiHub\Laravel\Email\DTO;

use ApiHub\Laravel\Http\Response;

/**
 * The provider-agnostic outcome of sending an email.
 */
class SendResult
{
    public function __construct(
        public bool $accepted,
        public ?string $id,
        public string $driver,
        public ?Response $response = null,
    ) {}

    public function accepted(): bool
    {
        return $this->accepted;
    }

    /**
     * The provider's message id, where one is returned.
     */
    public function id(): ?string
    {
        return $this->id;
    }
}
