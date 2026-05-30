<?php

namespace ApiHub\Laravel\Payments;

use ApiHub\Laravel\Contracts\PaymentGateway;
use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Payments\Drivers\AuthorizeNetDriver;
use ApiHub\Laravel\Payments\Drivers\PayPalDriver;
use ApiHub\Laravel\Payments\Drivers\RazorpayDriver;
use ApiHub\Laravel\Payments\Drivers\SquareDriver;
use ApiHub\Laravel\Payments\Drivers\StripeDriver;
use Illuminate\Support\Manager;

/**
 * Resolves payment gateway drivers from config('apihub.payments').
 *
 * @method \ApiHub\Laravel\Payments\DTO\ChargeResult charge(\ApiHub\Laravel\Payments\DTO\ChargeRequest $request)
 * @method \ApiHub\Laravel\Payments\DTO\RefundResult refund(\ApiHub\Laravel\Payments\DTO\RefundRequest $request)
 * @method bool verifyWebhook(string $payload, array $headers)
 */
class PaymentManager extends Manager
{
    protected ?FakePayments $fake = null;

    public function getDefaultDriver(): string
    {
        return (string) config('apihub.payments.default');
    }

    /**
     * Swap in an in-memory gateway for testing. All resolution returns it until
     * the manager is rebuilt.
     */
    public function fake(): FakePayments
    {
        return $this->fake = new FakePayments;
    }

    public function driver($driver = null): mixed
    {
        return $this->fake ?? parent::driver($driver);
    }

    protected function createStripeDriver(): PaymentGateway
    {
        return new StripeDriver($this->connector(), $this->driverConfig('stripe'));
    }

    protected function createRazorpayDriver(): PaymentGateway
    {
        return new RazorpayDriver($this->connector(), $this->driverConfig('razorpay'));
    }

    protected function createPaypalDriver(): PaymentGateway
    {
        return new PayPalDriver($this->connector(), $this->driverConfig('paypal'));
    }

    protected function createSquareDriver(): PaymentGateway
    {
        return new SquareDriver($this->connector(), $this->driverConfig('square'));
    }

    protected function createAuthorizenetDriver(): PaymentGateway
    {
        return new AuthorizeNetDriver($this->connector(), $this->driverConfig('authorizenet'));
    }

    protected function connector(): Connector
    {
        return app(Connector::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function driverConfig(string $name): array
    {
        return (array) config("apihub.payments.drivers.{$name}", []);
    }
}
