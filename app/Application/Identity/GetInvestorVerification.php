<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\InvestorVerificationStore;
use App\Domain\Identity\IdentityViolation;

/** @phpstan-import-type Submission from InvestorVerificationStore */
final class GetInvestorVerification
{
    public function __construct(private InvestorVerificationStore $store) {}

    /** @return Submission */
    public function handle(int $userId): array
    {
        return $this->store->get($userId);
    }

    /**
     * What the person's current uploads are, by side, so their own page can show them again.
     *
     * @return array<'front'|'back'|'selfie', array{filename: string, media_type: string}>
     */
    public function uploads(int $userId): array
    {
        return $this->store->uploads($userId);
    }

    /**
     * The person's own current upload on one side, to look at before they submit.
     *
     * @return array{filename: string, media_type: string, content: string}
     */
    public function document(int $userId, string $side): array
    {
        return $this->store->ownDocument($userId, $side);
    }

    /**
     * Where an unverified person's own identity check stands, for pages that ask them to verify:
     * 'pending' once it is with Compliance, 'required' before that. Null for anyone it does not
     * describe (a verified person, an organisation, an unlinked sign-in), so those pages keep
     * their own wording.
     *
     * @return 'pending'|'required'|null
     */
    public function stage(int $userId): ?string
    {
        try {
            $submission = $this->store->get($userId);
        } catch (IdentityViolation) {
            return null;
        }

        return $submission['verified'] ? null : ($submission['status'] === 'submitted' ? 'pending' : 'required');
    }
}
