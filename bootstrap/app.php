<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            // Ahead of Inertia: the shared locale prop and the root template's
            // <html lang> both read app()->getLocale(), so it has to be
            // negotiated before either is evaluated.
            SetLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['code', 'step_up', 'proof', 'step_up_proof']);
        $exceptions->render(function (IdentityViolation $exception, Request $request) {
            // A person still being verified browses the Investor deals until the role opens.
            if (! $request->expectsJson() && $request->routeIs('investor.home') && $exception->reason === 'IDENTITY_VERIFICATION_REQUIRED') {
                return redirect()->route('investor.deals');
            }

            if (! $request->expectsJson() && $request->routeIs('investor.home', 'investor.wallet', 'investor.verification', 'investor.portfolio', 'investor.profile', 'investor.verified', 'business.home', 'auditor.home', 'auditor.profile',
                'auditor.jobs.index', 'auditor.jobs.show', 'auditor.conflicts.index', 'auditor.conflicts.show', 'auditor.portfolio.index', 'auditor.reports.show', 'auditor.engagement.show', 'business.applications.show', 'business.applications.publish.show', 'business.campaigns.show', 'staff.applications.show', 'staff.applications.operations.show', 'business.audit-reports.show', 'staff.audit.show', 'staff.audit.operations.show', 'staff.disbursements.index', 'staff.disbursements.show', 'staff.investor-verifications.index', 'staff.staging-mail-testers.index', 'admin.home', 'identity.roles.resume')) {
                return Inertia::render('identity/access-denied', ['code' => $exception->reason, 'status' => $exception->status])
                    ->toResponse($request)->setStatusCode($exception->status);
            }

            return response()->json(['message' => $exception->reason, 'code' => $exception->reason], $exception->status);
        });

        // A command refused before it was recorded (an idempotency conflict, a lookup that found
        // nothing): its own code, and Laravel's errors bag with every 422. A page read in the
        // browser gets the error page instead of raw JSON; commands, lookups and the API keep JSON.
        // The page carries the status too, so a read that found nothing (404) reads as not found:
        // it is no account-access problem, and the public seal check reaches it with no account.
        $exceptions->render(function (CommandRejection $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*') && $request->isMethod('GET')) {
                return Inertia::render('identity/access-denied', ['code' => $exception->reason, 'status' => $exception->status])
                    ->toResponse($request)->setStatusCode($exception->status);
            }

            $body = ['message' => $exception->reason, 'code' => $exception->reason];
            if ($exception->status === 422) {
                $body['errors'] = (object) $exception->fieldErrors;
            }

            return response()->json($body, $exception->status);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*') && $request->isMethod('GET')) {
                return app(SetLocale::class)->handle($request, function (Request $request) {
                    $response = Inertia::render('identity/access-denied', [...app(HandleInertiaRequests::class)->share($request),
                        'code' => 'PAGE_NOT_FOUND', 'status' => 404])->toResponse($request)->setStatusCode(404);
                    $response->headers->set('Cache-Control', 'no-store, private');

                    return $response;
                });
            }
        });

        // A full-page browser read the server failed on (5xx) gets a safe Rozine page, not Laravel's
        // default: the original status, generic copy and a Retry that reads the same URL again, with
        // no exception detail. An unhandled throwable counts only when debug is off, so development
        // keeps Laravel's page. Inertia visits keep their client-side notice (#158); JSON, the API
        // and commands keep their answers; refusals and every 4xx keep their own contracts, and
        // reporting is untouched. The props are built here rather than by the shared-prop lookup,
        // because the failure may be the session, authentication or database that lookup touches.
        $exceptions->render(function (Throwable $exception, Request $request) {
            $status = match (true) {
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                $exception instanceof HttpResponseException, $exception instanceof AuthenticationException,
                $exception instanceof ValidationException, (bool) config('app.debug') => null,
                default => 500,
            };
            if ($status === null || $status < 500 || ! $request->isMethod('GET') || $request->hasHeader('X-Inertia') || $request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            return app(SetLocale::class)->handle($request, function (Request $request) use ($exception, $status) {
                Inertia::flushShared();
                $isolation = app(EnvironmentIsolation::class);
                $response = Inertia::render('identity/access-denied', [
                    'name' => config('app.name'),
                    'auth' => ['user' => null],
                    'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
                    'locale' => app()->getLocale(),
                    'nonLiveEnvironment' => $isolation->isIsolated() ? $isolation->profile() : null,
                    'code' => 'SERVICE_UNAVAILABLE',
                    'status' => $status,
                ])->toResponse($request)->setStatusCode($status);
                $response->headers->set('Cache-Control', 'private, no-store');
                // A valid Retry-After the failure carried (maintenance mode's --retry, say) passes
                // through on any 5xx; a 503 without one gets an explicit, bounded 60 seconds.
                $headers = array_change_key_case($exception instanceof HttpExceptionInterface ? $exception->getHeaders() : []);
                $retryAfter = is_scalar($headers['retry-after'] ?? null) ? trim((string) $headers['retry-after']) : '';
                $httpDate = DateTimeImmutable::createFromFormat('D, d M Y H:i:s \\G\\M\\T', $retryAfter, new DateTimeZone('UTC'));
                if (ctype_digit($retryAfter) || ($httpDate !== false && $httpDate->format('D, d M Y H:i:s \\G\\M\\T') === $retryAfter)) {
                    $response->headers->set('Retry-After', $retryAfter);
                } elseif ($status === 503) {
                    $response->headers->set('Retry-After', '60');
                }

                return $response;
            });
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
