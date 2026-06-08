<?php

use Fynduck\MaibEcommerce\Events\PaymentCallbackReceived;
use Fynduck\MaibEcommerce\Events\PaymentFailed;
use Fynduck\MaibEcommerce\Events\PaymentSucceeded;
use Fynduck\MaibEcommerce\Models\MaibPayment;
use Fynduck\MaibEcommerce\Support\Signature;
use Illuminate\Support\Facades\Event;

const KEY = 'test-signature-key';

function signedPayload(array $result): array
{
    return ['result' => $result, 'signature' => Signature::compute($result, KEY)];
}

it('accepts a valid signature and dispatches events', function () {
    Event::fake();

    $payload = signedPayload(['payId' => 'PID', 'orderId' => '123', 'status' => 'OK', 'amount' => '10.25']);

    $this->postJson(route('maib.callback'), $payload)
        ->assertOk()
        ->assertJson(['ok' => true]);

    Event::assertDispatched(PaymentCallbackReceived::class, fn ($e) => $e->callback->payId() === 'PID');
    Event::assertDispatched(PaymentSucceeded::class);
    Event::assertNotDispatched(PaymentFailed::class);
});

it('rejects an invalid signature with 403', function () {
    Event::fake();

    $this->postJson(route('maib.callback'), [
        'result' => ['payId' => 'PID', 'status' => 'OK'],
        'signature' => 'tampered',
    ])->assertForbidden();

    Event::assertNotDispatched(PaymentCallbackReceived::class);
});

it('dispatches PaymentFailed for a failed status', function () {
    Event::fake();

    $this->postJson(route('maib.callback'), signedPayload(['payId' => 'PID', 'status' => 'FAILED']))
        ->assertOk();

    Event::assertDispatched(PaymentFailed::class);
    Event::assertNotDispatched(PaymentSucceeded::class);
});

it('persists the payment when store_payments is enabled', function () {
    config()->set('maib.store_payments', true);

    $payload = signedPayload([
        'payId' => 'PID-1', 'orderId' => '999', 'status' => 'OK',
        'amount' => '12.50', 'currency' => 'MDL',
    ]);

    $this->postJson(route('maib.callback'), $payload)->assertOk();

    expect(MaibPayment::query()->where('pay_id', 'PID-1')->first())
        ->not->toBeNull()
        ->order_id->toBe('999')
        ->amount->toBe(12.5);
});

it('does not persist when store_payments is disabled', function () {
    config()->set('maib.store_payments', false);

    $this->postJson(route('maib.callback'), signedPayload(['payId' => 'PID-2', 'status' => 'OK']))->assertOk();

    expect(MaibPayment::query()->count())->toBe(0);
});
