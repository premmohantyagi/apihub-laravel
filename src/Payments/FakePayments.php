<?php

namespace ApiHub\Laravel\Payments;

use ApiHub\Laravel\Contracts\PaymentGateway;
use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use ApiHub\Laravel\Payments\DTO\ChargeResult;
use ApiHub\Laravel\Payments\DTO\RefundRequest;
use ApiHub\Laravel\Payments\DTO\RefundResult;
use Closure;
use PHPUnit\Framework\Assert;

/**
 * A test double that records charges and refunds instead of calling a gateway.
 * Returned by PaymentManager::fake().
 */
class FakePayments implements PaymentGateway
{
    /** @var ChargeRequest[] */
    public array $charges = [];

    /** @var RefundRequest[] */
    public array $refunds = [];

    public bool $webhookValid = true;

    public function charge(ChargeRequest $request): ChargeResult
    {
        $this->charges[] = $request;

        return new ChargeResult(
            successful: true,
            id: 'fake-charge-'.count($this->charges),
            status: 'succeeded',
            money: $request->money,
            driver: 'fake',
            response: null,
        );
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $this->refunds[] = $request;

        return new RefundResult(
            successful: true,
            id: 'fake-refund-'.count($this->refunds),
            status: 'succeeded',
            driver: 'fake',
            response: null,
        );
    }

    public function verifyWebhook(string $payload, array $headers): bool
    {
        return $this->webhookValid;
    }

    /**
     * @param  (Closure(ChargeRequest): bool)|null  $callback
     */
    public function assertCharged(?Closure $callback = null): void
    {
        $matches = $callback === null
            ? $this->charges
            : array_filter($this->charges, $callback);

        Assert::assertNotEmpty($matches, 'Expected a charge, but none matched.');
    }

    public function assertRefunded(?Closure $callback = null): void
    {
        $matches = $callback === null
            ? $this->refunds
            : array_filter($this->refunds, $callback);

        Assert::assertNotEmpty($matches, 'Expected a refund, but none matched.');
    }

    public function assertNothingCharged(): void
    {
        Assert::assertSame([], $this->charges, 'Expected no charges.');
    }
}
