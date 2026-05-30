<?php

namespace ApiHub\Laravel\Support;

/**
 * AWS Signature Version 4 request signer.
 *
 * Implemented over plain hashing so AWS services (SES today; S3, SNS, … later)
 * can be called through the HTTP client without pulling in the AWS SDK. The
 * signer hashes the exact payload bytes the caller will send — re-encoding the
 * body afterwards would invalidate the signature.
 *
 * @see https://docs.aws.amazon.com/general/latest/gr/sigv4-signing.html
 */
class AwsSignatureV4
{
    public function __construct(
        protected string $accessKey,
        protected string $secretKey,
        protected string $region,
        protected string $service,
    ) {}

    /**
     * Build the Authorization and X-Amz-Date headers to add to the request.
     *
     * @param  array<string, string>  $headers  Headers that participate in the signature (e.g. content-type). Host and X-Amz-Date are added automatically.
     * @param  string  $amzDate  ISO-8601 basic timestamp, e.g. 20150830T123600Z.
     * @return array<string, string>
     */
    public function headers(string $method, string $url, array $headers, string $payload, string $amzDate): array
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';

        $headers = array_change_key_case($headers, CASE_LOWER);
        $headers['host'] = $host;
        $headers['x-amz-date'] = $amzDate;

        $canonicalRequest = $this->canonicalRequest($method, $path, $query, $headers, $payload);
        $stringToSign = $this->stringToSign($amzDate, $canonicalRequest);
        $signature = $this->signature($amzDate, $stringToSign);

        $credential = $this->accessKey.'/'.$this->credentialScope($amzDate);

        $authorization = sprintf(
            'AWS4-HMAC-SHA256 Credential=%s, SignedHeaders=%s, Signature=%s',
            $credential,
            $this->signedHeaders($headers),
            $signature,
        );

        return [
            'Authorization' => $authorization,
            'X-Amz-Date' => $amzDate,
        ];
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function canonicalRequest(string $method, string $path, string $query, array $headers, string $payload): string
    {
        return implode("\n", [
            strtoupper($method),
            $this->canonicalPath($path),
            $this->canonicalQuery($query),
            $this->canonicalHeaders($headers),
            $this->signedHeaders($headers),
            hash('sha256', $payload),
        ]);
    }

    public function stringToSign(string $amzDate, string $canonicalRequest): string
    {
        return implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $this->credentialScope($amzDate),
            hash('sha256', $canonicalRequest),
        ]);
    }

    protected function signature(string $amzDate, string $stringToSign): string
    {
        $date = substr($amzDate, 0, 8);

        $kDate = hash_hmac('sha256', $date, 'AWS4'.$this->secretKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $this->service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        return hash_hmac('sha256', $stringToSign, $kSigning);
    }

    protected function credentialScope(string $amzDate): string
    {
        return sprintf('%s/%s/%s/aws4_request', substr($amzDate, 0, 8), $this->region, $this->service);
    }

    protected function canonicalPath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    protected function canonicalQuery(string $query): string
    {
        if ($query === '') {
            return '';
        }

        $pairs = [];

        foreach (explode('&', $query) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $pairs[] = [rawurlencode(urldecode($key)), rawurlencode(urldecode($value))];
        }

        usort($pairs, fn ($a, $b) => strcmp($a[0], $b[0]) ?: strcmp($a[1], $b[1]));

        return implode('&', array_map(fn ($pair) => $pair[0].'='.$pair[1], $pairs));
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function canonicalHeaders(array $headers): string
    {
        ksort($headers);

        $canonical = '';

        foreach ($headers as $name => $value) {
            $canonical .= $name.':'.trim((string) preg_replace('/\s+/', ' ', $value))."\n";
        }

        return $canonical;
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function signedHeaders(array $headers): string
    {
        $names = array_keys($headers);
        sort($names);

        return implode(';', $names);
    }
}
