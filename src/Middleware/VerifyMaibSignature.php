<?php

namespace Fynduck\MaibEcommerce\Middleware;

use Closure;
use Fynduck\MaibEcommerce\Support\Signature;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects maib callback requests whose signature does not match the configured
 * Signature Key. Runs before the controller so unverified payloads never reach
 * application logic.
 */
class VerifyMaibSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signatureKey = (string) config('maib.signature_key');
        $result = $request->input('result');
        $signature = $request->input('signature');

        if ($signatureKey === ''
            || ! is_array($result)
            || ! is_string($signature)
            || ! Signature::verify($result, $signature, $signatureKey)) {
            abort(403, 'Invalid maib callback signature.');
        }

        return $next($request);
    }
}
