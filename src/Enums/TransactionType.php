<?php

namespace Fynduck\MaibEcommerce\Enums;

enum TransactionType: string
{
    case DIRECT = 'direct';
    case HOLD = 'hold';
    case COMPLETE = 'complete';
    case REFUND = 'refund';
    case RECURRING = 'recurring';
    case ONECLICK = 'oneclick';
}
