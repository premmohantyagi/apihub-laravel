<?php

namespace ApiHub\Laravel\Messaging\Drivers;

use ApiHub\Laravel\Messaging\DTO\MessageResult;
use ApiHub\Laravel\Messaging\DTO\TextMessage;

/**
 * Twilio SMS — https://www.twilio.com/docs/sms/api/message-resource
 *
 * Posts form fields to the Messages resource with HTTP basic auth (Account SID
 * + auth token). The message id is returned as "sid".
 */
class TwilioDriver extends AbstractMessagingDriver
{
    public function send(TextMessage $message): MessageResult
    {
        $message->validate();

        $sid = (string) $this->config('sid');
        $token = (string) $this->config('token');
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $fields = array_merge([
            'To' => $this->requireTo($message),
            'From' => $message->from ?? (string) $this->config('from'),
            'Body' => $message->text,
        ], $message->options);

        $response = $this->connector->send('POST', $url, fn () => $this->connector->request()
            ->withBasicAuth($sid, $token)
            ->asForm()
            ->post($url, $fields), $fields)->throw($this->driverName());

        return new MessageResult(
            accepted: true,
            id: $response->json('sid'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function driverName(): string
    {
        return 'twilio';
    }
}
