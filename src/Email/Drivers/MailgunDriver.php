<?php

namespace ApiHub\Laravel\Email\Drivers;

use ApiHub\Laravel\Email\DTO\Address;
use ApiHub\Laravel\Email\DTO\EmailMessage;
use ApiHub\Laravel\Email\DTO\SendResult;

/**
 * Mailgun — https://documentation.mailgun.com/docs/mailgun/api-reference/openapi-final/tag/Messages/
 *
 * Posts form fields to /v3/{domain}/messages with HTTP basic auth ("api" +
 * private key). Custom headers are passed as h:Name fields.
 */
class MailgunDriver extends AbstractEmailDriver
{
    public function send(EmailMessage $message): SendResult
    {
        $message->validate();

        $endpoint = rtrim((string) $this->config('endpoint', 'https://api.mailgun.net'), '/');
        $domain = (string) $this->config('domain');
        $key = (string) $this->config('api_key');
        $url = "{$endpoint}/v3/{$domain}/messages";

        $fields = array_filter([
            'from' => $message->from?->toString(),
            'to' => $this->joinAddresses($message->to),
            'cc' => $this->joinAddresses($message->cc),
            'bcc' => $this->joinAddresses($message->bcc),
            'subject' => $message->subject,
            'html' => $message->html,
            'text' => $message->text,
            'h:Reply-To' => $message->replyTo?->toString(),
        ], fn ($value) => $value !== null && $value !== '');

        foreach ($message->headers as $name => $value) {
            $fields["h:{$name}"] = $value;
        }

        $response = $this->connector->send('POST', $url, function () use ($url, $key, $fields, $message) {
            $request = $this->connector->request()->withBasicAuth('api', $key);

            if ($message->hasAttachments()) {
                foreach ($message->attachments as $attachment) {
                    $request->attach('attachment', $attachment->content, $attachment->filename);
                }

                return $request->post($url, $fields);
            }

            return $request->asForm()->post($url, $fields);
        }, $fields)->throw($this->driverName());

        return new SendResult(
            accepted: true,
            id: $this->normaliseId($response->json('id')),
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function driverName(): string
    {
        return 'mailgun';
    }

    /**
     * @param  Address[]  $addresses
     */
    protected function joinAddresses(array $addresses): ?string
    {
        if ($addresses === []) {
            return null;
        }

        return implode(',', array_map(fn (Address $address) => $address->toString(), $addresses));
    }

    protected function normaliseId(mixed $id): ?string
    {
        return is_string($id) ? trim($id, '<>') : null;
    }
}
