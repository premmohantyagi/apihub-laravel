<?php

namespace ApiHub\Laravel\Messaging\Drivers;

use ApiHub\Laravel\Messaging\DTO\MessageResult;
use ApiHub\Laravel\Messaging\DTO\TextMessage;

/**
 * Telegram Bot API — https://core.telegram.org/bots/api#sendmessage
 *
 * Posts JSON to /bot{token}/sendMessage; `to` is the chat id. The reply wraps
 * the result in { ok, result: { message_id } }.
 */
class TelegramDriver extends AbstractMessagingDriver
{
    public function send(TextMessage $message): MessageResult
    {
        $message->validate();

        $token = (string) $this->config('bot_token');
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $body = array_merge([
            'chat_id' => $this->requireTo($message),
            'text' => $message->text,
        ], $message->options);

        $response = $this->connector->post($url, $body)->throw($this->driverName());

        $messageId = $response->json('result.message_id');

        return new MessageResult(
            accepted: (bool) $response->json('ok'),
            id: $messageId !== null ? (string) $messageId : null,
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function driverName(): string
    {
        return 'telegram';
    }
}
