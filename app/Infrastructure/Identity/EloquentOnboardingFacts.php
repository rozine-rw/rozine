<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\OnboardingFacts;
use App\Models\InvestorVerification;
use App\Models\Party;
use App\Models\RoleApplication;
use App\Models\User;
use DateTimeInterface;

/** Reads the hub's facts from the account, its Party, its identity check and its role application. */
final class EloquentOnboardingFacts implements OnboardingFacts
{
    public function facts(int $userId): ?array
    {
        $user = User::query()->find($userId);
        $party = $user?->party_id === null ? null : Party::query()->find($user->party_id);
        if ($user === null || $party === null) {
            return null;
        }
        $verification = InvestorVerification::query()->where('party_id', $party->id)->first();
        $application = RoleApplication::query()->where('party_id', $party->id)->first();

        return [
            'name' => (string) $user->name,
            'created_at' => self::at($user->created_at),
            'email_verified_at' => self::at($user->email_verified_at),
            'two_factor_at' => self::at($user->two_factor_confirmed_at),
            'kyc' => $this->kyc($party, $verification),
            'role' => $application === null ? null : $this->role($application),
            'roles' => array_values($party->memberships()->where('status', 'active')->orderBy('role')->pluck('role')
                ->map(fn (mixed $role): string => (string) $role)->all()),
        ];
    }

    /** @return array{status: 'none'|'draft'|'submitted'|'rejected'|'approved', submitted_at: ?string, approved_at: ?string, reason: ?string, items: list<array{key: string, label: string, note: string}>, requested_at: ?string} */
    private function kyc(Party $party, ?InvestorVerification $verification): array
    {
        $approved = $party->verified_at !== null && $party->verified_at->lte(now()) && $party->verifiedIdentity()->exists();
        $status = $approved ? 'approved' : match ($verification?->status) {
            'draft' => 'draft', 'submitted' => 'submitted', 'rejected' => 'rejected', default => 'none',
        };
        // Editing a sent-back check makes it a draft again; it still needs the changes Compliance asked for.
        $revision = $status === 'rejected' || $status === 'draft' ? ($verification->state['revision'] ?? null) : null;
        $status = is_array($revision) ? 'rejected' : $status;

        return [
            'status' => $status,
            'submitted_at' => self::at($verification?->submitted_at),
            'approved_at' => $approved ? self::at($party->verified_at) : null,
            'reason' => is_array($revision) ? $revision['reason'] : null,
            'items' => is_array($revision) ? self::items($revision['items']) : [],
            'requested_at' => is_array($revision) ? $revision['requested_at'] : null,
        ];
    }

    /** @return array{role: 'business'|'auditor'|'institution', status: 'draft'|'submitted'|'changes_requested'|'approved'|'rejected', company: ?string, submitted_at: ?string, decided_at: ?string, note: ?string, items: list<array{key: string, label: string, note: string}>, requested_at: ?string} */
    private function role(RoleApplication $application): array
    {
        $state = $application->state;
        $revision = $application->status === 'changes_requested' ? ($state['revision'] ?? null) : null;
        $company = $state['answers']['company']['legal_name'] ?? null;

        return [
            'role' => $application->role,
            'status' => match ($application->status) {
                'submitted' => 'submitted', 'changes_requested' => 'changes_requested', 'approved' => 'approved', 'rejected' => 'rejected', default => 'draft',
            },
            'company' => is_string($company) && $company !== '' ? $company : null,
            'submitted_at' => self::at($application->submitted_at),
            'decided_at' => self::at($application->decided_at),
            'note' => is_string($state['note'] ?? null) ? $state['note'] : null,
            'items' => is_array($revision) ? self::items($revision['items']) : [],
            'requested_at' => is_array($revision) ? $revision['requested_at'] : null,
        ];
    }

    /** @return list<array{key: string, label: string, note: string}> */
    private static function items(mixed $items): array
    {
        $list = [];
        foreach (is_array($items) ? $items : [] as $item) {
            if (is_array($item) && is_string($item['key'] ?? null) && is_string($item['label'] ?? null)) {
                $list[] = ['key' => $item['key'], 'label' => $item['label'], 'note' => is_string($item['note'] ?? null) ? $item['note'] : ''];
            }
        }

        return $list;
    }

    private static function at(?DateTimeInterface $at): ?string
    {
        return $at?->format(DATE_ATOM);
    }
}
