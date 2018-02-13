<?php

namespace Yamakadi\LineWebhooks\Tests;

use Yamakadi\LineBot\Events\Event;
use Yamakadi\LineWebhooks\LineWebhookCall;

class DummyJob
{
    /** @var \Yamakadi\LineBot\Events\Event */
    public $event;

    /** @var \Yamakadi\LineWebhooks\LineWebhookCall */
    public $lineWebhookCall;

    public function __construct(Event $event, LineWebhookCall $lineWebhookCall)
    {
        $this->event = $event;
        $this->lineWebhookCall = $lineWebhookCall;
    }

    public function handle()
    {
    }
}
