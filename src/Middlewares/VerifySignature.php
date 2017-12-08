<?php

namespace Yamakadi\LineWebhooks\Middlewares;

use Closure;
use Yamakadi\LineBot\LineBot;
use Yamakadi\LineBot\VerifiesSignature;
use Yamakadi\LineWebhooks\Exceptions\WebhookFailed;

class VerifySignature
{
    use VerifiesSignature;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     * @throws \Yamakadi\LineWebhooks\Exceptions\WebhookFailed
     */
    public function handle($request, Closure $next)
    {
        $signature = $request->header(LineBot::LINE_SIGNATURE);

        if (!$signature) {
            throw WebhookFailed::missingSignature();
        }

        if (!$this->isValid($signature, $request->getContent())) {
            throw WebhookFailed::invalidSignature($signature);
        }

        return $next($request);
    }

    /**
     * Check whether the request signature is valid
     *
     * @param string $signature
     * @param string $payload
     *
     * @return bool
     * @throws \Yamakadi\LineWebhooks\Exceptions\WebhookFailed
     */
    protected function isValid(string $signature, string $payload): bool
    {
        $secret = config('line-webhooks.channel_secret');

        if (empty($secret)) {
            throw WebhookFailed::channelSecretNotSet();
        }

        return $this->verifySignature($secret, $signature, $payload);
    }
}
