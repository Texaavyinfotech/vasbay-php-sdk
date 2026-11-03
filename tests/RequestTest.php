<?php

namespace Texaavy\Vasbay\Tests;

use Texaavy\Vasbay\Client;
use Texaavy\Vasbay\Exceptions\ApiException;
use Texaavy\Vasbay\Exceptions\AuthenticationException;
use Texaavy\Vasbay\Exceptions\NetworkException;
use Texaavy\Vasbay\Exceptions\VasbayException;
use Texaavy\Vasbay\Responses\ApiResponse;

/** request construction, JSON response parsing and error handling */
final class RequestTest extends TestCase
{
    public function testGetUrlHeadersAndQuery(): void
    {
        $this->http->queueSuccess([]);
        $this->client()->request('GET', '/sms/history', ['from' => '2026-10-01', 'page' => 2]);

        $req = $this->http->last();
        $this->assertSame('GET', $req['method']);
        $this->assertSame('https://vasbay.com/api/v3/sms/history?from=2026-10-01&page=2', $req['url']);
        $this->assertNull($req['body']);
        $this->assertSame('TEST_UID', $req['headers']['X-Api-Username']);
        $this->assertSame('test-api-key', $req['headers']['X-Api-Key']);
        $this->assertArrayNotHasKey('Content-Type', $req['headers']);
    }

    public function testPostSendsJsonBody(): void
    {
        $this->http->queueSuccess(['push_id' => 1], [], 201);
        $this->client()->request('POST', '/sms/send', ['numbers' => ['9895000000'], 'message' => 'Hi ₹ ✓ /path']);

        $req = $this->http->last();
        $this->assertSame('POST', $req['method']);
        $this->assertSame('https://vasbay.com/api/v3/sms/send', $req['url']);
        $this->assertSame('application/json', $req['headers']['Content-Type']);
        $this->assertSame('{"numbers":["9895000000"],"message":"Hi ₹ ✓ /path"}', $req['body'], 'unescaped unicode and slashes');
    }

    public function testPostWithoutDataSendsEmptyObject(): void
    {
        $this->http->queueSuccess([]);
        $this->client()->request('POST', '/scheduled/5/hold');

        $this->assertSame('{}', $this->http->last()['body']);
    }

    public function testCustomBaseUrl(): void
    {
        $this->http->queueSuccess([]);
        $this->client(['baseUrl' => 'https://panel.example.com/'])->account()->balance();

        $this->assertSame('https://panel.example.com/api/v3/balance', $this->http->last()['url']);
    }

    public function testSuccessfulResponseParsing(): void
    {
        $this->http->queueSuccess([['push_id' => 1], ['push_id' => 2]], ['page' => 1, 'total' => 2], 200, ['X-RateLimit-Remaining' => '239']);
        $r = $this->client()->sms()->history();

        $this->assertInstanceOf(ApiResponse::class, $r);
        $this->assertSame(200, $r->status());
        $this->assertSame([['push_id' => 1], ['push_id' => 2]], $r->data());
        $this->assertSame(['page' => 1, 'total' => 2], $r->meta());
        $this->assertSame(2, $r->meta('total'));
        $this->assertNull($r->meta('missing'));
        $this->assertCount(2, $r);
        $this->assertSame(1, $r[0]['push_id']);
        $this->assertSame(239, $r->rateLimitRemaining());
        $this->assertSame('239', $r->header('x-ratelimit-remaining'));
        $ids = [];
        foreach ($r as $row) {
            $ids[] = $row['push_id'];
        }
        $this->assertSame([1, 2], $ids);
        $this->assertSame(['success' => true, 'data' => $r->data(), 'meta' => $r->meta()], $r->jsonSerialize());
    }

    public function testAssociativeDataAccess(): void
    {
        $this->http->queueSuccess(['transactional' => 1250.5, 'marketing' => 0.0, 'international_eur' => 3.2]);
        $balance = $this->client()->account()->balance();

        $this->assertSame(1250.5, $balance['transactional']);
        $this->assertTrue(isset($balance['marketing']));
        $this->assertFalse(isset($balance['missing']));
        $this->assertNull($balance['missing']);
        $this->assertSame([], $balance->meta());
        $this->assertSame(3.2, $balance->toArray()['international_eur']);
    }

    public function testResponseIsReadOnly(): void
    {
        $this->http->queueSuccess(['a' => 1]);
        $r = $this->client()->account()->me();

        $this->expectException(\LogicException::class);
        $r['a'] = 2;
    }

    public function testApiErrorResponse(): void
    {
        $this->http->queueError('3076', 'Invalid Sender Id', 422);

        try {
            $this->client()->sms()->send(['to' => '9895000000', 'message' => 'x', 'senderId' => 'BAD']);
            $this->fail('expected ApiException');
        } catch (ApiException $e) {
            $this->assertNotInstanceOf(AuthenticationException::class, $e);
            $this->assertSame('3076', $e->getErrorCode());
            $this->assertSame('Invalid Sender Id', $e->getMessage());
            $this->assertSame(422, $e->getHttpStatus());
            $this->assertSame(['success' => false, 'error' => ['code' => '3076', 'message' => 'Invalid Sender Id']], $e->getResponseBody());
        }
    }

