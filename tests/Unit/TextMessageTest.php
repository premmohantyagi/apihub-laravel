<?php

use ApiHub\Laravel\Messaging\DTO\TextMessage;

it('builds a message fluently', function () {
    $message = TextMessage::make()
        ->to('+15551234567')
        ->from('Acme')
        ->text('Your code is 1234')
        ->option('unicode', true);

    expect($message->to)->toBe('+15551234567')
        ->and($message->from)->toBe('Acme')
        ->and($message->text)->toBe('Your code is 1234')
        ->and($message->options['unicode'])->toBeTrue();
});

it('passes validation with text', function () {
    $message = TextMessage::make()->to('+1')->text('Hi');
    $message->validate();

    expect($message->text)->toBe('Hi');
});

it('rejects a message with no text', function () {
    TextMessage::make()->to('+1')->validate();
})->throws(InvalidArgumentException::class);
