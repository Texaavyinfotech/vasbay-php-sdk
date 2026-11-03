# VASbay PHP SDK

The official PHP client for the [VASbay](https://vasbay.com) messaging API (v3). Send transactional,
marketing and international SMS, personalised bulk SMS and voice broadcast calls, follow delivery, and manage
sender IDs, DLT templates and scheduled sends, from any PHP application, with or without Laravel.

```php
use Texaavy\Vasbay\Client;

$vasbay = new Client(getenv('VASBAY_USERNAME'), getenv('VASBAY_API_KEY'));

$vasbay->sms()->send(['to' => '9895XXXXXX', 'message' => 'Your OTP is 4821. -Texaavy', 'senderId' => 'TXAAVY']);
$balance = $vasbay->account()->balance();
```

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Authentication](#authentication)
- [Initialising the client](#initialising-the-client)
- [SMS](#sms)
- [Voice calls](#voice-calls)
- [Account and balance](#account-and-balance)
- [Sender IDs, DLT templates, groups and scheduled SMS](#sender-ids-dlt-templates-groups-and-scheduled-sms)
- [Responses](#responses)
- [Error handling](#error-handling)
- [Laravel](#laravel)
- [Configuration reference](#configuration-reference)
- [WhatsApp and RCS](#whatsapp-and-rcs)
- [Testing](#testing)
- [Support](#support)

## Features

- **SMS**: single and multi-number sends, contact-group sends, bulk sends with your own reference per number,
  personalised (`#FIELD2#`) messages, scheduling, Unicode and flash SMS, DLT template / entity / content IDs.
- **Delivery**: status by send or by number, send history with delivery counts, every number of a send.
- **Voice (OBD)**: voice credits, audio files, caller IDs, send or schedule a campaign, results, cancel.
- **Account**: SMS balance, profile, notices.
- **Sender IDs and DLT templates**: list, look up, request / register, follow the review.
- **Scheduled SMS**: list, hold, resume, stop.
- API-key or app-token sign-in, configurable base URL (white-label panels), timeouts.
- Typed exceptions with the API's own error codes; no hidden retries that could send twice.
- No dependencies beyond `ext-curl` and `ext-json`. Optional Laravel facade and notification channel.

## Requirements

- PHP 7.4 or later, with the `curl` and `json` extensions
- A VASbay account with API access (My Profile › API Access)

The test suite has been run on PHP 8.5. The included GitHub Actions workflow runs it on PHP 7.4 to 8.4.

## Installation

```bash
composer require texaavy/vasbay-php-sdk
```

> Until the package is published on Packagist, install it from its Git repository:
>
> ```json
> {
>     "repositories": [{ "type": "vcs", "url": "https://github.com/<your-org>/vasbay-php-sdk" }],
>     "require": { "texaavy/vasbay-php-sdk": "^1.0" }
> }
> ```

## Authentication

The API accepts two kinds of credentials:

| Use | Credentials | Sent as |
| --- | --- | --- |
| Servers, scripts, plugins | **UID** (username) + **API key** from *My Profile › API Access* | `X-Api-Username` + `X-Api-Key` headers |
| Mobile / desktop apps | Panel login, exchanged for an app token | `Authorization: Bearer <token>` |

An API key works only together with its UID, and only on the panel domain the account belongs to and from the
IP addresses allowed for it.

Keep credentials in environment variables or your secrets manager, never in code under version control:

```bash
export VASBAY_USERNAME="your-uid"
export VASBAY_API_KEY="your-api-key"
```

## Initialising the client

```php
use Texaavy\Vasbay\Client;

// UID + API key
$vasbay = new Client(getenv('VASBAY_USERNAME'), getenv('VASBAY_API_KEY'));

// with options
$vasbay = new Client(getenv('VASBAY_USERNAME'), getenv('VASBAY_API_KEY'), [
    'baseUrl' => 'https://your-panel-domain.com',   // white-label panel; default https://vasbay.com
    'timeout' => 20,                                 // seconds, default 30
]);

// all as one array
$vasbay = new Client(['username' => '…', 'apiKey' => '…', 'baseUrl' => '…']);

// app token: sign in with the panel login
$vasbay = Client::login('login-name', 'password', 'My App');
$token = $vasbay->token();               // store it; later: new Client(['token' => $token])
$vasbay->logout();                       // signs this token out

// connectivity check, no sign-in needed
$vasbay->ping()['version'];              // "3.0"
```

## SMS

Parameters can be written in camelCase (`senderId`) or with the API's own names (`sender_id`).

```php
// one number
$sent = $vasbay->sms()->send([
    'to' => '9895XXXXXX',                     // or 'numbers'; a string, "98..,97.." or an array
    'message' => 'Your OTP is 4821. -Texaavy',
    'senderId' => 'TXAAVY',
]);
echo $sent['push_id'];

// all options
$vasbay->sms()->send([
    'to' => ['9895XXXXXX', '9744XXXXXX'],
    'groupIds' => [12, 15],                   // contact groups (see groups()); "to" are then extra numbers
    'message' => 'Diwali offer: 20% off today. -Texaavy',
    'senderId' => 'TXAAVY',
    'route' => 1,                             // 2 transactional (default), 1 marketing, 4 international
    'smsType' => 1,                           // 1 normal (default), 2 flash, 3 unicode
    'scheduleTime' => new DateTime('2026-12-25 10:30'),   // or "2026-12-25 10:30", panel time zone
    'removeDuplicate' => true,
    'templateId' => 41,
    'entityId' => '1701XXXXXXXXXXXXXXX',      // DLT ids as strings
    'contentId' => '1707XXXXXXXXXXXXXXX',
]);

// bulk with your own reference per number
$vasbay->sms()->sendBulk([
    'message' => 'Your order has shipped. -Texaavy',
    'senderId' => 'TXAAVY',
    'recipients' => [
        ['mobile' => '9895XXXXXX', 'ref' => 'ORD-1001'],
        ['mobile' => '9744XXXXXX', 'ref' => 'ORD-1002'],
    ],
]);

// personalised: #FIELD2#, #FIELD3#, … from "fields"
$vasbay->sms()->sendBulk([
    'message' => 'Dear #FIELD2#, your bill of Rs #FIELD3# is due. -Texaavy',
    'senderId' => 'TXAAVY',
    'recipients' => [['mobile' => '9895XXXXXX', 'ref' => 'INV-7', 'fields' => ['Anu', '1250']]],
]);

// delivery status
$vasbay->sms()->status($sent['push_id']);         // one send
$vasbay->sms()->status(['numberId' => 556677]);   // one number
$vasbay->sms()->status(['page' => 1]);            // latest numbers

// history (report) and the numbers of one send
$history = $vasbay->sms()->history(['from' => '2026-10-01', 'to' => '2026-10-31', 'via' => 'api', 'page' => 1]);
$failed = $vasbay->sms()->numbers($sent['push_id'], ['status' => 'failed']);
// status: delivered | sent | failed | dnd | unknown | stopped
```

## Voice calls

Voice broadcast (OBD) plays a recorded audio file to many numbers. The account needs the voice service;
audio files are uploaded in the panel (*Voice Call › Audio files*).

```php
$vasbay->voice()->balance();      // credits per route and billing type
$vasbay->voice()->files();        // ready audio files: id, name, seconds, pulses
$vasbay->voice()->callerIds();    // approved caller IDs and the default

$call = $vasbay->voice()->send([
    'fileId' => 12,
    'to' => ['9895XXXXXX', '9744XXXXXX'],      // or 'numbers'
    'route' => 2,                              // 1 promotional, 2 transactional
    'billingType' => 1,                        // 1/2 submission 15/30 s pulse, 3/4 answered 15/30 s pulse
    'callerId' => '04XXXXXXXX',
    'name' => 'Payment reminder',
    'scheduleTime' => '2026-11-10 10:00',      // optional
]);
$call->meta('ignored_numbers');                // numbers that were left out

$vasbay->voice()->calls(['from' => '2026-11-01', 'to' => '2026-11-30']);
$vasbay->voice()->call($call['id']);           // result of every number, pages of 100: call($id, 2)
$vasbay->voice()->cancel($call['id']);         // scheduled campaigns only, credits back
```

When route and billing type are left out, the first plan with credits is used.

## Account and balance

```php
$balance = $vasbay->account()->balance();
$balance['transactional'];        // float
$balance['marketing'];
$balance['international_eur'];

$vasbay->account()->me();             // id, username, UID, name, e-mail, company, account type, services
$vasbay->account()->notifications();  // notices, newest first
```

## Sender IDs, DLT templates, groups and scheduled SMS

```php
// sender IDs
$vasbay->senderIds()->list();                                   // all
$vasbay->senderIds()->list('approved');                         // approved | under_review | with_operator | rejected
$vasbay->senderIds()->list(null, ['senderId' => 'TXAAVY', 'route' => 2]);
$req = $vasbay->senderIds()->request([
    'senderId' => 'TXAAVY', 'route' => 2, 'entityId' => '1701XXXXXXXXXXXXXXX',
    'companyName' => 'Texaavy Infotech', 'explanation' => 'TeXAAVY = Texaavy Infotech',
    'purpose' => 'OTP and order updates', 'sampleSms' => 'Your OTP is 1234. -Texaavy',
]);
$vasbay->senderIds()->get($req['id'])['status'];

// DLT templates
$vasbay->templates()->list();                          // approved only
$vasbay->templates()->list(['status' => 'all']);
$t = $vasbay->templates()->submit([
    'type' => 'transactional', 'contentId' => '1707XXXXXXXXXXXXXXX',
    'senderId' => 'TXAAVY', 'text' => 'Your OTP is {#var#}. -Texaavy',
]);                                                    // entityId: the sender ID's when left out
$vasbay->templates()->get($t['id'])['status'];

// contact groups (read only)
$vasbay->groups()->list(['q' => 'customers']);
$vasbay->groups()->get(12);
$vasbay->groups()->contacts(12, ['page' => 2, 'perPage' => 100]);

// scheduled SMS
$vasbay->scheduled()->list(['status' => 'upcoming']);
$vasbay->scheduled()->hold($pushId);
$vasbay->scheduled()->resume($pushId);
$vasbay->scheduled()->stop($pushId);                   // refunds numbers not yet sent
$vasbay->scheduled()->stopDynamic($scheduleId);        // personalised sends, by schedule_id

// any other v3 endpoint
$vasbay->request('GET', '/sender-ids', ['status' => 'approved']);
```

## Responses

The API answers every call with one envelope:

```json
{ "success": true,  "data": { "push_id": 812345 }, "meta": { "page": 1, "total": 37 } }
{ "success": false, "error": { "code": "3076", "message": "Invalid Sender Id" } }
```

Successful calls return a `Texaavy\Vasbay\Responses\ApiResponse`, which reads like `data`:

```php
$r = $vasbay->sms()->history();

$r[0]['push_id'];            // array access to data
foreach ($r as $row) { … }   // iterate data
count($r);
$r->data();                  // data as sent
$r->toArray();               // data as an array
$r->meta();                  // ['page' => 1, 'per_page' => 20, 'total' => 37, …]
$r->meta('total');
$r->status();                // HTTP status: 200, or 201 for created
$r->rateLimitRemaining();    // requests left this minute (X-RateLimit-Remaining)
json_encode($r);             // the envelope again
```

## Error handling

Failures throw exceptions; nothing fails silently.

```
VasbayException                  any failure ("config", "invalid_response", …)
├── ApiException                 the API answered with an error
│   └── AuthenticationException  601 / 606: credentials refused or missing (HTTP 401)
└── NetworkException             no reply: "network" or "timeout"
```

```php
use Texaavy\Vasbay\Exceptions\ApiException;
use Texaavy\Vasbay\Exceptions\AuthenticationException;
use Texaavy\Vasbay\Exceptions\NetworkException;
use Texaavy\Vasbay\Exceptions\VasbayException;

try {
    $vasbay->sms()->send([...]);
} catch (AuthenticationException $e) {
    // wrong UID / API key, IP not allowed, or wrong panel domain
} catch (NetworkException $e) {
    // $e->isTimeout(). Requests are never retried automatically: a send that timed out may
    // still have been accepted, so check sms()->history() before sending again.
} catch (ApiException $e) {
    $e->getErrorCode();     // "3076"
    $e->getMessage();       // "Invalid Sender Id"
    $e->getHttpStatus();    // 422
    $e->getResponseBody();  // the decoded reply
} catch (VasbayException $e) {
    // configuration error, or a reply that was not the API's JSON
}
```

| HTTP | Codes | Meaning |
| --- | --- | --- |
| 401 | `601`, `606` | authentication failed / no credentials |
| 403 | `403` | service not enabled on the account (e.g. voice) |
| 404 | `602` | not found |
| 422 | `30xx`, `603`, `604` | validation, e.g. `3076` invalid sender ID, `3087` invalid request |
| 429 | `429` | rate limit (240 requests a minute per account) |

The full list of error codes is in the [API documentation](https://vasbay.com/api-documentation).

## Laravel

The SDK works in Laravel as it is. For convenience the package also contains an auto-discovered service provider
(Laravel 9 or later), a `Vasbay` facade and a `vasbay` notification channel. They are only loaded inside a Laravel
application, so the core package has no Laravel dependency.

`.env`:

```dotenv
VASBAY_USERNAME=your-uid
VASBAY_API_KEY=your-api-key
VASBAY_SENDER_ID=TXAAVY
# VASBAY_BASE_URL=https://your-panel-domain.com
# VASBAY_ROUTE=2
# VASBAY_TIMEOUT=30
```

Optionally publish the config file (`config/vasbay-sdk.php`):

```bash
php artisan vendor:publish --tag=vasbay-config
```

Use the client by injection or through the facade:

```php
use Texaavy\Vasbay\Client;
use Texaavy\Vasbay\Laravel\Facades\Vasbay;

public function __construct(private Client $vasbay) {}

Vasbay::sms()->send(['to' => $user->phone, 'message' => 'Your OTP is 4821. -Texaavy', 'senderId' => 'TXAAVY']);
Vasbay::account()->balance();
```

Notifications:

```php
use Texaavy\Vasbay\Laravel\VasbayMessage;

class OtpNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['vasbay'];
    }

    public function toVasbay($notifiable): VasbayMessage
    {
        return VasbayMessage::create("Your OTP is {$this->otp}. -Texaavy")
            ->from('TXAAVY')       // default: VASBAY_SENDER_ID
            ->template(41);        // optional; also ->route(1), ->at(now()->addHour())
    }
}

// the number: routeNotificationForVasbay() on the notifiable, or its "phone" attribute
public function routeNotificationForVasbay($notification): string
{
    return $this->mobile;
}
```

Without auto-discovery, register `Texaavy\Vasbay\Laravel\VasbayServiceProvider` yourself.

## Configuration reference

| Option | Default | |
| --- | --- | --- |
| `username` | – | UID from *My Profile › API Access* |
| `apiKey` | – | API key from *My Profile › API Access* |
| `token` | – | app token from `Client::login()`, instead of username + API key |
| `baseUrl` | `https://vasbay.com` | your panel domain on a white-label panel; `/api/v3` is added |
| `timeout` | `30` | seconds for the whole request (connecting: at most 10) |
| `allowInsecure` | `false` | permit `http://` base URLs other than localhost, for testing only |
| `httpClient` | `CurlHttpClient` | any `Texaavy\Vasbay\Http\HttpClientInterface`, e.g. to add a proxy or for tests |

TLS certificates are always verified and redirects are not followed.

## WhatsApp and RCS

The VASbay API v3 does not offer WhatsApp or RCS messaging at present (the former WhatsApp route is retired), so this
SDK has no WhatsApp or RCS methods. They will be added when the API supports them.

## Testing

```bash
composer install
composer test
```

The tests use mocked HTTP replies and a local PHP web server on 127.0.0.1; they never contact the live API
or send messages.

## Support

- Website: [vasbay.com](https://vasbay.com)
- API documentation: [vasbay.com/api-documentation](https://vasbay.com/api-documentation)
- Help with your account or credentials: contact VASbay support through [vasbay.com](https://vasbay.com)

## Licence

MIT © Texaavy Infotech Pvt Ltd. See [LICENSE](LICENSE).
