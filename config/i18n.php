<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | Locales the application will negotiate. This list is cross-checked against
    | the client catalogs in resources/js/lib/i18n/catalogs by the i18n policy
    | gate, so the server and the browser can never disagree about what ships.
    |
    | Which of these are complete at first pilot and first production release is
    | still D-07. Listing a locale here makes it negotiable, not launch-ready.
    |
    */

    'supported' => ['en', 'fr', 'rw'],

];
