<?php

namespace Fynduck\MaibEcommerce\Data;

use Fynduck\MaibEcommerce\Enums\Currency;
use Fynduck\MaibEcommerce\Enums\PaymentStatus;

/**
 * Unified result object for every payment-related endpoint. Each endpoint
 * populates the subset of fields it returns; the rest stay null. The full
 * decoded "result" payload is always available via $raw / toArray().
 */
readonly class PaymentResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public ?string $payId,
        public ?string $payUrl,
        public ?string $orderId,
        public ?string $billerId,
        public ?string $status,
        public ?string $statusMessage,
        public ?string $statusCode,
        public ?float $amount,
        public ?string $currency,
        public ?float $refundAmount,
        public ?string $cardNumber,
        public ?string $rrn,
        public ?string $approval,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $result
     */
    public static function fromResult(array $result): self
    {
        return new self(
            payId: self::str($result, 'payId'),
            payUrl: self::str($result, 'payUrl'),
            orderId: self::str($result, 'orderId'),
            billerId: self::str($result, 'billerId'),
            status: self::str($result, 'status'),
            statusMessage: self::str($result, 'statusMessage'),
            statusCode: self::str($result, 'statusCode'),
            amount: self::num($result, 'amount'),
            currency: self::str($result, 'currency'),
            refundAmount: self::num($result, 'refundAmount'),
            cardNumber: self::str($result, 'cardNumber'),
            rrn: self::str($result, 'rrn'),
            approval: self::str($result, 'approval'),
            raw: $result,
        );
    }

    public function statusEnum(): ?PaymentStatus
    {
        return $this->status !== null ? PaymentStatus::tryFrom($this->status) : null;
    }

    public function currencyEnum(): ?Currency
    {
        return $this->currency !== null ? Currency::tryFrom($this->currency) : null;
    }

    public function isSuccessful(): bool
    {
        return $this->statusEnum()?->isSuccessful() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private static function str(array $result, string $key): ?string
    {
        return isset($result[$key]) ? (string) $result[$key] : null;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private static function num(array $result, string $key): ?float
    {
        return isset($result[$key]) && is_numeric($result[$key]) ? (float) $result[$key] : null;
    }
}
