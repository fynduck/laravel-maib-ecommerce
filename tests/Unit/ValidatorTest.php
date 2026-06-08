<?php

use Fynduck\MaibEcommerce\Exceptions\PaymentException;
use Fynduck\MaibEcommerce\Support\Validator;

it('passes a fully valid payment payload', function () {
    Validator::validateParams([
        'amount' => 10.25,
        'currency' => 'MDL',
        'clientIp' => '135.250.245.121',
        'language' => 'en',
        'description' => 'Order',
        'email' => 'a@b.com',
        'orderId' => '123',
        'delivery' => 1.25,
        'items' => [['id' => '10', 'name' => 'P', 'price' => 2.5, 'quantity' => 2]],
        'callbackUrl' => 'https://example.com/cb',
    ]);

    expect(true)->toBeTrue();
});

it('requires listed keys', function () {
    Validator::requireKeys(['amount' => 1], ['amount', 'currency']);
})->throws(PaymentException::class, "Missing required parameter: 'currency'");

dataset('invalid params', [
    'amount below 1' => [['amount' => 0.5]],
    'amount as string' => [['amount' => '10']],
    'bad currency' => [['currency' => 'GBP']],
    'bad ip' => [['clientIp' => 'not-an-ip']],
    'bad email' => [['email' => 'nope']],
    'bad url' => [['callbackUrl' => 'not-a-url']],
    'billerExpiry wrong length' => [['billerExpiry' => '12']],
    'billerId not uuid' => [['billerId' => 'short']],
    'refundAmount too small' => [['refundAmount' => 0.001]],
    'empty items' => [['items' => []]],
    'description too long' => [['description' => str_repeat('x', 125)]],
]);

it('rejects invalid params', function (array $data) {
    Validator::validateParams($data);
})->with('invalid params')->throws(PaymentException::class);

it('validates a 36-char uuid', function () {
    Validator::assertUuid(str_repeat('a', 36), 'id');
    expect(true)->toBeTrue();

    Validator::assertUuid('too-short', 'id');
})->throws(PaymentException::class, "Invalid 'id'");
