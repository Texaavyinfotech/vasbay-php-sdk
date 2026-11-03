<?php

namespace Texaavy\Vasbay\Exceptions;

/**
 * Base exception of the SDK. Catch this one to handle every failure.
 *
 * Error codes are those of the VASbay API ("601" Authentication Failed, "3076" Invalid Sender Id, …),
 * or one of the SDK's own: "config", "network", "timeout", "invalid_response".
 */
class VasbayException extends \RuntimeException
{
    /** @var string */
    private $errorCode;

    /** @var int */
    private $httpStatus;

    /** @var array<string, mixed>|null */
    private $responseBody;

    /**
     * @param array<string, mixed>|null $responseBody the decoded JSON reply, when there was one
     */
    public function __construct(string $message, string $errorCode, int $httpStatus = 0, ?array $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
        $this->responseBody = $responseBody;
    }

    /** API error code ("601", "3076", …) or "config" / "network" / "timeout" / "invalid_response" */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /** HTTP status of the reply (0 when no reply was received) */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /** @return array<string, mixed>|null the decoded JSON reply, when there was one */
    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
