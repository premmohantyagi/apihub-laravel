<?php

namespace ApiHub\Laravel\Webhooks;

/**
 * Helpers for verifying inbound webhook signatures.
 *
 * Drivers pass the *raw* request body — the exact bytes the provider signed —
 * never a re-encoded array, so the computed digest matches. Comparisons are
 * constant-time to avoid leaking the expected value through timing.
 */
class SignatureVerifier
{
    public function hmac(string $payload, string $secret, string $algorithm = 'sha256'): string
    {
        return hash_hmac($algorithm, $payload, $secret);
    }

    /**
     * Base64-encoded raw HMAC — the form Square (and some others) sign with.
     */
    public function hmacBase64(string $payload, string $secret, string $algorithm = 'sha256'): string
    {
        return base64_encode(hash_hmac($algorithm, $payload, $secret, true));
    }

    public function equals(string $known, string $provided): bool
    {
        return hash_equals($known, $provided);
    }

    /**
     * Verify an HMAC signature over the raw payload.
     */
    public function verifyHmac(string $payload, string $signature, string $secret, string $algorithm = 'sha256'): bool
    {
        return $this->equals($this->hmac($payload, $secret, $algorithm), $signature);
    }
}
