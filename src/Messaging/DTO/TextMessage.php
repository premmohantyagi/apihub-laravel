<?php

namespace ApiHub\Laravel\Messaging\DTO;

use InvalidArgumentException;

/**
 * A provider-agnostic outbound message.
 *
 * `to` is the destination in whatever form the driver expects — a phone number
 * (Twilio, Vonage, MSG91, WhatsApp), a chat id (Telegram), or a channel
 * (Slack, Discord). Webhook drivers may omit it and use their configured target.
 *
 * @example
 * TextMessage::make()->to('+15551234567')->text('Your code is 1234');
 */
class TextMessage
{
    public ?string $to = null;

    public string $text = '';

    public ?string $from = null;

    /** @var array<string, mixed> */
    public array $options = [];

    public static function make(): self
    {
        return new self;
    }

    public function to(string $to): static
    {
        $this->to = $to;

        return $this;
    }

    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function from(string $from): static
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Set a provider-specific parameter merged into the request (e.g. Telegram
     * parse_mode, Twilio media). These win over the mapped defaults.
     */
    public function option(string $key, mixed $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        if ($this->text === '') {
            throw new InvalidArgumentException('A message requires text.');
        }
    }
}
