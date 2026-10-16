<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Environment\ManageStagingMailTesters;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The staging mail tester routes exist only on staging (404 elsewhere) and only for staff who may
 * manage the list (the staff refusal otherwise). Both answers come before a form request validates,
 * so a malformed change never reveals its validation rules to anyone outside that boundary. The
 * commands still recheck the permission under their own lock.
 */
class EnsureStagingMailTesterAccess
{
    public function __construct(private ManageStagingMailTesters $testers) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->testers->available(), 404);
        $this->testers->authorize((int) $request->user()?->getAuthIdentifier());

        return $next($request);
    }
}
