<?php

namespace Texaavy\Vasbay\Services;

use Texaavy\Vasbay\Responses\ApiResponse;

/** DLT templates: list, look up and register. */
class TemplateService extends Service
{
    /**
     * GET /templates : approved templates unless status is given.
     *
     * @param array{type?: string, variant?: string, status?: string, contentId?: string} $query
     *        status: approved (default) | under_review | with_operator | rejected | all
     */
    public function list(array $query = []): ApiResponse
    {
        return $this->httpGet('/templates', $query);
    }

    /** GET /templates/{id} : one template and its review status */
    public function get(int $id): ApiResponse
    {
        return $this->httpGet('/templates/' . $id);
    }

    /**
     * POST /templates : register a DLT template. entityId defaults (server side) to the sender ID's entity ID.
     *
     * @param array{type?: string, contentId: string, text: string, senderId?: string, entityId?: string, variant?: string} $template
     *        type: transactional (default) | marketing; variant: static | dynamic (found from #FIELD2#… when left out)
     */
    public function submit(array $template): ApiResponse
    {
        return $this->httpPost('/templates', self::defaults($template, ['type' => 'transactional']));
    }
}
