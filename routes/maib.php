<?php

use Fynduck\MaibEcommerce\Http\Controllers\CallbackController;
use Fynduck\MaibEcommerce\Middleware\VerifyMaibSignature;
use Illuminate\Support\Facades\Route;

$config = config('maib.callback');

if (($config['enabled'] ?? false) === true) {
    Route::post($config['route'], CallbackController::class)
        ->middleware(array_merge((array) ($config['middleware'] ?? []), [VerifyMaibSignature::class]))
        ->name($config['name'] ?? 'maib.callback');
}
