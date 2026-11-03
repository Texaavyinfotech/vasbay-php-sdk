<?php

namespace Texaavy\Vasbay\Services;

use Texaavy\Vasbay\Responses\ApiResponse;

/** Sender IDs (headers): list, look up and request new ones. */
class SenderIdService extends Service
{
    /**
     * GET /sender-ids
     *
     * @param string|null                            $status approved | under_review | with_operator | rejected
     * @param array{senderId?: string, route?: int} $query  look one up by name and route
     */
    public function list(?string $status = null, array $query = []): ApiResponse
    {
        return $this->httpGet('/sender-ids', ['status' => $status] + $query);
    }

    /** GET /sender-ids/{id} : one sender ID request and its review status */
    public function get(int $id): ApiResponse
    {
        return $this->httpGet('/sender-ids/' . $id);
    }

    /**
     * POST /sender-ids : request a sender ID (reviewed, then sent to the operator).
     *
     * @param array{senderId: string, route?: int, entityId: string, companyName: string, explanation: string,
     *              purpose: string, sampleSms: string} $request route defaults to 2 (transactional)
     */
    public function request(array $request): ApiResponse
    {
        return $this->httpPost('/sender-ids', self::defaults($request, ['route' => 2]));
    }
}
