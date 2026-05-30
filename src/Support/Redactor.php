<?php

namespace ApiHub\Laravel\Support;

/**
 * Recursively masks the value of any array key whose name contains one of the
 * configured needles. Used so credentials never reach the log channel.
 */
class Redactor
{
    /** @var string[] */
    protected array $needles;

    /**
     * @param  string[]  $needles
     */
    public function __construct(array $needles = [], protected string $placeholder = '[REDACTED]')
    {
        $this->needles = array_values(array_filter(array_map('strtolower', $needles)));
    }

    public function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $result = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && $this->matches($key)) {
                $result[$key] = $this->placeholder;

                continue;
            }

            $result[$key] = is_array($item) ? $this->redact($item) : $item;
        }

        return $result;
    }

    protected function matches(string $key): bool
    {
        $key = strtolower($key);

        foreach ($this->needles as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }
}
