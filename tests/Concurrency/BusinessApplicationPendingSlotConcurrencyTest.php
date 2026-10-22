<?php

declare(strict_types=1);

use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSubmission;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** Moves a draft to submitted with its quote and submission, on the default connection. */
function submitPendingCandidate(BusinessApplication $application): void
{
    $quote = BusinessApplicationQuote::factory()->create(['business_application_id' => $application->id]);
    $submission = BusinessApplicationSubmission::factory()->create(['business_application_id' => $application->id, 'business_application_quote_id' => $quote->id]);
    $application->forceFill(['status' => 'submitted', 'step' => 'submitted', 'current_quote_id' => $quote->id, 'current_submission_id' => $submission->id])->save();
}

it('keeps one pending application per Business when a contender works from a stale snapshot', function (string $isolation): void {
    $first = BusinessApplication::factory()->create();
    $businessId = $first->business_id;
    config(['database.connections.pending_slot_contender' => config('database.connections.pgsql')]);
    $contender = DB::connection('pending_slot_contender');
    $contender->statement("SET lock_timeout = '2s'");
    $contender->beginTransaction();
    $contender->statement('SET TRANSACTION ISOLATION LEVEL '.$isolation);
    expect($contender->table('business_applications')->where('business_id', $businessId)->where('status', 'submitted')->count())->toBe(0);

    submitPendingCandidate($first);

    $state = null;
    $default = DB::getDefaultConnection();
    DB::setDefaultConnection('pending_slot_contender');
    try {
        submitPendingCandidate(BusinessApplication::factory()->create(['business_id' => $businessId]));
    } catch (QueryException $refused) {
        $state = $refused->getCode();
    } finally {
        DB::setDefaultConnection($default);
        $contender->rollBack();
        DB::purge('pending_slot_contender');
    }

    expect($state)->toBeIn(['23505', '40001'])
        ->and(BusinessApplication::query()->where('business_id', $businessId)->where('status', 'submitted')->pluck('id')->all())->toBe([$first->id])
        ->and(DB::table('business_application_pending_slots')->where('business_id', $businessId)->value('business_application_id'))->toBe($first->id);
})->with(['READ COMMITTED', 'REPEATABLE READ', 'SERIALIZABLE']);
