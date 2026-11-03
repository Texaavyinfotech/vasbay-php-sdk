<?php

namespace Texaavy\Vasbay\Http;

use Texaavy\Vasbay\Exceptions\NetworkException;

/**
 * Transport used by the Client. The default is CurlHttpClient; pass your own (e.g. a mock in tests,
 * or one that adds a proxy) with the "httpClient" option.
 */
interface HttpClientInterface
{
    /**
     * @param string               $method  GET, POST, DELETE
     * @param string               $url     absolute URL, query string included
     * @param array<string,string> $headers header name => value
     * @param string|null          $body    raw request body (JSON), null for none
     * @param int                  $timeout seconds
     *
     * @throws NetworkException when no HTTP reply was received
     */
    public function send(string $method, string $url, array $headers, ?string $body, int $timeout): HttpResponse;
}
