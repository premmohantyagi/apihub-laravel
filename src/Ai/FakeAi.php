<?php

namespace ApiHub\Laravel\Ai;

use ApiHub\Laravel\Ai\DTO\ChatRequest;
use ApiHub\Laravel\Ai\DTO\ChatResponse;
use ApiHub\Laravel\Ai\DTO\Usage;
use ApiHub\Laravel\Contracts\AiProvider;
use Closure;
use PHPUnit\Framework\Assert;

/**
 * A test double that records requests and returns a canned reply instead of
 * calling a provider. Returned by AiManager::fake().
 */
class FakeAi implements AiProvider
{
    /** @var ChatRequest[] */
    public array $requests = [];

    /** @var string|Closure(ChatRequest): string */
    protected string|Closure $responder;

    public function __construct(string $reply = 'This is a fake AI response.')
    {
        $this->responder = $reply;
    }

    /**
     * @param  string|Closure(ChatRequest): string  $responder
     */
    public function respondWith(string|Closure $responder): static
    {
        $this->responder = $responder;

        return $this;
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $this->requests[] = $request;

        $content = $this->responder instanceof Closure
            ? ($this->responder)($request)
            : $this->responder;

        return new ChatResponse(
            content: $content,
            model: 'fake-model',
            finishReason: 'stop',
            usage: new Usage,
            driver: 'fake',
            response: null,
        );
    }

    /**
     * @param  (Closure(ChatRequest): bool)|null  $callback
     */
    public function assertChatted(?Closure $callback = null): void
    {
        $matches = $callback === null
            ? $this->requests
            : array_filter($this->requests, $callback);

        Assert::assertNotEmpty($matches, 'Expected a chat request, but none matched.');
    }

    public function assertNothingChatted(): void
    {
        Assert::assertSame([], $this->requests, 'Expected no chat requests.');
    }

    public function assertChattedCount(int $count): void
    {
        Assert::assertCount($count, $this->requests);
    }
}
