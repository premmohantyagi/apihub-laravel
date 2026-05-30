<?php

use ApiHub\Laravel\Support\AwsSignatureV4;

// Verified against AWS's official Signature V4 test suite (get-vanilla).
function signer(): AwsSignatureV4
{
    return new AwsSignatureV4('AKIDEXAMPLE', 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY', 'us-east-1', 'service');
}

it('builds the canonical request for the get-vanilla vector', function () {
    $canonical = signer()->canonicalRequest('GET', '/', '', [
        'host' => 'example.amazonaws.com',
        'x-amz-date' => '20150830T123600Z',
    ], '');

    expect($canonical)->toBe(implode("\n", [
        'GET',
        '/',
        '',
        'host:example.amazonaws.com',
        'x-amz-date:20150830T123600Z',
        '',
        'host;x-amz-date',
        'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    ]));
});

it('produces the documented signature and date headers', function () {
    $headers = signer()->headers('GET', 'https://example.amazonaws.com/', [], '', '20150830T123600Z');

    expect($headers['Authorization'])
        ->toContain('Credential=AKIDEXAMPLE/20150830/us-east-1/service/aws4_request')
        ->toContain('SignedHeaders=host;x-amz-date')
        ->toContain('Signature=5fa00fa31553b73ebf1942676e86291e8372ff2a2260956d9b8aae1d763fbf31')
        ->and($headers['X-Amz-Date'])->toBe('20150830T123600Z');
});

it('sorts and encodes the canonical query string', function () {
    $canonical = signer()->canonicalRequest('GET', '/', 'Version=2010-05-08&Action=ListUsers', [
        'host' => 'iam.amazonaws.com',
        'x-amz-date' => '20150830T123600Z',
    ], '');

    expect($canonical)->toContain("\nAction=ListUsers&Version=2010-05-08\n");
});
