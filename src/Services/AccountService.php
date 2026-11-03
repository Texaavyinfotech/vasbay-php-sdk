<?php

namespace Texaavy\Vasbay\Services;

use Texaavy\Vasbay\Responses\ApiResponse;

/** Account: SMS credit balance, profile and notices. */
class AccountService extends Service
{
    /**
     * GET /balance : SMS credits.
     *
     * @return ApiResponse data: {transactional: float, marketing: float, international_eur: float}
     */
    public function balance(): ApiResponse
    {
        return $this->httpGet('/balance');
    }

    /** GET /me : id, username, UID, name, e-mail, phone, company, account type, services, auth ("key" or "token") */
    public function me(): ApiResponse
    {
        return $this->httpGet('/me');
    }

    /** GET /notifications : notices for the account, newest first */
    public function notifications(): ApiResponse
    {
        return $this->httpGet('/notifications');
    }
}
