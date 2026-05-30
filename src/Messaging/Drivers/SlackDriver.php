<?php

namespace ApiHub\Laravel\Messaging\Drivers;

use ApiHub\Laravel\Messaging\DTO\MessageResult;
use ApiHub\Laravel\Messaging\DTO\TextMessage;
use InvalidArgumentException;

/**
 * Slack — https://api.slack.com/messaging/sending
 *
 * Two modes, chosen by what is configured:
 *  - a bot token → chat.postMessage (`to` is the channel; id is the "ts"),
 *  - an incoming webhook URL → a simple post (no id returned).
 */
class SlackDriver extends AbstractMessagingDriver
{
    public function send(TextMessage $message): MessageResult
    {
        $message->validate();

        $token = $this->config('token');

        if ($token) {
            return $this->sendViaApi($message, (string) $token);
        }

        $webhook = $message->to ?? $this->config('webhook_url');

        if (! $webhook) {
            throw new InvalidArgumentException('The slack driver requires a bot token or a webhook url.');
        }

        $response = $this->connector
            ->post((string) $webhook, array_merge(['text' => $message->text], $message->options))
            ->throw($this->driverName());

        return new MessageResult(
            accepted: $response->ok(),
            id: null,
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function sendViaApi(TextMessage $message, string $token): MessageResult
    {
        $body = array_merge([
            'channel' => $message->to ?? (string) $this->config('channel'),
            'text' => $message->text,
        ], $message->options);

        $response = $this->connector
            ->post('https://slack.com/api/chat.postMessage', $body, ['Authorization' => "Bearer {$token}"])
            ->throw($this->driverName());

        return new MessageResult(
            accepted: (bool) $response->json('ok'),
            id: $response->json('ts'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function driverName(): string
    {
        return 'slack';
    }
}
