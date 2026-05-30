<?php

use ApiHub\Laravel\Email\Drivers\SendgridDriver;
use ApiHub\Laravel\Email\DTO\EmailMessage;
use ApiHub\Laravel\Http\Connector;
use Illuminate\Support\Facades\Http;

it('posts json with a bearer token and reads the message id header', function () {
    Http::fake([
        'api.sendgrid.com/*' => Http::response('', 202, ['X-Message-Id' => 'sg-message-1']),
    ]);

    $driver = new SendgridDriver(new Connector(retries: 0), ['api_key' => 'SG.test']);

    $result = $driver->send(
        EmailMessage::make()
            ->from('noreply@acme.test', 'Acme')
            ->to('user@example.com')
            ->subject('Hi')
            ->text('plain')
            ->html('<p>x</p>')
    );

    expect($result->accepted())->toBeTrue()
        ->and($result->id())->toBe('sg-message-1');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $request->hasHeader('Authorization', 'Bearer SG.test')
            && $body['subject'] === 'Hi'
            && $body['from']['email'] === 'noreply@acme.test'
            && $body['personalizations'][0]['to'][0]['email'] === 'user@example.com'
            && count($body['content']) === 2;
    });
});
