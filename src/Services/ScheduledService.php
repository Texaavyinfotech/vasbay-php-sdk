<?php

namespace Texaavy\Vasbay\Services;

use Texaavy\Vasbay\Responses\ApiResponse;

/** Scheduled SMS: list, hold, resume and stop. */
class ScheduledService extends Service
{
    /**
     * GET /scheduled
     *
     * @param array{status?: string, type?: string} $query type: bulk | dynamic
     */
    public function list(array $query = []): ApiResponse
    {
        return $this->httpGet('/scheduled', $query);
    }

    /** POST /scheduled/{pushId}/hold */
    public function hold(int $pushId): ApiResponse
    {
        return $this->httpPost('/scheduled/' . $pushId . '/hold');
    }

    /** POST /scheduled/{pushId}/resume */
    public function resume(int $pushId): ApiResponse
    {
        return $this->httpPost('/scheduled/' . $pushId . '/resume');
    }

    /** POST /scheduled/{pushId}/stop : stops the schedule and refunds the numbers not yet sent */
    public function stop(int $pushId): ApiResponse
    {
        return $this->httpPost('/scheduled/' . $pushId . '/stop');
    }

    /** POST /scheduled/dynamic/{scheduleId}/stop : a personalised send, by the schedule_id sendBulk() returned */
    public function stopDynamic(int $scheduleId): ApiResponse
    {
        return $this->httpPost('/scheduled/dynamic/' . $scheduleId . '/stop');
    }
}
