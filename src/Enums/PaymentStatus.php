<?php

namespace Fynduck\MaibEcommerce\Enums;

/**
 * Payment statuses returned by the maib e-Commerce API.
 *
 * Unknown / future values are tolerated by callers via tryFrom(), so adding a
 * status here is non-breaking.
 */
enum PaymentStatus: string
{
    case CREATED = 'CREATED';
    case PENDING = 'PENDING';
    case OK = 'OK';
    case FAILED = 'FAILED';
    case REVERSED = 'REVERSED';
    case REFUNDED = 'REFUNDED';

    public function isSuccessful(): bool
    {
        return $this === self::OK;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}
