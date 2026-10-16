<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Provinces & Districts
    |--------------------------------------------------------------------------
    |
    | The administrative divisions of Rwanda, keyed by province. These power the
    | location selects on the Pulse waitlist and the validation rules that back
    | them, so a district is only ever accepted alongside its own province.
    |
    */

    'districts' => [
        'Kigali City' => ['Gasabo', 'Kicukiro', 'Nyarugenge'],
        'Northern' => ['Burera', 'Gakenke', 'Gicumbi', 'Musanze', 'Rulindo'],
        'Southern' => ['Gisagara', 'Huye', 'Kamonyi', 'Muhanga', 'Nyamagabe', 'Nyanza', 'Nyaruguru', 'Ruhango'],
        'Eastern' => ['Bugesera', 'Gatsibo', 'Kayonza', 'Kirehe', 'Ngoma', 'Nyagatare', 'Rwamagana'],
        'Western' => ['Karongi', 'Ngororero', 'Nyabihu', 'Nyamasheke', 'Rubavu', 'Rusizi', 'Rutsiro'],
    ],

];
