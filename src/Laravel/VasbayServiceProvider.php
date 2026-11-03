<?php

namespace Texaavy\Vasbay\Laravel;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Texaavy\Vasbay\Client;

/**
 * Optional Laravel integration, auto-discovered (extra.laravel in composer.json). Only loaded inside a Laravel
 * application, so the core SDK does not depend on Laravel.
 *
 * Registers the Client (singleton, built from config/vasbay-sdk.php), the "vasbay" notification channel
 * and the publishable config: php artisan vendor:publish --tag=vasbay-config
 */
class VasbayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/vasbay-sdk.php', 'vasbay-sdk');

        $this->app->singleton(Client::class, function ($app) {
            $c = $app['config']['vasbay-sdk'];

            return new Client([
                'username' => $c['username'] ?? null,
                'apiKey' => $c['api_key'] ?? null,
                'baseUrl' => $c['base_url'] ?? Client::DEFAULT_BASE_URL,
                'timeout' => $c['timeout'] ?? Client::DEFAULT_TIMEOUT,
            ]);
        });
        $this->app->alias(Client::class, 'vasbay');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__ . '/../../config/vasbay-sdk.php' => $this->app->configPath('vasbay-sdk.php')], 'vasbay-config');

        Notification::resolved(function (ChannelManager $channels) {
            $channels->extend('vasbay', function ($app) {
                return $app->make(VasbayChannel::class);
            });
        });
    }
}
