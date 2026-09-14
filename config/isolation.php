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

    /*
    | There is no approved, implemented live-money module yet. This is not an
    | environment toggle: adding one requires its policy and provider gates.
    */
    'live_money_enabled' => false,
];
