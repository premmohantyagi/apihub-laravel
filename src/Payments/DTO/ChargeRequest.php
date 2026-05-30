<?php

namespace ApiHub\Laravel\Payments\DTO;

use InvalidArgumentException;

/**
 * A provider-agnostic request to take a payment, built fluently.
 *
 * @example
 * ChargeRequest::make()
 *     ->amount(1050, 'USD')      // 1050 = $10.50
 *     ->source('pm_card_visa')   // gateway token / nonce / payment method
 *     ->description('Order #42');
 */
class ChargeRequest
{
    public ?Money $money = null;

    public ?string $source = null;

    public ?string $customer = null;

    public ?string $description = null;

    public ?string $reference = null;

    /** @var array<string, string> */
    public array $metadata = [];

    /** @var array<string, mixed> */
    public array $options = [];

    public static function make(): self
    {
        return new self;
    }

    public function amount(int $minorUnits, string $currency): static
    {
        $this->money = Money::of($minorUnits, $currency);

        return $this;
    }

    public function money(Money $money): static
    {
        $this->money = $money;

        return $this;
    }

    public function source(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function customer(string $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * A caller-supplied reference, used as the idempotency key where the gateway
     * supports one (Square, Stripe).
     */
    public function reference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * @param  array<string, string>  $metadata
     */
    public function metadata(array $metadata): static
    {
        $this->metadata = array_merge($this->metadata, $metadata);

        return $this;
    }

    public function option(string $key, mixed $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function requireMoney(): Money
    {
        if ($this->money === null) {
            throw new InvalidArgumentException('A charge requires an amount.');
        }

        return $this->money;
    }
}
