<?php

namespace Texaavy\Vasbay\Tests\Support;

use Texaavy\Vasbay\Http\HttpClientInterface;
use Texaavy\Vasbay\Http\HttpResponse;

/** records every request and answers with queued replies (or throws queued exceptions); never touches the network */
final class MockHttpClient implements HttpClientInterface
{
    /** @var array<int, array{method: string, url: string, headers: array<string, string>, body: ?string, timeout: int}> */
    public $requests = [];

    /** @var array<int, HttpResponse|\Throwable> */
    private $queue = [];

    /** @param mixed $data */
    public function queueSuccess($data, array $meta = [], int $status = 200, array $headers = []): self
    {
        $body = ['success' => true, 'data' => $data] + ($meta ? ['meta' => $meta] : []);

        return $this->queueRaw($status, (string) json_encode($body), $headers + ['Content-Type' => 'application/json']);
    }

    public function queueError(string $code, string $message, int $status): self
    {
        return $this->queueRaw($status, (string) json_encode(['success' => false, 'error' => ['code' => $code, 'message' => $message]]));
    }

    public function queueRaw(int $status, string $body, array $headers = []): self
    {
        $this->queue[] = new HttpResponse($status, $body, $headers);

        return $this;
    }

    public function queueException(\Throwable $e): self
    {
        $this->queue[] = $e;

        return $this;
    }

    public function send(string $method, string $url, array $headers, ?string $body, int $timeout): HttpResponse
    {
        $this->requests[] = compact('method', 'url', 'headers', 'body', 'timeout');
        $next = array_shift($this->queue);
        if ($next === null) {
            throw new \LogicException('No mocked reply queued for ' . $method . ' ' . $url);
        }
        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next;
    }

    /** @return array{method: string, url: string, headers: array<string, string>, body: ?string, timeout: int} */
    public function last(): array
    {
        if (! $this->requests) {
            throw new \LogicException('No request was sent.');
        }

        return $this->requests[count($this->requests) - 1];
    }

    /** @return array<string, mixed>|null */
    public function lastJson(): ?array
    {
        $body = $this->last()['body'];

        return $body === null ? null : json_decode($body, true);
    }

    /** @return array<string, mixed> */
    public function lastQuery(): array
    {
        $q = (string) parse_url($this->last()['url'], PHP_URL_QUERY);
        parse_str($q, $out);

        return $out;
    }

    public function lastPath(): string
    {
        return (string) parse_url($this->last()['url'], PHP_URL_PATH);
    }
}
