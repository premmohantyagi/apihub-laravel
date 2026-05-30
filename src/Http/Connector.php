<?php

namespace ApiHub\Laravel\Http;

use ApiHub\Laravel\Support\Redactor;
use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The shared HTTP engine every driver builds on.
 *
 * It applies the package-wide timeout, retry and header policy, normalises the
 * result into a {@see Response}, and (optionally) logs each call with secrets
 * redacted. Drivers that need a bespoke request shape — multipart uploads,
 * streaming, raw bodies — can take the configured PendingRequest from
 * request() and drive it directly.
 */
class Connector
{
    /**
     * @param  array<string, string>  $defaultHeaders
     */
    public function __construct(
        protected int $timeout = 10,
        protected int $retries = 2,
        protected int $retryDelay = 250,
        protected bool $logging = false,
        protected ?Redactor $redactor = null,
        protected array $defaultHeaders = [],
        protected ?string $baseUrl = null,
    ) {}

    /**
     * Return a copy of the connector pre-loaded with driver credentials, so a
     * single shared instance can be specialised per driver without mutation.
     *
     * @param  array<string, string>  $headers
     */
    public function withDefaults(array $headers = [], ?string $baseUrl = null): self
    {
        return new self(
            timeout: $this->timeout,
            retries: $this->retries,
            retryDelay: $this->retryDelay,
            logging: $this->logging,
            redactor: $this->redactor,
            defaultHeaders: array_merge($this->defaultHeaders, $headers),
            baseUrl: $baseUrl ?? $this->baseUrl,
        );
    }

    /**
     * A PendingRequest with the package timeout, retry and header policy
     * already applied.
     *
     * retry() is called positionally rather than with the named `throw:`
     * argument so this works across Laravel 8–13: Laravel 8's retry() predates
     * the $when/$throw parameters and simply ignores the extra arguments, while
     * on Laravel 9+ the trailing `false` keeps a failed response from throwing
     * after the retries are exhausted, so the caller decides how to react.
     *
     * @param  array<string, string>  $headers
     */
    public function request(array $headers = []): PendingRequest
    {
        $request = Http::timeout($this->timeout)
            ->withHeaders(array_merge($this->defaultHeaders, $headers));

        if ($this->retries > 0) {
            $request->retry($this->retries + 1, $this->retryDelay, null, false);
        }

        if ($this->baseUrl !== null) {
            $request->baseUrl($this->baseUrl);
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, string>  $headers
     */
    public function get(string $url, array $query = [], array $headers = []): Response
    {
        return $this->dispatch('GET', $url, $query, fn () => $this->request($headers)->get($url, $query));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     */
    public function post(string $url, array $data = [], array $headers = []): Response
    {
        return $this->dispatch('POST', $url, $data, fn () => $this->request($headers)->post($url, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     */
    public function put(string $url, array $data = [], array $headers = []): Response
    {
        return $this->dispatch('PUT', $url, $data, fn () => $this->request($headers)->put($url, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     */
    public function patch(string $url, array $data = [], array $headers = []): Response
    {
        return $this->dispatch('PATCH', $url, $data, fn () => $this->request($headers)->patch($url, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     */
    public function delete(string $url, array $data = [], array $headers = []): Response
    {
        return $this->dispatch('DELETE', $url, $data, fn () => $this->request($headers)->delete($url, $data));
    }

    /**
     * Execute a fully custom request — multipart uploads, raw signed bodies,
     * provider-specific auth — while keeping the normalised Response and the
     * redacted logging. The closure returns a Laravel client Response (drivers
     * build it from {@see request()}).
     *
     * @param  Closure(): \Illuminate\Http\Client\Response  $send
     * @param  array<string, mixed>  $logPayload
     */
    public function send(string $method, string $url, Closure $send, array $logPayload = []): Response
    {
        return $this->dispatch($method, $url, $logPayload, $send);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function dispatch(string $method, string $url, array $payload, Closure $send): Response
    {
        $response = Response::fromClient($send());

        if ($this->logging) {
            $this->log($method, $url, $payload, $response);
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function log(string $method, string $url, array $payload, Response $response): void
    {
        $redactor = $this->redactor;

        Log::debug("[apihub] {$method} {$url}", [
            'status' => $response->status(),
            'request' => $redactor ? $redactor->redact($payload) : $payload,
            'response' => $redactor ? $redactor->redact($response->json()) : $response->json(),
        ]);
    }
}
