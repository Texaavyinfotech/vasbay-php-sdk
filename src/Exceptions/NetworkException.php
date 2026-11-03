<?php

namespace Texaavy\Vasbay\Exceptions;

/**
 * No reply was received: connection failure, DNS or TLS error ("network") or the request timed out ("timeout").
 *
 * Requests are never retried automatically: a send that timed out may still have been accepted, and
 * repeating it could deliver the SMS twice. Check GET /sms/history (sms()->history()) before re-sending.
 */
class NetworkException extends VasbayException
{
    public function isTimeout(): bool
    {
        return $this->getErrorCode() === 'timeout';
    }
}
