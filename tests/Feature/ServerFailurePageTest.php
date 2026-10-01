<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->withoutVite();
    config(['app.debug' => false]);
    Route::middleware('web')->group(function (): void {
        Route::get('/test-failure/unavailable', fn () => throw new HttpException(503, 'db-host-10.0.0.7 is in maintenance'));
        Route::get('/test-failure/until/{status}', fn (Request $request, int $status) => throw new HttpException($status, 'down', null, ['retry-after' => $request->query('retry')]));
        Route::get('/test-failure/shared-prop', function (): never {
            Inertia::share('account', fn () => throw new RuntimeException('users store at db-host-10.0.0.7 unreachable'));

            throw new RuntimeException('db-host-10.0.0.7 refused secret_ledger_table');
        });
        Route::get('/test-failure/bad-gateway', fn () => throw new HttpException(502, 'upstream db-host-10.0.0.7'));
        Route::get('/test-failure/crash', fn () => throw new RuntimeException('db-host-10.0.0.7 refused secret_ledger_table'));
        Route::post('/test-failure/crash', fn () => throw new RuntimeException('db-host-10.0.0.7 refused secret_ledger_table'));
        Route::get('/test-failure/query', fn () => throw new QueryException('pgsql', 'select * from secret_ledger_table', [], new PDOException('connection to db-host-10.0.0.7 refused')));
        Route::get('/test-failure/forbidden', fn () => abort(403));
        Route::get('/test-failure/throttled', fn () => throw new HttpException(429, 'slow down'));
        Route::get('/test-failure/invalid', fn () => throw ValidationException::withMessages(['amount' => 'Too small.']));
        Route::get('/test-failure/signed-out', fn () => throw new AuthenticationException);
        Route::get('/test-failure/responded', fn () => throw new HttpResponseException(response('The upstream answer', 502)));
    });
    Route::get('/api/test-failure/crash', fn () => throw new RuntimeException('db-host-10.0.0.7 refused secret_ledger_table'));
});

/**
 * Asserts the response is the safe server-failure page with its status and no failure detail.
 *
 * @param  TestResponse<Response>  $response
 * @return TestResponse<Response>
 */
function assertServerFailurePage(TestResponse $response, int $status): TestResponse
{
    $response->assertStatus($status)
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied')
            ->where('code', 'SERVICE_UNAVAILABLE')->where('status', $status)->where('auth.user', null)
            ->where('nonLiveEnvironment', null)->has('locale')->missing('errors'));
    /* Details the failures carry, and traces would show: none may reach the page. */
    foreach (['db-host-10.0.0.7', 'secret_ledger_table', 'RuntimeException', 'QueryException', 'bootstrap/app.php', 'vendor/laravel'] as $secret) {
        $response->assertDontSee($secret, false);
    }

    return $response;
}

it('answers a full-page read that failed with 503 with the safe page, its status and a Retry-After', function (): void {
    assertServerFailurePage($this->withHeader('Accept-Language', 'rw')->get('/test-failure/unavailable'), 503)
        ->assertHeader('Retry-After', '60')
        ->assertInertia(fn (Assert $page): Assert => $page->where('locale', 'rw'));
    assertServerFailurePage($this->get('/test-failure/bad-gateway'), 502)->assertHeaderMissing('Retry-After');
});

it('passes a valid Retry-After the failure carried through and gives a 503 without one 60 seconds', function (): void {
    assertServerFailurePage($this->get('/test-failure/until/503?retry=120'), 503)->assertHeader('Retry-After', '120');
    assertServerFailurePage($this->get('/test-failure/until/503?retry='.urlencode('Wed, 30 Sep 2026 07:28:00 GMT')), 503)
        ->assertHeader('Retry-After', 'Wed, 30 Sep 2026 07:28:00 GMT');
    assertServerFailurePage($this->get('/test-failure/until/502?retry=30'), 502)->assertHeader('Retry-After', '30');
    foreach (['soon', '-5', '12.5', '', 'Wed, 32 Sep 2026 07:28:00 GMT', 'Wed, 30 Sep 2026 07:28:00 CET'] as $invalid) {
        assertServerFailurePage($this->get('/test-failure/until/503?retry='.urlencode($invalid)), 503)->assertHeader('Retry-After', '60');
        assertServerFailurePage($this->get('/test-failure/until/500?retry='.urlencode($invalid)), 500)->assertHeaderMissing('Retry-After');
    }
});

