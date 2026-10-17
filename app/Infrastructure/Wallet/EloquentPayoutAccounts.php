<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\Contracts\PartyNotices;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Wallet\Contracts\PayoutAccounts;
use App\Application\Wallet\Contracts\PayoutAccountVerifier;
use App\Application\Wallet\SyntheticDepositAdmission;
use App\Domain\Identity\PartyNoticeKind;
use App\Domain\Operations\ChangeScope;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Wallet\PayoutAccount;
use App\Models\InvestorFundingMethod;
use App\Models\InvestorWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Links and unlinks the Investor's payout accounts (`investor_funding_methods`). A link is recorded
 * only once the verifier confirms the account is in the Investor's own name, and is verified from
 * that moment; an account already linked is refused. Unlinking sets the one-way revocation the
 * table allows. Both record a security notice (BRS NTF-R01 "payout-account change") and touch the
 * wallet's change feed once it has a wallet, so an open wallet page drops or gains the account.
 */
final class EloquentPayoutAccounts implements PayoutAccounts
{
    public function __construct(private AuthorizeActiveRole $authority, private OperationJournal $journal, private PayoutAccountVerifier $verifier,
        private SyntheticDepositAdmission $admission, private PartyNotices $notices, private ChangeFeed $changes) {}

    public function linkable(): bool
    {
        return $this->verifier->source() !== null;
    }

    public function link(int $userId, string $requestId, array $input): array
    {
        return $this->authority->handle($userId, 'investor', null, null, function (array $identity) use ($userId, $requestId, $input): array {
            $partyId = (string) $identity['party']['id'];
            $permitted = ['type' => $input['type'] ?? null, 'network' => $input['network'] ?? null, 'bank' => $input['bank'] ?? null,
                'number_sha256' => hash('sha256', is_string($input['number'] ?? null) ? $input['number'] : '')];

            return $this->journal->execute('party:'.$partyId, $userId, 'wallet.link_method', $requestId, 'party', $partyId, $permitted,
                function (): void {}, function () use ($partyId, $userId, $input): OperationResult {
                    $source = $this->verifier->source() ?? throw new CommandRejection('POLICY_INPUT_REQUIRED');
                    if (! $this->admission->admitsInvestor($partyId)) {
                        throw new CommandRejection('SYNTHETIC_TESTER_REQUIRED', 403);
                    }
                    $account = PayoutAccount::fromInput($input);
                    if (is_array($account)) {
                        throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: $account);
                    }
                    DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['payout-accounts|'.$partyId]);
                    $linked = InvestorFundingMethod::query()->where('party_id', $partyId)->whereNull('revoked_at')->orderBy('id')->get();
                    if ($linked->contains(fn (InvestorFundingMethod $method): bool => $account->sameAs($method->kind, $method->reference))) {
                        throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: ['number' => ['That account is already linked.']]);
                    }
                    $refusal = $this->verifier->refusal($account, (string) User::query()->whereKey($userId)->value('name'));
                    if ($refusal !== null) {
                        throw new CommandRejection('PAYOUT_ACCOUNT_NOT_VERIFIED', 422, fieldErrors: ['number' => [$refusal]]);
                    }
                    $at = now('UTC')->startOfSecond();
                    $method = new InvestorFundingMethod;
                    $method->forceFill(['party_id' => $partyId, 'kind' => $account->kind, 'label' => $account->label, 'masked' => $account->masked,
                        'reference' => $account->reference, 'verification_source' => $source, 'verified_at' => $at, 'created_at' => $at])->save();
                    $this->recorded($partyId, PartyNoticeKind::PayoutAccountLinked, $method, 'payout-account-linked:'.$method->id);

                    return new OperationResult('PAYOUT_ACCOUNT_LINKED', ['method_id' => $method->id, 'kind' => $method->kind,
                        'label' => $method->label, 'masked' => $method->masked], 1);
                });
        });
    }

    public function unlink(int $userId, string $requestId, string $methodId): array
    {
        return $this->authority->handle($userId, 'investor', null, null, function (array $identity) use ($userId, $requestId, $methodId): array {
            $partyId = (string) $identity['party']['id'];

            return $this->journal->execute('party:'.$partyId, $userId, 'wallet.unlink_method', $requestId, 'investor_funding_method', $methodId, [],
                function (): void {}, function () use ($partyId, $methodId): OperationResult {
                    $method = InvestorFundingMethod::query()->where('party_id', $partyId)->whereKey(strtolower($methodId))->whereNull('revoked_at')
                        ->lockForUpdate()->first() ?? throw new CommandRejection('PAYOUT_ACCOUNT_NOT_FOUND', 404);
                    $method->forceFill(['revoked_at' => max(now('UTC')->startOfSecond(), $method->verified_at)])->save();
                    $this->recorded($partyId, PartyNoticeKind::PayoutAccountUnlinked, $method, 'payout-account-unlinked:'.$method->id);

                    return new OperationResult('PAYOUT_ACCOUNT_UNLINKED', ['method_id' => $method->id], 1);
                });
        });
    }

    private function recorded(string $partyId, PartyNoticeKind $kind, InvestorFundingMethod $method, string $eventKey): void
    {
        $this->notices->record($partyId, $kind, ['account' => $method->label.' '.$method->masked], $eventKey);
        $walletId = InvestorWallet::query()->where('party_id', $partyId)->value('id');
        if (is_string($walletId)) {
            $this->changes->record(ChangeScope::party($partyId), 'wallet', $walletId);
        }
    }
}
