<?php

namespace Texaavy\Vasbay\Exceptions;

/**
 * The credentials were refused: "601" (wrong username / API key, expired token, IP or domain not allowed)
 * or "606" (no credentials sent). HTTP 401.
 */
class AuthenticationException extends ApiException
{
}
