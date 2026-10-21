<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\StaffApplicationQueue;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessApplicationSubmission;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

final class EloquentStaffApplicationQueue implements StaffApplicationQueue
{
    /** Engine outcomes that refuse an application outright (UW-R03), as opposed to evidence still missing. */
    private const REJECTIONS = ['DSCR_BELOW_CUTOFF', 'RESTRICTION_ACTIVE'];

    public function __construct(private CanonicalJson $json, private RetainedStaffUnderwritingBasis $underwriting) {}

    /** @return array<string, mixed> */
    public function page(string $tab, string $search, ?string $before, int $limit, ?string $applicationId): array
    {
        $base = BusinessApplication::query()->where('status', 'submitted')->whereNotNull('current_submission_id');
        if ($search !== '') {
            $pattern = '%'.addcslashes($search, '%_\\').'%';
            $base->where(fn (Builder $query): Builder => $query->whereLike('draft->title', $pattern)->orWhere('id', strtolower($search)));
        }
        $released = BusinessApplicationRelease::query()->selectRaw('1')
            ->whereColumn('business_application_id', 'business_applications.id')->whereColumn('business_id', 'business_applications.business_id');
        $rejected = $this->rejected($search);
        $counts = ['pending' => (clone $base)->whereNotExists($released)->count(), 'approved' => (clone $base)->whereExists($released)->count(),
            'rejected' => count($rejected)];
        if ($tab === 'rejected') {
            $rows = array_values(array_filter($rejected, fn (array $row): bool => $before === null || $row['id'] < $before));

            return ['entries' => array_slice($rows, 0, $limit), 'selected' => null, 'underwriting_basis' => null, 'counts' => $counts,
                'next_cursor' => count($rows) > $limit ? $rows[$limit - 1]['id'] : null, 'tab' => $tab, 'search' => $search, 'before' => $before, 'limit' => $limit];
        }
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
        $underwritingBasis = null;
        foreach ($all as $application) {
            $submission = $submissions->get($application->current_submission_id);
            if ($submission === null) {
                throw new RuntimeException('APPLICATION_SUBMISSION_INTEGRITY_FAILED');
            }
            if ($application->id === $applicationId) {
                $underwritingBasis = $this->underwriting->project($application, $submission);
            }
            $rows[$application->id] = $this->row($application, $submission, $releases->get($application->id));
        }

        return ['entries' => $records->map(fn (BusinessApplication $application): array => $rows[$application->id])->values()->all(),
            'selected' => $applicationId === null ? null : $rows[$applicationId], 'underwriting_basis' => $underwritingBasis, 'counts' => $counts,
            'next_cursor' => $more ? $records->last()?->id : null, 'tab' => $tab, 'search' => $search, 'before' => $before, 'limit' => $limit];
    }

    /**
     * Drafts whose current quote the engine refused (UW-R03), newest first, each with the reason that
     * failed. A business holds at most one draft, so this reads every draft once.
     *
     * @return list<array<string, mixed>>
     */
    private function rejected(string $search): array
    {
        $drafts = BusinessApplication::query()->where('status', 'draft')->whereNotNull('current_quote_id')
            ->join('business_profiles', 'business_profiles.id', '=', 'business_applications.business_id')
            ->orderByDesc('business_applications.id')
            ->get(['business_applications.*', 'business_profiles.profile->name AS business_name', 'business_profiles.profile->industry AS business_sector']);
        $quotes = BusinessApplicationQuote::query()->whereKey($drafts->pluck('current_quote_id'))->get()->keyBy('id');
        $rows = [];
        foreach ($drafts as $application) {
            $quote = $quotes->get($application->current_quote_id);
            $result = $quote?->payload['result'] ?? [];
            $title = (string) ($quote?->payload['draft']['title'] ?? '');
            if (($result['eligible'] ?? true) || ! in_array($result['code'] ?? null, self::REJECTIONS, true)
                || ($search !== '' && ! str_contains(mb_strtolower($title), mb_strtolower($search)) && $application->id !== strtolower($search))) {
                continue;
            }
            $draft = $quote->payload['draft'];
            $rows[] = ['id' => $application->id, 'business' => (string) $application->getAttribute('business_name'),
                'sector' => (string) $application->getAttribute('business_sector'), 'note_title' => $title,
                'requested' => ['amount' => (string) $draft['target'], 'currency' => 'RWF'], 'term_months' => (int) $draft['term_months'],
                'rate_pct' => null, 'capacity_used_pct' => null, 'rating' => null, 'submitted_at' => (string) $quote->payload['evaluated_at'],
                'state' => 'rejected', 'decision' => ['code' => 'reject', 'reason' => '', 'reason_code' => (string) $result['code']]];
        }

        return $rows;
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
