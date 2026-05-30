<?php

namespace ApiHub\Laravel\Payments\Drivers;

use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use ApiHub\Laravel\Payments\DTO\ChargeResult;
use ApiHub\Laravel\Payments\DTO\RefundRequest;
use ApiHub\Laravel\Payments\DTO\RefundResult;

/**
 * Stripe — https://stripe.com/docs/api
 *
 * charge() creates and confirms a PaymentIntent (form-encoded, as the Stripe
 * API expects). Webhooks are verified with the t=…,v1=… scheme.
 */
class StripeDriver extends AbstractPaymentDriver
{
    public function charge(ChargeRequest $request): ChargeResult
    {
        $money = $request->requireMoney();

        $fields = array_filter([
            'amount' => $money->minorUnits,
            'currency' => strtolower($money->currency),
            'payment_method' => $request->source,
            'customer' => $request->customer,
            'description' => $request->description,
            'confirm' => 'true',
        ], fn ($value) => $value !== null);

        foreach ($request->metadata as $key => $value) {
            $fields["metadata[{$key}]"] = $value;
        }

        $fields = array_merge($fields, $request->options);

        $response = $this->post('https://api.stripe.com/v1/payment_intents', $fields, $request->reference)
            ->throw($this->driverName());

        return new ChargeResult(
            successful: $response->json('status') === 'succeeded',
            id: $response->json('id'),
            status: $response->json('status'),
            money: $money,
            driver: $this->driverName(),
            response: $response,
        );
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $fields = array_filter([
            'payment_intent' => $request->paymentId,
            'amount' => $request->money?->minorUnits,
            'reason' => $request->reason,
        ], fn ($value) => $value !== null);

        $response = $this->post('https://api.stripe.com/v1/refunds', array_merge($fields, $request->options))
            ->throw($this->driverName());

        return new RefundResult(
            successful: in_array($response->json('status'), ['succeeded', 'pending'], true),
            id: $response->json('id'),
            status: $response->json('status'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    public function verifyWebhook(string $payload, array $headers): bool
    {
        $secret = (string) $this->config('webhook_secret');
        $header = $this->header($headers, 'Stripe-Signature');

        if ($secret === '' || $header === null) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $segment) {
            [$key, $value] = array_pad(explode('=', $segment, 2), 2, '');
            $parts[trim($key)] = trim($value);
        }

        $timestamp = $parts['t'] ?? '';
        $signature = $parts['v1'] ?? '';

        if ($timestamp === '' || $signature === '') {
            return false;
        }

        return $this->verifier()->verifyHmac("{$timestamp}.{$payload}", $signature, $secret);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    protected function post(string $url, array $fields, ?string $idempotencyKey = null)
    {
        $secret = (string) $this->config('secret');

        return $this->connector->send('POST', $url, function () use ($url, $secret, $fields, $idempotencyKey) {
            $request = $this->connector->request()->withToken($secret)->asForm();

            if ($idempotencyKey !== null) {
                $request->withHeaders(['Idempotency-Key' => $idempotencyKey]);
            }

            return $request->post($url, $fields);
        }, $fields);
    }

    protected function driverName(): string
    {
        return 'stripe';
    }
}
