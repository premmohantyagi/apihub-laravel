<?php

use ApiHub\Laravel\Support\Redactor;

it('redacts matching keys recursively', function () {
    $redactor = new Redactor(['password', 'token']);

    $clean = $redactor->redact([
        'user' => 'alice',
        'password' => 'secret',
        'nested' => ['api_token' => 'abc', 'safe' => 1],
    ]);

    expect($clean['user'])->toBe('alice')
        ->and($clean['password'])->toBe('[REDACTED]')
        ->and($clean['nested']['api_token'])->toBe('[REDACTED]')
        ->and($clean['nested']['safe'])->toBe(1);
});

it('matches needles as a substring of the key', function () {
    $clean = (new Redactor(['secret']))->redact(['client_secret' => 'x', 'name' => 'y']);

    expect($clean['client_secret'])->toBe('[REDACTED]')
        ->and($clean['name'])->toBe('y');
});

it('leaves non-array values untouched', function () {
    expect((new Redactor(['token']))->redact('plain'))->toBe('plain');
});
