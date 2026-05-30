<?php

use ApiHub\Laravel\Facades\Sms;
use ApiHub\Laravel\Messaging\Drivers\DiscordDriver;
use ApiHub\Laravel\Messaging\Drivers\TelegramDriver;
use ApiHub\Laravel\Messaging\Drivers\TwilioDriver;
use ApiHub\Laravel\Messaging\Drivers\WhatsAppDriver;
use ApiHub\Laravel\Messaging\DTO\TextMessage;
use ApiHub\Laravel\Messaging\FakeMessaging;
use ApiHub\Laravel\Messaging\MessagingManager;

it('resolves the configured default and named drivers', function () {
    $manager = app(MessagingManager::class);

    expect($manager->driver())->toBeInstanceOf(TwilioDriver::class)
        ->and($manager->driver('telegram'))->toBeInstanceOf(TelegramDriver::class)
        ->and($manager->driver('whatsapp'))->toBeInstanceOf(WhatsAppDriver::class)
        ->and($manager->driver('discord'))->toBeInstanceOf(DiscordDriver::class);
});

it('records messages through the facade when faked', function () {
    $fake = Sms::fake();

    Sms::send(TextMessage::make()->to('+15551234567')->text('Hi'));

    expect($fake)->toBeInstanceOf(FakeMessaging::class);
    $fake->assertSentCount(1);
    $fake->assertSent(fn (TextMessage $message) => $message->to === '+15551234567');
});
