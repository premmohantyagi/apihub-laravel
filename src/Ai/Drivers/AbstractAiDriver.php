<?php

namespace ApiHub\Laravel\Ai\Drivers;

use ApiHub\Laravel\Ai\DTO\ChatRequest;
use ApiHub\Laravel\Contracts\AiProvider;
use ApiHub\Laravel\Http\Connector;

/**
 * Shared base for AI drivers: holds the HTTP connector and config, and resolves
 * the model to use (request → config → driver default).
 */
abstract class AbstractAiDriver implements AiProvider
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected Connector $connector,
        protected array $config,
    ) {}

    abstract protected function driverName(): string;

    abstract protected function defaultModel(): string;

    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    protected function model(ChatRequest $request): string
    {
        return $request->model ?? $this->config('model') ?? $this->defaultModel();
    }
}
