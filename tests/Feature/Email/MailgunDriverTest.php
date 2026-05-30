<?php

use ApiHub\Laravel\Email\Drivers\MailgunDriver;
use ApiHub\Laravel\Email\DTO\EmailMessage;
use ApiHub\Laravel\Exceptions\AuthenticationException;
use ApiHub\Laravel\Http\Connector;
use Illuminate\Support\Facades\Http;

function mailgunDriver(): MailgunDriver
{
    return new MailgunDriver(new Connector(retries: 0), [
        'endpoint' => 'https://api.mailgun.net',
        'domain' => 'mg.acme.test',
        'api_key' => 'key-secret',
    ]);
}

it('posts a form-encoded message and returns the cleaned id', function () {
    Http::fake([
        'api.mailgun.net/*' => Http::response(['id' => '<20250530@mg.acme.test>', 'message' => 'Queued'], 200),
    ]);

    $result = mailgunDriver()->send(
        EmailMessage::make()->from('noreply@acme.test', 'Acme')->to('user@example.com')->subject('Hi')->html('<p>x</p>')
    );

    expect($result->accepted())->toBeTrue()
        ->and($result->id())->toBe('20250530@mg.acme.test');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/v3/mg.acme.test/messages')
            && $request->hasHeader('Authorization')
            && $request->data()['from'] === 'Acme <noreply@acme.test>'
            && $request->data()['to'] === 'user@example.com';
    });
});

it('raises a mapped exception on failure', function () {
    Http::fake(['api.mailgun.net/*' => Http::response(['message' => 'bad key'], 401)]);

    mailgunDriver()->send(
        EmailMessage::make()->from('a@acme.test')->to('b@example.com')->text('Hi')
    );
})->throws(AuthenticationException::class);
