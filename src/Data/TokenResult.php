<?php

namespace Fynduck\MaibEcommerce\Data;

readonly class TokenResult
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken,
        public int $expiresIn,
        public ?int $refreshExpiresIn,
    ) {}

    /**
     * @param  array<string, mixed>  $result
     */
    public static function fromResult(array $result): self
    {
        return new self(
            accessToken: (string) ($result['accessToken'] ?? ''),
            refreshToken: isset($result['refreshToken']) ? (string) $result['refreshToken'] : null,
            expiresIn: (int) ($result['expiresIn'] ?? 0),
            refreshExpiresIn: isset($result['refreshExpiresIn']) ? (int) $result['refreshExpiresIn'] : null,
        );
    }
}
