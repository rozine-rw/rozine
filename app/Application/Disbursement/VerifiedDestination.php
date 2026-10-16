<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

use App\Domain\Disbursement\IntentDigest;

/**
 * A Business payout destination as its owning source verified it (§10.8 tokenized beneficiary,
 * #96 answer 1). Its digest binds the destination revision to the verified Business, the mandate
 * that authorized it, the rail and account token, the environment and the verification evidence.
 * The masked label is for display only and never part of the binding.
 */
final readonly class VerifiedDestination
{
    public function __construct(
        public string $id,
        public int $revision,
        public string $businessId,
        public string $mandateId,
        public string $rail,
        public string $accountTokenSha256,
        public string $environment,
        public string $evidenceSha256,
        public string $verifiedAt,
        public string $expiresAt,
        public string $masked,
    ) {}

    public function digest(): string
    {
        return IntentDigest::destination(['destination_id' => $this->id, 'revision' => $this->revision, 'business_id' => $this->businessId,
            'mandate_id' => $this->mandateId, 'rail' => $this->rail, 'account_token_sha256' => $this->accountTokenSha256,
            'environment' => $this->environment, 'evidence_sha256' => $this->evidenceSha256, 'verified_at' => $this->verifiedAt,
            'expires_at' => $this->expiresAt]);
    }
}
