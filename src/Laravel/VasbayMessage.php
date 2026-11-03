<?php

namespace Texaavy\Vasbay\Laravel;

/** an SMS built in a notification's toVasbay() */
class VasbayMessage
{
    /** @var string */
    public $content = '';

    /** @var string|null */
    public $senderId;

    /** @var int|null */
    public $route;

    /** @var int|null */
    public $templateId;

    /** @var string|null */
    public $scheduleTime;

    public static function create(string $content = ''): self
    {
        return (new self())->content($content);
    }

    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function from(string $senderId): self
    {
        $this->senderId = $senderId;

        return $this;
    }

    /** 2 transactional (default), 1 marketing, 4 international */
    public function route(int $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function template(int $templateId): self
    {
        $this->templateId = $templateId;

        return $this;
    }

    /**
     * send later, e.g. now()->addHour() or "2026-12-25 10:30"
     *
     * @param \DateTimeInterface|string $time
     */
    public function at($time): self
    {
        $this->scheduleTime = $time instanceof \DateTimeInterface ? $time->format('Y-m-d H:i') : (string) $time;

        return $this;
    }
}
