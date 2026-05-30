<?php

use ApiHub\Laravel\Ai\AiManager;
use ApiHub\Laravel\Ai\Drivers\AnthropicDriver;
use ApiHub\Laravel\Ai\Drivers\DeepSeekDriver;
use ApiHub\Laravel\Ai\Drivers\GeminiDriver;
use ApiHub\Laravel\Ai\Drivers\OpenAiDriver;
use ApiHub\Laravel\Ai\DTO\ChatRequest;
use ApiHub\Laravel\Ai\FakeAi;
use ApiHub\Laravel\Facades\Ai;

it('resolves the configured default and named drivers', function () {
    $manager = app(AiManager::class);

    expect($manager->driver())->toBeInstanceOf(OpenAiDriver::class)
        ->and($manager->driver('anthropic'))->toBeInstanceOf(AnthropicDriver::class)
        ->and($manager->driver('gemini'))->toBeInstanceOf(GeminiDriver::class)
        ->and($manager->driver('deepseek'))->toBeInstanceOf(DeepSeekDriver::class);
});

it('records and answers chat requests through the facade when faked', function () {
    $fake = Ai::fake('Mocked reply');

    $response = Ai::chat(ChatRequest::make()->user('Hi'));

    expect($fake)->toBeInstanceOf(FakeAi::class)
        ->and($response->content)->toBe('Mocked reply');

    $fake->assertChattedCount(1);
    $fake->assertChatted(fn (ChatRequest $request) => $request->messages[0]->content === 'Hi');
});

it('supports a dynamic fake responder', function () {
    $fake = Ai::fake()->respondWith(fn (ChatRequest $request) => strtoupper($request->messages[0]->content));

    expect(Ai::chat(ChatRequest::make()->user('echo'))->content)->toBe('ECHO');
});
