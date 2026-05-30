<?php

use ApiHub\Laravel\Exceptions\AuthenticationException;
use ApiHub\Laravel\Exceptions\RateLimitException;
use ApiHub\Laravel\Exceptions\RequestException;
use ApiHub\Laravel\Exceptions\ServerException;
use ApiHub\Laravel\Http\Connector;
use Illuminate\Support\Facades\Http;

function throwFor(int $status, array $body = []): void
{
    Http::fake(['*' => Http::response($body, $status)]);

    (new Connector(retries: 0))->get('https://api.example.com/x')->throw('stripe');
}

it('maps 401 to an authentication exception', function () {
    throwFor(401, ['message' => 'bad key']);
})->throws(AuthenticationException::class);

it('maps 429 to a rate limit exception', function () {
    throwFor(429);
})->throws(RateLimitException::class);

it('maps other 4xx to a request exception', function () {
    throwFor(422);
})->throws(RequestException::class);

it('maps 5xx to a server exception', function () {
    throwFor(503);
})->throws(ServerException::class);

it('does not throw on a successful response', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $response = (new Connector(retries: 0))->get('https://api.example.com/x')->throw();

    expect($response->ok())->toBeTrue();
});

it('includes the driver name and provider detail in the message', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'No such customer']], 404)]);

    expect(fn () => (new Connector(retries: 0))->get('https://api.example.com/x')->throw('stripe'))
        ->toThrow(RequestException::class, '[stripe]');
});
