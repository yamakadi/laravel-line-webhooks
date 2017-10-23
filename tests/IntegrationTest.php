<?php

namespace Yamakadi\LineWebhooks\Tests;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use LINE\LINEBot\Event\BaseEvent;
use Yamakadi\LineWebhooks\LineWebhookCall;

class IntegrationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Event::fake();

        Bus::fake();

        Route::lineWebhooks('line-webhooks');

        config(['line-webhooks.jobs' => ['text_message' => DummyJob::class]]);
    }

    /** @test */
    public function it_can_handle_a_valid_request()
    {
        $payload = $this->getFakePayload();

        $headers = ['X_LINE_SIGNATURE' => $this->determineLineSignature($payload)];

        $this
            ->postJson('line-webhooks', $payload, $headers)
            ->assertSuccessful();

        $this->assertCount(1, LineWebhookCall::get());

        $webhookCall = LineWebhookCall::first();

        $this->assertEquals('text_message', $webhookCall->type);
        $this->assertEquals($payload, json_decode($webhookCall->payload, true));
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('line-webhooks::text_message', function ($event, BaseEvent $baseEvent, LineWebhookCall $eventPayload) use ($webhookCall) {
            return $eventPayload->id === $webhookCall->id;
        });

        Bus::assertDispatched(DummyJob::class, function (DummyJob $job) use ($webhookCall) {
            return $job->lineWebhookCall->id === $webhookCall->id;
        });
    }

    /** @test */
    public function a_request_with_an_invalid_signature_wont_be_logged()
    {
        $payload = $this->getFakePayload();

        $headers = ['X_LINE_SIGNATURE' => 'invalid_signature'];

        $this
            ->postJson('line-webhooks', $payload, $headers)
            ->assertStatus(400);

        $this->assertCount(0, LineWebhookCall::get());

        Event::assertNotDispatched('line-webhooks::text_message');

        Bus::assertNotDispatched(DummyJob::class);
    }

    /** @test */
    public function a_request_with_an_invalid_payload_will_be_logged_but_events_and_jobs_will_not_be_dispatched()
    {
        $payload = ['invalid_payload'];

        $headers = ['X_LINE_SIGNATURE' => $this->determineLineSignature($payload)];

        $this
            ->postJson('line-webhooks', $payload, $headers)
            ->assertStatus(400);

        $this->assertCount(1, LineWebhookCall::get());

        $webhookCall = LineWebhookCall::first();

        $this->assertEquals('invalid_event', $webhookCall->type);
        $this->assertEquals(['invalid_payload'], json_decode($webhookCall->payload, true));
        $this->assertEquals('Webhook call did not contain a valid payload.', $webhookCall->exception['message']);

        Event::assertNotDispatched('line-webhooks::text_message');

        Bus::assertNotDispatched(DummyJob::class);
    }
}
