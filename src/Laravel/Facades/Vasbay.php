<?php

namespace Texaavy\Vasbay\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Texaavy\Vasbay\Client;

/**
 * Vasbay::sms()->send([...]); Vasbay::account()->balance();
 *
 * @method static \Texaavy\Vasbay\Services\SmsService sms()
 * @method static \Texaavy\Vasbay\Services\AccountService account()
 * @method static \Texaavy\Vasbay\Services\SenderIdService senderIds()
 * @method static \Texaavy\Vasbay\Services\TemplateService templates()
 * @method static \Texaavy\Vasbay\Services\ScheduledService scheduled()
 * @method static \Texaavy\Vasbay\Services\GroupService groups()
 * @method static \Texaavy\Vasbay\Services\VoiceService voice()
 * @method static \Texaavy\Vasbay\Responses\ApiResponse ping()
 * @method static \Texaavy\Vasbay\Responses\ApiResponse request(string $method, string $path, ?array $data = null, bool $authenticate = true)
 *
 * @see Client
 */
class Vasbay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
