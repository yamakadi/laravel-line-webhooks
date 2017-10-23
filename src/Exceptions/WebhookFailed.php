<?php

namespace Yamakadi\LineWebhooks\Exceptions;

use Exception;
use Yamakadi\LineWebhooks\LineWebhookCall;

class WebhookFailed extends Exception
{
    public static function missingSignature()
    {
        return new static('The request did not contain a header named `X_LINE_SIGNATURE`.');
    }

    public static function invalidSignature($signature)
    {
        return new static("The signature `{$signature}` found in the header named `X_LINE_SIGNATURE` is invalid.");
    }

    public static function channelSecretNotSet()
    {
        return new static('The Line webhook channel secret is not set. Make sure that the `line-webhooks.channel_secret` config key is set to the value you found on the Line dashboard.');
    }

    public static function jobClassDoesNotExist(string $jobClass, LineWebhookCall $webhookCall)
    {
        return new static("Could not process webhook id `{$webhookCall->id}` of type `{$webhookCall->type} because the configured jobclass `$jobClass` does not exist.");
    }

    public static function invalidPayload($payload)
    {
        return new static("Webhook call did not contain a valid payload.");
    }

    public function render($request)
    {
        return response(['error' => $this->getMessage()], 400);
    }
}