it('answers an unhandled failure with the safe 500 page when debug is off and still reports it', function (): void {
    Exceptions::fake();

    assertServerFailurePage($this->get('/test-failure/crash'), 500);
    assertServerFailurePage($this->get('/test-failure/query'), 500);

    Exceptions::assertReported(fn (RuntimeException $exception): bool => str_contains($exception->getMessage(), 'secret_ledger_table'));
    Exceptions::assertReported(QueryException::class);
});

it('renders the page without the shared-prop lookup when authentication itself failed', function (): void {
    Auth::resolveUsersUsing(fn () => throw new RuntimeException('users store at db-host-10.0.0.7 unreachable'));

    assertServerFailurePage($this->get('/test-failure/crash'), 500);
});

it('renders the page with minimal props when the shared props themselves throw', function (): void {
    app()->bind(HandleInertiaRequests::class, fn () => new class extends HandleInertiaRequests
    {
        public function share(Request $request): array
        {
            throw new RuntimeException('session store at db-host-10.0.0.7 unreachable');
        }
    });

    /* The lookup fails before the route runs, so the failure is the lookup's own: a 500. */
    assertServerFailurePage($this->get('/test-failure/unavailable'), 500);
});

it('drops shared props registered before the failure rather than resolving them for the page', function (): void {
    assertServerFailurePage($this->get('/test-failure/shared-prop'), 500)
        ->assertInertia(fn (Assert $page): Assert => $page->missing('account'));
});

it('leaves a signed-in person out of the page rather than reading their account again', function (): void {
    $this->actingAs(User::factory()->create());

    assertServerFailurePage($this->get('/test-failure/crash'), 500);
});

it("keeps Laravel's own answer for an unhandled failure when debug is on", function (): void {
    config(['app.debug' => true]);

    /* Laravel's debug page shows source around each frame, so it is told apart by the page JSON. */
    $this->get('/test-failure/crash')->assertInternalServerError()->assertSee('secret_ledger_table')
        ->assertDontSee('"component":"identity\\/access-denied"', false);
    assertServerFailurePage($this->get('/test-failure/unavailable'), 503);
});

it('keeps Inertia visits, JSON, the API and commands on their existing answers', function (): void {
    $version = (string) app(HandleInertiaRequests::class)->version(Request::create('/'));
    $inertia = ['X-Inertia' => 'true', 'X-Inertia-Version' => $version];

    $this->withHeaders($inertia)->get('/test-failure/crash')->assertInternalServerError()->assertHeaderMissing('X-Inertia')
        ->assertDontSee('SERVICE_UNAVAILABLE');
    $this->withHeaders($inertia)->get('/test-failure/unavailable')->assertServiceUnavailable()->assertHeaderMissing('X-Inertia')
        ->assertDontSee('SERVICE_UNAVAILABLE');
    $this->getJson('/test-failure/crash')->assertInternalServerError()->assertExactJson(['message' => 'Server Error']);
    $this->getJson('/test-failure/unavailable')->assertServiceUnavailable()->assertJsonMissingPath('component');
    $this->get('/api/test-failure/crash')->assertInternalServerError()->assertExactJson(['message' => 'Server Error']);
    $this->post('/test-failure/crash')->assertInternalServerError()->assertDontSee('SERVICE_UNAVAILABLE');
});

it('leaves 4xx answers, validation, sign-in redirects and responded exceptions unchanged', function (): void {
    $this->get('/test-failure/unmatched')->assertNotFound()
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied')->where('code', 'PAGE_NOT_FOUND')->where('status', 404));
    $this->get('/test-failure/forbidden')->assertForbidden()->assertDontSee('SERVICE_UNAVAILABLE');
    $this->get('/test-failure/throttled')->assertTooManyRequests()->assertDontSee('SERVICE_UNAVAILABLE');
    $this->from('/somewhere')->get('/test-failure/invalid')->assertRedirect('/somewhere')->assertSessionHasErrors('amount');
    $this->get('/test-failure/signed-out')->assertRedirect(route('login'));
    $this->get('/test-failure/responded')->assertStatus(502)->assertContent('The upstream answer');
});
