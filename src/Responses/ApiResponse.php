<?php

namespace Texaavy\Vasbay\Responses;

/**
 * A successful API reply: {"success": true, "data": …, "meta": {…}}.
 *
 * Reads like the "data" array ($response['push_id'], foreach, count), with the paging and other
 * extras of "meta" in meta(), the HTTP status in status() and the reply headers in header().
 *
 * @implements \ArrayAccess<array-key, mixed>
 * @implements \IteratorAggregate<array-key, mixed>
 */
final class ApiResponse implements \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable
{
    /** @var mixed */
    private $data;

    /** @var array<string, mixed> */
    private $meta;

    /** @var int */
    private $status;

    /** @var array<string, string> */
    private $headers;

    /**
     * @param mixed                 $data
     * @param array<string, mixed>  $meta
     * @param array<string, string> $headers lower-case names
     */
    public function __construct($data, array $meta = [], int $status = 200, array $headers = [])
    {
        $this->data = $data;
        $this->meta = $meta;
        $this->status = $status;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    /** @return mixed the "data" of the reply */
    public function data()
    {
        return $this->data;
    }

    /**
     * "meta" of the reply (page, per_page, total, …), or one key of it.
     *
     * @return mixed
     */
    public function meta(?string $key = null)
    {
        if ($key === null) {
            return $this->meta;
        }

        return $this->meta[$key] ?? null;
    }

    /** HTTP status (200, or 201 for created resources) */
    public function status(): int
    {
        return $this->status;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /** requests left this minute (X-RateLimit-Remaining), when the API sent it */
    public function rateLimitRemaining(): ?int
    {
        $v = $this->header('X-RateLimit-Remaining');

        return $v === null ? null : (int) $v;
    }

    /** @return array<array-key, mixed> "data" as an array (a scalar is wrapped) */
    public function toArray(): array
    {
        return is_array($this->data) ? $this->data : ($this->data === null ? [] : [$this->data]);
    }

    /** @param mixed $offset */
    public function offsetExists($offset): bool
    {
        return is_array($this->data) && array_key_exists($offset, $this->data);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return is_array($this->data) ? ($this->data[$offset] ?? null) : null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        throw new \LogicException('API responses are read-only.');
    }

    /** @param mixed $offset */
    public function offsetUnset($offset): void
    {
        throw new \LogicException('API responses are read-only.');
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->toArray());
    }

    public function count(): int
    {
        return count($this->toArray());
    }

    /** @return array{success: true, data: mixed, meta?: array<string, mixed>} the reply as the API sent it */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['success' => true, 'data' => $this->data] + ($this->meta ? ['meta' => $this->meta] : []);
    }
}
