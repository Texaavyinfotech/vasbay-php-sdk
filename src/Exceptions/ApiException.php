<?php

namespace Texaavy\Vasbay\Exceptions;

/**
 * The API answered with {"success": false, "error": {"code": "…", "message": "…"}}.
 * Validation errors (30xx) come with HTTP 422, rate limits with 429 ("429"), missing services with 403.
 */
class ApiException extends VasbayException
{
}
