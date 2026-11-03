<?php

/*
 * Send an SMS and check its delivery status. This sends a real SMS and uses credits.
 *
 *   php examples/send-sms.php 9895XXXXXX "Your OTP is 4821. -Texaavy" TXAAVY
 *
 * The message must match an approved DLT template of the sender ID (India).
 */

require __DIR__ . '/bootstrap.php';

use Texaavy\Vasbay\Exceptions\VasbayException;

[, $to, $message, $senderId] = $argv + [null, null, null, null];
if (! $to || ! $message || ! $senderId) {
    fwrite(STDERR, "Usage: php examples/send-sms.php <mobile> <message> <sender id>\n");
    exit(1);
}

$vasbay = vasbay();

try {
    $sent = $vasbay->sms()->send([
        'to' => $to,              // one number, "98..,97.." or an array
        'message' => $message,
        'senderId' => $senderId,
        'route' => 2,             // 2 transactional (default), 1 marketing, 4 international
    ]);
    echo 'Accepted, push_id ', $sent['push_id'], PHP_EOL;

    // delivery reports arrive over the next seconds to minutes
    $status = $vasbay->sms()->status($sent['push_id']);
    echo json_encode($status->data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
} catch (VasbayException $e) {
    fwrite(STDERR, 'Failed: [' . $e->getErrorCode() . '] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
