<?php

namespace ApiHub\Laravel\Payments\DTO;

/**
 * A provider-agnostic refund request, built fluently. A null amount means a
 * full refund of the referenced payment.
 *
 * @example
 * RefundRequest::make()->payment('pi_123')->amount(500, 'USD');
 */
class RefundRequest
{
    public string $paymentId = '';

    public ?Money $money = null;

    public ?string $reason = null;

    /** @var array<string, mixed> */
    public array $options = [];

    public static function make(): self
    {
        return new self;
    }

    public function payment(string $paymentId): static
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    public function amount(int $minorUnits, string $currency): static
    {
        $this->money = Money::of($minorUnits, $currency);

        return $this;
    }

    public function reason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function option(string $key, mixed $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }
}
