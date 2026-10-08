<?php

declare(strict_types=1);

return [
    /*
    | Demo is a separate APP_ENV=demo installation, never a request parameter.
    | Both switches are opt-in. UAT/staging and production cannot enable them.
    | Destructive Artisan commands additionally require the reset switch.
    */
    'demo_enabled' => env('ROZINE_DEMO_ENABLED', false),
    'demo_reset_enabled' => env('ROZINE_DEMO_RESET_ENABLED', false),

    /* Engineering-owned temporary switches. Expiry is 00:00 UTC; review before extension. */
    'demo_flags' => [
        'owner' => 'Aminu and Erastus (Engineering/Security)',
        'reason' => 'Synthetic-only Phase 0 foundation verification; not the Phase 3 financial demo book.',
        'expires_at' => '2026-10-14',
    ],

    /*
    | There is no approved, implemented live-money module yet. This is not an
    | environment toggle: adding one requires its policy and provider gates.
    */
    'live_money_enabled' => false,

    /*
    | Staging real mail (MAIL_MAILER=smtp on uat) reaches only these
    | owner-approved testers, given as exact addresses or @domains separated by
    | commas, and at most this many messages per hour. Staging refuses to send
    | until both are set; demo and production ignore them.
    */
    'staging_mail' => [
        'recipients' => array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) env('STAGING_MAIL_RECIPIENTS', '')),
        ))),
        'hourly_limit' => filter_var(env('STAGING_MAIL_HOURLY_LIMIT'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
    ],
];
