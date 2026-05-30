<?php

use ApiHub\Laravel\Ai\Drivers\GeminiDriver;
use ApiHub\Laravel\Ai\DTO\ChatRequest;
use ApiHub\Laravel\Http\Connector;
use Illuminate\Support\Facades\Http;

it('puts the model in the path, the key in the query, and maps gemini fields', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'modelVersion' => 'gemini-1.5-flash',
            'candidates' => [[
                'content' => ['parts' => [['text' => 'Gemini hi']]],
                'finishReason' => 'STOP',
            ]],
            'usageMetadata' => ['promptTokenCount' => 4, 'candidatesTokenCount' => 2, 'totalTokenCount' => 6],
        ], 200),
    ]);

    $driver = new GeminiDriver(new Connector(retries: 0), [
        'api_key' => 'gk-test',
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
    ]);

    $response = $driver->chat(
        ChatRequest::make()->model('gemini-1.5-flash')->system('sys')->user('Hi')->temperature(0.5)
    );

    expect($response->content)->toBe('Gemini hi')
        ->and($response->finishReason)->toBe('STOP')
        ->and($response->usage->totalTokens)->toBe(6);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), 'models/gemini-1.5-flash:generateContent')
            && str_contains($request->url(), 'key=gk-test')
            && $body['systemInstruction']['parts'][0]['text'] === 'sys'
            && $body['contents'][0] === ['role' => 'user', 'parts' => [['text' => 'Hi']]]
            && $body['generationConfig']['temperature'] === 0.5;
    });
});
