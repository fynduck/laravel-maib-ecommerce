<?php

namespace Fynduck\MaibEcommerce\Support;

use Fynduck\MaibEcommerce\Enums\Currency;
use Fynduck\MaibEcommerce\Exceptions\PaymentException;

/**
 * Validates request parameters before they are sent to the maib API.
 *
 * Rules are ported from the official maib-sdk-php so the package fails fast with
 * the same constraints the gateway enforces.
 */
class Validator
{
    /**
     * Ensure every key listed in $required is present and non-null.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $required
     *
     * @throws PaymentException
     */
    public static function requireKeys(array $data, array $required): void
    {
        foreach ($required as $key) {
            if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                throw new PaymentException("Missing required parameter: '{$key}'.");
            }
        }
    }

    /**
     * Validate any of the recognised maib parameters that are present in $data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws PaymentException
     */
    public static function validateParams(array $data): void
    {
        foreach ($data as $key => $value) {
            match ($key) {
                'billerId' => self::assertUuid($value, 'billerId'),
                'billerExpiry' => self::assertLength($value, 'billerExpiry', 4),
                'payId' => self::assertMaxLength($value, 'payId', 36),
                'amount' => self::assertNumericMin($value, 'amount', 1),
                'confirmAmount', 'refundAmount' => self::assertNumericMin($value, $key, 0.01),
                'currency' => self::assertCurrency($value),
                'clientIp' => self::assertIp($value),
                'language' => self::assertLength($value, 'language', 2),
                'description' => self::assertMaxLength($value, 'description', 124),
                'clientName' => self::assertMaxLength($value, 'clientName', 128),
                'email' => self::assertEmail($value),
                'phone' => self::assertMaxLength($value, 'phone', 40),
                'orderId' => self::assertMaxLength($value, 'orderId', 36),
                'delivery' => self::assertNumericMin($value, 'delivery', 0),
                'items' => self::assertItems($value),
                'callbackUrl', 'okUrl', 'failUrl' => self::assertUrl($value, $key),
                default => null,
            };
        }
    }

    public static function assertUuid(mixed $value, string $field): void
    {
        if (! is_string($value) || strlen($value) !== 36) {
            throw new PaymentException("Invalid '{$field}': must be a 36-character string.");
        }
    }

    public static function assertLength(mixed $value, string $field, int $length): void
    {
        if (! is_string($value) || strlen($value) !== $length) {
            throw new PaymentException("Invalid '{$field}': must be a {$length}-character string.");
        }
    }

    public static function assertMaxLength(mixed $value, string $field, int $max): void
    {
        if (! is_string($value) || strlen($value) > $max) {
            throw new PaymentException("Invalid '{$field}': must be a string of at most {$max} characters.");
        }
    }

    public static function assertNumericMin(mixed $value, string $field, float $min): void
    {
        if (is_string($value) || ! is_numeric($value) || $value < $min) {
            throw new PaymentException("Invalid '{$field}': must be a number greater than or equal to {$min}.");
        }
    }

    public static function assertCurrency(mixed $value): void
    {
        if (! is_string($value) || Currency::tryFrom($value) === null) {
            throw new PaymentException("Invalid 'currency': must be one of MDL, EUR, USD.");
        }
    }

    public static function assertIp(mixed $value): void
    {
        if (! is_string($value) || filter_var($value, FILTER_VALIDATE_IP) === false) {
            throw new PaymentException("Invalid 'clientIp': must be a valid IP address.");
        }
    }

    public static function assertEmail(mixed $value): void
    {
        if (! is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new PaymentException("Invalid 'email': must be a valid email address.");
        }
    }

    public static function assertUrl(mixed $value, string $field): void
    {
        if (! is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new PaymentException("Invalid '{$field}': must be a valid URL.");
        }
    }

    public static function assertItems(mixed $value): void
    {
        if (! is_array($value) || $value === []) {
            throw new PaymentException("Invalid 'items': must be a non-empty array.");
        }

        foreach ($value as $index => $item) {
            if (! is_array($item)) {
                throw new PaymentException("Invalid 'items[{$index}]': must be an array.");
            }

            if (isset($item['id'])) {
                self::assertMaxLength($item['id'], "items[{$index}].id", 36);
            }
            if (isset($item['name'])) {
                self::assertMaxLength($item['name'], "items[{$index}].name", 128);
            }
            if (isset($item['price'])) {
                self::assertNumericMin($item['price'], "items[{$index}].price", 0);
            }
            if (isset($item['quantity'])) {
                self::assertNumericMin($item['quantity'], "items[{$index}].quantity", 1);
            }
        }
    }
}
