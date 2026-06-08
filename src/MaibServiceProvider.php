<?php

namespace Fynduck\MaibEcommerce;

use Fynduck\MaibEcommerce\Auth\TokenManager;
use Fynduck\MaibEcommerce\Contracts\MaibClientContract;
use Fynduck\MaibEcommerce\Http\Gateway;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class MaibServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/maib.php', 'maib');

        $this->app->singleton(Gateway::class, function (Application $app): Gateway {
            $config = $app['config']['maib'];

            return new Gateway($config['base_url'], (int) $config['timeout']);
        });

        $this->app->singleton(TokenManager::class, function (Application $app): TokenManager {
            $config = $app['config']['maib'];

            return new TokenManager(
                gateway: $app->make(Gateway::class),
                cache: Cache::store($config['token']['cache_store'] ?? null),
                projectId: (string) $config['project_id'],
                projectSecret: (string) $config['project_secret'],
                cacheKey: $config['token']['cache_prefix'] ?? 'maib_token',
                ttlLeeway: (int) ($config['token']['ttl_leeway'] ?? 30),
            );
        });

        $this->app->singleton(MaibClientContract::class, function (Application $app): MaibClient {
            return new MaibClient($app->make(Gateway::class), $app->make(TokenManager::class));
        });

        $this->app->alias(MaibClientContract::class, 'maib');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/maib.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/maib.php' => $this->app->configPath('maib.php'),
            ], 'maib-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'maib-migrations');
        }
    }
}
