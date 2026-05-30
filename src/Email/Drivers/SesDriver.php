<?php

namespace ApiHub\Laravel\Email\Drivers;

use ApiHub\Laravel\Email\DTO\Address;
use ApiHub\Laravel\Email\DTO\EmailMessage;
use ApiHub\Laravel\Email\DTO\SendResult;
use ApiHub\Laravel\Support\AwsSignatureV4;
use InvalidArgumentException;

/**
 * Amazon SES v2 — https://docs.aws.amazon.com/ses/latest/APIReference-V2/API_SendEmail.html
 *
 * Posts a SigV4-signed JSON request to the regional endpoint. The signature
 * covers the exact body bytes, so the raw JSON is hashed and sent verbatim.
 * Attachments require a raw MIME body (not the Simple content used here) and
 * are not yet supported by this driver.
 */
class SesDriver extends AbstractEmailDriver
{
    public function send(EmailMessage $message): SendResult
    {
        $message->validate();

        if ($message->hasAttachments()) {
            throw new InvalidArgumentException('The SES driver does not yet support attachments (requires a raw MIME body).');
        }

        $region = (string) $this->config('region', 'us-east-1');
        $host = "email.{$region}.amazonaws.com";
        $url = "https://{$host}/v2/email/outbound-emails";

        $body = array_filter([
            'FromEmailAddress' => $message->from?->toString(),
            'Destination' => array_filter([
                'ToAddresses' => $this->stringAddresses($message->to),
                'CcAddresses' => $this->stringAddresses($message->cc),
                'BccAddresses' => $this->stringAddresses($message->bcc),
            ]),
            'ReplyToAddresses' => $message->replyTo !== null ? [$message->replyTo->toString()] : null,
            'Content' => [
                'Simple' => [
                    'Subject' => ['Data' => $message->subject],
                    'Body' => array_filter([
                        'Html' => $message->html !== null ? ['Data' => $message->html] : null,
                        'Text' => $message->text !== null ? ['Data' => $message->text] : null,
                    ]),
                ],
            ],
        ], fn ($value) => $value !== null && $value !== []);

        $raw = (string) json_encode($body);
        $amzDate = gmdate('Ymd\THis\Z');

        $signer = new AwsSignatureV4(
            accessKey: (string) $this->config('key'),
            secretKey: (string) $this->config('secret'),
            region: $region,
            service: 'ses',
        );

        $signed = $signer->headers('POST', $url, ['content-type' => 'application/json'], $raw, $amzDate);

        $response = $this->connector->send('POST', $url, fn () => $this->connector
            ->request($signed)
            ->withBody($raw, 'application/json')
            ->send('POST', $url), $body)->throw($this->driverName());

        return new SendResult(
            accepted: true,
            id: $response->json('MessageId'),
            driver: $this->driverName(),
            response: $response,
        );
    }

    protected function driverName(): string
    {
        return 'ses';
    }

    /**
     * @param  Address[]  $addresses
     * @return string[]
     */
    protected function stringAddresses(array $addresses): array
    {
        return array_map(fn (Address $address) => $address->toString(), $addresses);
    }
}
