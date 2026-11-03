<?php

/*
 * VASbay PHP SDK, Laravel configuration. Put the credentials in .env, never in this file:
 *
 *   VASBAY_USERNAME=   UID from My Profile › API Access
 *   VASBAY_API_KEY=    API key from My Profile › API Access
 *   VASBAY_SENDER_ID=  an approved sender ID, e.g. TXAAVY
 *   VASBAY_BASE_URL=   your panel domain on a white-label panel (default https://vasbay.com)
 */
return [
    'base_url' => env('VASBAY_BASE_URL', 'https://vasbay.com'),
    'username' => env('VASBAY_USERNAME'),
    'api_key' => env('VASBAY_API_KEY'),

    // used by the notification channel when a message does not set its own
    'sender_id' => env('VASBAY_SENDER_ID'),
    'route' => (int) env('VASBAY_ROUTE', 2),      // 2 transactional, 1 marketing, 4 international

    'timeout' => (int) env('VASBAY_TIMEOUT', 30),
];
