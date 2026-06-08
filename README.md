# Laravel maib e-Commerce

A Laravel package for the [maib](https://www.maib.md/) (Moldova Agroindbank) e-Commerce
payment gateway. It implements the [maibmerchants e-Commerce API](https://docs.maibmerchants.md)
natively in Laravel — HTTP client, container bindings, a facade, config, events, signature
verification and an optional payments table.

> This package does **not** depend on `maib-ecomm/maib-sdk-php`. It re-implements the same
> API contract idiomatically for Laravel.

## Requirements

- PHP 8.2+
- Laravel 10, 11, 12 or 13

## Installation

```bash
composer require fynduck/laravel-maib-ecommerce
```

Publish the config:

```bash
php artisan vendor:publish --tag=maib-config
```

Add your project credentials (issued in the maibmerchants.md dashboard after activation) to `.env`:

```dotenv
MAIB_PROJECT_ID=your-project-id
MAIB_PROJECT_SECRET=your-project-secret
MAIB_SIGNATURE_KEY=your-signature-key

# Optional
MAIB_BASE_URL=https://api.maibmerchants.md/v1/
MAIB_CALLBACK_ROUTE=/maib/callback
MAIB_STORE_PAYMENTS=false
```

The access token is generated, cached and refreshed automatically — you never handle tokens.

## Usage

All methods are available on the `Maib` facade and return a `PaymentResult` object.

### Direct payment

```php
use Fynduck\MaibEcommerce\Facades\Maib;

$payment = Maib::pay([
    'amount' => 10.25,
    'currency' => 'MDL',
    'clientIp' => $request->ip(),
    'description' => 'Order #123',
    'orderId' => '123',
    'callbackUrl' => route('maib.callback'),
    'okUrl' => route('checkout.success'),
    'failUrl' => route('checkout.failed'),
]);

// Persist $payment->payId, then redirect the customer to the maib checkout page:
return redirect()->away($payment->payUrl);
```

### Two-step payment (hold + complete)

```php
$hold = Maib::hold(['amount' => 10.25, 'currency' => 'MDL', 'clientIp' => $request->ip()]);
// ...later, capture all or part of the held amount:
$captured = Maib::complete(['payId' => $hold->payId, 'confirmAmount' => 10.25]);
```

### Refund

```php
$refund = Maib::refund(['payId' => $payId, 'refundAmount' => 10.25]);
```

### Payment info

```php
$info = Maib::payInfo($payId); // 36-char payId
$info->statusEnum(); // PaymentStatus enum
```

### Recurring & one-click

```php
// Register a card (redirects to checkout); maib returns a billerId on the callback.
$save = Maib::saveRecurring(['billerExpiry' => '1230', 'currency' => 'MDL', 'clientIp' => $ip]);
return redirect()->away($save->payUrl);

// Later, charge the stored card without customer interaction:
$charge = Maib::executeRecurring(['billerId' => $billerId, 'amount' => 6.25, 'currency' => 'MDL']);

// One-click works the same way:
$save = Maib::saveOneclick(['billerExpiry' => '1230', 'currency' => 'MDL', 'clientIp' => $ip]);
$charge = Maib::executeOneclick(['billerId' => $billerId, 'amount' => 6.25, 'currency' => 'MDL', 'clientIp' => $ip]);

// Remove a stored card:
Maib::deleteCard($billerId);
```

## Callbacks (webhooks)

maib sends a signed notification to your `callbackUrl` after each payment. The package
registers a route (default `POST /maib/callback`, named `maib.callback`) guarded by a
signature-verification middleware. Requests with an invalid signature are rejected with a
`403` before reaching your application.

On a valid callback the package dispatches:

- `Fynduck\MaibEcommerce\Events\PaymentCallbackReceived` — every callback
- `Fynduck\MaibEcommerce\Events\PaymentSucceeded` — when status is `OK`
- `Fynduck\MaibEcommerce\Events\PaymentFailed` — when status is `FAILED`

Listen for them in your app:

```php
use Fynduck\MaibEcommerce\Events\PaymentSucceeded;
use Illuminate\Support\Facades\Event;

Event::listen(function (PaymentSucceeded $event) {
    $payId = $event->callback->payId();
    // mark your order as paid...
});
```

Each event carries a `CallbackResult` (`$event->callback`) with a typed `PaymentResult`
(`$event->callback->payment`) and the raw verified `result` array.

> **Security:** the signature is validated using your `MAIB_SIGNATURE_KEY` with the maib
> algorithm (recursive key sort → append key → `:`-join → `base64(sha256())`) and a
> timing-safe comparison. Never expose the signature key.

## Optional payment persistence

To record every callback in a `maib_payments` table:

```bash
php artisan vendor:publish --tag=maib-migrations
php artisan migrate
```

Then set `MAIB_STORE_PAYMENTS=true`. Incoming callbacks are upserted into the
`Fynduck\MaibEcommerce\Models\MaibPayment` model (keyed by `pay_id`). The core client works
without this table — persistence is entirely opt-in.

## Testing

```bash
composer test      # Pest
composer lint      # Pint (dry run)
composer analyse   # PHPStan / Larastan
```

## License

MIT
