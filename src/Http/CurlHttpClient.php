<?php

namespace Texaavy\Vasbay\Http;

use Texaavy\Vasbay\Exceptions\NetworkException;

/**
 * cURL transport. TLS certificates and host names are always verified, redirects are not followed,
 * and nothing is retried: repeating a send could deliver the SMS twice.
 */
class CurlHttpClient implements HttpClientInterface
{
    /** @var int */
    private $connectTimeout;

    public function __construct(int $connectTimeout = 10)
    {
        $this->connectTimeout = $connectTimeout;
    }

    public function send(string $method, string $url, array $headers, ?string $body, int $timeout): HttpResponse
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }
        $received = [];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min($this->connectTimeout, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$received): int {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $received[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return strlen($line);
            },
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $reply = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($reply === false) {
            $error = curl_error($ch);
            $timedOut = curl_errno($ch) === CURLE_OPERATION_TIMEDOUT;
            self::close($ch);

            throw new NetworkException(
                $timedOut ? 'The request timed out.' : 'Network error: ' . $error,
                $timedOut ? 'timeout' : 'network'
            );
        }
        self::close($ch);

        return new HttpResponse($status, (string) $reply, $received);
    }

    /** @param resource|\CurlHandle $ch */
    private static function close($ch): void
    {
        // curl_close() is a no-op since PHP 8.0 and deprecated in 8.5; handles are freed when unset
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }
    }
}
