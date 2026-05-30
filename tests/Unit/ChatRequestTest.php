<?php

use ApiHub\Laravel\Ai\DTO\ChatRequest;
use ApiHub\Laravel\Ai\DTO\Message;

it('builds a chat request fluently', function () {
    $request = ChatRequest::make()
        ->model('gpt-4o-mini')
        ->system('Be concise.')
        ->user('Hello')
        ->assistant('Hi')
        ->temperature(0.7)
        ->maxTokens(128)
        ->option('top_p', 0.9);

    expect($request->model)->toBe('gpt-4o-mini')
        ->and($request->system)->toBe('Be concise.')
        ->and($request->messages)->toHaveCount(2)
        ->and($request->messages[0]->role)->toBe(Message::USER)
        ->and($request->messages[1]->role)->toBe(Message::ASSISTANT)
        ->and($request->temperature)->toBe(0.7)
        ->and($request->maxTokens)->toBe(128)
        ->and($request->options['top_p'])->toBe(0.9);
});

it('passes validation with at least one message', function () {
    $request = ChatRequest::make()->user('Hi');
    $request->validate();

    expect($request->messages)->toHaveCount(1);
});

it('rejects a request with no messages', function () {
    ChatRequest::make()->validate();
})->throws(InvalidArgumentException::class);
