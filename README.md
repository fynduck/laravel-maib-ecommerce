# Laravel maib e-Commerce

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/fynduck/laravel-maib-ecommerce.svg?style=flat-square)](https://packagist.org/packages/laravel-maib-ecommerce)
![Tests](https://github.com/fynduck/laravel-maib-ecommerce/actions/workflows/FileUpload.yml/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/fynduck/laravel-maib-ecommerce.svg?style=flat-square)](https://packagist.org/packages/laravel-maib-ecommerce)

A Laravel package for the [maib](https://www.maib.md/) (Moldova Agroindbank) e-Commerce
payment gateway. It implements the [maibmerchants e-Commerce API](https://docs.maibmerchants.md)
natively in Laravel — HTTP client, container bindings, a facade, config, events, signature
verification and an optional payments table.

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

Only `amount`, `currency` and `clientIp` are required; everything else is optional. You can
pass the `Currency` and `Language` enums instead of raw strings to avoid mistyping — the
client converts them automatically:

```php
use Fynduck\MaibEcommerce\Facades\Maib;
use Fynduck\MaibEcommerce\Enums\Currency;
use Fynduck\MaibEcommerce\Enums\Language;

$payment = Maib::pay([
    // required
    'amount' => 10.25,
    'currency' => Currency::MDL,        // or 'MDL'
    'clientIp' => $request->ip(),
    'language' => Language::EN,          // or 'en' (ro | ru | en)

    // optional
    'description' => 'Order #123',       // max 124 chars
    'clientName' => 'John Doe',          // max 128 chars
    'email' => 'john@example.com',
    'phone' => '069123456',              // max 40 chars
    'orderId' => '123',                  // max 36 chars
    'delivery' => 1.25,                  // delivery price
    'items' => [
        ['id' => 'SKU-1', 'name' => 'Product 1', 'price' => 2.50, 'quantity' => 2],
        ['id' => 'SKU-2', 'name' => 'Product 2', 'price' => 4.00, 'quantity' => 1],
    ],
    'callbackUrl' => route('maib.callback'),
    'okUrl' => route('checkout.success'),
    'failUrl' => route('checkout.failed'),
]);

// Persist $payment->payId, then redirect the customer to the maib checkout page:
return redirect()->away($payment->payUrl);
```

The same optional keys (`description`, `clientName`, `email`, `phone`, `orderId`,
`delivery`, `items`, `okUrl`, `failUrl`, `callbackUrl`) apply to `hold()`, `saveRecurring()`
and `saveOneclick()`. For the authoritative, always-current list of every parameter and its
constraints, see the **[maib e-Commerce API docs](https://docs.maibmerchants.md/e-commerce/maib-e-commerce-api)**.

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

Each event carries a `CallbackResult` (`$event->callback`) with a typed `PaymentResult`
(`$event->callback->payment`) and the raw verified `result` array.

### Registering listeners

Map a listener to the event in your `App\Providers\EventServiceProvider`:

```php
namespace App\Providers;

use App\Listeners\MarkOrderAsPaid;
use Fynduck\MaibEcommerce\Events\PaymentFailed;
use Fynduck\MaibEcommerce\Events\PaymentSucceeded;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentSucceeded::class => [
            MarkOrderAsPaid::class,
        ],
        PaymentFailed::class => [
            // App\Listeners\NotifyCustomerOfFailure::class,
        ],
    ];
}
```

> On Laravel 11+ the `EventServiceProvider` is no longer in the default skeleton.
> You can still create it (and register it in `bootstrap/providers.php`), or just bind
> the listener in `AppServiceProvider::boot()`:
> `Event::listen(PaymentSucceeded::class, MarkOrderAsPaid::class);`

### Run listeners on the queue (recommended)

The callback request comes from maib and is **waiting on your HTTP response** — and maib
may **retry** the notification if you are slow or error out. Do not do slow work (DB writes,
sending mail, calling other APIs) inline in the listener: that blocks the response and risks
duplicate callbacks. Instead make the listener queued by implementing `ShouldQueue`, so the
controller returns `200` immediately and the work runs on a worker:

```php
namespace App\Listeners;

use Fynduck\MaibEcommerce\Events\PaymentSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;

class MarkOrderAsPaid implements ShouldQueue
{
    public string $queue = 'payments';

    public function handle(PaymentSucceeded $event): void
    {
        $payId = $event->callback->payId();
        // mark your order as paid, notify the customer, etc.
    }
}
```

Make sure a queue worker is running (`php artisan queue:work`). Because callbacks can be
re-delivered, keep listeners **idempotent** — key off `payId` so processing the same
notification twice is harmless.

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
