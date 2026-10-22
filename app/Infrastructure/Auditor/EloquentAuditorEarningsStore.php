<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditorEarningsStore;
use App\Application\Business\Contracts\AuditedRaises;
use App\Application\Disbursement\Contracts\DisbursementFeeFacts;
use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Domain\Identity\IdentityViolation;
use App\Models\AuditPartnerCredit;
use App\Models\AuditReport;
use App\Models\AuditReportSeal;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;

/**
 * The partner's platform fee shares, read under the current Auditor role and its Party lock. Each
 * credit names the Business as the partner sealed it (the seal's own record of it) and repeats the
 * raise and the fee its disbursement was opened under. The month is the Kigali calendar month. No
 * ledger entry pays the pooled `audit_partner_fee_payable` account out yet (it is only ever
 * credited), so every credit is still owed; a partner payout must be subtracted here once it exists.
 *
 * @phpstan-import-type Earnings from AuditorEarningsStore
 * @phpstan-import-type EarningLine from AuditorEarningsStore
 */
final class EloquentAuditorEarningsStore implements AuditorEarningsStore
{
    private const string ZONE = 'Africa/Kigali';

    /** The most deals the Portfolio lists; `deals` still counts them all. */
    private const int MANAGED = 50;

    public function __construct(private IdentityRepository $identities, private AuthorizeActiveRole $roles, private DisbursementFeeFacts $fees,
        private AuditedRaises $raises) {}

    /** @return Earnings */
    public function statement(int $userId, int $contextRevision, DateTimeImmutable $now): array
    {
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
        $local = $now->setTimezone(new DateTimeZone(self::ZONE));
        $start = new DateTimeImmutable($local->format('Y-m').'-01 00:00:00', new DateTimeZone(self::ZONE));

        return $this->roles->handle($userId, 'auditor', $partyId, $contextRevision, function () use ($partyId, $local, $start): array {
            $lines = $this->lines(AuditPartnerCredit::query()->where('auditor_party_id', $partyId)
                ->where('created_at', '>=', $start->setTimezone(new DateTimeZone('UTC'))));
            $total = (string) AuditPartnerCredit::query()->where('auditor_party_id', $partyId)->selectRaw('COALESCE(SUM(amount), 0)::text AS total')->value('total');

            return ['month' => $local->format('Y-m'), 'this_month' => self::sum(array_column($lines, 'amount')), 'total' => $total,
                'owed' => $total, 'deals' => AuditPartnerCredit::query()->where('auditor_party_id', $partyId)->count(),
                'lines' => $lines, 'managed' => $this->lines(AuditPartnerCredit::query()->where('auditor_party_id', $partyId)->limit(self::MANAGED)),
                'raising' => $this->raises->unfunded(array_values(AuditReport::query()->where('author_party_id', $partyId)->where('kind', 'flash')
                    ->whereIn('id', AuditReportSeal::query()->select('audit_report_id'))->pluck('application_id')->all()), self::MANAGED)];
        });
    }

    /**
     * The credits newest first, each with the Business as its seal names it and the raise and fee
     * its disbursement was opened under.
     *
     * @param  Builder<AuditPartnerCredit>  $credits
     * @return list<EarningLine>
     */
    private function lines(Builder $credits): array
    {
        $credits = $credits->orderByDesc('created_at')->orderByDesc('id')->get();
        $seals = AuditReportSeal::query()->whereIn('audit_report_id', $credits->pluck('audit_report_id')->all())->get()->keyBy('audit_report_id');
        $fees = $this->fees->forDisbursements(array_values($credits->pluck('disbursement_id')->all()));
        $lines = [];
        foreach ($credits as $credit) {
            /** @var array{business: array{profile: array{name: string, district: string}}} $sealed */
            $sealed = $seals[$credit->audit_report_id]->payload;
            $fee = $fees[$credit->disbursement_id];
            $lines[] = ['id' => $credit->id, 'business' => $sealed['business']['profile']['name'], 'district' => $sealed['business']['profile']['district'],
                'raise' => $fee['raise'], 'platform_fee' => $fee['platform_fee'], 'amount' => $credit->amount,
                'credited_at' => $credit->created_at->toIso8601String()];
        }

        return $lines;
    }

    /** @param  list<string>  $amounts  whole francs */
    private static function sum(array $amounts): string
    {
        return (string) array_sum(array_map(intval(...), $amounts));
    }
}
