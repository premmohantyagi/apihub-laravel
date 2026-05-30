<?php

use ApiHub\Laravel\Email\DTO\EmailMessage;

it('builds a message fluently and renders named addresses', function () {
    $message = EmailMessage::make()
        ->from('noreply@acme.test', 'Acme')
        ->to('user@example.com')
        ->cc(['a@example.com', 'b@example.com'])
        ->subject('Hello')
        ->html('<p>Hi</p>');

    expect($message->from->toString())->toBe('Acme <noreply@acme.test>')
        ->and($message->to)->toHaveCount(1)
        ->and($message->cc)->toHaveCount(2)
        ->and($message->subject)->toBe('Hello');
});

it('passes validation when complete', function () {
    $message = EmailMessage::make()
        ->from('a@acme.test')
        ->to('b@example.com')
        ->text('Hi');

    $message->validate();
})->throwsNoExceptions();

it('rejects a message with no from address', function () {
    EmailMessage::make()->to('b@example.com')->text('Hi')->validate();
})->throws(InvalidArgumentException::class);

it('rejects a message with no body', function () {
    EmailMessage::make()->from('a@acme.test')->to('b@example.com')->validate();
})->throws(InvalidArgumentException::class);
