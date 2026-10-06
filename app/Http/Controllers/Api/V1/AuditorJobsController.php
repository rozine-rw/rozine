<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\AuditorJobsController as WebAuditorJobsController;

/** Shared protected actions and projections; Sanctum abilities are checked on every route. */
class AuditorJobsController extends WebAuditorJobsController {}
