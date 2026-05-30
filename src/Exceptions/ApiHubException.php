<?php

namespace ApiHub\Laravel\Exceptions;

use ApiHub\Laravel\Http\Response;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Base of the unified exception hierarchy. Every driver failure surfaces as
 * one of these, mapped from the HTTP status, so calling code can catch a
 * single family across all providers.
 */
class ApiHubException extends RuntimeException
{
    public function __construct(
        string $message,
        public int $statusCode = 0,
        public ?Response $response = null,
        public ?string $driver = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function fromResponse(Response $response, ?string $driver = null): self
    {
        $status = $response->status();
        $message = static::messageFor($response, $driver);

        return match (true) {
            in_array($status, [401, 403], true) => new AuthenticationException($message, $status, $response, $driver),
            $status === 429 => new RateLimitException($message, $status, $response, $driver),
            $status >= 400 && $status < 500 => new RequestException($message, $status, $response, $driver),
            $status >= 500 => new ServerException($message, $status, $response, $driver),
            default => new self($message, $status, $response, $driver),
        };
    }

    protected static function messageFor(Response $response, ?string $driver): string
    {
        $detail = $response->json('error.message')
            ?? $response->json('message')
            ?? $response->json('error')
            ?? $response->body();

        $detail = is_string($detail) && $detail !== '' ? $detail : 'request failed';
        $prefix = $driver !== null ? "[{$driver}] " : '';

        return $prefix.'API request failed with status '.$response->status().': '.Str::limit($detail, 200);
    }
}
