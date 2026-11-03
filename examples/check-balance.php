<?php

/*
 * SMS credit balance (and voice credits, when the account has the voice service).
 *
 *   php examples/check-balance.php
 */

require __DIR__ . '/bootstrap.php';

use Texaavy\Vasbay\Exceptions\ApiException;
use Texaavy\Vasbay\Exceptions\VasbayException;

$vasbay = vasbay();

try {
    $balance = $vasbay->account()->balance();
    printf("Transactional: %s\nMarketing:     %s\nInternational: EUR %s\n",
        $balance['transactional'], $balance['marketing'], $balance['international_eur']);
} catch (VasbayException $e) {
    fwrite(STDERR, 'Failed: [' . $e->getErrorCode() . '] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

try {
    foreach ($vasbay->voice()->balance() as $plan) {
        printf("Voice %s, %s: %s\n", $plan['route'], $plan['billing'], $plan['credits']);
    }
} catch (ApiException $e) {
    if ($e->getErrorCode() !== '403') {   // 403: voice calls are not enabled on this account
        throw $e;
    }
}
