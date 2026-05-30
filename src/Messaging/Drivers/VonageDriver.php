<?php

namespace ApiHub\Laravel\Messaging\Drivers;

use ApiHub\Laravel\Messaging\DTO\MessageResult;
use ApiHub\Laravel\Messaging\DTO\TextMessage;

/**
 * Vonage (Nexmo) SMS — https://developer.vonage.com/en/api/sms
 *
 * Posts form fields to /sms/json. The endpoint returns HTTP 200 even for
 * logical failures, so acceptance is read from the per-message status ("0" is
 * success) rather than the HTTP status alone.
 */
class VonageDriver extends AbstractMessagingDriver
{
    public function send(TextMessage $message): MessageResult
    {
        $message->validate();

        $url = 'https://rest.nexmo.com/sms/json';

        $fields = array_merge([
            'api_key' => (string) $this->config('key'),
            'api_secret' => (string) $this->config('secret'),
            'to' => $this->requireTo($message),
            'from' => $message->from ?? (string) $this->config('from'),
            'text' => $message->text,
        ], $message->options);

        $response = $this->connector->send('POST', $url, fn () => $this->connector->request()
            ->asForm()
            ->post($url, $fields), $fields)->throw($this->driverName());

        return new MessageResult(
            accepted: $response->json('messages.0.status') === '0',
            id: $response->json('messages.0.message-id'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function driverName(): string
    {
        return 'vonage';
    }
}
