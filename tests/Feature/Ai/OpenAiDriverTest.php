<?php

use ApiHub\Laravel\Ai\Drivers\OpenAiDriver;
use ApiHub\Laravel\Ai\DTO\ChatRequest;
use ApiHub\Laravel\Http\Connector;
use Illuminate\Support\Facades\Http;

it('posts chat completions and maps the response', function () {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['role' => 'assistant', 'content' => 'Hello!'], 'finish_reason' => 'stop']],
            'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 2, 'total_tokens' => 7],
        ], 200),
    ]);

    $driver = new OpenAiDriver(new Connector(retries: 0), [
        'api_key' => 'sk-test',
        'base_url' => 'https://api.openai.com/v1',
    ]);

    $response = $driver->chat(
        ChatRequest::make()->model('gpt-4o-mini')->system('Be nice')->user('Hi')
    );

    expect($response->content)->toBe('Hello!')
        ->and($response->model)->toBe('gpt-4o-mini')
        ->and($response->finishReason)->toBe('stop')
        ->and($response->usage->totalTokens)->toBe(7);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), '/chat/completions')
            && $request->hasHeader('Authorization', 'Bearer sk-test')
            && $body['model'] === 'gpt-4o-mini'
            && $body['messages'][0] === ['role' => 'system', 'content' => 'Be nice']
            && $body['messages'][1] === ['role' => 'user', 'content' => 'Hi'];
    });
});
