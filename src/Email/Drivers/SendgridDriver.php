<?php

namespace ApiHub\Laravel\Email\Drivers;

use ApiHub\Laravel\Email\DTO\Address;
use ApiHub\Laravel\Email\DTO\EmailMessage;
use ApiHub\Laravel\Email\DTO\SendResult;

/**
 * SendGrid — https://www.twilio.com/docs/sendgrid/api-reference/mail-send/mail-send
 *
 * Posts JSON to /v3/mail/send with a bearer token. A successful send returns
 * 202 with an empty body; the message id is in the X-Message-Id header.
 */
class SendgridDriver extends AbstractEmailDriver
{
    public function send(EmailMessage $message): SendResult
    {
        $message->validate();

        $key = (string) $this->config('api_key');
        $url = 'https://api.sendgrid.com/v3/mail/send';

        $personalization = array_filter([
            'to' => $this->mapAddresses($message->to),
            'cc' => $this->mapAddresses($message->cc),
            'bcc' => $this->mapAddresses($message->bcc),
        ]);

        $payload = array_filter([
            'personalizations' => [$personalization],
            'from' => $this->mapAddress($message->from),
            'reply_to' => $this->mapAddress($message->replyTo),
            'subject' => $message->subject,
            'content' => $this->content($message),
            'headers' => $message->headers ?: null,
            'attachments' => $this->attachments($message),
        ], fn ($value) => $value !== null && $value !== []);

        $response = $this->connector
            ->post($url, $payload, ['Authorization' => "Bearer {$key}"])
            ->throw($this->driverName());

        return new SendResult(
            accepted: true,
            id: $response->header('X-Message-Id'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function driverName(): string
    {
        return 'sendgrid';
    }

    /**
     * @return array<int, array{value: string, type: string}>
     */
    protected function content(EmailMessage $message): array
    {
        $content = [];

        if ($message->text !== null) {
            $content[] = ['type' => 'text/plain', 'value' => $message->text];
        }

        if ($message->html !== null) {
            $content[] = ['type' => 'text/html', 'value' => $message->html];
        }

        return $content;
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
            'content' => $attachment->base64(),
            'filename' => $attachment->filename,
            'type' => $attachment->contentType,
            'disposition' => 'attachment',
        ], $message->attachments);
    }

    /**
     * @param  Address[]  $addresses
     * @return array<int, array<string, string>>
     */
    protected function mapAddresses(array $addresses): array
    {
        return array_values(array_filter(array_map(fn (Address $address) => $this->mapAddress($address), $addresses)));
    }

    /**
     * @return array<string, string>|null
     */
    protected function mapAddress(?Address $address): ?array
    {
        if ($address === null) {
            return null;
        }

        return array_filter([
            'email' => $address->email,
            'name' => $address->name,
        ], fn ($value) => $value !== null);
    }
}
