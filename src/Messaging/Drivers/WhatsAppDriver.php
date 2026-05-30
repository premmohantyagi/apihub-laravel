<?php

namespace ApiHub\Laravel\Messaging\Drivers;

use ApiHub\Laravel\Messaging\DTO\MessageResult;
use ApiHub\Laravel\Messaging\DTO\TextMessage;

/**
 * WhatsApp Business Platform (Cloud API) —
 * https://developers.facebook.com/docs/whatsapp/cloud-api/reference/messages
 *
 * Posts JSON to the Graph API messages endpoint with a bearer token; `to` is
 * the recipient phone number. The message id is at messages[0].id.
 */
class WhatsAppDriver extends AbstractMessagingDriver
{
    public function send(TextMessage $message): MessageResult
    {
        $message->validate();

        $token = (string) $this->config('token');
        $phoneNumberId = (string) $this->config('phone_number_id');
        $version = (string) $this->config('api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        $body = array_merge([
            'messaging_product' => 'whatsapp',
            'to' => $this->requireTo($message),
            'type' => 'text',
            'text' => ['body' => $message->text],
        ], $message->options);

        $response = $this->connector
            ->post($url, $body, ['Authorization' => "Bearer {$token}"])
            ->throw($this->driverName());

        return new MessageResult(
            accepted: true,
            id: $response->json('messages.0.id'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function driverName(): string
    {
        return 'whatsapp';
    }
}
