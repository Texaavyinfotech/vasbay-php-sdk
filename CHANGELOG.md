# Changelog

All notable changes to `texaavy/vasbay-php-sdk` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/). Releases are made from Git tags.

## [Unreleased]

## [1.0.0]

First Composer / Packagist release. Built from the PHP SDK that VASbay panels offer as a download
(`texaavy/sms-gateway` 3.1.0), on the same API v3 endpoints, parameters, authentication and error codes.

### Added
- `Texaavy\Vasbay\Client` with service groups: `sms()`, `account()`, `senderIds()`, `templates()`,
  `scheduled()`, `groups()`, `voice()`, plus `ping()`, `Client::login()`, `logout()` and `request()` for any endpoint.
- `new Client('UID', 'API_KEY')` positional constructor, besides the options array.
- Voice calls (OBD): balance, audio files, caller IDs, send / schedule, campaigns, campaign results, cancel.
- Contact groups (read only) and `sms()->numbers()` (`GET /sms/pushes/{id}/numbers`).
- `"to"` as an alias of `"numbers"`; parameters accepted in camelCase or the API's snake_case.
- `ApiResponse`: array access to `data`, plus `meta()` (paging, totals), `status()`, `header()`, `rateLimitRemaining()`.
- Exceptions: `VasbayException` → `ApiException` → `AuthenticationException`, and `NetworkException` (`isTimeout()`).
- Pluggable transport (`HttpClientInterface`, default `CurlHttpClient`).
- Optional Laravel integration: auto-discovered service provider, `Vasbay` facade, `vasbay` notification channel.
- PHPUnit test suite with mocked HTTP replies and a local cURL test server.

### Changed (compared with the downloadable SDK)
- Namespace `Texaavy\SmsGateway` → `Texaavy\Vasbay`; flat methods moved into service groups
  (`$sms->send()` → `$vasbay->sms()->send()`, `$sms->balance()` → `$vasbay->account()->balance()`, …).
- Methods return `ApiResponse` instead of a bare array (array access still works).
- Default base URL is `https://vasbay.com`; white-label panels pass their own domain as `baseUrl`.
- User-Agent `vasbay-php-sdk/<version>`.

[Unreleased]: https://github.com/texaavy/vasbay-php-sdk/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/texaavy/vasbay-php-sdk/releases/tag/v1.0.0
