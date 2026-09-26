<?php

declare(strict_types=1);

use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
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
        $exceptions->dontFlash(['code', 'step_up', 'proof']);
        $exceptions->render(function (IdentityViolation $exception, Request $request) {
            if (! $request->expectsJson() && $request->routeIs('investor.home', 'business.home', 'auditor.home', 'auditor.profile',
                'auditor.jobs.index', 'auditor.jobs.show', 'auditor.conflicts.index', 'auditor.conflicts.show', 'auditor.reports.show', 'auditor.engagement.show', 'business.applications.show', 'business.audit-reports.show', 'staff.audit.show', 'staff.audit.operations.show', 'admin.home', 'identity.roles.resume')) {
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

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
