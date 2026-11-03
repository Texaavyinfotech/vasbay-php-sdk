<?php

namespace Texaavy\Vasbay\Services;

use Texaavy\Vasbay\Responses\ApiResponse;

/** Contact groups (read only), to send to with sms()->send(['groupIds' => [...]]). */
class GroupService extends Service
{
    /**
     * GET /groups : groups by name, each with its number of active contacts.
     *
     * @param array{q?: string, page?: int, perPage?: int} $query
     */
    public function list(array $query = []): ApiResponse
    {
        return $this->httpGet('/groups', $query);
    }

    /** GET /groups/{id} */
    public function get(int $id): ApiResponse
    {
        return $this->httpGet('/groups/' . $id);
    }

    /**
     * GET /groups/{id}/contacts : active contacts; q matches number or name.
     *
     * @param array{q?: string, page?: int, perPage?: int} $query
     */
    public function contacts(int $id, array $query = []): ApiResponse
    {
        return $this->httpGet('/groups/' . $id . '/contacts', $query);
    }
}
