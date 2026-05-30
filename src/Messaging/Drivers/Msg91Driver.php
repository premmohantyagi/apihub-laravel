<?php

namespace ApiHub\Laravel\Messaging\Drivers;

use ApiHub\Laravel\Messaging\DTO\MessageResult;
use ApiHub\Laravel\Messaging\DTO\TextMessage;

/**
 * MSG91 SMS — https://docs.msg91.com/
 *
 * Uses the plain-text HTTP endpoint, which authenticates with an authkey and
 * returns the request id as the response body.
 */
class Msg91Driver extends AbstractMessagingDriver
{
    public function send(TextMessage $message): MessageResult
    {
        $message->validate();

        $url = 'https://api.msg91.com/api/sendhttp.php';

        $query = array_merge([
            'authkey' => (string) $this->config('auth_key'),
            'mobiles' => $this->requireTo($message),
            'message' => $message->text,
            'sender' => $message->from ?? (string) $this->config('sender'),
            'route' => (string) $this->config('route', '4'),
            'country' => (string) $this->config('country', '91'),
        ], $message->options);

        $response = $this->connector->get($url, $query)->throw($this->driverName());

        $id = trim($response->body());

        return new MessageResult(
            accepted: true,
            id: $id !== '' ? $id : null,
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function driverName(): string
    {
        return 'msg91';
    }
}
