<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Business\Contracts\BusinessNoticeAudience;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Identity\Contracts\PartyNotices;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Wallet\Contracts\BusinessPayoutAccounts;
use App\Application\Wallet\Contracts\PayoutAccountVerifier;
use App\Application\Wallet\SyntheticDepositAdmission;
use App\Domain\Identity\PartyNoticeKind;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Wallet\PayoutAccount;
use App\Models\BusinessFundingMethod;
use Illuminate\Support\Facades\DB;

/**
 * Links and unlinks the Business's own accounts (`business_funding_methods`) under the
 * `business.wallet.withdraw` mandate permission, since a linked account is where the Business's money
 * can be sent. A link is recorded only once the verifier confirms the account is held in the
 * Business's registered name (BRS v2.0 KYC-R03), and is verified from that moment; an account already
 * linked is refused. Unlinking sets the one-way revocation the table allows. Both record a security
 * notice for the Business (BRS NTF-R01 "payout-account change").
 */
final class EloquentBusinessPayoutAccounts implements BusinessPayoutAccounts
{
    private const string PERMISSION = 'business.wallet.withdraw';

    public function __construct(private WithBusinessAuthority $authority, private OperationJournal $journal, private PayoutAccountVerifier $verifier,
        private SyntheticDepositAdmission $admission, private PartyNotices $notices, private BusinessNoticeAudience $audience) {}

    public function linkable(): bool
    {
        return $this->verifier->source() !== null && $this->admission->admitsBusiness();
    }

    public function link(int $userId, int $contextRevision, string $businessId, string $requestId, array $input): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, self::PERMISSION, null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $requestId, $input): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404));
                $permitted = ['identity_context_revision' => $contextRevision, 'type' => $input['type'] ?? null, 'network' => $input['network'] ?? null,
                    'bank' => $input['bank'] ?? null, 'number_sha256' => hash('sha256', is_string($input['number'] ?? null) ? $input['number'] : '')];

                return $this->journal->execute('party:'.$partyId, $userId, 'business.wallet.link_method', $requestId, 'business_profile', $business['id'], $permitted,
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, self::PERMISSION, null, fn (): bool => true);
                    },
                    function () use ($business, $input): OperationResult {
                        $source = $this->verifier->source() ?? throw new CommandRejection('POLICY_INPUT_REQUIRED');
                        if (! $this->admission->admitsBusiness()) {
                            throw new CommandRejection('SYNTHETIC_BUSINESS_FUNDING_OFF', 403);
                        }
                        $account = PayoutAccount::fromInput($input);
                        if (is_array($account)) {
                            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: $account);
                        }
                        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['business-payout-accounts|'.$business['id']]);
                        $linked = BusinessFundingMethod::query()->where('business_id', $business['id'])->whereNull('revoked_at')->orderBy('id')->get();
                        if ($linked->contains(fn (BusinessFundingMethod $method): bool => $account->sameAs($method->kind, $method->reference))) {
                            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: ['number' => ['That account is already linked.']]);
                        }
                        if ($this->verifier->refusal($account, $business['profile']['name']) !== null) {
                            throw new CommandRejection('PAYOUT_ACCOUNT_NOT_VERIFIED', 422,
                                fieldErrors: ['number' => ['This account is not in the business\'s registered name. Link an account the business holds.']]);
                        }
                        $at = now('UTC')->startOfSecond();
                        $method = new BusinessFundingMethod;
                        $method->forceFill(['business_id' => $business['id'], 'kind' => $account->kind, 'label' => $account->label, 'masked' => $account->masked,
                            'reference' => $account->reference, 'verification_source' => $source, 'verified_at' => $at, 'created_at' => $at])->save();
                        $this->recorded($business['id'], PartyNoticeKind::PayoutAccountLinked, $method, 'business-payout-account-linked:'.$method->id);

                        return new OperationResult('PAYOUT_ACCOUNT_LINKED', ['method_id' => $method->id, 'kind' => $method->kind,
                            'label' => $method->label, 'masked' => $method->masked], 1);
                    });
            });
    }

    public function unlink(int $userId, int $contextRevision, string $businessId, string $requestId, string $methodId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, self::PERMISSION, null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $requestId, $methodId): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404));

                return $this->journal->execute('party:'.$partyId, $userId, 'business.wallet.unlink_method', $requestId, 'business_funding_method', strtolower($methodId),
                    ['identity_context_revision' => $contextRevision],
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, self::PERMISSION, null, fn (): bool => true);
                    },
                    function () use ($business, $methodId): OperationResult {
                        $method = BusinessFundingMethod::query()->where('business_id', $business['id'])->whereKey(strtolower($methodId))->whereNull('revoked_at')
                            ->lockForUpdate()->first() ?? throw new CommandRejection('PAYOUT_ACCOUNT_NOT_FOUND', 404);
                        $method->forceFill(['revoked_at' => max(now('UTC')->startOfSecond(), $method->verified_at)])->save();
                        $this->recorded($business['id'], PartyNoticeKind::PayoutAccountUnlinked, $method, 'business-payout-account-unlinked:'.$method->id);

                        return new OperationResult('PAYOUT_ACCOUNT_UNLINKED', ['method_id' => $method->id], 1);
                    });
            });
    }

    private function recorded(string $businessId, PartyNoticeKind $kind, BusinessFundingMethod $method, string $eventKey): void
    {
        $this->notices->record($this->audience->entityParty($businessId), $kind, ['account' => $method->label.' '.$method->masked], $eventKey);
    }
}