    public function testRateLimitError(): void
    {
        $this->http->queueError('429', 'Too many requests. Retry in 12 seconds.', 429);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Too many requests');
        $this->client()->account()->balance();
    }

    public function testInvalidCredentials(): void
    {
        $this->http->queueError('601', 'Authentication Failed', 401);

        try {
            $this->client()->account()->balance();
            $this->fail('expected AuthenticationException');
        } catch (AuthenticationException $e) {
            $this->assertSame('601', $e->getErrorCode());
            $this->assertSame(401, $e->getHttpStatus());
        }
    }

    public function testMissingCredentialsReply(): void
    {
        $this->http->queueError('606', 'Send X-Api-Username and X-Api-Key headers, or an Authorization: Bearer token.', 401);

        $this->expectException(AuthenticationException::class);
        $this->client()->account()->me();
    }

    public function testErrorWithoutCodeUsesHttpStatus(): void
    {
        $this->http->queueRaw(500, '{"success":false}');

        try {
            $this->client()->account()->me();
            $this->fail('expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame('500', $e->getErrorCode());
            $this->assertSame('Request failed (HTTP 500).', $e->getMessage());
        }
    }

    public function testNonJsonResponse(): void
    {
        $this->http->queueRaw(502, '<html>Bad Gateway</html>');

        try {
            $this->client()->account()->balance();
            $this->fail('expected VasbayException');
        } catch (VasbayException $e) {
            $this->assertNotInstanceOf(ApiException::class, $e);
            $this->assertSame('invalid_response', $e->getErrorCode());
            $this->assertSame(502, $e->getHttpStatus());
        }
    }

    public function testJsonWithoutEnvelope(): void
    {
        $this->http->queueRaw(200, '{"foo":"bar"}');

        $this->expectException(VasbayException::class);
        $this->expectExceptionMessage('Unexpected response (HTTP 200).');
        $this->client()->account()->balance();
    }

    public function testNetworkErrorPropagates(): void
    {
        $this->http->queueException(new NetworkException('Network error: Could not resolve host', 'network'));

        try {
            $this->client()->sms()->send(['to' => '9895000000', 'message' => 'x']);
            $this->fail('expected NetworkException');
        } catch (NetworkException $e) {
            $this->assertSame('network', $e->getErrorCode());
            $this->assertSame(0, $e->getHttpStatus());
            $this->assertFalse($e->isTimeout());
        }
        $this->assertCount(1, $this->http->requests, 'never retried');
    }

    public function testTimeoutIsNotRetried(): void
    {
        $this->http->queueException(new NetworkException('The request timed out.', 'timeout'));

        try {
            $this->client()->sms()->send(['to' => '9895000000', 'message' => 'x']);
            $this->fail('expected NetworkException');
        } catch (NetworkException $e) {
            $this->assertTrue($e->isTimeout());
        }
        $this->assertCount(1, $this->http->requests);
    }

    public function testPingIsUnauthenticated(): void
    {
        $this->http->queueSuccess(['api' => 'SMS API', 'version' => '3.0']);
        $r = $this->client()->ping();

        $req = $this->http->last();
        $this->assertSame('https://vasbay.com/api/v3', $req['url']);
        $this->assertArrayNotHasKey('X-Api-Key', $req['headers']);
        $this->assertArrayNotHasKey('X-Api-Username', $req['headers']);
        $this->assertSame('3.0', $r['version']);
    }

    public function testLoginUsesAppToken(): void
    {
        $this->http->queueSuccess(['token' => 'vb3_new', 'user' => ['username' => 'demo']]);
        $this->http->queueSuccess(['transactional' => 1.0]);

        $client = Client::login('demo', 'secret', 'Tests', ['httpClient' => $this->http]);

        $loginReq = $this->http->requests[0];
        $this->assertSame('https://vasbay.com/api/v3/auth/login', $loginReq['url']);
        $this->assertSame(['login' => 'demo', 'password' => 'secret', 'device_name' => 'Tests'], json_decode((string) $loginReq['body'], true));
        $this->assertArrayNotHasKey('Authorization', $loginReq['headers']);
        $this->assertArrayNotHasKey('X-Api-Key', $loginReq['headers']);
        $this->assertSame('vb3_new', $client->token());
        $this->assertSame(['username' => 'demo'], $client->user);

        $client->account()->balance();
        $this->assertSame('Bearer vb3_new', $this->http->last()['headers']['Authorization']);
    }

    public function testFailedLogin(): void
    {
        $this->http->queueError('601', 'Wrong login or password', 401);

        $this->expectException(AuthenticationException::class);
        Client::login('demo', 'wrong', 'Tests', ['httpClient' => $this->http]);
    }

    public function testLogout(): void
    {
        $this->http->queueSuccess(null);
        (new Client(['token' => 'vb3_x', 'httpClient' => $this->http]))->logout();

        $this->assertSame('POST', $this->http->last()['method']);
        $this->assertSame('/api/v3/auth/logout', $this->http->lastPath());
    }
}
