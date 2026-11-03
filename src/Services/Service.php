<?php

namespace Texaavy\Vasbay\Services;

use Texaavy\Vasbay\Client;
use Texaavy\Vasbay\Responses\ApiResponse;

/**
 * Base of the service groups. Parameters may be given in camelCase (senderId, scheduleTime) or with the
 * API's own snake_case names (sender_id, schedule_time); both are sent as the API's names.
 */
abstract class Service
{
    /** @var Client */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /** @param array<string, mixed>|null $data */
    protected function httpGet(string $path, ?array $data = null): ApiResponse
    {
        return $this->client->request('GET', $path, $data === null ? null : $this->params($data));
    }

    /** @param array<string, mixed>|null $data */
    protected function httpPost(string $path, ?array $data = null): ApiResponse
    {
        return $this->client->request('POST', $path, $data === null ? null : $this->params($data));
    }

    /**
     * Top-level keys to the API's snake_case names; null and "" dropped, booleans as 1 / 0,
     * schedule_time from a DateTimeInterface, numbers as strings.
     *
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    protected function params(array $values): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            $key = self::snake((string) $key);
            if ($key === 'schedule_time') {
                $value = self::time($value);
            } elseif ($key === 'numbers') {
                $value = is_array($value) ? array_values(array_map('strval', $value)) : $value;
            }
            if ($value === null || $value === '') {
                continue;
            }
            $out[$key] = is_bool($value) ? ($value ? 1 : 0) : $value;
        }

        return $out;
    }

    protected static function snake(string $key): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
    }

    /**
     * Scheduled time as the API takes it: "Y-m-d H:i", in the panel's time zone.
     *
     * @param mixed $time string | DateTimeInterface | null
     */
    protected static function time($time): ?string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('Y-m-d H:i');
        }

        return $time === null || $time === '' ? null : (string) $time;
    }

    /**
     * $values with $alias renamed to $key (when $key itself is not given).
     *
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    protected static function alias(array $values, string $alias, string $key): array
    {
        if (array_key_exists($alias, $values)) {
            if (! array_key_exists($key, $values)) {
                $values[$key] = $values[$alias];
            }
            unset($values[$alias]);
        }

        return $values;
    }

    /**
     * $values with $defaults for the keys not given (in either spelling).
     *
     * @param array<string, mixed> $values
     * @param array<string, mixed> $defaults snake_case key => value
     * @return array<string, mixed>
     */
    protected static function defaults(array $values, array $defaults): array
    {
        $given = [];
        foreach (array_keys($values) as $k) {
            $given[self::snake((string) $k)] = true;
        }
        foreach ($defaults as $k => $v) {
            if (! isset($given[$k])) {
                $values[$k] = $v;
            }
        }

        return $values;
    }
}
