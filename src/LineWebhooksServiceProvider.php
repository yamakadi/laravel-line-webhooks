<?php

namespace Yamakadi\LineWebhooks;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LINE\LINEBot;

class LineWebhooksServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/line-webhooks.php' => config_path('line-webhooks.php'),
            ], 'config');
        }

        if (! class_exists('CreateLineWebhookCallsTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__ . '/../database/migrations/create_line_webhook_calls_table.php.stub' => database_path('migrations/'.$timestamp.'_create_line_webhook_calls_table.php'),
            ], 'migrations');
        }

        Route::macro('lineWebhooks', function ($url) {
            return Route::post($url, '\Yamakadi\LineWebhooks\LineWebhooksController');
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/line-webhooks.php', 'line-webhooks');

        $this->app->bind(LINEBot::class, function($app) {
            $httpClient = new LINEBot\HTTPClient\CurlHTTPClient(config('line-webhooks.channel_access_token'));

            return new LINEBot($httpClient, ['channelSecret' => config('line-webhooks.channel_secret')]);
        });
    }
}
