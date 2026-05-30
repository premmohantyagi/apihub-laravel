<?php

namespace ApiHub\Laravel\Contracts;

use ApiHub\Laravel\Ai\DTO\ChatRequest;
use ApiHub\Laravel\Ai\DTO\ChatResponse;

/**
 * The unified interface every AI chat driver implements.
 */
interface AiProvider
{
    public function chat(ChatRequest $request): ChatResponse;
}
