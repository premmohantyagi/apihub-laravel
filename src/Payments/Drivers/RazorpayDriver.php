<?php

namespace ApiHub\Laravel\Payments\Drivers;

use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use ApiHub\Laravel\Payments\DTO\ChargeResult;
use ApiHub\Laravel\Payments\DTO\RefundRequest;
use ApiHub\Laravel\Payments\DTO\RefundResult;

/**
 * Razorpay — https://razorpay.com/docs/api/
 *
 * charge() creates an Order (Razorpay's server-side initiation; the payment is
 * completed by the client checkout and captured against this order). refund()
 * acts on a payment id. Webhooks are verified with an HMAC-SHA256 of the body.
 */
class RazorpayDriver extends AbstractPaymentDriver
{
    public function charge(ChargeRequest $request): ChargeResult
    {
        $money = $request->requireMoney();

        $body = array_merge(array_filter([
            'amount' => $money->minorUnits,
            'currency' => $money->currency,
            'receipt' => $request->reference,
            'notes' => $request->metadata ?: null,
        ], fn ($value) => $value !== null), $request->options);

        $response = $this->connector
            ->send('POST', 'https://api.razorpay.com/v1/orders', fn () => $this->basicRequest()
                ->post('https://api.razorpay.com/v1/orders', $body), $body)
            ->throw($this->driverName());

        return new ChargeResult(
            successful: $response->ok(),
            id: $response->json('id'),
            status: $response->json('status'),
            money: $money,
            driver: $this->driverName(),
            response: $response,
        );
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $url = "https://api.razorpay.com/v1/payments/{$request->paymentId}/refund";

        $body = array_merge(array_filter([
            'amount' => $request->money?->minorUnits,
        ], fn ($value) => $value !== null), $request->options);

        $response = $this->connector
            ->send('POST', $url, fn () => $this->basicRequest()->post($url, $body), $body)
            ->throw($this->driverName());

        return new RefundResult(
            successful: $response->ok(),
            id: $response->json('id'),
            status: $response->json('status'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    public function verifyWebhook(string $payload, array $headers): bool
    {
        $secret = (string) $this->config('webhook_secret');
        $signature = $this->header($headers, 'X-Razorpay-Signature');

        if ($secret === '' || $signature === null) {
            return false;
        }

        return $this->verifier()->verifyHmac($payload, $signature, $secret);
    }

    protected function basicRequest()
    {
        return $this->connector->request()
            ->withBasicAuth((string) $this->config('key'), (string) $this->config('secret'));
    }

    protected function driverName(): string
    {
        return 'razorpay';
    }
}
