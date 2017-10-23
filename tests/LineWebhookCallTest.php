<?php

namespace Yamakadi\LineWebhooks\Tests;

use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use LINE\LINEBot\Event\BaseEvent;
use Yamakadi\LineWebhooks\LineWebhookCall;

class LineWebhookCallTest extends TestCase
{
    /** @var \Yamakadi\LineWebhooks\LineWebhookCall */
    public $lineWebhookCall;

    public function setUp()
    {
        parent::setUp();

        Bus::fake();

        Event::fake();

        config(['line-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        $this->lineWebhookCall = LineWebhookCall::create([
            'type' => 'my_type',
            'payload' => $this->getFakePayload(),
        ]);
    }

    /** @test */
    public function it_will_fire_off_the_configured_job()
    {
        $this->lineWebhookCall->process(new BaseEvent($this->getFakePayload()));

        Bus::assertDispatched(DummyJob::class, function (DummyJob $job) {
            return $job->lineWebhookCall->id === $this->lineWebhookCall->id;
        });
    }

    /** @test */
    public function it_will_not_dispatch_a_job_for_another_type()
    {
        config(['line-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->lineWebhookCall->process($this->getFakeBaseEvent());

        Bus::assertNotDispatched(DummyJob::class);
    }

    /** @test */
    public function it_will_not_dispatch_jobs_when_no_jobs_are_configured()
    {
        config(['line-webhooks.jobs' => []]);

        $this->lineWebhookCall->process($this->getFakeBaseEvent());

        Bus::assertNotDispatched(DummyJob::class);
    }

    /** @test */
    public function it_will_dispatch_events_even_when_no_corresponding_job_is_configured()
    {
        config(['line-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->lineWebhookCall->process($this->getFakeBaseEvent());

        $webhookCall = $this->lineWebhookCall;

        Event::assertDispatched("line-webhooks::{$webhookCall->type}", function ($event, BaseEvent $baseEvent, $eventPayload) use ($webhookCall) {
            if (! $eventPayload instanceof LineWebhookCall) {
                return false;
            }

            return $eventPayload->id === $webhookCall->id;
        });
    }

    /** @test */
    public function it_can_save_an_exception()
    {
        $this->lineWebhookCall->saveException(new Exception('my message', 123));

        $this->assertEquals(123, $this->lineWebhookCall->exception['code']);
        $this->assertEquals('my message', $this->lineWebhookCall->exception['message']);
        $this->assertGreaterThan(200, strlen($this->lineWebhookCall->exception['trace']));
    }

    /** @test */
    public function processing_a_webhook_will_clear_the_exception()
    {
        $this->lineWebhookCall->saveException(new Exception('my message', 123));

        $this->lineWebhookCall->process($this->getFakeBaseEvent());

        $this->assertNull($this->lineWebhookCall->exception);
    }
}
