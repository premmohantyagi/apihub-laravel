<?php

namespace ApiHub\Laravel\Http;

use ApiHub\Laravel\Exceptions\ApiHubException;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Arr;

/**
 * A normalised, driver-agnostic view of an API response.
 *
 * Every driver returns one of these so calling code sees the same shape
 * regardless of provider. The original Laravel HTTP response is kept on the
 * side as an escape hatch via raw().
 */
class Response
{
    public function __construct(
        protected bool $ok,
        protected int $status,
        protected array $json,
        protected string $body,
        protected ?ClientResponse $raw = null,
    ) {}

    public static function fromClient(ClientResponse $response): self
    {
        return new self(
            ok: $response->successful(),
            status: $response->status(),
            json: is_array($decoded = $response->json()) ? $decoded : [],
            body: (string) $response->body(),
            raw: $response,
        );
    }

    public function ok(): bool
    {
        return $this->ok;
    }

    public function failed(): bool
    {
        return ! $this->ok;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * The decoded JSON body, or a single value addressed with "dot" notation.
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->json;
        }

        return Arr::get($this->json, $key, $default);
    }

    public function header(string $name): ?string
    {
        $value = $this->raw?->header($name);

        return $value === null || $value === '' ? null : $value;
    }

    /**
     * The underlying Laravel HTTP response — for provider-specific features the
     * unified interface does not expose.
     */
    public function raw(): ?ClientResponse
    {
        return $this->raw;
    }

    /**
     * Throw a mapped ApiHubException when the response was not successful.
     *
     * @throws ApiHubException
     */
    public function throw(?string $driver = null): self
    {
        if ($this->failed()) {
            throw ApiHubException::fromResponse($this, $driver);
        }

        return $this;
    }
}
