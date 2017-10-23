<?php

namespace Yamakadi\LineWebhooks\Tests\Middlewares;

use Illuminate\Support\Facades\Route;
use Yamakadi\LineWebhooks\Tests\TestCase;
use Yamakadi\LineWebhooks\Middlewares\VerifySignature;

class VerifySignatureTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Route::post('line-webhooks', function () {
            return 'ok';
        })->middleware(VerifySignature::class);
    }

    /** @test */
    public function it_will_succeed_when_the_request_has_a_valid_signature()
    {
        $payload = $this->getFakePayload();

        $response = $this->postJson(
            'line-webhooks',
            $payload,
            ['X_LINE_SIGNATURE' => $this->determineLineSignature($payload)]
        );

        $response
            ->assertStatus(200)
            ->assertSee('ok');
    }

    /** @test */
    public function it_will_fail_when_the_signature_header_is_not_set()
    {
        $response = $this->postJson(
            'line-webhooks',
            $this->getFakePayload()
        );

        $response
            ->assertStatus(400)
            ->assertJson([
                'error' => 'The request did not contain a header named `X_LINE_SIGNATURE`.',
            ]);
    }

    /** @test */
    public function it_will_fail_when_the_signing_secret_is_not_set()
    {
        config(['line-webhooks.channel_secret' => '']);

        $response = $this->postJson(
            'line-webhooks',
            $this->getFakePayload(),
            ['X_LINE_SIGNATURE' => 'abc']
        );

        $response
            ->assertStatus(400)
            ->assertSee('The Line webhook channel secret is not set.');
    }

    /** @test */
    public function it_will_fail_when_the_signature_is_invalid()
    {
        $response = $this->postJson(
            'line-webhooks',
            $this->getFakePayload(),
            ['X_LINE_SIGNATURE' => 'abc']
        );

        $response
            ->assertStatus(400)
            ->assertSee('found in the header named `X_LINE_SIGNATURE` is invalid');
    }
}
