<?php

namespace Yamakadi\LineWebhooks;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Yamakadi\LineBot\AccessToken\Issue;
use Yamakadi\LineBot\AccessToken\Token;
use Yamakadi\LineBot\Channel;
use Yamakadi\LineBot\LineBot;

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

        $this->app->bind(Channel::class, function($app) {
            return new Channel(config('line-webhooks.channel_id'), config('line-webhooks.channel_secret'));
        });

        $this->app->bind(Token::class, function($app) {
            if(config('line-webhooks.channel_access_token')) {
                return Token::make(config('line-webhooks.channel_access_token'));
            }

            $issue = new Issue(new Client());
            $channel = $app->make(Channel::class);

            return $issue($channel->id(), $channel->secret());
        });


        $this->app->bind(LineBot::class, function($app) {
            return new LineBot($app->make(Channel::class), $app->make(Token::class), new Client());
        });
    }
}
