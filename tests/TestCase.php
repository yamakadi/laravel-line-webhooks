<?php

namespace Yamakadi\LineWebhooks\Tests;

use Exception;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Yamakadi\LineBot\Events\Generic;
use Yamakadi\LineWebhooks\LineWebhooksServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Set up the environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config(['line-webhooks.channel_id' => 'test_channel_id']);
        config(['line-webhooks.channel_secret' => 'test_channel_secret']);
        config(['line-webhooks.channel_access_token' => 'test_channel_access_token']);
    }

    protected function setUpDatabase()
    {
        include_once __DIR__ . '/../database/migrations/create_line_webhook_calls_table.php.stub';

        (new \CreateLineWebhookCallsTable())->up();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            LineWebhooksServiceProvider::class,
        ];
    }

    protected function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new class extends Handler
        {
            public function __construct()
            {
            }

            public function report(Exception $e)
            {
            }

            public function render($request, Exception $exception)
            {
                throw $exception;
            }
        });
    }

    protected function determineLineSignature(array $payload): string
    {
        $secret = config('line-webhooks.channel_secret');

        $signature = base64_encode(hash_hmac('sha256', json_encode($payload), $secret, true));

        return "{$signature}";
    }

    protected function getFakePayload(array $overwrite = [])
    {
        $payload = [
            'events' =>
                [
                    [
                        'type' => 'message',
                        'replyToken' => 'token',
                        'source' =>
                            [
                                'userId' => 'id',
                                'type' => 'user',
                            ],
                        'timestamp' => time(),
                        'message' =>
                            [
                                'type' => 'text',
                                'id' => 'message-id',
                                'text' => 'Hello!',
                            ],
                    ],
                ],
        ];

        return array_merge($payload, $overwrite);
    }

    protected function getFakeEvent(array $overwrite = [])
    {
        return new class($this->getFakePayload($overwrite)) extends Generic
        {
            const TYPE = 'test';

            public function __construct(array $source)
            {
                parent::__construct(time(), $source);
            }
        };
    }
}



