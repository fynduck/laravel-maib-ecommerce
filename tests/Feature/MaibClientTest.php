<?php

use Fynduck\MaibEcommerce\Data\PaymentResult;
use Fynduck\MaibEcommerce\Enums\Currency;
use Fynduck\MaibEcommerce\Enums\Language;
use Fynduck\MaibEcommerce\Exceptions\PaymentException;
use Fynduck\MaibEcommerce\Facades\Maib;
use Illuminate\Support\Facades\Http;

$uuid = str_repeat('a', 36);

function assertSentTo(string $endpoint, string $method): void
{
    Http::assertSent(static fn ($request) => str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/v1/' . $endpoint)
        && $request->method() === $method
        && $request->hasHeader('Authorization', 'Bearer TKN'));
}

describe('with a faked gateway', function () use ($uuid): void {
    beforeEach(function (): void {
        Http::fake([
            '*generate-token' => Http::response([
                'ok'     => true,
                'result' => ['accessToken' => 'TKN', 'expiresIn' => 3600],
            ]),
            '*' => Http::response([
                'ok'     => true,
                'result' => [
                    'payId'    => 'PID', 'payUrl' => 'https://pay.example', 'orderId' => '123',
                    'billerId' => str_repeat('b', 36), 'status' => 'OK', 'amount' => 10.25,
                    'currency' => 'MDL', 'refundAmount' => 10.25,
                ],
            ]),
        ]);
    });

    it('sends a direct payment', function (): void {
        $result = Maib::pay(['amount' => 10.25, 'currency' => 'MDL', 'clientIp' => '1.2.3.4']);

        expect($result)->toBeInstanceOf(PaymentResult::class)
            ->and($result->payUrl)->toBe('https://pay.example')
            ->and($result->payId)->toBe('PID');
        assertSentTo('pay', 'POST');
    });

    it('sends a hold', function (): void {
        Maib::hold(['amount' => 10.25, 'currency' => 'EUR', 'clientIp' => '1.2.3.4']);
        assertSentTo('hold', 'POST');
    });

    it('accepts Currency and Language enums and sends their scalar values', function (): void {
        Maib::pay([
            'amount'   => 10.25,
            'currency' => Currency::MDL,
            'clientIp' => '1.2.3.4',
            'language' => Language::EN,
        ]);

        Http::assertSent(fn ($request) => str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/v1/pay')
            && $request->data()['currency'] === 'MDL'
            && $request->data()['language'] === 'en');
    });

    it('completes a hold', function (): void {
        $result = Maib::complete(['payId' => 'PID']);
        expect($result->status)->toBe('OK');
        assertSentTo('complete', 'POST');
    });

    it('refunds a payment', function (): void {
        $result = Maib::refund(['payId' => 'PID', 'refundAmount' => 10.25]);
        expect($result->refundAmount)->toBe(10.25);
        assertSentTo('refund', 'POST');
    });

    it('fetches payment info via GET with id query', function () use ($uuid): void {
        Maib::payInfo($uuid);
        Http::assertSent(static fn ($request) => str_contains($request->url(), 'pay-info?id=' . $uuid)
            && $request->method() === 'GET');
    });

    it('saves a recurring card', function (): void {
        Maib::saveRecurring(['billerExpiry' => '1230', 'currency' => 'USD', 'clientIp' => '1.2.3.4']);
        assertSentTo('savecard-recurring', 'POST');
    });

    it('executes a recurring payment', function () use ($uuid): void {
        Maib::executeRecurring(['billerId' => $uuid, 'amount' => 6.25, 'currency' => 'USD']);
        assertSentTo('execute-recurring', 'POST');
    });

    it('saves a one-click card', function (): void {
        Maib::saveOneclick(['billerExpiry' => '1230', 'currency' => 'EUR', 'clientIp' => '1.2.3.4']);
        assertSentTo('savecard-oneclick', 'POST');
    });

    it('executes a one-click payment', function () use ($uuid): void {
        Maib::executeOneclick(['billerId' => $uuid, 'amount' => 6.25, 'currency' => 'EUR', 'clientIp' => '1.2.3.4']);
        assertSentTo('execute-oneclick', 'POST');
    });

    it('deletes a card via DELETE with id query', function () use ($uuid): void {
        $result = Maib::deleteCard($uuid);
        expect($result->billerId)->toBe(str_repeat('b', 36));
        Http::assertSent(static fn ($request) => str_contains($request->url(), 'delete-card?id=' . $uuid)
            && $request->method() === 'DELETE');
    });
});

it('throws when required params are missing', function (): void {
    Maib::pay(['amount' => 10.25]);
})->throws(PaymentException::class, "Missing required parameter: 'currency'");

it('throws a PaymentException on an API error envelope', function (): void {
    Http::fake([
        '*generate-token' => Http::response(['ok' => true, 'result' => ['accessToken' => 'TKN', 'expiresIn' => 3600]]),
        '*'               => Http::response(['ok' => false, 'errors' => [['errorMessage' => 'Invalid amount', 'errorCode' => 'E001']]], 400),
    ]);

    Maib::pay(['amount' => 10.25, 'currency' => 'MDL', 'clientIp' => '1.2.3.4']);
})->throws(PaymentException::class, 'Invalid amount');
