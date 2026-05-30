<?php

namespace ApiHub\Laravel\Ai\Drivers;

use ApiHub\Laravel\Ai\DTO\ChatRequest;
use ApiHub\Laravel\Ai\DTO\ChatResponse;
use ApiHub\Laravel\Ai\DTO\Message;
use ApiHub\Laravel\Ai\DTO\Usage;

/**
 * Anthropic Messages — https://docs.anthropic.com/en/api/messages
 *
 * Differs from OpenAI: the system prompt is a top-level field (not a message),
 * max_tokens is required, auth is via x-api-key, and the reply is a list of
 * content blocks that are concatenated into text.
 */
class AnthropicDriver extends AbstractAiDriver
{
    public function chat(ChatRequest $request): ChatResponse
    {
        $request->validate();

        $base = rtrim((string) $this->config('base_url', 'https://api.anthropic.com/v1'), '/');
        $url = "{$base}/messages";

        $system = $this->systemPrompt($request);

        $messages = array_map(
            fn (Message $message) => ['role' => $message->role, 'content' => $message->content],
            array_values(array_filter($request->messages, fn (Message $m) => $m->role !== Message::SYSTEM)),
        );

        $body = array_merge(array_filter([
            'model' => $this->model($request),
            'max_tokens' => $request->maxTokens ?? 1024,
            'system' => $system,
            'messages' => $messages,
            'temperature' => $request->temperature,
        ], fn ($value) => $value !== null), $request->options);

        $response = $this->connector->post($url, $body, [
            'x-api-key' => (string) $this->config('api_key'),
            'anthropic-version' => (string) $this->config('version', '2023-06-01'),
        ])->throw($this->driverName());

        return new ChatResponse(
            content: $this->extractText($response->json('content', [])),
            model: $response->json('model'),
            finishReason: $response->json('stop_reason'),
            usage: $this->usage($response->json('usage', [])),
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function systemPrompt(ChatRequest $request): ?string
    {
        $fromMessages = array_map(
            fn (Message $m) => $m->content,
            array_values(array_filter($request->messages, fn (Message $m) => $m->role === Message::SYSTEM)),
        );

        $parts = array_filter([$request->system, ...$fromMessages]);

        return $parts === [] ? null : implode("\n\n", $parts);
    }

    /**
     * @param  array<int, mixed>  $blocks
     */
    protected function extractText(array $blocks): string
    {
        $text = '';

        foreach ($blocks as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text') {
                $text .= $block['text'] ?? '';
            }
        }

        return $text;
    }

    /**
     * @param  array<string, mixed>  $usage
     */
    protected function usage(array $usage): Usage
    {
        $prompt = (int) ($usage['input_tokens'] ?? 0);
        $completion = (int) ($usage['output_tokens'] ?? 0);

        return new Usage($prompt, $completion, $prompt + $completion);
    }

    protected function driverName(): string
    {
        return 'anthropic';
    }

    protected function defaultModel(): string
    {
        return 'claude-3-5-sonnet-latest';
    }
}
