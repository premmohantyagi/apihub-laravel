# ApiHub for Laravel

[![tests](https://github.com/premmohantyagi/apihub-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/premmohantyagi/apihub-laravel/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/premmohantyagi/apihub-laravel.svg)](https://packagist.org/packages/premmohantyagi/apihub-laravel)
[![License](https://img.shields.io/packagist/l/premmohantyagi/apihub-laravel.svg)](LICENSE)

**ApiHub** is a unified integration package for Laravel. It gives you one clean,
consistent, driver-based interface over global third-party APIs — **Payments,
AI, Email, and SMS & Messaging** — so you can swap providers with a config
change instead of a rewrite.

Every driver is built on Laravel's HTTP client (no heavy vendor SDKs), so the
package stays light and conflict-free while sharing one timeout/retry policy, a
normalised response object, a unified exception hierarchy, secret redaction in
logs, and webhook signature verification.

---

## Table of contents

- [Why ApiHub](#why-apihub)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Core concepts](#core-concepts)
  - [Drivers & facades](#drivers--facades)
  - [Responses & the `raw()` escape hatch](#responses--the-raw-escape-hatch)
  - [Error handling](#error-handling)
  - [Testing with fakes](#testing-with-fakes)
- [Email](#email)
  - [Mailgun](#mailgun) · [SendGrid](#sendgrid) · [Amazon SES](#amazon-ses) · [Resend](#resend)
- [AI](#ai)
  - [OpenAI](#openai) · [Anthropic](#anthropic) · [Google Gemini](#google-gemini) · [DeepSeek](#deepseek)
- [SMS & Messaging](#sms--messaging)
  - [Twilio](#twilio) · [Vonage](#vonage) · [MSG91](#msg91) · [Telegram](#telegram) · [WhatsApp](#whatsapp) · [Slack](#slack) · [Discord](#discord)
- [Payments](#payments)
  - [Stripe](#stripe) · [Razorpay](#razorpay) · [PayPal](#paypal) · [Square](#square) · [Authorize.Net](#authorizenet)
  - [Verifying webhooks](#verifying-webhooks)
- [Environment variables reference](#environment-variables-reference)
- [Development](#development)
- [Roadmap](#roadmap)
- [License](#license)

---

## Why ApiHub

- **One interface per category.** Swap Stripe for Razorpay, or OpenAI for
  Anthropic, by changing a config value — not your code.
- **No SDK bloat.** Drivers talk raw REST over `illuminate/http`. Official SDKs
  are optional, never required.
- **Production-grade core.** Retries, timeouts, mapped exceptions, redacted
  logging, and constant-time webhook verification are built in for free.
- **Escape hatch always.** `->raw()` exposes the underlying response for
  provider-specific features the unified interface doesn't cover.
- **Test-friendly.** Every category ships a fake (`Email::fake()`, `Ai::fake()`,
  `Sms::fake()`, `Payments::fake()`) with assertions.

---

## Requirements

- PHP **8.0 – 8.4**
- Laravel **8, 9, 10, 11, 12, or 13**

---

## Installation

Install via Composer:

```bash
composer require premmohantyagi/apihub-laravel
```

The service provider and the `Payments`, `Ai`, `Email`, and `Sms` facades are
auto-discovered — no manual registration needed.

Publish the config file:

```bash
php artisan vendor:publish --tag=apihub-config
```

This creates `config/apihub.php`. Add the credentials you need to your `.env`
(see [Environment variables reference](#environment-variables-reference)).

---

## Configuration

Everything lives in a single `config/apihub.php`. Each category has a `default`
driver and a `drivers` array of per-provider credentials. Global HTTP, logging,
and queue settings apply to every driver.

```php
return [
    'http' => [
        'timeout'     => env('APIHUB_HTTP_TIMEOUT', 10),
        'retries'     => env('APIHUB_HTTP_RETRIES', 2),
        'retry_delay' => env('APIHUB_HTTP_RETRY_DELAY', 250), // ms
    ],

    'logging' => [
        'enabled'     => env('APIHUB_LOGGING', false), // logs each call at debug level
        'redact_keys' => ['password', 'secret', 'token', 'api_key', /* … */],
    ],

    'queue' => [
        'connection' => env('APIHUB_QUEUE_CONNECTION'),
        'name'       => env('APIHUB_QUEUE_NAME', 'default'),
    ],

    'payments'  => ['default' => env('APIHUB_PAYMENTS_DRIVER', 'stripe'),   'drivers' => [/* … */]],
    'ai'        => ['default' => env('APIHUB_AI_DRIVER', 'openai'),         'drivers' => [/* … */]],
    'email'     => ['default' => env('APIHUB_EMAIL_DRIVER', 'mailgun'),     'drivers' => [/* … */]],
    'messaging' => ['default' => env('APIHUB_MESSAGING_DRIVER', 'twilio'),  'drivers' => [/* … */]],
];
```

When **logging** is enabled, every request is logged at debug level with any key
matching `redact_keys` masked, so credentials never reach the log channel.

---

## Core concepts

### Drivers & facades

Each category exposes a facade that resolves the **default** driver, or a
specific one via `driver()`:

```php
use ApiHub\Laravel\Facades\Email;

Email::send($message);              // uses the configured default driver
Email::driver('resend')->send($m);  // uses a specific driver
```

| Category        | Facade     | Default env                  | Drivers |
|-----------------|------------|------------------------------|---------|
| Email           | `Email`    | `APIHUB_EMAIL_DRIVER`        | `mailgun`, `sendgrid`, `ses`, `resend` |
| AI              | `Ai`       | `APIHUB_AI_DRIVER`           | `openai`, `anthropic`, `gemini`, `deepseek` |
| SMS & Messaging | `Sms`      | `APIHUB_MESSAGING_DRIVER`    | `twilio`, `vonage`, `msg91`, `telegram`, `whatsapp`, `slack`, `discord` |
| Payments        | `Payments` | `APIHUB_PAYMENTS_DRIVER`     | `stripe`, `razorpay`, `paypal`, `square`, `authorizenet` |

### Responses & the `raw()` escape hatch

Every result is a typed DTO with a consistent shape. The original provider
payload is always available through the response:

```php
$result = Email::send($message);

$result->accepted();          // bool
$result->id();                // provider message id
$result->response?->raw();    // the underlying Laravel HTTP response
$result->response?->json('some.provider.specific.field');
```

### Error handling

On a failed HTTP response, drivers throw a mapped exception. Catch the base
class to handle any provider uniformly:

```php
use ApiHub\Laravel\Exceptions\ApiHubException;
use ApiHub\Laravel\Exceptions\AuthenticationException;
use ApiHub\Laravel\Exceptions\RateLimitException;

try {
    Ai::chat($request);
} catch (AuthenticationException $e) {   // 401 / 403
    // bad or missing credentials
} catch (RateLimitException $e) {        // 429
    // back off and retry later
} catch (ApiHubException $e) {            // any other 4xx / 5xx
    report($e);
    $e->statusCode;   // HTTP status
    $e->driver;       // which driver failed
    $e->response;     // the ApiHub Response (->raw(), ->json(), ->status())
}
```

| Exception                  | HTTP status |
|----------------------------|-------------|
| `AuthenticationException`  | 401, 403    |
| `RateLimitException`       | 429         |
| `RequestException`         | other 4xx   |
| `ServerException`          | 5xx         |
| `ApiHubException` (base)   | anything else / catch-all |

> Webhook verification and the fakes never throw on a bad signature or in tests
> — they return `false` / record the call.

### Testing with fakes

Each category swaps in an in-memory implementation with assertions:

```php
$fake = Email::fake();

// ...code under test calls Email::send(...)...

$fake->assertSent(fn ($message) => $message->subject === 'Welcome');
$fake->assertSentCount(1);
```

See each category below for its specific fake assertions.

---

## Email

```php
use ApiHub\Laravel\Facades\Email;
use ApiHub\Laravel\Email\DTO\EmailMessage;
use ApiHub\Laravel\Email\DTO\Attachment;

$message = EmailMessage::make()
    ->from('noreply@acme.test', 'Acme')
    ->to('user@example.com')                 // string, or an array of addresses
    ->cc(['team@acme.test'])
    ->bcc('audit@acme.test')
    ->replyTo('support@acme.test', 'Support')
    ->subject('Welcome')
    ->html('<p>Hello!</p>')
    ->text('Hello!')                         // optional plain-text part
    ->header('X-Campaign', 'welcome')
    ->attach(Attachment::fromPath(storage_path('invoice.pdf')));

$result = Email::send($message);

$result->accepted();   // bool
$result->id();         // provider message id
```

`EmailMessage` validates that it has a `from`, at least one `to`, and an `html`
or `text` body before any driver sends it.

**Attachments** — `Attachment::fromPath($path, $filename = null, $contentType = null)`
or `new Attachment($filename, $rawBytes, $contentType)`.

**Testing**

```php
$fake = Email::fake();
Email::send($message);

$fake->assertSent(fn ($message) => $message->subject === 'Welcome');
$fake->assertSentCount(1);
$fake->assertNothingSent();
```

### Mailgun

Posts to `/v3/{domain}/messages` with HTTP basic auth. Supports attachments
(multipart).

```php
// config/apihub.php → email.drivers.mailgun
'mailgun' => [
    'api_key'  => env('MAILGUN_API_KEY'),
    'domain'   => env('MAILGUN_DOMAIN'),
    'endpoint' => env('MAILGUN_ENDPOINT', 'https://api.mailgun.net'), // use https://api.eu.mailgun.net for EU
],
```

```php
Email::driver('mailgun')->send($message);
```

### SendGrid

Posts JSON to `/v3/mail/send` with a bearer token. The message id is read from
the `X-Message-Id` response header.

```php
'sendgrid' => [
    'api_key' => env('SENDGRID_API_KEY'),
],
```

```php
Email::driver('sendgrid')->send($message);
```

### Amazon SES

Sends a SigV4-signed request to the SES v2 API in your region.

```php
'ses' => [
    'key'    => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
],
```

```php
Email::driver('ses')->send($message);
```

> **Note:** the SES driver currently sends "Simple" content. Attachments require
> a raw MIME body and are not yet supported — passing one throws an
> `InvalidArgumentException`.

### Resend

Posts JSON to `/emails` with a bearer token. The id is returned in the body.

```php
'resend' => [
    'api_key' => env('RESEND_API_KEY'),
],
```

```php
Email::driver('resend')->send($message);
```

---

## AI

```php
use ApiHub\Laravel\Facades\Ai;
use ApiHub\Laravel\Ai\DTO\ChatRequest;

$request = ChatRequest::make()
    ->model('gpt-4o-mini')                       // optional; falls back to config/driver default
    ->system('You are concise.')
    ->user('Summarise Laravel in one sentence.')
    ->assistant('Sure — ')                       // optional prior turn
    ->temperature(0.7)
    ->maxTokens(256)
    ->option('top_p', 0.9);                      // provider-specific passthrough

$response = Ai::chat($request);

$response->content;             // assistant text (content blocks already flattened)
$response->model;
$response->finishReason;
$response->usage->promptTokens;
$response->usage->completionTokens;
$response->usage->totalTokens;
```

One `ChatRequest` works across every provider — the drivers translate it to each
API's shape, so switching providers is just `->driver('anthropic')`.

**Testing**

```php
$fake = Ai::fake('Mocked reply');
// or: Ai::fake()->respondWith(fn ($request) => strtoupper($request->messages[0]->content));

$response = Ai::chat(ChatRequest::make()->user('Hi'));   // 'Mocked reply'

$fake->assertChatted(fn ($request) => $request->messages[0]->content === 'Hi');
$fake->assertChattedCount(1);
```

### OpenAI

Chat Completions API (`/v1/chat/completions`), bearer auth.

```php
'openai' => [
    'api_key'      => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),  // optional
    'base_url'     => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    // 'model'     => 'gpt-4o',                     // optional default model
],
```

```php
Ai::driver('openai')->chat($request);
```

### Anthropic

Messages API (`/v1/messages`), `x-api-key` auth. The system prompt is sent as a
top-level field and `max_tokens` is always supplied (defaults to 1024).

```php
'anthropic' => [
    'api_key'  => env('ANTHROPIC_API_KEY'),
    'version'  => env('ANTHROPIC_VERSION', '2023-06-01'),
    'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
],
```

```php
Ai::driver('anthropic')->chat($request->model('claude-3-5-sonnet-latest'));
```

### Google Gemini

`generateContent` endpoint. The model goes in the path, the key is a query
param, and the system prompt becomes a `systemInstruction`.

```php
'gemini' => [
    'api_key'  => env('GEMINI_API_KEY'),
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
],
```

```php
Ai::driver('gemini')->chat($request->model('gemini-1.5-flash'));
```

### DeepSeek

OpenAI-compatible Chat Completions API — same request/response shape, different
endpoint and default model (`deepseek-chat`).

```php
'deepseek' => [
    'api_key'  => env('DEEPSEEK_API_KEY'),
    'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
],
```

```php
Ai::driver('deepseek')->chat($request);
```

> Default model names are sensible **fallbacks only** and overridable per driver
> via the `model` config key — pass `->model(...)` explicitly in production.

---

## SMS & Messaging

```php
use ApiHub\Laravel\Facades\Sms;
use ApiHub\Laravel\Messaging\DTO\TextMessage;

$message = TextMessage::make()
    ->to('+15551234567')      // phone (SMS/WhatsApp), chat id (Telegram), channel (Slack/Discord)
    ->from('Acme')            // optional sender override (SMS)
    ->text('Your code is 1234')
    ->option('parse_mode', 'Markdown');  // provider-specific passthrough

$result = Sms::send($message);

$result->accepted();   // bool
$result->id();         // provider message id, where one is returned
```

**Testing**

```php
$fake = Sms::fake();
Sms::send(TextMessage::make()->to('+15551234567')->text('Hi'));

$fake->assertSent(fn ($message) => $message->to === '+15551234567');
$fake->assertSentCount(1);
$fake->assertNothingSent();
```

### Twilio

Posts form fields to the Messages resource with HTTP basic auth. Returns the
Twilio `sid` as the id.

```php
'twilio' => [
    'sid'   => env('TWILIO_SID'),
    'token' => env('TWILIO_AUTH_TOKEN'),
    'from'  => env('TWILIO_FROM'),   // default sender, overridable per message
],
```

```php
Sms::driver('twilio')->send(TextMessage::make()->to('+15551234567')->text('Hi'));
```

### Vonage

Posts to the Nexmo SMS API. Vonage returns HTTP 200 even on logical failure, so
acceptance is read from the per-message status (`0` = success).

```php
'vonage' => [
    'key'    => env('VONAGE_KEY'),
    'secret' => env('VONAGE_SECRET'),
    'from'   => env('VONAGE_FROM'),
],
```

```php
$result = Sms::driver('vonage')->send($message);
$result->accepted();   // false if the gateway reported a non-zero status
```

### MSG91

Uses the plain-text HTTP endpoint (popular in India). The request id is returned
as the response body.

```php
'msg91' => [
    'auth_key' => env('MSG91_AUTH_KEY'),
    'sender'   => env('MSG91_SENDER'),
],
```

```php
Sms::driver('msg91')->send(TextMessage::make()->to('919876543210')->text('Hi'));
```

### Telegram

Posts JSON to `/bot{token}/sendMessage`; `to` is the chat id.

```php
'telegram' => [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
],
```

```php
Sms::driver('telegram')->send(TextMessage::make()->to('123456789')->text('Hi'));
```

### WhatsApp

WhatsApp Business Platform (Cloud API) via the Graph API; bearer auth. `to` is
the recipient phone number.

```php
'whatsapp' => [
    'token'            => env('WHATSAPP_TOKEN'),
    'phone_number_id'  => env('WHATSAPP_PHONE_NUMBER_ID'),
    // 'api_version'   => 'v21.0',   // optional, defaults to v21.0
],
```

```php
Sms::driver('whatsapp')->send(TextMessage::make()->to('15551234567')->text('Hi'));
```

### Slack

Two modes, chosen by what you configure:

- a **bot token** → `chat.postMessage` (`to` is the channel; id is the `ts`),
- a **webhook URL** → a simple post (no id returned).

```php
'slack' => [
    'token'       => env('SLACK_BOT_TOKEN'),     // preferred when set
    'webhook_url' => env('SLACK_WEBHOOK_URL'),   // fallback
],
```

```php
// Bot token mode — post to a channel:
Sms::driver('slack')->send(TextMessage::make()->to('#general')->text('Deploy finished'));

// Webhook mode — no channel needed:
Sms::driver('slack')->send(TextMessage::make()->text('Deploy finished'));
```

### Discord

Two modes:

- a **bot token + channel id** (`to`) → the channel messages endpoint (returns id),
- a **webhook URL** → an execute-webhook post (204, no body).

```php
'discord' => [
    'bot_token'   => env('DISCORD_BOT_TOKEN'),
    'webhook_url' => env('DISCORD_WEBHOOK_URL'),
],
```

```php
// Webhook mode:
Sms::driver('discord')->send(TextMessage::make()->text('Build passed ✅'));

// Bot mode — to a channel id:
Sms::driver('discord')->send(TextMessage::make()->to('123456789012345678')->text('Hi'));
```

---

## Payments

```php
use ApiHub\Laravel\Facades\Payments;
use ApiHub\Laravel\Payments\DTO\ChargeRequest;
use ApiHub\Laravel\Payments\DTO\RefundRequest;

$charge = Payments::charge(
    ChargeRequest::make()
        ->amount(1050, 'USD')      // 1050 = $10.50, held in the currency's minor units
        ->source('pm_card_visa')   // gateway token / nonce / payment method
        ->customer('cus_123')      // optional
        ->description('Order #42')
        ->reference('order-42')    // used as the idempotency key where supported
        ->metadata(['order_id' => '42'])
);

$charge->successful();   // bool
$charge->id();           // gateway id (payment intent / order / payment / transaction)
$charge->status();

$refund = Payments::refund(
    RefundRequest::make()
        ->payment($charge->id())
        ->amount(500, 'USD')       // omit for a full refund
        ->reason('requested_by_customer')
);
```

> **`charge()` semantics differ by gateway** — it maps to each one's primary
> server-side call: Stripe creates+confirms a PaymentIntent; Razorpay and PayPal
> create an order (completed by the buyer); Square creates a payment;
> Authorize.Net runs an auth-capture. The `id`, `status`, and `->raw()` let you
> continue each gateway's own flow.

**Money** — amounts are always in **minor units** (cents, paise). The `Money`
DTO formats decimals correctly, including zero-decimal currencies like JPY.

**Testing**

```php
$fake = Payments::fake();
Payments::charge(ChargeRequest::make()->amount(1050, 'USD')->source('tok'));

$fake->assertCharged(fn ($request) => $request->money?->minorUnits === 1050);
$fake->assertRefunded();
$fake->assertNothingCharged();
$fake->webhookValid = false;   // control verifyWebhook() in tests
```

### Stripe

Creates and confirms a PaymentIntent (form-encoded). Refunds act on the payment
intent id.

```php
'stripe' => [
    'secret'         => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

```php
Payments::driver('stripe')->charge(
    ChargeRequest::make()->amount(2000, 'USD')->source('pm_card_visa')
);
```

### Razorpay

`charge()` creates an Order (the payment is completed by the client checkout and
captured against this order). `refund()` acts on a payment id.

```php
'razorpay' => [
    'key'            => env('RAZORPAY_KEY'),
    'secret'         => env('RAZORPAY_SECRET'),
    'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
],
```

```php
$order = Payments::driver('razorpay')->charge(
    ChargeRequest::make()->amount(50000, 'INR')->reference('rcpt-1')
);
// $order->id() → "order_..." — pass it to Razorpay Checkout on the client.
```

### PayPal

Uses OAuth2 client-credentials for a bearer token. `charge()` creates an Orders
v2 order; `refund()` acts on a capture id.

```php
'paypal' => [
    'client_id'     => env('PAYPAL_CLIENT_ID'),
    'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    'mode'          => env('PAYPAL_MODE', 'sandbox'),  // 'sandbox' | 'live'
    'webhook_id'    => env('PAYPAL_WEBHOOK_ID'),        // required for webhook verification
],
```

```php
$order = Payments::driver('paypal')->charge(
    ChargeRequest::make()->amount(1050, 'USD')->description('Order #42')
);
```

### Square

`charge()` creates a Payment from a card nonce (`source`). `refund()` refunds a
payment.

```php
'square' => [
    'access_token'     => env('SQUARE_ACCESS_TOKEN'),
    'environment'      => env('SQUARE_ENV', 'sandbox'),  // 'sandbox' | 'production'
    'version'          => env('SQUARE_VERSION', '2024-10-17'),
    'signature_key'    => env('SQUARE_WEBHOOK_SIGNATURE_KEY'), // for webhook verification
    'notification_url' => env('SQUARE_WEBHOOK_URL'),           // the URL Square posts to
],
```

```php
Payments::driver('square')->charge(
    ChargeRequest::make()->amount(1050, 'USD')->source('cnon:card-nonce-ok')->reference('idem-1')
);
```

### Authorize.Net

`charge()` runs an `authCaptureTransaction` against an Accept.js opaque-data
token (the `source`).

```php
'authorizenet' => [
    'login_id'        => env('AUTHORIZENET_LOGIN_ID'),
    'transaction_key' => env('AUTHORIZENET_TRANSACTION_KEY'),
    'environment'     => env('AUTHORIZENET_ENV', 'sandbox'),  // 'sandbox' | 'production'
    'signature_key'   => env('AUTHORIZENET_SIGNATURE_KEY'),   // for webhook verification
],
```

```php
Payments::driver('authorizenet')->charge(
    ChargeRequest::make()
        ->amount(1050, 'USD')
        ->source('eyJjb2RlIjoi...')                 // Accept.js opaque dataValue
        ->option('data_descriptor', 'COMMON.ACCEPT.INAPP.PAYMENT')
);

// Refunds require the original transaction id plus the card's last four and expiry:
Payments::driver('authorizenet')->refund(
    RefundRequest::make()
        ->payment('60160000001')
        ->amount(1050, 'USD')
        ->option('card_number', '1111')          // last four
        ->option('expiration_date', '2026-12')
);
```

### Verifying webhooks

Verify the signature against the **raw request body** (never a re-encoded array):

```php
use ApiHub\Laravel\Facades\Payments;

public function handle(Request $request)
{
    $valid = Payments::driver('stripe')->verifyWebhook(
        $request->getContent(),       // the raw body
        $request->headers->all(),     // all request headers
    );

    abort_unless($valid, 400);

    // ...handle the verified event...
}
```

| Gateway        | Verification |
|----------------|--------------|
| Stripe         | HMAC-SHA256 over `{timestamp}.{body}` (`Stripe-Signature: t=…,v1=…`) |
| Razorpay       | HMAC-SHA256 of the body (`X-Razorpay-Signature`) |
| Square         | base64 HMAC-SHA256 over `notification_url + body` (`x-square-hmacsha256-signature`) |
| Authorize.Net  | HMAC-SHA512 hex over the body (`X-ANET-Signature: sha512=…`) |
| PayPal         | Calls PayPal's verify-webhook-signature API (needs `webhook_id`) |

---

## Environment variables reference

```dotenv
# ── Global ──────────────────────────────────────────────
APIHUB_HTTP_TIMEOUT=10
APIHUB_HTTP_RETRIES=2
APIHUB_HTTP_RETRY_DELAY=250
APIHUB_LOGGING=false
APIHUB_QUEUE_CONNECTION=
APIHUB_QUEUE_NAME=default

# ── Email ───────────────────────────────────────────────
APIHUB_EMAIL_DRIVER=mailgun
MAILGUN_API_KEY=
MAILGUN_DOMAIN=
MAILGUN_ENDPOINT=https://api.mailgun.net
SENDGRID_API_KEY=
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
RESEND_API_KEY=

# ── AI ──────────────────────────────────────────────────
APIHUB_AI_DRIVER=openai
OPENAI_API_KEY=
OPENAI_ORGANIZATION=
ANTHROPIC_API_KEY=
GEMINI_API_KEY=
DEEPSEEK_API_KEY=

# ── SMS & Messaging ─────────────────────────────────────
APIHUB_MESSAGING_DRIVER=twilio
TWILIO_SID=
TWILIO_AUTH_TOKEN=
TWILIO_FROM=
VONAGE_KEY=
VONAGE_SECRET=
VONAGE_FROM=
MSG91_AUTH_KEY=
MSG91_SENDER=
TELEGRAM_BOT_TOKEN=
WHATSAPP_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
SLACK_BOT_TOKEN=
SLACK_WEBHOOK_URL=
DISCORD_BOT_TOKEN=
DISCORD_WEBHOOK_URL=

# ── Payments ────────────────────────────────────────────
APIHUB_PAYMENTS_DRIVER=stripe
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
RAZORPAY_KEY=
RAZORPAY_SECRET=
RAZORPAY_WEBHOOK_SECRET=
PAYPAL_CLIENT_ID=
PAYPAL_CLIENT_SECRET=
PAYPAL_MODE=sandbox
PAYPAL_WEBHOOK_ID=
SQUARE_ACCESS_TOKEN=
SQUARE_ENV=sandbox
SQUARE_WEBHOOK_SIGNATURE_KEY=
SQUARE_WEBHOOK_URL=
AUTHORIZENET_LOGIN_ID=
AUTHORIZENET_TRANSACTION_KEY=
AUTHORIZENET_ENV=sandbox
AUTHORIZENET_SIGNATURE_KEY=
```

---

## Development

```bash
composer install
composer test    # Pest test suite
composer pint    # Laravel Pint (code style)
composer stan    # PHPStan / Larastan (level 5)
```

The package runtime supports **PHP 8.0+** and **Laravel 8+**. The modern test
tooling (Pest 2/3) needs PHP 8.1+, so CI runs the full suite across PHP 8.1–8.4
× Laravel 10–12 and additionally lints every source file under **PHP 8.0** to
guarantee it stays parse-compatible with the lowest supported version.

---

## Roadmap

- [x] **Core engine** — HTTP connector, normalised responses, exception
      hierarchy, redaction, webhook verification, managers & facades.
- [x] **Email** — Mailgun, SendGrid, SES, Resend (+ AWS SigV4 signer).
- [x] **AI** — OpenAI, Anthropic, Gemini, DeepSeek.
- [x] **SMS & Messaging** — Twilio, Vonage, MSG91, Telegram, WhatsApp, Slack, Discord.
- [x] **Payments** — Stripe, Razorpay, PayPal, Square, Authorize.Net (+ webhook verification).
- [ ] Future categories — Cloud & Storage, Social, Maps, Auth, Monitoring — build
      on the same core without changes to it.

---

## License

ApiHub for Laravel is open-source software licensed under the [MIT license](LICENSE).
