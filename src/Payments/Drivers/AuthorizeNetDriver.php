<?php

namespace ApiHub\Laravel\Payments\Drivers;

use ApiHub\Laravel\Http\Response;
use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use ApiHub\Laravel\Payments\DTO\ChargeResult;
use ApiHub\Laravel\Payments\DTO\RefundRequest;
use ApiHub\Laravel\Payments\DTO\RefundResult;
use InvalidArgumentException;

/**
 * Authorize.Net — https://developer.authorize.net/api/reference/
 *
 * JSON API (despite the /xml/ path). charge() runs an authCaptureTransaction
 * against an Accept.js opaqueData token (the `source`). refund() needs the
 * original transaction id plus the card's last four and expiry (via options),
 * which the gateway requires. Webhooks are verified with an HMAC-SHA512 hex
 * digest in the X-ANET-Signature header.
 *
 * Note: Authorize.Net prefixes its JSON responses with a UTF-8 BOM, so the body
 * is parsed manually rather than via the standard json decode.
 */
class AuthorizeNetDriver extends AbstractPaymentDriver
{
    public function charge(ChargeRequest $request): ChargeResult
    {
        $money = $request->requireMoney();

        if ($request->source === null) {
            throw new InvalidArgumentException('The authorizenet driver requires a source (Accept.js opaque data value).');
        }

        $transaction = array_filter([
            'transactionType' => 'authCaptureTransaction',
            'amount' => $money->decimal(),
            'payment' => ['opaqueData' => [
                'dataDescriptor' => (string) ($request->options['data_descriptor'] ?? 'COMMON.ACCEPT.INAPP.PAYMENT'),
                'dataValue' => $request->source,
            ]],
            'order' => $request->reference !== null ? ['invoiceNumber' => $request->reference] : null,
        ], fn ($value) => $value !== null);

        $data = $this->send(['transactionRequest' => $transaction]);
        $response = $data['response'];
        $parsed = $data['parsed'];

        return new ChargeResult(
            successful: ($parsed['messages']['resultCode'] ?? null) === 'Ok',
            id: $parsed['transactionResponse']['transId'] ?? null,
            status: $parsed['transactionResponse']['responseCode'] ?? null,
            money: $money,
            driver: $this->driverName(),
            response: $response,
        );
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $cardNumber = $request->options['card_number'] ?? null;
        $expirationDate = $request->options['expiration_date'] ?? null;

        if ($cardNumber === null || $expirationDate === null) {
            throw new InvalidArgumentException('The authorizenet driver requires card_number (last four) and expiration_date options to refund.');
        }

        $transaction = array_filter([
            'transactionType' => 'refundTransaction',
            'amount' => $request->money?->decimal(),
            'payment' => ['creditCard' => ['cardNumber' => $cardNumber, 'expirationDate' => $expirationDate]],
            'refTransId' => $request->paymentId,
        ], fn ($value) => $value !== null);

        $data = $this->send(['transactionRequest' => $transaction]);
        $parsed = $data['parsed'];

        return new RefundResult(
            successful: ($parsed['messages']['resultCode'] ?? null) === 'Ok',
            id: $parsed['transactionResponse']['transId'] ?? null,
            status: $parsed['transactionResponse']['responseCode'] ?? null,
            driver: $this->driverName(),
            response: $data['response'],
        );
    }

    public function verifyWebhook(string $payload, array $headers): bool
    {
        $key = (string) $this->config('signature_key');
        $header = $this->header($headers, 'X-ANET-Signature');

        if ($key === '' || $header === null) {
            return false;
        }

        $provided = strtoupper(str_ireplace('sha512=', '', $header));
        $expected = strtoupper(hash_hmac('sha512', $payload, $key));

        return $this->verifier()->equals($expected, $provided);
    }

    /**
     * @param  array<string, mixed>  $transactionRequest
     * @return array{response: Response, parsed: array<string, mixed>}
     */
    protected function send(array $transactionRequest): array
    {
        $body = ['createTransactionRequest' => array_merge([
            'merchantAuthentication' => [
                'name' => (string) $this->config('login_id'),
                'transactionKey' => (string) $this->config('transaction_key'),
            ],
        ], $transactionRequest)];

        $response = $this->connector->post($this->endpoint(), $body)->throw($this->driverName());

        return ['response' => $response, 'parsed' => $this->parse($response)];
    }

    /**
     * @return array<string, mixed>
     */
    protected function parse(Response $response): array
    {
        $body = ltrim($response->body(), "\xEF\xBB\xBF \n\r\t");
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function endpoint(): string
    {
        return $this->config('environment') === 'production'
            ? 'https://api.authorize.net/xml/v1/request.api'
            : 'https://apitest.authorize.net/xml/v1/request.api';
    }

    protected function driverName(): string
    {
        return 'authorizenet';
    }
}
