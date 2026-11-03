<?php

namespace Texaavy\Vasbay\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Texaavy\Vasbay\Client;
use Texaavy\Vasbay\Exceptions\AuthenticationException;
use Texaavy\Vasbay\Exceptions\NetworkException;

/**
 * The real cURL transport against PHP's built-in web server on 127.0.0.1 (never the live API):
 * headers and body on the wire, reply headers, connection errors and timeouts.
 */
final class CurlHttpClientTest extends BaseTestCase
{
    /** @var resource|null */
    private static $server;

    /** @var int */
    private static $port = 0;

    public static function setUpBeforeClass(): void
    {
        if (! function_exists('proc_open')) {
            return;
        }
        self::$port = self::freePort();
        $cmd = [PHP_BINARY, '-S', '127.0.0.1:' . self::$port, __DIR__ . '/Support/echo-server.php'];
        $null = stripos(PHP_OS, 'WIN') === 0 ? 'NUL' : '/dev/null';
        self::$server = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['file', $null, 'w'], 2 => ['file', $null, 'w']], $pipes);

        $until = microtime(true) + 5;
        while (microtime(true) < $until) {
            $s = @fsockopen('127.0.0.1', self::$port, $errno, $errstr, 0.2);
            if ($s) {
                fclose($s);

                return;
            }
            usleep(50000);
        }
        self::tearDownAfterClass();
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        self::$server = null;
    }

    protected function setUp(): void
    {
        if (! is_resource(self::$server)) {
            $this->markTestSkipped('Could not start the PHP built-in web server.');
        }
    }

    public function testHeadersAndJsonBodyOnTheWire(): void
    {
        $r = $this->client()->sms()->send(['to' => '9895000001', 'message' => 'Hello ✓', 'senderId' => 'TXAAVY']);

        $this->assertSame('POST', $r['method']);
        $this->assertSame('/api/v3/sms/send', $r['path']);
        $this->assertSame('TEST_UID', $r['headers']['x-api-username']);
        $this->assertSame('test-api-key', $r['headers']['x-api-key']);
        $this->assertSame('application/json', $r['headers']['accept']);
        $this->assertSame('application/json', $r['headers']['content-type']);
        $this->assertStringStartsWith('vasbay-php-sdk/', $r['headers']['user-agent']);
        $this->assertEquals(['numbers' => '9895000001', 'message' => 'Hello ✓', 'sender_id' => 'TXAAVY', 'route' => 2, 'sms_type' => 1], json_decode($r['body'], true));
        $this->assertSame(238, $r->rateLimitRemaining());
    }

    public function testGetQueryOnTheWire(): void
    {
        $r = $this->client()->sms()->history(['from' => '2026-10-01', 'via' => 'api']);

        $this->assertSame('GET', $r['method']);
        $this->assertSame(['from' => '2026-10-01', 'via' => 'api'], $r['query']);
        $this->assertSame('', $r['body']);
    }

    public function testBearerTokenOnTheWire(): void
    {
        $client = new Client(['token' => 'vb3_abc', 'baseUrl' => 'http://127.0.0.1:' . self::$port]);
        $r = $client->account()->me();

        $this->assertSame('Bearer vb3_abc', $r['headers']['authorization']);
    }

    public function testErrorReplyOverCurl(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication Failed');
        $this->client()->request('GET', '/denied');
    }

    public function testTimeout(): void
    {
        try {
            $this->client(['timeout' => 1])->request('GET', '/slow');
            $this->fail('expected NetworkException');
        } catch (NetworkException $e) {
            $this->assertSame('timeout', $e->getErrorCode());
            $this->assertTrue($e->isTimeout());
        }
    }

    public function testConnectionRefused(): void
    {
        $client = new Client('TEST_UID', 'test-api-key', ['baseUrl' => 'http://127.0.0.1:' . self::freePort(), 'timeout' => 5]);

        try {
            $client->account()->balance();
            $this->fail('expected NetworkException');
        } catch (NetworkException $e) {
            $this->assertSame('network', $e->getErrorCode());
            $this->assertSame(0, $e->getHttpStatus());
            $this->assertStringStartsWith('Network error: ', $e->getMessage());
        }
    }

    private function client(array $options = []): Client
    {
        return new Client('TEST_UID', 'test-api-key', $options + ['baseUrl' => 'http://127.0.0.1:' . self::$port]);
    }

    /** a local port nothing listens on */
    private static function freePort(): int
    {
        $s = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $name = (string) stream_socket_get_name($s, false);
        fclose($s);

        return (int) substr($name, strrpos($name, ':') + 1);
    }
}
