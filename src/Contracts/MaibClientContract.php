<?php

namespace Fynduck\MaibEcommerce\Contracts;

use Fynduck\MaibEcommerce\Data\PaymentResult;

interface MaibClientContract
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function pay(array $data): PaymentResult;

    /**
     * @param  array<string, mixed>  $data
     */
    public function hold(array $data): PaymentResult;

    /**
     * @param  array<string, mixed>  $data
     */
    public function complete(array $data): PaymentResult;

    /**
     * @param  array<string, mixed>  $data
     */
    public function refund(array $data): PaymentResult;

    public function payInfo(string $payId): PaymentResult;

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveRecurring(array $data): PaymentResult;

    /**
     * @param  array<string, mixed>  $data
     */
    public function executeRecurring(array $data): PaymentResult;

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveOneclick(array $data): PaymentResult;

    /**
     * @param  array<string, mixed>  $data
     */
    public function executeOneclick(array $data): PaymentResult;

    public function deleteCard(string $billerId): PaymentResult;
}
