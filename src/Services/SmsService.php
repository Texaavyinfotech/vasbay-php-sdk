<?php

namespace Texaavy\Vasbay\Services;

use Texaavy\Vasbay\Responses\ApiResponse;

/**
 * SMS: send, bulk / personalised send, delivery status, history and the numbers of a send.
 *
 * route: 2 transactional (default), 1 marketing, 4 international.
 * smsType: 1 normal (default), 2 flash, 3 unicode (any language).
 */
class SmsService extends Service
{
    /**
     * POST /sms/send
     *
     * $vasbay->sms()->send(['to' => '9895XXXXXX', 'message' => 'Your OTP is 4821. -Texaavy', 'senderId' => 'TXAAVY']);
     *
     * @param array{
     *     to?: string|string[], numbers?: string|string[], groupIds?: int[], message: string, senderId?: string,
     *     route?: int, smsType?: int, scheduleTime?: string|\DateTimeInterface, removeDuplicate?: bool,
     *     templateId?: int, entityId?: string, contentId?: string
     * } $sms "to" is the same as "numbers": one number, a comma-separated list or an array
     *
     * @return ApiResponse data: push_id, scheduled_at, numbers, …
     */
    public function send(array $sms): ApiResponse
    {
        $sms = self::alias($sms, 'to', 'numbers');

        return $this->httpPost('/sms/send', self::defaults($sms, ['route' => 2, 'sms_type' => 1]));
    }

    /**
     * POST /sms/bulk : your own reference per number; give "fields" for personalised (#FIELD2#…) messages.
     *
     * @param array{
     *     message: string, senderId?: string, route?: int, smsType?: int, scheduleTime?: string|\DateTimeInterface,
     *     recipients: array<array{mobile: string, ref?: string, fields?: string[]}>
     * } $sms
     */
    public function sendBulk(array $sms): ApiResponse
    {
        return $this->httpPost('/sms/bulk', self::defaults($sms, ['route' => 2, 'sms_type' => 1, 'recipients' => []]));
    }

    /**
     * GET /sms/status : delivery status of a send (pushId), of one number (numberId) or the latest numbers (page).
     *
     * @param int|array{pushId?: int, numberId?: int, page?: int} $query an int is a pushId
     */
    public function status($query = []): ApiResponse
    {
        if (is_int($query) || (is_string($query) && ctype_digit($query))) {
            $query = ['pushId' => (int) $query];
        }

        return $this->httpGet('/sms/status', (array) $query);
    }

    /**
     * GET /sms/history : sends with their delivery counts (report).
     *
     * @param array{from?: string, to?: string, page?: int, via?: string, scheduled?: bool} $query
     *        from / to: Y-m-d; via: web | mobile | api
     */
    public function history(array $query = []): ApiResponse
    {
        return $this->httpGet('/sms/history', $query);
    }

    /**
     * GET /sms/pushes/{id}/numbers : every number of a send with its status.
     *
     * @param array{status?: string, q?: string, page?: int, perPage?: int} $query
     *        status: delivered | sent | failed | dnd | unknown | stopped; q: digits of a number
     */
    public function numbers(int $pushId, array $query = []): ApiResponse
    {
        return $this->httpGet('/sms/pushes/' . $pushId . '/numbers', $query);
    }
}
