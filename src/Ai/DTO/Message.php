<?php

namespace ApiHub\Laravel\Ai\DTO;

/**
 * A single chat message. Roles follow the common system/user/assistant
 * convention; drivers translate them to each provider's vocabulary
 * (e.g. Gemini's "model" for "assistant").
 */
class Message
{
    public const SYSTEM = 'system';

    public const USER = 'user';

    public const ASSISTANT = 'assistant';

    public function __construct(
        public string $role,
        public string $content,
    ) {}

    public static function system(string $content): self
    {
        return new self(self::SYSTEM, $content);
    }

    public static function user(string $content): self
    {
        return new self(self::USER, $content);
    }

    public static function assistant(string $content): self
    {
        return new self(self::ASSISTANT, $content);
    }

    /**
     * @return array{role: string, content: string}
     */
    public function toArray(): array
    {
        return ['role' => $this->role, 'content' => $this->content];
    }
}
