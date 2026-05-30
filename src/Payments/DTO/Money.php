<?php

namespace ApiHub\Laravel\Payments\DTO;

/**
 * An amount of money held in the currency's smallest unit (cents, paise, …) —
 * the only representation that's exact across gateways. Drivers that need a
 * decimal string (PayPal, Authorize.net) get one from decimal(), which respects
 * zero-decimal currencies like JPY.
 */
class Money
{
    /**
     * Currencies with no minor unit, where the minor amount IS the major amount.
     *
     * @var string[]
     */
    private const ZERO_DECIMAL = [
        'BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW', 'MGA',
        'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];

    public function __construct(
        public int $minorUnits,
        public string $currency,
    ) {}

    public static function of(int $minorUnits, string $currency): self
    {
        return new self($minorUnits, strtoupper($currency));
    }

    public function isZeroDecimal(): bool
    {
        return in_array(strtoupper($this->currency), self::ZERO_DECIMAL, true);
    }

    /**
     * The amount as a major-unit decimal string, e.g. 1050 USD → "10.50",
     * 1050 JPY → "1050".
     */
    public function decimal(): string
    {
        if ($this->isZeroDecimal()) {
            return (string) $this->minorUnits;
        }

        return number_format($this->minorUnits / 100, 2, '.', '');
    }
}
