<?php

use ApiHub\Laravel\Ai\AiManager;
use ApiHub\Laravel\Email\EmailManager;
use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Messaging\MessagingManager;
use ApiHub\Laravel\Payments\PaymentManager;
use ApiHub\Laravel\Support\Redactor;
use ApiHub\Laravel\Webhooks\SignatureVerifier;

it('loads the package config', function () {
    expect(config('apihub.http.timeout'))->toBe(10)
        ->and(config('apihub.payments.default'))->not->toBeNull()
        ->and(config('apihub.ai.default'))->not->toBeNull();
});

it('resolves each category manager as a singleton', function () {
    foreach ([PaymentManager::class, AiManager::class, EmailManager::class, MessagingManager::class] as $manager) {
        expect(app($manager))->toBeInstanceOf($manager)
            ->and(app($manager))->toBe(app($manager));
    }
});

it('reports its configured default driver per category', function () {
    expect(app(PaymentManager::class)->getDefaultDriver())->toBe('stripe')
        ->and(app(AiManager::class)->getDefaultDriver())->toBe('openai')
        ->and(app(EmailManager::class)->getDefaultDriver())->toBe('mailgun')
        ->and(app(MessagingManager::class)->getDefaultDriver())->toBe('twilio');
});

it('resolves core support services', function () {
    expect(app(Redactor::class))->toBeInstanceOf(Redactor::class)
        ->and(app(SignatureVerifier::class))->toBeInstanceOf(SignatureVerifier::class)
        ->and(app(Connector::class))->toBeInstanceOf(Connector::class);
});
