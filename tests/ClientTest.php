<?php

namespace Texaavy\Vasbay\Tests;

use Texaavy\Vasbay\Client;
use Texaavy\Vasbay\Exceptions\VasbayException;
use Texaavy\Vasbay\Services\AccountService;
use Texaavy\Vasbay\Services\GroupService;
use Texaavy\Vasbay\Services\ScheduledService;
use Texaavy\Vasbay\Services\SenderIdService;
use Texaavy\Vasbay\Services\SmsService;
use Texaavy\Vasbay\Services\TemplateService;
use Texaavy\Vasbay\Services\VoiceService;

/** initialisation, configuration and authentication headers */
final class ClientTest extends TestCase
{
    public function testPositionalCredentials(): void
    {
        $client = new Client('UID123', 'key-abc');

        $this->assertSame(Client::DEFAULT_BASE_URL, $client->getBaseUrl());
        $this->assertSame('https://vasbay.com', $client->getBaseUrl());
        $this->assertSame(30, $client->getTimeout());
        $this->assertNull($client->token());
    }

    public function testArrayOptions(): void
    {
        $client = new Client(['username' => 'UID123', 'apiKey' => 'key-abc', 'baseUrl' => 'https://sms.example.com/', 'timeout' => 12]);

        $this->assertSame('https://sms.example.com', $client->getBaseUrl(), 'trailing slash is trimmed');
        $this->assertSame(12, $client->getTimeout());
    }

    public function testApiKeyHeaders(): void
    {
        $headers = (new Client('UID123', 'key-abc'))->getHeaders();

        $this->assertSame('UID123', $headers['X-Api-Username']);
        $this->assertSame('key-abc', $headers['X-Api-Key']);
        $this->assertSame('application/json', $headers['Accept']);
        $this->assertStringStartsWith('vasbay-php-sdk/' . Client::VERSION, $headers['User-Agent']);
        $this->assertArrayNotHasKey('Authorization', $headers);
    }

    public function testBearerTokenHeaders(): void
    {
        $client = new Client(['token' => 'vb3_token']);
        $headers = $client->getHeaders();

        $this->assertSame('Bearer vb3_token', $headers['Authorization']);
        $this->assertArrayNotHasKey('X-Api-Key', $headers);
        $this->assertSame('vb3_token', $client->token());
    }

    public function testTokenWinsOverApiKey(): void
    {
        $headers = (new Client(['token' => 'vb3_token', 'username' => 'UID', 'apiKey' => 'k']))->getHeaders();

        $this->assertSame('Bearer vb3_token', $headers['Authorization']);
        $this->assertArrayNotHasKey('X-Api-Username', $headers);
    }

    public function testMissingCredentials(): void
    {
        $this->expectConfigError();
        new Client([]);
    }

    public function testApiKeyWithoutUsername(): void
    {
        $this->expectConfigError();
        new Client('', 'key-abc');
    }

    public function testUsernameWithoutApiKey(): void
    {
        $this->expectConfigError();
        new Client('UID123');
    }

    public function testPlainHttpIsRejected(): void
    {
        $this->expectConfigError();
        new Client('UID', 'key', ['baseUrl' => 'http://sms.example.com']);
    }

    public function testPlainHttpAllowedForLocalhost(): void
    {
        $this->assertSame('http://localhost:8000', (new Client('UID', 'key', ['baseUrl' => 'http://localhost:8000']))->getBaseUrl());
        $this->assertSame('http://127.0.0.1', (new Client('UID', 'key', ['baseUrl' => 'http://127.0.0.1']))->getBaseUrl());
    }

    public function testLocalhostLookalikeIsRejected(): void
    {
        $this->expectConfigError();
        new Client('UID', 'key', ['baseUrl' => 'http://localhost.attacker.example']);
    }

    public function testAllowInsecure(): void
    {
        $client = new Client('UID', 'key', ['baseUrl' => 'http://sms.lan', 'allowInsecure' => true]);

        $this->assertSame('http://sms.lan', $client->getBaseUrl());
    }

    public function testRelativeBaseUrlIsRejected(): void
    {
        $this->expectConfigError();
        new Client('UID', 'key', ['baseUrl' => 'vasbay.com']);
    }

    public function testInvalidHttpClientIsRejected(): void
    {
        $this->expectConfigError();
        new Client('UID', 'key', ['httpClient' => new \stdClass()]);
    }

    public function testServiceAccessors(): void
    {
        $client = $this->client();

        $this->assertInstanceOf(SmsService::class, $client->sms());
        $this->assertInstanceOf(AccountService::class, $client->account());
        $this->assertInstanceOf(SenderIdService::class, $client->senderIds());
        $this->assertInstanceOf(TemplateService::class, $client->templates());
        $this->assertInstanceOf(ScheduledService::class, $client->scheduled());
        $this->assertInstanceOf(GroupService::class, $client->groups());
        $this->assertInstanceOf(VoiceService::class, $client->voice());
        $this->assertSame($client->sms(), $client->sms(), 'services are reused');
    }

    public function testTimeoutIsPassedToTransport(): void
    {
        $this->http->queueSuccess([]);
        $this->client(['timeout' => 7])->account()->balance();

        $this->assertSame(7, $this->http->last()['timeout']);
    }

    private function expectConfigError(): void
    {
        $this->expectException(VasbayException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessageMatches('/username|token|baseUrl|https|httpClient/');
    }
}
