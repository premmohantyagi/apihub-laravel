<?php

namespace ApiHub\Laravel\Ai\DTO;

/**
 * Token accounting for a completion, normalised across providers.
 */
class Usage
{
    public function __construct(
        public int $promptTokens = 0,
        public int $completionTokens = 0,
        public int $totalTokens = 0,
    ) {}
}
