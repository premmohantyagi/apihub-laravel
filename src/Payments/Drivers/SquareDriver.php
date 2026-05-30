<?php

namespace ApiHub\Laravel\Payments\Drivers;

use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use ApiHub\Laravel\Payments\DTO\ChargeResult;
use ApiHub\Laravel\Payments\DTO\RefundRequest;
use ApiHub\Laravel\Payments\DTO\RefundResult;
use InvalidArgumentException;

/**
 * Square — https://developer.squareup.com/reference/square
 *
 * charge() creates a Payment from a source id (card nonce); refund() refunds a
 * payment. Webhooks are verified with a base64 HMAC-SHA256 over
 * (notification_url + body).
 */
class SquareDriver extends AbstractPaymentDriver
{
    public function charge(ChargeRequest $request): ChargeResult
    {
        $money = $request->requireMoney();

        if ($request->source === null) {
            throw new InvalidArgumentException('The square driver requires a source id (card nonce).');
        }

        $body = array_merge(array_filter([
            'source_id' => $request->source,
            'idempotency_key' => $this->idempotencyKey($request->reference),
            'amount_money' => ['amount' => $money->minorUnits, 'currency' => $money->currency],
            'customer_id' => $request->customer,
            'note' => $request->description,
        ], fn ($value) => $value !== null), $request->options);

        $response = $this->connector
            ->post($this->base().'/v2/payments', $body, $this->headers())
            ->throw($this->driverName());

        return new ChargeResult(
            successful: $response->json('payment.status') === 'COMPLETED',
            id: $response->json('payment.id'),
            status: $response->json('payment.status'),
            money: $money,
            driver: $this->driverName(),
            response: $response,
        );
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $body = array_merge(array_filter([
            'idempotency_key' => $this->idempotencyKey(null),
            'payment_id' => $request->paymentId,
            'amount_money' => $request->money !== null
                ? ['amount' => $request->money->minorUnits, 'currency' => $request->money->currency]
                : null,
            'reason' => $request->reason,
        ], fn ($value) => $value !== null), $request->options);

        $response = $this->connector
            ->post($this->base().'/v2/refunds', $body, $this->headers())
            ->throw($this->driverName());

        return new RefundResult(
            successful: in_array($response->json('refund.status'), ['COMPLETED', 'PENDING'], true),
            id: $response->json('refund.id'),
            status: $response->json('refund.status'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    public function verifyWebhook(string $payload, array $headers): bool
    {
        $key = (string) $this->config('signature_key');
        $notificationUrl = (string) $this->config('notification_url');
        $signature = $this->header($headers, 'x-square-hmacsha256-signature');

        if ($key === '' || $signature === null) {
            return false;
        }

        $expected = $this->verifier()->hmacBase64($notificationUrl.$payload, $key);

        return $this->verifier()->equals($expected, $signature);
    }

    protected function idempotencyKey(?string $reference): string
    {
        return $reference ?? bin2hex(random_bytes(16));
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.(string) $this->config('access_token'),
            'Square-Version' => (string) $this->config('version', '2024-10-17'),
        ];
    }

    protected function base(): string
    {
        return $this->config('environment') === 'production'
            ? 'https://connect.squareup.com'
            : 'https://connect.squareupsandbox.com';
    }

    protected function driverName(): string
    {
        return 'square';
    }
}
