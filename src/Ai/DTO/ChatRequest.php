<?php

namespace ApiHub\Laravel\Ai\DTO;

use InvalidArgumentException;

/**
 * A provider-agnostic chat completion request, built fluently.
 *
 * @example
 * ChatRequest::make()
 *     ->model('gpt-4o-mini')
 *     ->system('You are concise.')
 *     ->user('Summarise Laravel in a sentence.');
 */
class ChatRequest
{
    public ?string $model = null;

    public ?string $system = null;

    /** @var Message[] */
    public array $messages = [];

    public ?float $temperature = null;

    public ?int $maxTokens = null;

    /** @var array<string, mixed> */
    public array $options = [];

    public static function make(): self
    {
        return new self;
    }

    public function model(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function system(string $content): static
    {
        $this->system = $content;

        return $this;
    }

    public function message(string $role, string $content): static
    {
        $this->messages[] = new Message($role, $content);

        return $this;
    }

    public function user(string $content): static
    {
        return $this->message(Message::USER, $content);
    }

    public function assistant(string $content): static
    {
        return $this->message(Message::ASSISTANT, $content);
    }

    public function temperature(float $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function maxTokens(int $maxTokens): static
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    /**
     * Set a provider-specific parameter merged into the request body (e.g.
     * top_p, stop, response_format). These win over the mapped defaults.
     */
    public function option(string $key, mixed $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        if ($this->messages === []) {
            throw new InvalidArgumentException('A chat request requires at least one message.');
        }
    }
}
