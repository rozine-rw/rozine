<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\AuditorProfileController as WebAuditorProfileController;

/**
 * The Auditor Profile over `/api/v1`: the same protected actions and Resources as the web routes,
 * with each read and command also requiring its Sanctum token ability (`auditor:read` or
 * `auditor:command`).
 */
class AuditorProfileController extends WebAuditorProfileController {}
