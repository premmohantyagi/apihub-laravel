<?php

namespace ApiHub\Laravel\Ai\DTO;

use ApiHub\Laravel\Http\Response;

/**
 * The provider-agnostic result of a chat completion. The assistant's text is
 * flattened into a single string; the original payload is on raw() via the
 * response.
 */
class ChatResponse
{
    public function __construct(
        public string $content,
        public ?string $model,
        public ?string $finishReason,
        public Usage $usage,
        public string $driver,
        public ?Response $response = null,
    ) {}

    public function content(): string
    {
        return $this->content;
    }

    public function usage(): Usage
    {
        return $this->usage;
    }
}
