<?php

namespace ApiHub\Laravel\Email\Drivers;

use ApiHub\Laravel\Email\DTO\Address;
use ApiHub\Laravel\Email\DTO\EmailMessage;
use ApiHub\Laravel\Email\DTO\SendResult;

/**
 * Resend — https://resend.com/docs/api-reference/emails/send-email
 *
 * Posts JSON to /emails with a bearer token; the message id is returned in the
 * JSON body as "id".
 */
class ResendDriver extends AbstractEmailDriver
{
    public function send(EmailMessage $message): SendResult
    {
        $message->validate();

        $key = (string) $this->config('api_key');
        $url = 'https://api.resend.com/emails';

        $payload = array_filter([
            'from' => $message->from?->toString(),
            'to' => $this->stringAddresses($message->to),
            'cc' => $this->stringAddresses($message->cc),
            'bcc' => $this->stringAddresses($message->bcc),
            'reply_to' => $message->replyTo?->toString(),
            'subject' => $message->subject,
            'html' => $message->html,
            'text' => $message->text,
            'headers' => $message->headers ?: null,
            'attachments' => $this->attachments($message),
        ], fn ($value) => $value !== null && $value !== []);

        $response = $this->connector
            ->post($url, $payload, ['Authorization' => "Bearer {$key}"])
            ->throw($this->driverName());

        return new SendResult(
            accepted: true,
            id: $response->json('id'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function driverName(): string
    {
        return 'resend';
    }

    /**
     * @param  Address[]  $addresses
     * @return string[]
     */
    protected function stringAddresses(array $addresses): array
    {
        return array_map(fn (Address $address) => $address->toString(), $addresses);
    }

    /**
     * @return array<int, array<string, string>>|null
     */
    protected function attachments(EmailMessage $message): ?array
    {
        if (! $message->hasAttachments()) {
            return null;
        }

        return array_map(fn ($attachment) => [
            'filename' => $attachment->filename,
            'content' => $attachment->base64(),
            'content_type' => $attachment->contentType,
        ], $message->attachments);
    }
}
