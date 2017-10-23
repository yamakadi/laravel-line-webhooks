<?php

namespace Yamakadi\LineWebhooks\Tests;

use LINE\LINEBot\Event\BaseEvent;
use Yamakadi\LineWebhooks\LineWebhookCall;

class DummyJob
{
    /** @var \LINE\LINEBot\Event\BaseEvent */
    public $event;

    /** @var \Yamakadi\LineWebhooks\LineWebhookCall */
    public $lineWebhookCall;

    public function __construct(BaseEvent $event, LineWebhookCall $lineWebhookCall)
    {
        $this->event = $event;
        $this->lineWebhookCall = $lineWebhookCall;
    }

    public function handle()
    {
    }
}
