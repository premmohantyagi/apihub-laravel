<?php

use ApiHub\Laravel\Ai\Drivers\AnthropicDriver;
use ApiHub\Laravel\Ai\DTO\ChatRequest;
use ApiHub\Laravel\Http\Connector;
use Illuminate\Support\Facades\Http;

it('lifts the system prompt out, requires max_tokens, and concatenates content blocks', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'model' => 'claude-3-5-sonnet-latest',
            'content' => [
                ['type' => 'text', 'text' => 'Hi '],
                ['type' => 'text', 'text' => 'there'],
            ],
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => 10, 'output_tokens' => 3],
        ], 200),
    ]);

    $driver = new AnthropicDriver(new Connector(retries: 0), [
        'api_key' => 'ak-test',
        'version' => '2023-06-01',
        'base_url' => 'https://api.anthropic.com/v1',
    ]);

    $response = $driver->chat(
        ChatRequest::make()->model('claude-3-5-sonnet-latest')->system('sys')->user('Hi')
    );

    expect($response->content)->toBe('Hi there')
        ->and($response->finishReason)->toBe('end_turn')
        ->and($response->usage->promptTokens)->toBe(10)
        ->and($response->usage->completionTokens)->toBe(3)
        ->and($response->usage->totalTokens)->toBe(13);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), '/messages')
            && $request->hasHeader('x-api-key', 'ak-test')
            && $request->hasHeader('anthropic-version', '2023-06-01')
            && $body['system'] === 'sys'
            && $body['max_tokens'] === 1024
            && $body['messages'][0] === ['role' => 'user', 'content' => 'Hi'];
    });
});
