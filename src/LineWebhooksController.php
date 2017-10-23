<?php

namespace Yamakadi\LineWebhooks;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Routing\Controller;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Exception\InvalidSignatureException;
use Yamakadi\LineWebhooks\Exceptions\WebhookFailed;
use Yamakadi\LineWebhooks\Middlewares\VerifySignature;

class LineWebhooksController extends Controller
{
    public function __construct()
    {
        $this->middleware(VerifySignature::class);
    }

    public function __invoke(Request $request, LINEBot $line)
    {
        $modelClass = config('line-webhooks.model');

        $payload = $request->getContent();
        $signature = $request->header(HTTPHeader::LINE_SIGNATURE);

        try {
            $events = $line->parseEventRequest($payload, $signature);
        } catch (InvalidSignatureException $e) {
            throw WebhookFailed::invalidSignature($signature);
        } catch (InvalidEventRequestException $e) {
            $lineWebhookCall = $modelClass::create([
                'type' => 'invalid_event',
                'signature' => $signature,
                'payload' => $payload,
            ]);

            $lineWebhookCall->saveException($exception = WebhookFailed::invalidPayload($payload));

            throw $exception;
        }


        foreach ($events as $event) {
            $lineWebhookCall = $modelClass::create([
                'type' => snake_case(class_basename($event)),
                'signature' => $signature,
                'payload' => $payload,
            ]);

            try {
                $lineWebhookCall->process($event);
            } catch (Exception $exception) {
                $lineWebhookCall->saveException($exception);

                throw $exception;
            }
        }

    }
}
