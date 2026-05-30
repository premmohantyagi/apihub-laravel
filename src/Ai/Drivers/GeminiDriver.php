<?php

namespace ApiHub\Laravel\Ai\Drivers;

use ApiHub\Laravel\Ai\DTO\ChatRequest;
use ApiHub\Laravel\Ai\DTO\ChatResponse;
use ApiHub\Laravel\Ai\DTO\Message;
use ApiHub\Laravel\Ai\DTO\Usage;

/**
 * Google Gemini generateContent — https://ai.google.dev/api/generate-content
 *
 * Differs from OpenAI: the model is part of the path, the key is a query param,
 * the assistant role is "model", system text is a separate systemInstruction,
 * and generation params live under generationConfig.
 */
class GeminiDriver extends AbstractAiDriver
{
    public function chat(ChatRequest $request): ChatResponse
    {
        $request->validate();

        $base = rtrim((string) $this->config('base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $key = (string) $this->config('api_key');
        $model = $this->model($request);
        $url = "{$base}/models/{$model}:generateContent?key=".urlencode($key);

        $contents = [];

        foreach ($request->messages as $message) {
            if ($message->role === Message::SYSTEM) {
                continue;
            }

            $contents[] = [
                'role' => $message->role === Message::ASSISTANT ? 'model' : 'user',
                'parts' => [['text' => $message->content]],
            ];
        }

        $generationConfig = array_filter([
            'temperature' => $request->temperature,
            'maxOutputTokens' => $request->maxTokens,
        ], fn ($value) => $value !== null);

        $body = array_merge(array_filter([
            'contents' => $contents,
            'systemInstruction' => $request->system !== null
                ? ['parts' => [['text' => $request->system]]]
                : null,
            'generationConfig' => $generationConfig === [] ? null : $generationConfig,
        ], fn ($value) => $value !== null), $request->options);

        $response = $this->connector->post($url, $body)->throw($this->driverName());

        return new ChatResponse(
            content: $this->extractText($response->json('candidates.0.content.parts', [])),
            model: $response->json('modelVersion', $model),
            finishReason: $response->json('candidates.0.finishReason'),
            usage: $this->usage($response->json('usageMetadata', [])),
            driver: $this->driverName(),
            response: $response,
        );
    }

    /**
     * @param  array<int, mixed>  $parts
     */
    protected function extractText(array $parts): string
    {
        $text = '';

        foreach ($parts as $part) {
            if (is_array($part)) {
                $text .= $part['text'] ?? '';
            }
        }

        return $text;
    }

    /**
     * @param  array<string, mixed>  $usage
     */
    protected function usage(array $usage): Usage
    {
        return new Usage(
            (int) ($usage['promptTokenCount'] ?? 0),
            (int) ($usage['candidatesTokenCount'] ?? 0),
            (int) ($usage['totalTokenCount'] ?? 0),
        );
    }

    protected function driverName(): string
    {
        return 'gemini';
    }

    protected function defaultModel(): string
    {
        return 'gemini-1.5-flash';
    }
}
