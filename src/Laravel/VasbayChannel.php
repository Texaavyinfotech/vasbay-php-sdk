<?php

namespace Texaavy\Vasbay\Laravel;

use Illuminate\Notifications\Notification;
use Texaavy\Vasbay\Client;
use Texaavy\Vasbay\Responses\ApiResponse;

/**
 * Notification channel "vasbay":
 *
 *   public function via($notifiable) { return ['vasbay']; }
 *   public function toVasbay($notifiable) { return VasbayMessage::create('Your OTP is 4821. -Texaavy'); }
 *
 * The number comes from $notifiable->routeNotificationForVasbay(), or its "phone" attribute.
 */
class VasbayChannel
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function send(object $notifiable, Notification $notification): ?ApiResponse
    {
        $to = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('vasbay', $notification)
            : null;
        if (! $to) {
            $to = $notifiable->phone ?? null;
        }
        if (! $to || ! method_exists($notification, 'toVasbay')) {
            return null;
        }
        $message = $notification->toVasbay($notifiable);
        if (is_string($message)) {
            $message = VasbayMessage::create($message);
        }
        $config = function_exists('config') ? (array) config('vasbay-sdk') : [];

        return $this->client->sms()->send([
            'numbers' => (array) $to,
            'message' => $message->content,
            'senderId' => $message->senderId ?? ($config['sender_id'] ?? null),
            'route' => $message->route ?? ($config['route'] ?? 2),
            'templateId' => $message->templateId,
            'scheduleTime' => $message->scheduleTime,
        ]);
    }
}
