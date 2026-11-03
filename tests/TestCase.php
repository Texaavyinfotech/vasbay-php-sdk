<?php

namespace Texaavy\Vasbay\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Texaavy\Vasbay\Client;
use Texaavy\Vasbay\Tests\Support\MockHttpClient;

abstract class TestCase extends BaseTestCase
{
    /** @var MockHttpClient */
    protected $http;

    protected function setUp(): void
    {
        parent::setUp();
        $this->http = new MockHttpClient();
    }

    protected function client(array $options = []): Client
    {
        return new Client('TEST_UID', 'test-api-key', $options + ['httpClient' => $this->http]);
    }
}
