<?php

namespace ApiHub\Laravel;

use ApiHub\Laravel\Ai\AiManager;
use ApiHub\Laravel\Email\EmailManager;
use ApiHub\Laravel\Http\Connector;
use ApiHub\Laravel\Messaging\MessagingManager;
use ApiHub\Laravel\Payments\PaymentManager;
use ApiHub\Laravel\Support\Redactor;
use ApiHub\Laravel\Webhooks\SignatureVerifier;
use Illuminate\Support\ServiceProvider;

class ApiHubServiceProvider extends ServiceProvider
{
    /** @var array<int, class-string> */
    private const MANAGERS = [
        PaymentManager::class,
        AiManager::class,
        EmailManager::class,
        MessagingManager::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/apihub.php', 'apihub');

        $this->app->singleton(Redactor::class, fn ($app) => new Redactor(
            needles: (array) config('apihub.logging.redact_keys', []),
        ));

        $this->app->singleton(SignatureVerifier::class, fn ($app) => new SignatureVerifier);

        // A shared, pre-configured HTTP engine. Drivers specialise it per
        // provider with withDefaults() rather than constructing their own.
        $this->app->bind(Connector::class, fn ($app) => new Connector(
            timeout: (int) config('apihub.http.timeout', 10),
            retries: (int) config('apihub.http.retries', 2),
            retryDelay: (int) config('apihub.http.retry_delay', 250),
            logging: (bool) config('apihub.logging.enabled', false),
            redactor: $app->make(Redactor::class),
        ));

        foreach (self::MANAGERS as $manager) {
            $this->app->singleton($manager, fn ($app) => new $manager($app));
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/apihub.php' => config_path('apihub.php'),
        ], 'apihub-config');
    }
}
