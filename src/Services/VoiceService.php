<?php

namespace Texaavy\Vasbay\Services;

use Texaavy\Vasbay\Responses\ApiResponse;

/**
 * Voice calls (OBD voice broadcast): send or schedule a recorded message to many numbers and follow the results.
 * The account needs the voice service (error "403" otherwise). Upload audio files in the panel
 * (Voice Call › Audio files) first.
 *
 * route: 1 promotional, 2 transactional.
 * billingType: 1 submission · 15 s pulse, 2 submission · 30 s pulse, 3 answered · 15 s pulse, 4 answered · 30 s pulse.
 * Left out, the first plan with credits is used.
 */
class VoiceService extends Service
{
    /** GET /voice/balance : credits per route and billing type */
    public function balance(): ApiResponse
    {
        return $this->httpGet('/voice/balance');
    }

    /** GET /voice/files : ready audio files */
    public function files(): ApiResponse
    {
        return $this->httpGet('/voice/files');
    }

    /** GET /voice/caller-ids : approved caller IDs and the default */
    public function callerIds(): ApiResponse
    {
        return $this->httpGet('/voice/caller-ids');
    }

    /**
     * POST /voice/calls : start (or schedule) a voice campaign. meta "ignored_numbers" lists numbers left out.
     *
     * @param array{
     *     fileId: int, to?: string|string[], numbers?: string|string[], route?: int, billingType?: int,
     *     callerId?: string, name?: string, scheduleTime?: string|\DateTimeInterface
     * } $call "to" is the same as "numbers"
     */
    public function send(array $call): ApiResponse
    {
        return $this->httpPost('/voice/calls', self::alias($call, 'to', 'numbers'));
    }

    /**
     * GET /voice/calls : campaigns.
     *
     * @param array{from?: string, to?: string, state?: string, q?: string, page?: int, perPage?: int} $query
     */
    public function calls(array $query = []): ApiResponse
    {
        return $this->httpGet('/voice/calls', $query);
    }

    /** GET /voice/calls/{id} : one campaign with the result of every number (pages of 100) */
    public function call(int $id, int $page = 1): ApiResponse
    {
        return $this->httpGet('/voice/calls/' . $id, $page > 1 ? ['page' => $page] : []);
    }

    /** POST /voice/calls/{id}/cancel : cancel a scheduled campaign, credits back */
    public function cancel(int $id): ApiResponse
    {
        return $this->httpPost('/voice/calls/' . $id . '/cancel');
    }
}
