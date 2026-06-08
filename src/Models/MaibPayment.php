<?php

namespace Fynduck\MaibEcommerce\Models;

use Fynduck\MaibEcommerce\Data\CallbackResult;
use Fynduck\MaibEcommerce\Enums\Currency;
use Fynduck\MaibEcommerce\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Optional record of a maib payment. Only used when `maib.store_payments` is
 * enabled and the published migration has been run.
 *
 * @property string $pay_id
 * @property ?string $order_id
 * @property ?string $biller_id
 * @property ?PaymentStatus $status
 * @property ?string $status_message
 * @property ?float $amount
 * @property ?Currency $currency
 * @property ?string $card_number
 * @property ?string $rrn
 * @property ?string $approval
 * @property array<string, mixed> $raw
 */
class MaibPayment extends Model
{
    protected $table = 'maib_payments';

    protected $guarded = [];

    protected $casts = [
        'status' => PaymentStatus::class,
        'currency' => Currency::class,
        'amount' => 'float',
        'raw' => 'array',
    ];

    public static function updateFromCallback(CallbackResult $callback): ?self
    {
        $payment = $callback->payment;

        if ($payment->payId === null) {
            return null;
        }

        return static::query()->updateOrCreate(
            ['pay_id' => $payment->payId],
            [
                'order_id' => $payment->orderId,
                'biller_id' => $payment->billerId,
                'status' => $payment->statusEnum(),
                'status_message' => $payment->statusMessage,
                'amount' => $payment->amount,
                'currency' => $payment->currencyEnum(),
                'card_number' => $payment->cardNumber,
                'rrn' => $payment->rrn,
                'approval' => $payment->approval,
                'raw' => $payment->toArray(),
            ],
        );
    }
}
