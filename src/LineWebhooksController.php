<?php

namespace Yamakadi\LineWebhooks;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Yamakadi\LineBot\Exceptions\InvalidRequestException;
use Yamakadi\LineBot\Exceptions\InvalidSignatureException;
use Yamakadi\LineBot\LineBot;
use Yamakadi\LineWebhooks\Exceptions\WebhookFailed;
use Yamakadi\LineWebhooks\Middlewares\VerifySignature;

class LineWebhooksController extends Controller
{
    public function __construct()
    {
        $this->middleware(VerifySignature::class);
    }

    public function __invoke(Request $request, LineBot $line)
    {
        $modelClass = config('line-webhooks.model');

        $payload = $request->getContent();
        $signature = $request->header(LineBot::LINE_SIGNATURE);

        try {
            $events = $line->parse($payload, $signature);
        } catch (InvalidSignatureException $e) {
            throw WebhookFailed::invalidSignature($signature);
        } catch (InvalidRequestException $e) {
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
                'type' => $event::TYPE,
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
