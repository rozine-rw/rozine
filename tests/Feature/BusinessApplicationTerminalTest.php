<?php

declare(strict_types=1);

use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSubmission;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BusinessQuoteFixture;

it('keeps a submitted application terminal even under direct database mutations', function (string $change): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $request = (string) Str::uuid();
    $receipt = BusinessQuoteFixture::submit($fixture, $accepted, revision: 4, request: $request);
    $application = $fixture['application']->refresh();
    $before = $application->getRawOriginal();
    expect(fn () => DB::transaction(function () use ($application, $change): void {
        $query = DB::table('business_applications')->where('id', $application->id);
        if ($change === 'delete') {
            $query->delete();

            return;
        }
        $query->update(match ($change) {
            'reopen' => ['status' => 'draft', 'step' => 'review', 'current_submission_id' => null],
            'revision' => ['revision' => $application->revision + 1],
            'draft' => ['draft' => '{}'],
            'quote' => ['current_quote_id' => null],
            'submission' => ['current_submission_id' => null],
            default => throw new LogicException('Unknown submitted application mutation.'),
        });
    }))->toThrow(QueryException::class, 'Submitted business applications are immutable');
    expect($application->refresh()->getRawOriginal())->toBe($before)
        ->and(BusinessQuoteFixture::submit($fixture, $accepted, revision: 4, request: $request))->toBe($receipt);
})->with(['reopen', 'revision', 'draft', 'quote', 'submission', 'delete']);

it('requires submission status step and pointer to agree in PostgreSQL', function (string $change): void {
    $fixture = BusinessQuoteFixture::ready();
    $application = $fixture['application'];
    $submission = BusinessApplicationSubmission::factory()->create(['business_application_id' => $application->id,
        'business_application_quote_id' => $application->current_quote_id]);
    $before = $application->getRawOriginal();
    expect(fn () => DB::transaction(fn (): int => DB::table('business_applications')->where('id', $application->id)->update(match ($change) {
        'status' => ['status' => 'submitted'],
        'step' => ['step' => 'submitted'],
        'pointer' => ['current_submission_id' => $submission->id],
        'quote' => ['status' => 'submitted', 'step' => 'submitted', 'current_submission_id' => $submission->id, 'current_quote_id' => null],
        default => throw new LogicException('Unknown inconsistent application state.'),
    })))->toThrow(QueryException::class, 'business_application_submission_state');
    expect($application->refresh()->getRawOriginal())->toBe($before);
})->with(['status', 'step', 'pointer', 'quote']);

it('rejects a second immutable submission for the same application even with a different revision', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    expect(fn () => DB::transaction(fn (): BusinessApplicationSubmission => BusinessApplicationSubmission::factory()->create([
        'business_application_id' => $fixture['application']->id, 'business_application_quote_id' => $fixture['application']->refresh()->current_quote_id,
        'revision' => 999,
    ])))->toThrow(QueryException::class, 'application_submission_once');
    $this->assertDatabaseCount('business_application_submissions', 1);
});

it('enforces one unresolved submitted application per Business at the database boundary', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    $another = BusinessApplication::factory()->create(['business_id' => $fixture['audit']['business']]);
    $quote = BusinessApplicationQuote::factory()->create(['business_application_id' => $another->id]);
    $submission = BusinessApplicationSubmission::factory()->create(['business_application_id' => $another->id, 'business_application_quote_id' => $quote->id]);
    expect(fn () => DB::transaction(fn (): bool => $another->forceFill(['status' => 'submitted', 'step' => 'submitted',
        'current_quote_id' => $quote->id, 'current_submission_id' => $submission->id])->save()))->toThrow(QueryException::class, 'business_applications_one_pending');
    expect($another->refresh()->status)->toBe('draft')->and(BusinessApplication::query()->where('status', 'submitted')->count())->toBe(1);
});

it('refuses rollback after submission while preserving the terminal database guard and receipt', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    $before = $fixture['application']->refresh()->getRawOriginal();
    $migration = require database_path('migrations/2026_09_25_042503_protect_submitted_business_applications.php');
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Submitted business applications require a forward migration; rollback is refused');
    expect(fn () => DB::transaction(fn (): int => DB::table('business_applications')->where('id', $fixture['application']->id)->update(['revision' => 999])))
        ->toThrow(QueryException::class, 'Submitted business applications are immutable');
    expect($fixture['application']->refresh()->getRawOriginal())->toBe($before);
});

it('allows unused protection to be reversed and reapplied without changing draft or quote data', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $before = $fixture['application']->getRawOriginal();
    $migration = require database_path('migrations/2026_09_25_042503_protect_submitted_business_applications.php');
    $guard = fn (): bool => DB::table('pg_trigger')->where('tgname', 'business_application_submitted_immutable')
        ->whereRaw('tgrelid = ?::regclass', ['business_applications'])->exists();
    expect($guard())->toBeTrue();
    $migration->down();
    expect($guard())->toBeFalse()->and($fixture['application']->refresh()->getRawOriginal())->toBe($before);
    $migration->up();
    expect($guard())->toBeTrue()->and($fixture['application']->refresh()->getRawOriginal())->toBe($before)
        ->and(BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture))['code'])->toBe('APPLICATION_SUBMITTED');
});

it('does not make an unused draft undeletable', function (): void {
    $application = BusinessApplication::factory()->create();
    expect(DB::table('business_applications')->where('id', $application->id)->delete())->toBe(1);
});
