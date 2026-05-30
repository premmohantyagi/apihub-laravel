<?php

namespace ApiHub\Laravel\Payments\Drivers;

use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use ApiHub\Laravel\Payments\DTO\ChargeResult;
use ApiHub\Laravel\Payments\DTO\RefundRequest;
use ApiHub\Laravel\Payments\DTO\RefundResult;

/**
 * PayPal Orders v2 — https://developer.paypal.com/docs/api/orders/v2/
 *
 * Uses OAuth2 client-credentials for a bearer token. charge() creates an order
 * (to be approved/captured by the buyer); refund() acts on a capture id.
 * Webhooks are verified by PayPal's verify-webhook-signature API, not a local
 * HMAC, so verifyWebhook() makes an authenticated call.
 */
class PayPalDriver extends AbstractPaymentDriver
{
    public function charge(ChargeRequest $request): ChargeResult
    {
        $money = $request->requireMoney();
        $base = $this->base();

        $body = array_merge([
            'intent' => 'CAPTURE',
            'purchase_units' => [array_filter([
                'amount' => ['currency_code' => $money->currency, 'value' => $money->decimal()],
                'description' => $request->description,
                'reference_id' => $request->reference,
            ], fn ($value) => $value !== null)],
        ], $request->options);

        $response = $this->connector
            ->post("{$base}/v2/checkout/orders", $body, $this->authHeader())
            ->throw($this->driverName());

        return new ChargeResult(
            successful: in_array($response->json('status'), ['CREATED', 'COMPLETED', 'APPROVED'], true),
            id: $response->json('id'),
            status: $response->json('status'),
            money: $money,
            driver: $this->driverName(),
            response: $response,
        );
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $base = $this->base();
        $url = "{$base}/v2/payments/captures/{$request->paymentId}/refund";

        $body = array_filter([
            'amount' => $request->money !== null
                ? ['value' => $request->money->decimal(), 'currency_code' => $request->money->currency]
                : null,
            'note_to_payer' => $request->reason,
        ], fn ($value) => $value !== null);

        $response = $this->connector->post($url, array_merge($body, $request->options), $this->authHeader())
            ->throw($this->driverName());

        return new RefundResult(
            successful: in_array($response->json('status'), ['COMPLETED', 'PENDING'], true),
            id: $response->json('id'),
            status: $response->json('status'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    public function verifyWebhook(string $payload, array $headers): bool
    {
        $base = $this->base();

        $body = [
            'auth_algo' => $this->header($headers, 'PAYPAL-AUTH-ALGO'),
            'cert_url' => $this->header($headers, 'PAYPAL-CERT-URL'),
            'transmission_id' => $this->header($headers, 'PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $this->header($headers, 'PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $this->header($headers, 'PAYPAL-TRANSMISSION-TIME'),
            'webhook_id' => (string) $this->config('webhook_id'),
            'webhook_event' => json_decode($payload, true),
        ];

        $response = $this->connector
            ->post("{$base}/v1/notifications/verify-webhook-signature", $body, $this->authHeader())
            ->throw($this->driverName());

        return $response->json('verification_status') === 'SUCCESS';
    }

    /**
     * @return array<string, string>
     */
    protected function authHeader(): array
    {
        return ['Authorization' => 'Bearer '.$this->token()];
    }

    protected function token(): string
    {
        $base = $this->base();
        $url = "{$base}/v1/oauth2/token";

        $response = $this->connector->send('POST', $url, fn () => $this->connector->request()
            ->withBasicAuth((string) $this->config('client_id'), (string) $this->config('client_secret'))
            ->asForm()
            ->post($url, ['grant_type' => 'client_credentials']))->throw($this->driverName());

        return (string) $response->json('access_token');
    }

    protected function base(): string
    {
        return $this->config('mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    protected function driverName(): string
    {
        return 'paypal';
    }
}
