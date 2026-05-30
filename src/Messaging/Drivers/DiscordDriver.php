<?php

namespace ApiHub\Laravel\Messaging\Drivers;

use ApiHub\Laravel\Messaging\DTO\MessageResult;
use ApiHub\Laravel\Messaging\DTO\TextMessage;
use InvalidArgumentException;

/**
 * Discord — https://discord.com/developers/docs/resources/message
 *
 * Two modes, chosen by what is configured:
 *  - a bot token + channel id (`to`) → the channel messages endpoint (returns id),
 *  - a webhook URL → an execute-webhook post (204, no body).
 */
class DiscordDriver extends AbstractMessagingDriver
{
    public function send(TextMessage $message): MessageResult
    {
        $message->validate();

        $botToken = $this->config('bot_token');

        if ($botToken && $message->to !== null && $message->to !== '') {
            return $this->sendViaBot($message, (string) $botToken);
        }

        $webhook = $message->to ?? $this->config('webhook_url');

        if (! $webhook) {
            throw new InvalidArgumentException('The discord driver requires a bot token + channel or a webhook url.');
        }

        $response = $this->connector
            ->post((string) $webhook, array_merge(['content' => $message->text], $message->options))
            ->throw($this->driverName());

        return new MessageResult(
            accepted: $response->ok(),
            id: $response->json('id'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function sendViaBot(TextMessage $message, string $token): MessageResult
    {
        $url = "https://discord.com/api/v10/channels/{$message->to}/messages";

        $response = $this->connector
            ->post($url, array_merge(['content' => $message->text], $message->options), ['Authorization' => "Bot {$token}"])
            ->throw($this->driverName());

        return new MessageResult(
            accepted: true,
            id: $response->json('id'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function driverName(): string
    {
        return 'discord';
    }
}
