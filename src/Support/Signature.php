<?php

namespace Fynduck\MaibEcommerce\Support;

/**
 * Computes and verifies the maib callback notification signature.
 *
 * Algorithm (ported verbatim from maib-sdk-php / maibmerchants docs):
 *   1. Take the "result" object from the callback payload.
 *   2. Recursively sort it by key (ksort, SORT_STRING) at every nesting level.
 *   3. Append the Signature Key as the final element.
 *   4. Recursively implode all scalar values with a ":" separator (depth-first).
 *   5. signature = base64_encode(sha256(string, raw_output: true)).
 */
class Signature
{
    /**
     * @param  array<string, mixed>  $result
     */
    public static function compute(array $result, string $signatureKey): string
    {
        $sorted = self::sortByKeyRecursive($result);
        $sorted[] = $signatureKey;

        $signString = self::implodeRecursive(':', $sorted);

        return base64_encode(hash('sha256', $signString, true));
    }

    /**
     * Timing-safe comparison of the computed signature against the received one.
     *
     * @param  array<string, mixed>  $result
     */
    public static function verify(array $result, string $receivedSignature, string $signatureKey): bool
    {
        return hash_equals(self::compute($result, $signatureKey), $receivedSignature);
    }

    /**
     * @param  array<string|int, mixed>  $array
     * @return array<string|int, mixed>
     */
    private static function sortByKeyRecursive(array $array): array
    {
        ksort($array, SORT_STRING);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::sortByKeyRecursive($value);
            }
        }

        return $array;
    }

    /**
     * @param  array<string|int, mixed>  $array
     */
    private static function implodeRecursive(string $separator, array $array): string
    {
        $result = '';

        foreach ($array as $item) {
            $result .= (is_array($item) ? self::implodeRecursive($separator, $item) : self::stringify($item)) . $separator;
        }

        return substr($result, 0, -1);
    }

    private static function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        return (string) $value;
    }
}
