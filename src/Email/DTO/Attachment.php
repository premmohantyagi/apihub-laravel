<?php

namespace ApiHub\Laravel\Email\DTO;

use RuntimeException;

/**
 * A file attached to an email. Holds the raw bytes so every driver can encode
 * them however its API expects (base64, multipart, …).
 */
class Attachment
{
    public function __construct(
        public string $filename,
        public string $content,
        public string $contentType = 'application/octet-stream',
    ) {}

    public static function fromPath(string $path, ?string $filename = null, ?string $contentType = null): self
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read attachment at [{$path}].");
        }

        return new self(
            filename: $filename ?? basename($path),
            content: $contents,
            contentType: $contentType ?? (mime_content_type($path) ?: 'application/octet-stream'),
        );
    }

    public function base64(): string
    {
        return base64_encode($this->content);
    }
}
