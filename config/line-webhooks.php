<?php

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
        // 'message_event' => \App\Jobs\LineWebhooks\HandleIncomingMessage::class,
        // 'beacon_detection_event' => \App\Jobs\LineWebhooks\HandleBeaconSignal::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Yamakadi\LineWebhooks\LineWebhookCall.
     */
    'model' => Yamakadi\LineWebhooks\LineWebhookCall::class,
];