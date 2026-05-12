<?php

namespace TuttoInCloud\NotifyClient;

use Illuminate\Support\ServiceProvider;

class NotifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/notify-client.php', 'notify-client');

        $this->app->singleton(NotifyClient::class, function ($app) {
            $config = $app['config']['notify-client'];

            return new NotifyClient(
                baseUrl: $config['base_url'],
                token: $config['token'] ?? '',
                timeout: $config['timeout'] ?? 10,
            );
        });

        $this->app->afterResolving('mail.manager', function ($manager) {
            $manager->extend('notify', function () {
                return new NotifyTransport(
                    $this->app->make(NotifyClient::class),
                );
            });
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/notify-client.php' => config_path('notify-client.php'),
        ], 'notify-client-config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'notify-client-migrations');
    }
}
