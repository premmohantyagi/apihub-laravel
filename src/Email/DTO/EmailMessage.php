<?php

namespace ApiHub\Laravel\Email\DTO;

use InvalidArgumentException;

/**
 * A provider-agnostic email. Built fluently, then handed to any driver, which
 * maps it to that provider's request shape.
 *
 * @example
 * EmailMessage::make()
 *     ->from('noreply@acme.test', 'Acme')
 *     ->to('user@example.com')
 *     ->subject('Hello')
 *     ->html('<p>Hi</p>');
 */
class EmailMessage
{
    public ?Address $from = null;

    public ?Address $replyTo = null;

    /** @var Address[] */
    public array $to = [];

    /** @var Address[] */
    public array $cc = [];

    /** @var Address[] */
    public array $bcc = [];

    public string $subject = '';

    public ?string $html = null;

    public ?string $text = null;

    /** @var Attachment[] */
    public array $attachments = [];

    /** @var array<string, string> */
    public array $headers = [];

    public static function make(): self
    {
        return new self;
    }

    public function from(string $email, ?string $name = null): static
    {
        $this->from = new Address($email, $name);

        return $this;
    }

    public function replyTo(string $email, ?string $name = null): static
    {
        $this->replyTo = new Address($email, $name);

        return $this;
    }

    /**
     * @param  string|Address|array<int, string|Address>  $email
     */
    public function to(string|Address|array $email, ?string $name = null): static
    {
        $this->to = array_merge($this->to, $this->normalise($email, $name));

        return $this;
    }

    /**
     * @param  string|Address|array<int, string|Address>  $email
     */
    public function cc(string|Address|array $email, ?string $name = null): static
    {
        $this->cc = array_merge($this->cc, $this->normalise($email, $name));

        return $this;
    }

    /**
     * @param  string|Address|array<int, string|Address>  $email
     */
    public function bcc(string|Address|array $email, ?string $name = null): static
    {
        $this->bcc = array_merge($this->bcc, $this->normalise($email, $name));

        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function html(string $html): static
    {
        $this->html = $html;

        return $this;
    }

    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function attach(Attachment $attachment): static
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function hasAttachments(): bool
    {
        return $this->attachments !== [];
    }

    /**
     * Ensure the message is complete enough for any driver to send.
     *
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        if ($this->from === null) {
            throw new InvalidArgumentException('An email message requires a "from" address.');
        }

        if ($this->to === []) {
            throw new InvalidArgumentException('An email message requires at least one "to" recipient.');
        }

        if ($this->html === null && $this->text === null) {
            throw new InvalidArgumentException('An email message requires an html or text body.');
        }
    }

    /**
     * @param  string|Address|array<int, string|Address>  $email
     * @return Address[]
     */
    protected function normalise(string|Address|array $email, ?string $name): array
    {
        if (is_array($email)) {
            return array_map(
                fn ($entry) => $entry instanceof Address ? $entry : new Address((string) $entry),
                array_values($email),
            );
        }

        return [$email instanceof Address ? $email : new Address($email, $name)];
    }
}
