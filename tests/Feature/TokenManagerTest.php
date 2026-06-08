<?php

use Fynduck\MaibEcommerce\Auth\TokenManager;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    // accessToken expires in 100s, refresh token in 7200s.
    // The returned accessToken differs depending on the auth method used.
    Http::fake(static function ($request) {
        $body = $request->data();
        $accessToken = isset($body['refreshToken']) ? 'AT-refreshed' : 'AT-new';

        return Http::response([
            'ok'     => true,
            'result' => [
                'accessToken'      => $accessToken,
                'refreshToken'     => 'RT1',
                'expiresIn'        => 100,
                'refreshExpiresIn' => 7200,
            ],
        ]);
    });
});

it('generates once and caches the token', function (): void {
    $tm = app(TokenManager::class);

    expect($tm->accessToken())->toBe('AT-new')
        ->and($tm->accessToken())->toBe('AT-new');

    Http::assertSentCount(1);
});

it('refreshes using the refresh token when the access token expires', function (): void {
    $tm = app(TokenManager::class);
    expect($tm->accessToken())->toBe('AT-new');

    $this->travel(120)->seconds();

    expect($tm->accessToken())->toBe('AT-refreshed');

    Http::assertSent(static fn ($request) => isset($request->data()['refreshToken']));
});

it('re-authenticates with credentials when the refresh token also expires', function (): void {
    $tm = app(TokenManager::class);
    expect($tm->accessToken())->toBe('AT-new');

    $this->travel(8000)->seconds();

    expect($tm->accessToken())->toBe('AT-new');

    Http::assertSent(static fn ($request) => isset($request->data()['projectId'])
        && $request->data()['projectId'] === 'test-project-id');
});
