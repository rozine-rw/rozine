<?php

declare(strict_types=1);

use App\Models\StaffAccount;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return [
    'models' => [
        'permission' => Permission::class,
        'role' => Role::class,
        'team' => null,
        'default_model' => StaffAccount::class,
    ],

    /** Staff authorization retains the dedicated account, MFA and command transaction checks. */
    'register_permission_check_method' => false,
    'teams' => false,
    'enable_wildcard_permission' => false,
];
