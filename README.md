# Handle Line Webhooks in a Laravel application

[![Latest Version on Packagist](https://img.shields.io/packagist/v/yamakadi/laravel-line-webhooks.svg?style=flat-square)](https://packagist.org/packages/yamakadi/laravel-line-webhooks)
[![Build Status](https://img.shields.io/travis/yamakadi/laravel-line-webhooks/master.svg?style=flat-square)](https://travis-ci.org/yamakadi/laravel-line-webhooks)
[![StyleCI](https://styleci.io/repos/105920179/shield?branch=master)](https://styleci.io/repos/105920179)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/a027b103-772c-4dbc-a2a4-a6ccc07e127f.svg?style=flat-square)](https://insight.sensiolabs.com/projects/a027b103-772c-4dbc-a2a4-a6ccc07e127f)
[![Quality Score](https://img.shields.io/scrutinizer/g/yamakadi/laravel-line-webhooks.svg?style=flat-square)](https://scrutinizer-ci.com/g/yamakadi/laravel-line-webhooks)
[![Total Downloads](https://img.shields.io/packagist/dt/yamakadi/laravel-line-webhooks.svg?style=flat-square)](https://packagist.org/packages/yamakadi/laravel-line-webhooks)

[Line](https://developers.line.me/en/docs/) can notify your application of events using webhooks. This package can help you handle those webhooks. Out of the box it will verify the Line signature of all incoming requests. All valid calls will be logged to the database. You can easily define jobs or events that should be dispatched when specific events hit your app.

This package will not handle what should be done after the webhook request has been validated and the right job or event is called. You should still code up any work (eg. regarding messages) yourself.

Before using this package we highly recommend reading [the entire documentation on the messaging api over at Line](https://developers.line.me/en/docs/messaging-api/overview/).

## Installation

You can install the package via composer:

```bash
composer require yamakadi/laravel-line-webhooks
```

The service provider will automatically register itself.

You must publish the config file with:
```bash
php artisan vendor:publish --provider="Yamakadi\LineWebhooks\LineWebhooksServiceProvider" --tag="config"
```

This is the contents of the config file that will be published at `config/line-webhooks.php`:

```php
return [
    /*
     * You need to define your channel secret and access token in your environment variables
     */
    'channel_id' => env('LINEBOT_CHANNEL_ID'),
    'channel_secret' => env('LINEBOT_CHANNEL_SECRET'),
    'channel_access_token' => env('LINEBOT_CHANNEL_ACCESS_TOKEN'),

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Line event in snake_case.
     *
     * You can find a list of Line webhook types here:
     * https://developers.line.me/en/docs/messaging-api/reference/#webhook-event-objects
     */
    'jobs' => [
        // 'message' => \App\Jobs\LineWebhooks\HandleIncomingMessage::class,
        // 'beacon' => \App\Jobs\LineWebhooks\HandleBeaconSignal::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Yamakadi\LineWebhooks\LineWebhookCall.
     */
    'model' => Yamakadi\LineWebhooks\LineWebhookCall::class,
];

```

Next, you must publish the migration with:
```bash
php artisan vendor:publish --provider="Yamakadi\LineWebhooks\LineWebhooksServiceProvider" --tag="migrations"
```

After the migration has been published you can create the `line_webhook_calls` table by running the migrations:

```bash
php artisan migrate
```

Finally, take care of the routing: At [the Line dashboard](https://developers.line.me/console/) you must configure at what url Line webhooks should hit your app. In the routes file of your app you must pass that route to `Route::lineWebhooks`:

```php
Route::lineWebhooks('webhook-route-configured-at-the-line-dashboard'); 
```

Behind the scenes this will register a `POST` route to a controller provided by this package. Because Line has no way of getting a csrf-token, you must add that route to the `except` array of the `VerifyCsrfToken` middleware:

```php
protected $except = [
    'webhook-route-configured-at-the-line-dashboard',
];
```

## Usage

Line will send out webhooks for several event types. You can find the [full list of events types](https://developers.line.me/en/docs/messaging-api/reference/#webhook-event-objects) in the Line documentation.

Line will sign all requests hitting the webhook url of your app. This package will automatically verify if the signature is valid. If it is not, the request was probably not sent by Line.
 
Unless something goes terribly wrong, this package will always respond with a `200` to webhook requests. Sending a `200` will prevent Line from resending the same event over and over again. All webhook requests with a valid signature will be logged in the `line_webhook_calls` table. The table has a `payload` column where the entire payload of the incoming webhook is saved.

If the signature is not valid, the request will not be logged in the `line_webhook_calls` table but a `Yamakadi\LineWebhooks\WebhookFailed` exception will be thrown.
If something goes wrong during the webhook request the thrown exception will be saved in the `exception` column. In that case the controller will send a `500` instead of `200`. 
 
There are two ways this package enables you to handle webhook requests: you can opt to queue a job or listen to the events the package will fire.
 
 
### Handling webhook requests using jobs 
If you want to do something when a specific event type comes in you can define a job that does the work. Here's an example of such a job:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Yamakadi\LineBot\Events\Event;
use Yamakadi\LineWebhooks\LineWebhookCall;

class HandleIncomingText implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    
    /** @var \Yamakadi\LineBot\Events\Event */
    public $event;
    
    /** @var \Yamakadi\LineWebhooks\LineWebhookCall */
    public $webhookCall;

    public function __construct(Event $event, LineWebhookCall $webhookCall)
    {
        $this->event = $event;
        $this->webhookCall = $webhookCall;
    }

    public function handle()
    {
        // do your work here
        
        // you can access the payload of the webhook call with `$this->webhookCall->payload`
    }
}
```

We highly recommend that you make this job queueable, because this will minimize the response time of the webhook requests. This allows you to handle more line webhook requests and avoid timeouts.

After having created your job you must register it at the `jobs` array in the `line-webhooks.php` config file. The key should be the name of [the line event type](https://developers.line.me/en/docs/messaging-api/reference/#webhook-event-objects) in snake_case. The value should be the fully qualified classname.

```php
// config/line-webhooks.php

'jobs' => [
    'message' => \App\Jobs\LineWebhooks\HandleIncomingMessage::class,
],
```

### Handling webhook requests using events

Instead of queueing jobs to perform some work when a webhook request comes in, you can opt to listen to the events this package will fire. Whenever a valid request hits your app, the package will fire a `line-webhooks::<name-of-the-event>` event.

The payload of the events will be the instance of `LineWebhookCall` that was created for the incoming request and the event object from the Line SDK. 

Let's take a look at how you can listen for such an event. In the `EventServiceProvider` you can register listeners.

```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    'line-webhooks::message' => [
        App\Jobs\LineWebhooks\HandleIncomingMessage::class,
    ],
];
```

Here's an example of such a listener:

```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Yamakadi\LineBot\Events\Event;
use Yamakadi\LineWebhooks\LineWebhookCall;

class ReplyWithQuote implements ShouldQueue
{
    public function handle(Event $event, LineWebhookCall $webhookCall)
    {
        // do your work here

        // you can access the payload of the webhook call with `$webhookCall->payload`
    }   
}
```

We highly recommend that you make the event listener queueable, as this will minimize the response time of the webhook requests. This allows you to handle more Line webhook requests and avoid timeouts.

The above example is only one way to handle events in Laravel. To learn the other options, read [the Laravel documentation on handling events](https://laravel.com/docs/5.5/events). 

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about what has changed recently.

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email me@kakirigi.com instead of using the issue tracker.

## Credits

- [Yamamoto Kadir](https://github.com/yamakadi)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
