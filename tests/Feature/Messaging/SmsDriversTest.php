<?php

use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Messaging\Drivers\Msg91Driver;
use ApiHub\Laravel\Messaging\Drivers\TwilioDriver;
use ApiHub\Laravel\Messaging\Drivers\VonageDriver;
use ApiHub\Laravel\Messaging\DTO\TextMessage;
use Illuminate\Support\Facades\Http;

it('twilio posts form fields with basic auth and returns the sid', function () {
    Http::fake([
        'api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201),
    ]);

    $driver = new TwilioDriver(new Connector(retries: 0), [
        'sid' => 'AC_sid',
        'token' => 'tok',
        'from' => '+15550000000',
    ]);

    $result = $driver->send(TextMessage::make()->to('+15551234567')->text('Hi'));

    expect($result->accepted())->toBeTrue()
        ->and($result->id())->toBe('SM123');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/Accounts/AC_sid/Messages.json')
            && $request->hasHeader('Authorization')
            && $request->data()['To'] === '+15551234567'
            && $request->data()['From'] === '+15550000000'
            && $request->data()['Body'] === 'Hi';
    });
});

it('vonage reads acceptance from the per-message status', function () {
    Http::fake([
        'rest.nexmo.com/*' => Http::response(['messages' => [['status' => '0', 'message-id' => 'vid-1']]], 200),
    ]);

    $driver = new VonageDriver(new Connector(retries: 0), ['key' => 'k', 'secret' => 's', 'from' => 'Acme']);

    $result = $driver->send(TextMessage::make()->to('15551234567')->text('Hi'));

    expect($result->accepted())->toBeTrue()
        ->and($result->id())->toBe('vid-1');
});

it('vonage marks a non-zero status as not accepted', function () {
    Http::fake([
        'rest.nexmo.com/*' => Http::response(['messages' => [['status' => '2', 'error-text' => 'Missing key']]], 200),
    ]);

    $driver = new VonageDriver(new Connector(retries: 0), ['key' => 'k', 'secret' => 's']);

    $result = $driver->send(TextMessage::make()->to('15551234567')->text('Hi'));

    expect($result->accepted())->toBeFalse();
});

it('msg91 sends a query request and returns the body as the id', function () {
    Http::fake([
        'api.msg91.com/*' => Http::response('3a4b5c6d', 200),
    ]);

    $driver = new Msg91Driver(new Connector(retries: 0), ['auth_key' => 'ak', 'sender' => 'ACME']);

    $result = $driver->send(TextMessage::make()->to('15551234567')->text('Hi'));

    expect($result->accepted())->toBeTrue()
        ->and($result->id())->toBe('3a4b5c6d');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'authkey=ak')
        && str_contains($request->url(), 'mobiles=15551234567')
        && str_contains($request->url(), 'sender=ACME'));
});

it('requires a destination', function () {
    $driver = new TwilioDriver(new Connector(retries: 0), ['sid' => 'x', 'token' => 'y']);

    $driver->send(TextMessage::make()->text('Hi'));
})->throws(InvalidArgumentException::class);
