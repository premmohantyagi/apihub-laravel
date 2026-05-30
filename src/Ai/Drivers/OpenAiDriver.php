<?php

namespace ApiHub\Laravel\Ai\Drivers;

use ApiHub\Laravel\Ai\DTO\ChatRequest;
use ApiHub\Laravel\Ai\DTO\ChatResponse;
use ApiHub\Laravel\Ai\DTO\Usage;

/**
 * OpenAI Chat Completions — https://platform.openai.com/docs/api-reference/chat
 *
 * Also the base for any OpenAI-compatible API (see DeepSeek), which only differ
 * by base URL and default model.
 */
class OpenAiDriver extends AbstractAiDriver
{
    public function chat(ChatRequest $request): ChatResponse
    {
        $request->validate();

        $base = rtrim((string) $this->config('base_url', 'https://api.openai.com/v1'), '/');
        $url = "{$base}/chat/completions";

        $messages = [];

        if ($request->system !== null) {
            $messages[] = ['role' => 'system', 'content' => $request->system];
        }

        foreach ($request->messages as $message) {
            $messages[] = $message->toArray();
        }

        $body = array_merge(array_filter([
            'model' => $this->model($request),
            'messages' => $messages,
            'temperature' => $request->temperature,
            'max_tokens' => $request->maxTokens,
        ], fn ($value) => $value !== null), $request->options);

        $response = $this->connector
            ->post($url, $body, $this->headers())
            ->throw($this->driverName());

        return new ChatResponse(
            content: (string) $response->json('choices.0.message.content', ''),
            model: $response->json('model'),
            finishReason: $response->json('choices.0.finish_reason'),
            usage: new Usage(
                (int) $response->json('usage.prompt_tokens', 0),
                (int) $response->json('usage.completion_tokens', 0),
                (int) $response->json('usage.total_tokens', 0),
            ),
            driver: $this->driverName(),
            response: $response,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        $headers = ['Authorization' => 'Bearer '.(string) $this->config('api_key')];

        if ($organization = $this->config('organization')) {
            $headers['OpenAI-Organization'] = (string) $organization;
        }

        return $headers;
    }

    protected function driverName(): string
    {
        return 'openai';
    }

    protected function defaultModel(): string
    {
        return 'gpt-4o-mini';
    }
}
