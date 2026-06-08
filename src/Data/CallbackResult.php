<?php

namespace Fynduck\MaibEcommerce\Data;

use Fynduck\MaibEcommerce\Enums\PaymentStatus;

/**
 * Represents a verified callback notification received on the callback URL.
 */
readonly class CallbackResult
{
    /**
     * @param  array<string, mixed>  $result
     */
    public function __construct(
        public PaymentResult $payment,
        public array $result,
        public string $signature,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  The full decoded callback body.
     */
    public static function fromPayload(array $payload): self
    {
        $result = is_array($payload['result'] ?? null) ? $payload['result'] : [];

        return new self(
            payment: PaymentResult::fromResult($result),
            result: $result,
            signature: (string) ($payload['signature'] ?? ''),
        );
    }

    public function payId(): ?string
    {
        return $this->payment->payId;
    }

    public function status(): ?PaymentStatus
    {
        return $this->payment->statusEnum();
    }

    public function isSuccessful(): bool
    {
        return $this->payment->isSuccessful();
    }
}
