<?php

namespace ApiHub\Laravel\Messaging;

use ApiHub\Laravel\Contracts\MessageSender;
use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Messaging\Drivers\DiscordDriver;
use ApiHub\Laravel\Messaging\Drivers\Msg91Driver;
use ApiHub\Laravel\Messaging\Drivers\SlackDriver;
use ApiHub\Laravel\Messaging\Drivers\TelegramDriver;
use ApiHub\Laravel\Messaging\Drivers\TwilioDriver;
use ApiHub\Laravel\Messaging\Drivers\VonageDriver;
use ApiHub\Laravel\Messaging\Drivers\WhatsAppDriver;
use Illuminate\Support\Manager;

/**
 * Resolves SMS & messaging drivers from config('apihub.messaging').
 *
 * @method \ApiHub\Laravel\Messaging\DTO\MessageResult send(\ApiHub\Laravel\Messaging\DTO\TextMessage $message)
 */
class MessagingManager extends Manager
{
    protected ?FakeMessaging $fake = null;

    public function getDefaultDriver(): string
    {
        return (string) config('apihub.messaging.default');
    }

    /**
     * Swap in an in-memory sender for testing. All resolution returns it until
     * the manager is rebuilt.
     */
    public function fake(): FakeMessaging
    {
        return $this->fake = new FakeMessaging;
    }

    public function driver($driver = null): mixed
    {
        return $this->fake ?? parent::driver($driver);
    }

    protected function createTwilioDriver(): MessageSender
    {
        return new TwilioDriver($this->connector(), $this->driverConfig('twilio'));
    }

    protected function createVonageDriver(): MessageSender
    {
        return new VonageDriver($this->connector(), $this->driverConfig('vonage'));
    }

    protected function createMsg91Driver(): MessageSender
    {
        return new Msg91Driver($this->connector(), $this->driverConfig('msg91'));
    }

    protected function createTelegramDriver(): MessageSender
    {
        return new TelegramDriver($this->connector(), $this->driverConfig('telegram'));
    }

    protected function createWhatsappDriver(): MessageSender
    {
        return new WhatsAppDriver($this->connector(), $this->driverConfig('whatsapp'));
    }

    protected function createSlackDriver(): MessageSender
    {
        return new SlackDriver($this->connector(), $this->driverConfig('slack'));
    }

    protected function createDiscordDriver(): MessageSender
    {
        return new DiscordDriver($this->connector(), $this->driverConfig('discord'));
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
        return (array) config("apihub.messaging.drivers.{$name}", []);
    }
}
