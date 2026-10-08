<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Identity\GetInvestorVerification;
use App\Application\Wallet\Contracts\WalletStore;
use App\Domain\Identity\IdentityViolation;

/**
 * Who is reading an Investor page, under current Investor authority. A verified Investor gets
 * their wallet facts; a person whose identity is still unverified gets no wallet and the stage of
 * their verification instead, because verification comes before any investment (BRS FR-100,
 * CR-1), not before reading the Investor pages. Any other refusal stands.
 *
 * @phpstan-type Viewer array{identity_context_revision: int, wallet: array<string, mixed>|null, verification: 'required'|'pending'|null}
 */
final class GetInvestorViewer
{
    public function __construct(private WalletStore $wallets, private GetInvestorVerification $verification) {}

    /** @return Viewer */
    public function handle(int $userId, ?int $contextRevision): array
    {
        try {
            $wallet = $this->wallets->page($userId, $contextRevision, []);
        } catch (IdentityViolation $violation) {
            if ($violation->reason !== 'IDENTITY_VERIFICATION_REQUIRED') {
                throw $violation;
            }
            $submission = $this->verification->handle($userId);
            if ($submission['verified']) {
                throw $violation;
            }

            return ['identity_context_revision' => $submission['identity_context_revision'], 'wallet' => null,
                'verification' => $submission['status'] === 'submitted' ? 'pending' : 'required'];
        }

        return ['identity_context_revision' => (int) $wallet['identity_context_revision'], 'wallet' => $wallet, 'verification' => null];
    }
}
