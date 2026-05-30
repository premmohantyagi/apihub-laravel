<?php

namespace ApiHub\Laravel\Email;

use ApiHub\Laravel\Contracts\Mailer;
use ApiHub\Laravel\Email\Drivers\MailgunDriver;
use ApiHub\Laravel\Email\Drivers\ResendDriver;
use ApiHub\Laravel\Email\Drivers\SendgridDriver;
use ApiHub\Laravel\Email\Drivers\SesDriver;
use ApiHub\Laravel\Http\Connector;
use Illuminate\Support\Manager;

/**
 * Resolves transactional email drivers from config('apihub.email').
 *
 * @method \ApiHub\Laravel\Email\DTO\SendResult send(\ApiHub\Laravel\Email\DTO\EmailMessage $message)
 */
class EmailManager extends Manager
{
    protected ?FakeMailer $fake = null;

    public function getDefaultDriver(): string
    {
        return (string) config('apihub.email.default');
    }

    /**
     * Swap in an in-memory mailer for testing. All resolution returns it until
     * the manager is rebuilt.
     */
    public function fake(): FakeMailer
    {
        return $this->fake = new FakeMailer;
    }

    public function driver($driver = null): mixed
    {
        return $this->fake ?? parent::driver($driver);
    }

    protected function createMailgunDriver(): Mailer
    {
        return new MailgunDriver($this->connector(), $this->driverConfig('mailgun'));
    }

    protected function createSendgridDriver(): Mailer
    {
        return new SendgridDriver($this->connector(), $this->driverConfig('sendgrid'));
    }

    protected function createSesDriver(): Mailer
    {
        return new SesDriver($this->connector(), $this->driverConfig('ses'));
    }

    protected function createResendDriver(): Mailer
    {
        return new ResendDriver($this->connector(), $this->driverConfig('resend'));
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
        return (array) config("apihub.email.drivers.{$name}", []);
    }
}
