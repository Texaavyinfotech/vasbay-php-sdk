<?php

namespace Texaavy\Vasbay;

use Texaavy\Vasbay\Exceptions\ApiException;
use Texaavy\Vasbay\Exceptions\AuthenticationException;
use Texaavy\Vasbay\Exceptions\VasbayException;
use Texaavy\Vasbay\Http\CurlHttpClient;
use Texaavy\Vasbay\Http\HttpClientInterface;
use Texaavy\Vasbay\Responses\ApiResponse;
use Texaavy\Vasbay\Services\AccountService;
use Texaavy\Vasbay\Services\GroupService;
use Texaavy\Vasbay\Services\ScheduledService;
use Texaavy\Vasbay\Services\SenderIdService;
use Texaavy\Vasbay\Services\SmsService;
use Texaavy\Vasbay\Services\TemplateService;
use Texaavy\Vasbay\Services\VoiceService;

/**
 * VASbay API v3 client.
 *
 *   $vasbay = new Client(getenv('VASBAY_USERNAME'), getenv('VASBAY_API_KEY'));
 *   $vasbay->sms()->send(['to' => '9895XXXXXX', 'message' => 'Your OTP is 4821. -Texaavy', 'senderId' => 'TXAAVY']);
 *   $balance = $vasbay->account()->balance();
 *
 * Sign-in, as the API accepts it:
 *   - username (UID) + API key from My Profile › API Access  → X-Api-Username / X-Api-Key headers
 *   - an app token from Client::login()                      → Authorization: Bearer <token>
 *
 * Reference: https://vasbay.com/api-documentation#v3
 */
class Client
{
    public const VERSION = '1.0.0';

    public const DEFAULT_BASE_URL = 'https://vasbay.com';

    public const API_PATH = '/api/v3';

    public const DEFAULT_TIMEOUT = 30;

    /** API error codes that mean the credentials were refused */
    private const AUTH_ERRORS = ['601', '606'];

    /** @var string */
    private $baseUrl;

    /** @var int */
    private $timeout;

    /** @var array<string, string> */
    private $auth;

    /** @var HttpClientInterface */
    private $http;

    /** @var array<string, object> */
    private $services = [];

    /** @var array<string, mixed>|null the account returned by login() */
    public $user;

    /**
     * new Client('UID', 'API_KEY')
     * new Client('UID', 'API_KEY', ['baseUrl' => 'https://your-panel-domain', 'timeout' => 20])
     * new Client(['username' => 'UID', 'apiKey' => 'API_KEY', 'baseUrl' => …, 'timeout' => …])
     * new Client(['token' => 'vb3_…'])
     *
     * Options: username, apiKey, token, baseUrl (default https://vasbay.com; use your own panel domain on a
     * white-label panel), timeout (seconds, default 30), allowInsecure (permit http:// for local testing),
     * httpClient (an HttpClientInterface, e.g. a mock).
     *
     * @param string|array<string, mixed> $username UID, or all options as an array
     * @param array<string, mixed>        $options
     *
     * @throws VasbayException "config" when credentials or the base URL are missing or unsafe
     */
    public function __construct($username, ?string $apiKey = null, array $options = [])
    {
        if (is_array($username)) {
            $options = $username + $options;
        } else {
            $options = ['username' => $username, 'apiKey' => $apiKey] + $options;
        }

        $this->baseUrl = rtrim(trim((string) ($options['baseUrl'] ?? self::DEFAULT_BASE_URL)), '/');
        $this->timeout = max(1, (int) ($options['timeout'] ?? self::DEFAULT_TIMEOUT));

        $http = $options['httpClient'] ?? null;
        if ($http !== null && ! $http instanceof HttpClientInterface) {
            throw new VasbayException('httpClient must implement ' . HttpClientInterface::class . '.', 'config');
        }
        $this->http = $http ?: new CurlHttpClient();

        if (! empty($options['token'])) {
            $this->auth = ['Authorization' => 'Bearer ' . $options['token']];
        } elseif (! empty($options['username']) && ! empty($options['apiKey'])) {
            $this->auth = ['X-Api-Username' => (string) $options['username'], 'X-Api-Key' => (string) $options['apiKey']];
        } elseif (! empty($options['_guest'])) {
            $this->auth = [];
        } else {
            throw new VasbayException('Pass a username + apiKey (from My Profile › API Access) or an app token.', 'config');
        }

        if ($this->baseUrl === '' || ! preg_match('#^https?://[^/\s]+#i', $this->baseUrl)) {
            throw new VasbayException('baseUrl must be an absolute URL, e.g. https://vasbay.com.', 'config');
        }
        if (! preg_match('#^https://#i', $this->baseUrl)
            && ! preg_match('#^http://(localhost|127\.0\.0\.1)(:\d+)?(/|$)#i', $this->baseUrl)
            && empty($options['allowInsecure'])) {
            throw new VasbayException('baseUrl must use https:// (set allowInsecure for local testing).', 'config');
        }
    }

