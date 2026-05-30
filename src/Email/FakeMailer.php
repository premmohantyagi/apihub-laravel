<?php

namespace ApiHub\Laravel\Email;

use ApiHub\Laravel\Contracts\Mailer;
use ApiHub\Laravel\Email\DTO\EmailMessage;
use ApiHub\Laravel\Email\DTO\SendResult;
use Closure;
use PHPUnit\Framework\Assert;

/**
 * A test double that records messages instead of sending them. Returned by
 * EmailManager::fake() so application code can assert on what would be sent.
 */
class FakeMailer implements Mailer
{
    /** @var EmailMessage[] */
    public array $sent = [];

    public function send(EmailMessage $message): SendResult
    {
        $this->sent[] = $message;

        return new SendResult(
            accepted: true,
            id: 'fake-'.count($this->sent),
            driver: 'fake',
            response: null,
        );
    }

    /**
     * @param  (Closure(EmailMessage): bool)|null  $callback
     */
    public function assertSent(?Closure $callback = null): void
    {
        $matches = $callback === null
            ? $this->sent
            : array_filter($this->sent, $callback);

        Assert::assertNotEmpty($matches, 'Expected an email to have been sent, but none matched.');
    }

    public function assertNothingSent(): void
    {
        Assert::assertSame([], $this->sent, 'Expected no emails to have been sent.');
    }

    public function assertSentCount(int $count): void
    {
        Assert::assertCount($count, $this->sent);
    }
}
