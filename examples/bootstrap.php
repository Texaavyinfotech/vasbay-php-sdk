<?php

/*
 * Shared setup of the examples. Credentials come from environment variables, never from the code:
 *
 *   export VASBAY_USERNAME="your UID"        # My Profile › API Access
 *   export VASBAY_API_KEY="your API key"     # My Profile › API Access
 *   export VASBAY_BASE_URL="https://vasbay.com"   # optional: your panel domain on a white-label panel
 *
 *   php examples/check-balance.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Texaavy\Vasbay\Client;

function vasbay(): Client
{
    $username = getenv('VASBAY_USERNAME');
    $apiKey = getenv('VASBAY_API_KEY');
    if (! $username || ! $apiKey) {
        fwrite(STDERR, "Set VASBAY_USERNAME and VASBAY_API_KEY (My Profile › API Access) first.\n");
        exit(1);
    }

    return new Client($username, $apiKey, [
        'baseUrl' => getenv('VASBAY_BASE_URL') ?: Client::DEFAULT_BASE_URL,
    ]);
}
