<?php

use ApiHub\Laravel\Email\Drivers\MailgunDriver;
use ApiHub\Laravel\Email\Drivers\ResendDriver;
use ApiHub\Laravel\Email\Drivers\SesDriver;
use ApiHub\Laravel\Email\DTO\EmailMessage;
use ApiHub\Laravel\Email\EmailManager;
use ApiHub\Laravel\Email\FakeMailer;
use ApiHub\Laravel\Facades\Email;

it('resolves the configured default and named drivers', function () {
    $manager = app(EmailManager::class);

    expect($manager->driver())->toBeInstanceOf(MailgunDriver::class)
        ->and($manager->driver('resend'))->toBeInstanceOf(ResendDriver::class)
        ->and($manager->driver('ses'))->toBeInstanceOf(SesDriver::class);
});

it('records messages through the facade when faked', function () {
    $fake = Email::fake();

    Email::send(
        EmailMessage::make()->from('a@acme.test')->to('b@example.com')->subject('Hi')->text('Hello')
    );

    expect($fake)->toBeInstanceOf(FakeMailer::class);
    $fake->assertSentCount(1);
    $fake->assertSent(fn (EmailMessage $message) => $message->subject === 'Hi');
});