    /**
     * Sign in with the panel login (for apps) and get a client that uses an app token; the token is in token().
     *
     * @param array<string, mixed> $options same as the constructor (baseUrl, timeout, httpClient, …)
     */
    public static function login(string $login, string $password, string $deviceName = 'App', array $options = []): self
    {
        $guest = new self(['_guest' => true] + $options);
        $data = $guest->request('POST', '/auth/login', ['login' => $login, 'password' => $password, 'device_name' => $deviceName])->data();
        unset($options['username'], $options['apiKey']);
        $client = new self(['token' => $data['token']] + $options);
        $client->user = $data['user'] ?? null;

        return $client;
    }

    /** sign this app token out (POST /auth/logout) */
    public function logout(): ApiResponse
    {
        return $this->request('POST', '/auth/logout');
    }

    /** the app token in use, or null when signed in with an API key */
    public function token(): ?string
    {
        return isset($this->auth['Authorization']) ? substr($this->auth['Authorization'], 7) : null;
    }

    /** GET / : API name and version, no sign-in needed. Handy to test connectivity. */
    public function ping(): ApiResponse
    {
        return $this->request('GET', '/', null, false);
    }

    /* ---------------- services ---------------- */

    public function sms(): SmsService
    {
        return $this->service(SmsService::class);
    }

    public function account(): AccountService
    {
        return $this->service(AccountService::class);
    }

    public function senderIds(): SenderIdService
    {
        return $this->service(SenderIdService::class);
    }

    public function templates(): TemplateService
    {
        return $this->service(TemplateService::class);
    }

    public function scheduled(): ScheduledService
    {
        return $this->service(ScheduledService::class);
    }

    public function groups(): GroupService
    {
        return $this->service(GroupService::class);
    }

    public function voice(): VoiceService
    {
        return $this->service(VoiceService::class);
    }

    /* ---------------- configuration ---------------- */

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /** @return array<string, string> request headers (authentication included) */
    public function getHeaders(): array
    {
        return ['Accept' => 'application/json', 'User-Agent' => 'vasbay-php-sdk/' . self::VERSION . ' PHP/' . PHP_VERSION] + $this->auth;
    }

    /* ---------------- transport ---------------- */

    /**
     * Call any v3 endpoint: GET data goes in the query string, other methods send it as a JSON body.
     *
     * @param string                    $path e.g. "/sms/send"
     * @param array<string, mixed>|null $data
     *
     * @throws AuthenticationException credentials refused (601, 606)
     * @throws ApiException            any other error reply of the API
     * @throws Exceptions\NetworkException no reply (network, timeout)
     * @throws VasbayException         the reply was not the API's JSON ("invalid_response")
     */
    public function request(string $method, string $path, ?array $data = null, bool $authenticate = true): ApiResponse
    {
        $method = strtoupper($method);
        $url = $this->baseUrl . self::API_PATH . ($path === '/' ? '' : '/' . ltrim($path, '/'));
        $headers = $this->getHeaders();
        if (! $authenticate) {
            $headers = array_diff_key($headers, $this->auth);
        }

        $body = null;
        if ($method === 'GET' || $method === 'DELETE') {
            if ($data) {
                $url .= '?' . http_build_query($data);
            }
        } else {
            $headers['Content-Type'] = 'application/json';
            $body = json_encode($data === null || $data === [] ? new \stdClass() : $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($body === false) {
                throw new VasbayException('Could not encode the request as JSON: ' . json_last_error_msg(), 'config');
            }
        }

        // no automatic retry: repeating a send could deliver the SMS twice
        $reply = $this->http->send($method, $url, $headers, $body, $this->timeout);
        $status = $reply->status();

        $json = json_decode($reply->body(), true);
        if (! is_array($json) || ! array_key_exists('success', $json)) {
            throw new VasbayException('Unexpected response (HTTP ' . $status . ').', 'invalid_response', $status);
        }
        if (empty($json['success'])) {
            $error = is_array($json['error'] ?? null) ? $json['error'] : [];
            $code = (string) ($error['code'] ?? $status);
            $message = (string) ($error['message'] ?? 'Request failed (HTTP ' . $status . ').');
            if (in_array($code, self::AUTH_ERRORS, true) || $status === 401) {
                throw new AuthenticationException($message, $code, $status, $json);
            }

            throw new ApiException($message, $code, $status, $json);
        }

        return new ApiResponse($json['data'] ?? null, is_array($json['meta'] ?? null) ? $json['meta'] : [], $status, $reply->headers());
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function service(string $class)
    {
        if (! isset($this->services[$class])) {
            $this->services[$class] = new $class($this);
        }

        return $this->services[$class];
    }
}
