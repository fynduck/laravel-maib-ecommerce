<?php

namespace Fynduck\MaibEcommerce\Http\Controllers;

use Fynduck\MaibEcommerce\Data\CallbackResult;
use Fynduck\MaibEcommerce\Events\PaymentCallbackReceived;
use Fynduck\MaibEcommerce\Events\PaymentFailed;
use Fynduck\MaibEcommerce\Events\PaymentSucceeded;
use Fynduck\MaibEcommerce\Models\MaibPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles the verified maib callback (signature is checked by middleware first).
 *
 * Dispatches a generic PaymentCallbackReceived event plus a status-specific
 * PaymentSucceeded / PaymentFailed event, and optionally records the payment.
 */
class CallbackController
{
    public function __invoke(Request $request): JsonResponse
    {
        $callback = CallbackResult::fromPayload($request->all());

        if (config('maib.store_payments')) {
            MaibPayment::updateFromCallback($callback);
        }

        PaymentCallbackReceived::dispatch($callback);

        if ($callback->isSuccessful()) {
            PaymentSucceeded::dispatch($callback);
        } elseif ($callback->status()?->isFailed() ?? false) {
            PaymentFailed::dispatch($callback);
        }

        return response()->json(['ok' => true]);
    }
}
