<?php

namespace ApiHub\Laravel\Contracts;

use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use ApiHub\Laravel\Payments\DTO\ChargeResult;
use ApiHub\Laravel\Payments\DTO\RefundRequest;
use ApiHub\Laravel\Payments\DTO\RefundResult;

/**
 * The unified interface every payment gateway implements.
 *
 * charge() maps to each gateway's primary server-side payment call, which is
 * not identical across providers (Stripe creates+confirms a PaymentIntent;
 * Razorpay and PayPal create an order; Square creates a payment; Authorize.net
 * runs an auth-capture). The returned id + status, and ->raw() on the response,
 * let callers continue each gateway's own flow.
 */
interface PaymentGateway
{
    public function charge(ChargeRequest $request): ChargeResult;

    public function refund(RefundRequest $request): RefundResult;

    /**
     * Verify the signature of an inbound webhook against the raw request body.
     *
     * @param  array<string, string>  $headers
     */
    public function verifyWebhook(string $payload, array $headers): bool;
}
