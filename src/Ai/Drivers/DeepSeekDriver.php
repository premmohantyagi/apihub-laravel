<?php

namespace ApiHub\Laravel\Ai\Drivers;

/**
 * DeepSeek — https://api-docs.deepseek.com/
 *
 * OpenAI-compatible Chat Completions API; only the base URL and default model
 * differ, so it reuses the OpenAI driver's request/response mapping.
 */
class DeepSeekDriver extends OpenAiDriver
{
    protected function driverName(): string
    {
        return 'deepseek';
    }

    protected function defaultModel(): string
    {
        return 'deepseek-chat';
    }
}
