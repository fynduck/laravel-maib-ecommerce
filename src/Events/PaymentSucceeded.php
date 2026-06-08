<?php

namespace Fynduck\MaibEcommerce\Events;

use Fynduck\MaibEcommerce\Data\CallbackResult;
use Illuminate\Foundation\Events\Dispatchable;

class PaymentSucceeded
{
    use Dispatchable;

    public function __construct(
        public readonly CallbackResult $callback,
    ) {}
}
