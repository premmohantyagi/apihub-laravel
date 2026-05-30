<?php

namespace ApiHub\Laravel\Email\DTO;

/**
 * An email participant — an address with an optional display name.
 */
class Address
{
    public function __construct(
        public string $email,
        public ?string $name = null,
    ) {}

    /**
     * Render as "Name <email>" (or just the address when unnamed).
     */
    public function toString(): string
    {
        return $this->name !== null && $this->name !== ''
            ? sprintf('%s <%s>', $this->name, $this->email)
            : $this->email;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
