<?php

use ApiHub\Laravel\Http\Connector;
use Illuminate\Support\Facades\Http;

it('performs a post and normalises the response', function () {
    Http::fake([
        'api.example.com/*' => Http::response(['id' => 'ch_1', 'paid' => true], 201),
    ]);

    $response = (new Connector(timeout: 5, retries: 0))
        ->post('https://api.example.com/charges', ['amount' => 100]);

    expect($response->ok())->toBeTrue()
        ->and($response->status())->toBe(201)
        ->and($response->json('id'))->toBe('ch_1');
});

it('marks a 4xx response as failed without throwing', function () {
    Http::fake(['*' => Http::response(['message' => 'nope'], 422)]);

    $response = (new Connector(retries: 0))->get('https://api.example.com/x');

    expect($response->failed())->toBeTrue()
        ->and($response->status())->toBe(422)
        ->and($response->json('message'))->toBe('nope');
});

it('sends configured default headers', function () {
    Http::fake(['*' => Http::response([], 200)]);

    (new Connector(retries: 0))
        ->withDefaults(['Authorization' => 'Bearer abc'])
        ->get('https://api.example.com/x');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer abc'));
});
