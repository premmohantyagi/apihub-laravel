<?php

use ApiHub\Laravel\Email\Drivers\ResendDriver;
use ApiHub\Laravel\Email\DTO\EmailMessage;
use ApiHub\Laravel\Http\Connector;
use Illuminate\Support\Facades\Http;

it('posts json with a bearer token and reads the id from the body', function () {
    Http::fake([
        'api.resend.com/*' => Http::response(['id' => 're_abc123'], 200),
    ]);

    $driver = new ResendDriver(new Connector(retries: 0), ['api_key' => 're_test']);

    $result = $driver->send(
        EmailMessage::make()
            ->from('noreply@acme.test', 'Acme')
            ->to('user@example.com')
            ->subject('Hi')
            ->html('<p>x</p>')
    );

    expect($result->accepted())->toBeTrue()
        ->and($result->id())->toBe('re_abc123');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $request->hasHeader('Authorization', 'Bearer re_test')
            && $body['from'] === 'Acme <noreply@acme.test>'
            && $body['to'][0] === 'user@example.com'
            && $body['subject'] === 'Hi';
    });
});
