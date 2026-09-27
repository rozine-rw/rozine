<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\StaffApplicationQueue;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessApplicationSubmission;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

final class EloquentStaffApplicationQueue implements StaffApplicationQueue
{
    public function __construct(private CanonicalJson $json) {}

    /** @return array<string, mixed> */
    public function page(string $tab, string $search, ?string $before, int $limit, ?string $applicationId): array
    {
        $base = BusinessApplication::query()->where('status', 'submitted')->whereNotNull('current_submission_id');
        if ($search !== '') {
            $base->where(fn (Builder $query): Builder => $query->whereLike('draft->title', '%'.$search.'%')->orWhere('id', $search));
        }
        $released = BusinessApplicationRelease::query()->selectRaw('1')
            ->whereColumn('business_application_id', 'business_applications.id')->whereColumn('business_id', 'business_applications.business_id');
        $counts = ['pending' => (clone $base)->whereNotExists($released)->count(), 'approved' => (clone $base)->whereExists($released)->count()];
        $query = $tab === 'approved' ? (clone $base)->whereExists($released) : (clone $base)->whereNotExists($released);
        if ($before !== null) {
            $query->where('id', '<', $before);
        }
        $records = $query->orderByDesc('id')->limit($limit + 1)->get();
        $more = $records->count() > $limit;
        $records = $records->take($limit);
        $selected = null;
        if ($applicationId !== null) {
            $selected = BusinessApplication::query()->where('status', 'submitted')->whereNotNull('current_submission_id')->find($applicationId);
            if ($selected === null) {
                throw new CommandRejection('APPLICATION_NOT_FOUND', 404);
            }
        }
        $all = $selected === null ? $records : $records->concat([$selected])->unique('id');
        $submissions = BusinessApplicationSubmission::query()->whereKey($all->pluck('current_submission_id'))->get()->keyBy('id');
        $releases = BusinessApplicationRelease::query()->whereIn('business_application_id', $all->pluck('id'))->get()->keyBy('business_application_id');
        $rows = [];
        foreach ($all as $application) {
            $submission = $submissions->get($application->current_submission_id);
            if ($submission === null) {
                throw new RuntimeException('APPLICATION_SUBMISSION_INTEGRITY_FAILED');
            }
            $rows[$application->id] = $this->row($application, $submission, $releases->get($application->id));
        }

        return ['entries' => $records->map(fn (BusinessApplication $application): array => $rows[$application->id])->values()->all(),
            'selected' => $applicationId === null ? null : $rows[$applicationId], 'counts' => $counts,
            'next_cursor' => $more ? $records->last()?->id : null, 'tab' => $tab, 'search' => $search, 'before' => $before, 'limit' => $limit];
    }

    /** @return array<string, mixed> */
    private function row(BusinessApplication $application, BusinessApplicationSubmission $submission, ?BusinessApplicationRelease $release): array
    {
        $payload = $submission->payload;
        if (! hash_equals($submission->sha256, hash('sha256', $this->json->encode($payload)))
            || $submission->business_application_id !== $application->id || $payload['submission_id'] !== $submission->id
            || $payload['application_id'] !== $application->id || $payload['application_revision'] !== $submission->revision
            || $payload['quote_id'] !== $submission->business_application_quote_id || $payload['binding_sha256'] !== $submission->binding_sha256
            || hash('sha256', $this->json->encode($payload['agreement'])) !== $submission->binding_sha256
            || $payload['review']['application']['business_id'] !== $application->business_id) {
            throw new RuntimeException('APPLICATION_SUBMISSION_INTEGRITY_FAILED');
        }
        if ($release !== null) {
            $receipt = $release->payload;
            if (! hash_equals($release->sha256, hash('sha256', $this->json->encode($receipt)))
                || $release->business_id !== $application->business_id || $receipt['release_id'] !== $release->id
                || $receipt['application_id'] !== $application->id || $receipt['business_id'] !== $application->business_id
                || $receipt['exposure_reservation_id'] !== $release->exposure_reservation_id || $receipt['actor_user_id'] !== $release->actor_user_id) {
                throw new RuntimeException('APPLICATION_RELEASE_INTEGRITY_FAILED');
            }
        }
        $quote = $payload['review']['quote'];
        $draft = $payload['review']['application']['draft'];
        $evidence = $payload['public_evidence'];

        return ['id' => $application->id, 'business' => $evidence['business']['name'], 'sector' => $evidence['business']['industry'],
            'note_title' => $draft['title'], 'requested' => $quote['requested_principal'], 'term_months' => $quote['term_months'],
            'rate_pct' => $quote['rate_pct'], 'capacity_used_pct' => null, 'rating' => $evidence['rating'], 'submitted_at' => $payload['submitted_at'],
            'state' => $release === null ? 'submitted' : 'approved',
            'decision' => ['code' => 'review', 'reason' => '', 'reason_code' => 'CURRENT_RELEASE_REVIEW_REQUIRED'],
            'capacity' => ['used_pct' => null, 'approved' => $quote['offered_principal'], 'active_notes' => null, 'outstanding' => null],
            'use_of_funds' => implode(', ', $draft['use_of_funds'])];
    }
}
