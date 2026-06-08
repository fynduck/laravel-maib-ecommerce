<?php

namespace Fynduck\MaibEcommerce\Http;

use Fynduck\MaibEcommerce\Exceptions\PaymentException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin transport over the Laravel HTTP client (the Http facade).
 *
 * Builds requests against the maib base URL, attaches the bearer token when
 * provided, and unwraps the { ok, result } envelope into a plain result array.
 */
class Gateway
{
    public function __construct(
        protected string $baseUrl,
        protected int $timeout = 30,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws PaymentException
     */
    public function post(string $uri, array $data, ?string $token = null): array
    {
        return $this->send('POST', $uri, $data, $token);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws PaymentException
     */
    public function get(string $uri, array $query = [], ?string $token = null): array
    {
        return $this->send('GET', $uri, $query, $token);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws PaymentException
     */
    public function delete(string $uri, array $query = [], ?string $token = null): array
    {
        return $this->send('DELETE', $uri, $query, $token);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws PaymentException
     */
    protected function send(string $method, string $uri, array $data, ?string $token): array
    {
        $request = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson();

        if ($token !== null) {
            $request = $request->withToken($token);
        }

        try {
            $response = $request->send($method, ltrim($uri, '/'), [
                $method === 'GET' || $method === 'DELETE' ? 'query' : 'json' => $data,
            ]);
        } catch (ConnectionException $e) {
            throw new PaymentException("maib request to '{$uri}' failed: {$e->getMessage()}", previous: $e);
        }

        return $this->handle($uri, $response);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws PaymentException
     */
    protected function handle(string $uri, Response $response): array
    {
        $body = $response->json();

        if (! is_array($body)) {
            throw new PaymentException("maib request to '{$uri}' returned an invalid (non-JSON) response.");
        }

        if ($response->failed() || ($body['ok'] ?? false) !== true) {
            [$message, $code] = $this->extractError($body);

            throw PaymentException::fromApiError("maib request to '{$uri}' failed: {$message}", $code);
        }

        return is_array($body['result'] ?? null) ? $body['result'] : [];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{0: string, 1: ?string}
     */
    protected function extractError(array $body): array
    {
        $error = $body['errors'][0] ?? null;

        if (is_array($error)) {
            return [
                (string) ($error['errorMessage'] ?? 'Unknown error details'),
                isset($error['errorCode']) ? (string) $error['errorCode'] : null,
            ];
        }

        return ['Unknown error details', null];
    }
}
