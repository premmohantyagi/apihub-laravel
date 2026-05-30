<?php

namespace ApiHub\Laravel\Messaging;

use ApiHub\Laravel\Contracts\MessageSender;
use ApiHub\Laravel\Messaging\DTO\MessageResult;
use ApiHub\Laravel\Messaging\DTO\TextMessage;
use Closure;
use PHPUnit\Framework\Assert;

/**
 * A test double that records messages instead of sending them. Returned by
 * MessagingManager::fake().
 */
class FakeMessaging implements MessageSender
{
    /** @var TextMessage[] */
    public array $sent = [];

    public function send(TextMessage $message): MessageResult
    {
        $this->sent[] = $message;

        return new MessageResult(
            accepted: true,
            id: 'fake-'.count($this->sent),
            driver: 'fake',
            response: null,
        );
    }

    /**
     * @param  (Closure(TextMessage): bool)|null  $callback
     */
    public function assertSent(?Closure $callback = null): void
    {
        $matches = $callback === null
            ? $this->sent
            : array_filter($this->sent, $callback);

        Assert::assertNotEmpty($matches, 'Expected a message to have been sent, but none matched.');
    }

    public function assertNothingSent(): void
    {
        Assert::assertSame([], $this->sent, 'Expected no messages to have been sent.');
    }

    public function assertSentCount(int $count): void
    {
        Assert::assertCount($count, $this->sent);
    }
}
