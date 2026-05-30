<?php

use ApiHub\Laravel\Email\Drivers\SesDriver;
use ApiHub\Laravel\Email\DTO\Attachment;
use ApiHub\Laravel\Email\DTO\EmailMessage;
use ApiHub\Laravel\Http\Connector;
use Illuminate\Support\Facades\Http;

function sesDriver(): SesDriver
{
    return new SesDriver(new Connector(retries: 0), [
        'key' => 'AKIDEXAMPLE',
        'secret' => 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY',
        'region' => 'us-east-1',
    ]);
}

it('sends a sigv4-signed json request to the regional endpoint', function () {
    Http::fake([
        'email.us-east-1.amazonaws.com/*' => Http::response(['MessageId' => 'ses-1'], 200),
    ]);

    $result = sesDriver()->send(
        EmailMessage::make()->from('noreply@acme.test')->to('user@example.com')->subject('Hi')->html('<p>x</p>')
    );

    expect($result->accepted())->toBeTrue()
        ->and($result->id())->toBe('ses-1');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/v2/email/outbound-emails')
            && $request->hasHeader('X-Amz-Date')
            && str_starts_with($request->header('Authorization')[0], 'AWS4-HMAC-SHA256')
            && str_contains($request->body(), 'FromEmailAddress');
    });
});

it('rejects attachments until raw MIME support lands', function () {
    sesDriver()->send(
        EmailMessage::make()
            ->from('a@acme.test')
            ->to('b@example.com')
            ->text('Hi')
            ->attach(new Attachment('note.txt', 'hello'))
    );
})->throws(InvalidArgumentException::class);
