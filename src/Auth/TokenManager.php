<?php

namespace Fynduck\MaibEcommerce\Auth;

use Fynduck\MaibEcommerce\Data\TokenResult;
use Fynduck\MaibEcommerce\Exceptions\TokenException;
use Fynduck\MaibEcommerce\Http\Gateway;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Obtains and transparently refreshes the maib access token.
 *
 * The token (with its access/refresh expiry timestamps) is cached so a single
 * token is shared across requests and refreshed only when it is about to
 * expire. Callers never deal with tokens directly.
 */
class TokenManager
{
    private const ENDPOINT = 'generate-token';

    public function __construct(
        protected Gateway $gateway,
        protected CacheRepository $cache,
        protected string $projectId,
        protected string $projectSecret,
        protected string $cacheKey = 'maib_token',
        protected int $ttlLeeway = 30,
    ) {}

    /**
     * Return a valid access token, generating or refreshing as needed.
     *
     * @throws TokenException
     */
    public function accessToken(): string
    {
        $state = $this->cache->get($this->cacheKey);

        if (is_array($state) && $this->isFresh($state['accessExpiresAt'] ?? 0)) {
            return $state['accessToken'];
        }

        if (is_array($state)
            && ! empty($state['refreshToken'])
            && $this->isFresh($state['refreshExpiresAt'] ?? 0)) {
            return $this->store($this->request(['refreshToken' => $state['refreshToken']]));
        }

        return $this->store($this->request([
            'projectId' => $this->projectId,
            'projectSecret' => $this->projectSecret,
        ]));
    }

    /**
     * Forget the cached token, forcing a fresh generation on next use.
     */
    public function forget(): void
    {
        $this->cache->forget($this->cacheKey);
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws TokenException
     */
    protected function request(array $payload): TokenResult
    {
        $result = $this->gateway->post(self::ENDPOINT, $payload);

        if (empty($result['accessToken'])) {
            throw new TokenException('maib did not return an access token.');
        }

        return TokenResult::fromResult($result);
    }

    protected function store(TokenResult $token): string
    {
        $now = now()->getTimestamp();

        $state = [
            'accessToken' => $token->accessToken,
            'accessExpiresAt' => $now + $token->expiresIn,
            'refreshToken' => $token->refreshToken,
            'refreshExpiresAt' => $now + ($token->refreshExpiresIn ?? 0),
        ];

        // Keep the cache entry alive as long as the refresh token may be useful.
        $ttl = max($token->expiresIn, $token->refreshExpiresIn ?? 0) + 60;
        $this->cache->put($this->cacheKey, $state, $ttl);

        return $token->accessToken;
    }

    protected function isFresh(int $expiresAt): bool
    {
        return $expiresAt - $this->ttlLeeway > now()->getTimestamp();
    }
}
