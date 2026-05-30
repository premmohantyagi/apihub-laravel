<?php

use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Messaging\Drivers\DiscordDriver;
use ApiHub\Laravel\Messaging\Drivers\SlackDriver;
use ApiHub\Laravel\Messaging\Drivers\TelegramDriver;
use ApiHub\Laravel\Messaging\Drivers\WhatsAppDriver;
use ApiHub\Laravel\Messaging\DTO\TextMessage;
use Illuminate\Support\Facades\Http;

it('telegram posts to the bot endpoint and returns the message id', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 42]], 200),
    ]);

    $driver = new TelegramDriver(new Connector(retries: 0), ['bot_token' => '123:ABC']);

    $result = $driver->send(TextMessage::make()->to('98765')->text('Hi'));

    expect($result->accepted())->toBeTrue()
        ->and($result->id())->toBe('42');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/bot123:ABC/sendMessage')
        && $request->data()['chat_id'] === '98765'
        && $request->data()['text'] === 'Hi');
});

it('whatsapp posts to the graph api with a bearer token', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200),
    ]);

    $driver = new WhatsAppDriver(new Connector(retries: 0), ['token' => 'wa-token', 'phone_number_id' => '55501']);

    $result = $driver->send(TextMessage::make()->to('15551234567')->text('Hi'));

    expect($result->id())->toBe('wamid.X');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/55501/messages')
        && $request->hasHeader('Authorization', 'Bearer wa-token')
        && $request->data()['messaging_product'] === 'whatsapp'
        && $request->data()['text']['body'] === 'Hi');
});

it('slack uses chat.postMessage when a bot token is configured', function () {
    Http::fake([
        'slack.com/*' => Http::response(['ok' => true, 'ts' => '1699999999.000100'], 200),
    ]);

    $driver = new SlackDriver(new Connector(retries: 0), ['token' => 'xoxb-1']);

    $result = $driver->send(TextMessage::make()->to('#general')->text('Hi'));

    expect($result->accepted())->toBeTrue()
        ->and($result->id())->toBe('1699999999.000100');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'chat.postMessage')
        && $request->hasHeader('Authorization', 'Bearer xoxb-1')
        && $request->data()['channel'] === '#general');
});

it('slack falls back to an incoming webhook', function () {
    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $driver = new SlackDriver(new Connector(retries: 0), ['webhook_url' => 'https://hooks.slack.com/services/T/B/X']);

    $result = $driver->send(TextMessage::make()->text('Hi'));

    expect($result->accepted())->toBeTrue()
        ->and($result->id())->toBeNull();
});

it('discord posts content to a webhook (204 no body)', function () {
    Http::fake([
        'discord.com/api/webhooks/*' => Http::response('', 204),
    ]);

    $driver = new DiscordDriver(new Connector(retries: 0), ['webhook_url' => 'https://discord.com/api/webhooks/1/abc']);

    $result = $driver->send(TextMessage::make()->text('Hi'));

    expect($result->accepted())->toBeTrue();

    Http::assertSent(fn ($request) => $request->data()['content'] === 'Hi');
});

it('discord uses the bot endpoint when a token and channel are present', function () {
    Http::fake([
        'discord.com/api/v10/*' => Http::response(['id' => 'msg-1'], 200),
    ]);

    $driver = new DiscordDriver(new Connector(retries: 0), ['bot_token' => 'bot-token']);

    $result = $driver->send(TextMessage::make()->to('123456')->text('Hi'));

    expect($result->id())->toBe('msg-1');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/channels/123456/messages')
        && $request->hasHeader('Authorization', 'Bot bot-token'));
});
