<?php

use Fynduck\MaibEcommerce\Contracts\MaibClientContract;
use Fynduck\MaibEcommerce\Facades\Maib;
use Fynduck\MaibEcommerce\MaibClient;

it('binds the client into the container', function (): void {
    expect(app('maib'))->toBeInstanceOf(MaibClient::class)
        ->and(app(MaibClientContract::class))->toBeInstanceOf(MaibClient::class);
});

it('resolves the facade root to the client', function (): void {
    expect(Maib::getFacadeRoot())->toBeInstanceOf(MaibClient::class);
});

it('registers the callback route', function (): void {
    expect(route('maib.callback'))->toEndWith('/maib/callback');
});
