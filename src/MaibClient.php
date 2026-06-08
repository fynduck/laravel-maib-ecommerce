<?php

namespace Fynduck\MaibEcommerce;

use Fynduck\MaibEcommerce\Auth\TokenManager;
use Fynduck\MaibEcommerce\Contracts\MaibClientContract;
use Fynduck\MaibEcommerce\Data\PaymentResult;
use Fynduck\MaibEcommerce\Http\Gateway;
use Fynduck\MaibEcommerce\Support\Validator;

class MaibClient implements MaibClientContract
{
    public function __construct(
        protected Gateway $gateway,
        protected TokenManager $tokens,
    ) {}

    /**
     * Direct payment. Requires amount, currency, clientIp.
     *
     * @param  array<string, mixed>  $data
     */
    public function pay(array $data): PaymentResult
    {
        return $this->postPayment('pay', $data, ['amount', 'currency', 'clientIp']);
    }

    /**
     * Two-step payment authorization (hold). Requires amount, currency, clientIp.
     *
     * @param  array<string, mixed>  $data
     */
    public function hold(array $data): PaymentResult
    {
        return $this->postPayment('hold', $data, ['amount', 'currency', 'clientIp']);
    }

    /**
     * Capture a previously held payment. Requires payId.
     *
     * @param  array<string, mixed>  $data
     */
    public function complete(array $data): PaymentResult
    {
        return $this->postPayment('complete', $data, ['payId']);
    }

    /**
     * Refund a payment. Requires payId.
     *
     * @param  array<string, mixed>  $data
     */
    public function refund(array $data): PaymentResult
    {
        return $this->postPayment('refund', $data, ['payId']);
    }

    /**
     * Retrieve payment information by payId (36-char UUID).
     */
    public function payInfo(string $payId): PaymentResult
    {
        Validator::assertUuid($payId, 'id');

        return PaymentResult::fromResult(
            $this->gateway->get('pay-info', ['id' => $payId], $this->tokens->accessToken())
        );
    }

    /**
     * Register a card for recurring payments. Requires billerExpiry, currency, clientIp.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveRecurring(array $data): PaymentResult
    {
        return $this->postPayment('savecard-recurring', $data, ['billerExpiry', 'currency', 'clientIp']);
    }

    /**
     * Charge a previously registered recurring card. Requires billerId, amount, currency.
     *
     * @param  array<string, mixed>  $data
     */
    public function executeRecurring(array $data): PaymentResult
    {
        return $this->postPayment('execute-recurring', $data, ['billerId', 'amount', 'currency']);
    }

    /**
     * Register a card for one-click payments. Requires billerExpiry, currency, clientIp.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveOneclick(array $data): PaymentResult
    {
        return $this->postPayment('savecard-oneclick', $data, ['billerExpiry', 'currency', 'clientIp']);
    }

    /**
     * Charge a previously registered one-click card. Requires billerId, amount, currency, clientIp.
     *
     * @param  array<string, mixed>  $data
     */
    public function executeOneclick(array $data): PaymentResult
    {
        return $this->postPayment('execute-oneclick', $data, ['billerId', 'amount', 'currency', 'clientIp']);
    }

    /**
     * Delete a registered card by billerId (36-char UUID).
     */
    public function deleteCard(string $billerId): PaymentResult
    {
        Validator::assertUuid($billerId, 'id');

        return PaymentResult::fromResult(
            $this->gateway->delete('delete-card', ['id' => $billerId], $this->tokens->accessToken())
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $required
     */
    protected function postPayment(string $endpoint, array $data, array $required): PaymentResult
    {
        Validator::requireKeys($data, $required);
        Validator::validateParams($data);

        return PaymentResult::fromResult(
            $this->gateway->post($endpoint, $data, $this->tokens->accessToken())
        );
    }
}
