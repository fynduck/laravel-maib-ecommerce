<?php

use Fynduck\MaibEcommerce\Support\Signature;

it('computes the signature using sorted keys, appended key, and base64 sha256', function (): void {
    $result = [
        'payId'    => 'abc',
        'orderId'  => '123',
        'status'   => 'OK',
        'amount'   => '10.25',
        'currency' => 'MDL',
    ];
    $key = 'test-signature-key';

    // Independently build the expected string: keys sorted (SORT_STRING),
    // signature key appended, joined by ":", then base64(sha256(raw)).
    $expectedString = '10.25:MDL:123:abc:OK:' . $key;
    $expected = base64_encode(hash('sha256', $expectedString, true));

    expect(Signature::compute($result, $key))->toBe($expected);
});

it('verifies a valid signature and rejects a tampered one', function (): void {
    $result = ['payId' => 'abc', 'status' => 'OK'];
    $key = 'secret';
    $valid = Signature::compute($result, $key);

    expect(Signature::verify($result, $valid, $key))->toBeTrue()
        ->and(Signature::verify($result, $valid . 'x', $key))->toBeFalse()
        ->and(Signature::verify(['payId' => 'abc', 'status' => 'FAILED'], $valid, $key))->toBeFalse();
});

it('sorts nested arrays recursively', function (): void {
    $result = [
        'status' => 'OK',
        'items'  => [
            ['name' => 'Product', 'id' => '10', 'price' => '2.50'],
        ],
    ];
    $key = 'k';

    // Outer sorted: items, status. items[0] sorted: id, name, price.
    $expectedString = '10:Product:2.50:OK:' . $key;
    $expected = base64_encode(hash('sha256', $expectedString, true));

    expect(Signature::compute($result, $key))->toBe($expected);
});
