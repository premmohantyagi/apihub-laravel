<?php

use ApiHub\Laravel\Ai\Drivers\DeepSeekDriver;
use ApiHub\Laravel\Ai\DTO\ChatRequest;
use ApiHub\Laravel\Http\Connector;
use Illuminate\Support\Facades\Http;

it('reuses the openai mapping against the deepseek endpoint with its default model', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'model' => 'deepseek-chat',
            'choices' => [['message' => ['content' => 'DS reply'], 'finish_reason' => 'stop']],
            'usage' => ['prompt_tokens' => 3, 'completion_tokens' => 4, 'total_tokens' => 7],
        ], 200),
    ]);

    $driver = new DeepSeekDriver(new Connector(retries: 0), [
        'api_key' => 'dk-test',
        'base_url' => 'https://api.deepseek.com/v1',
    ]);

    $response = $driver->chat(ChatRequest::make()->user('Hi'));

    expect($response->content)->toBe('DS reply')
        ->and($response->usage->totalTokens)->toBe(7);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.deepseek.com/v1/chat/completions')
            && $request->hasHeader('Authorization', 'Bearer dk-test')
            && $request->data()['model'] === 'deepseek-chat';
    });
});
