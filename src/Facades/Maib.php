<?php

namespace Fynduck\MaibEcommerce\Facades;

use Fynduck\MaibEcommerce\Contracts\MaibClientContract;
use Fynduck\MaibEcommerce\Data\PaymentResult;
use Fynduck\MaibEcommerce\MaibClient;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PaymentResult pay(array<string, mixed> $data)
 * @method static PaymentResult hold(array<string, mixed> $data)
 * @method static PaymentResult complete(array<string, mixed> $data)
 * @method static PaymentResult refund(array<string, mixed> $data)
 * @method static PaymentResult payInfo(string $payId)
 * @method static PaymentResult saveRecurring(array<string, mixed> $data)
 * @method static PaymentResult executeRecurring(array<string, mixed> $data)
 * @method static PaymentResult saveOneclick(array<string, mixed> $data)
 * @method static PaymentResult executeOneclick(array<string, mixed> $data)
 * @method static PaymentResult deleteCard(string $billerId)
 *
 * @see MaibClient
 */
class Maib extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MaibClientContract::class;
    }
}
