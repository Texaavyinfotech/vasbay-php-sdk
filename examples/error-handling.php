<?php

/*
 * Every kind of failure, from the most specific to the most general.
 *
 *   php examples/error-handling.php
 */

require __DIR__ . '/bootstrap.php';

use Texaavy\Vasbay\Exceptions\ApiException;
use Texaavy\Vasbay\Exceptions\AuthenticationException;
use Texaavy\Vasbay\Exceptions\NetworkException;
use Texaavy\Vasbay\Exceptions\VasbayException;

$vasbay = vasbay();

try {
    // no sender ID and an empty message: the API answers with a validation error
    $vasbay->sms()->send(['to' => '9895XXXXXX', 'message' => '']);
} catch (AuthenticationException $e) {
    // 601: wrong UID / API key, IP not allowed or wrong panel domain; 606: no credentials sent
    echo 'Check VASBAY_USERNAME / VASBAY_API_KEY: ', $e->getMessage(), PHP_EOL;
} catch (NetworkException $e) {
    // no reply. Not retried automatically: a send that timed out may still have been accepted,
    // so look at $vasbay->sms()->history() before sending again.
    echo $e->isTimeout() ? 'Timed out' : 'Network problem', ': ', $e->getMessage(), PHP_EOL;
} catch (ApiException $e) {
    // any other error reply: 30xx validation (HTTP 422), 429 rate limit, 403 service not enabled, …
    printf("API error %s (HTTP %d): %s\n", $e->getErrorCode(), $e->getHttpStatus(), $e->getMessage());
} catch (VasbayException $e) {
    // configuration ("config") or a reply that was not the API's JSON ("invalid_response")
    printf("SDK error %s: %s\n", $e->getErrorCode(), $e->getMessage());
}
