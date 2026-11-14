<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Business\Contracts\CampaignTitles;
use App\Application\Business\Contracts\NoteServicing;
use App\Application\Business\Contracts\ServicingPayouts;
use App\Application\Business\Contracts\ServicingStatements;
use App\Application\Business\ServicingQuote;
use App\Application\Environment\UatMoneySimulation;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Primary\RetainedNoteInstalment;
use App\Application\Primary\RetainedNoteSchedule;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Models\BusinessRepayment;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;

/**
 * Servicing on UAT while its money simulation is on (Erastus, 14 Nov 2026), until the servicing
 * domain (S4-B, S4-D) replaces it. A note services from its issued schedule (#314): each instalment
 * falls due on its Kigali due date for its scheduled principal and return. The Business pays from
 * its wallet either everything due now or the next instalment early, always whole instalments at
 * their scheduled amount, so an instalment is either paid or not. No late fee, DPD charge or
 * auto-collection is simulated; an unpaid instalment past its date reads overdue with its days past
 * due. Once a repayment commits, it is paid on to the holders (`ServicingPayouts`).
 *
 * The servicing revision is one more than the repayments recorded on the note, so a payment quoted
 * before another one landed is refused `VERSION_CONFLICT` by the wallet.
 *
 * @phpstan-import-type Statement from ServicingStatements
 * @phpstan-import-type Components from ServicingStatements
 * @phpstan-import-type Instalment from ServicingStatements
 * @phpstan-import-type Recent from ServicingStatements
 */
final readonly class SimulatedNoteServicing implements NoteServicing, ServicingStatements
{
    public function __construct(private RetainedNoteSchedules $schedules, private CampaignTitles $titles, private UatMoneySimulation $simulation,
        private ServicingPayouts $payouts) {}

    public function lockForPayment(string $businessId, string $noteId): ?ServicingQuote
    {
        $this->assertSimulating();
        $noteId = strtolower($noteId);
        $campaignId = self::campaignId($noteId);
        $schedule = $campaignId === null ? null : $this->schedules->find($campaignId);
        if ($schedule === null || $schedule->businessId !== $businessId) {
            return null;
        }
        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['servicing-note:'.$noteId]);
        $paid = $this->paid($noteId);
        $options = [];
        foreach ($this->options($schedule, $paid) as $option) {
            $options[$option['key']] = ['total' => WalletMoney::of($option['amounts']['total']['amount']), 'instalment_indexes' => $option['instalment_indexes']];
        }

        return new ServicingQuote($noteId, $this->titles->title((string) $campaignId), $this->revision($noteId), $options);
    }

    public function applyRepayment(string $noteId, string $repaymentId, string $option, WalletMoney $amount, int $expectedRevision): int
    {
        $this->assertSimulating();
        $noteId = strtolower($noteId);
        if ($this->revision($noteId, $repaymentId) !== $expectedRevision) {
            throw new CommandRejection('VERSION_CONFLICT', 409, $this->revision($noteId, $repaymentId));
        }
        // Paid on once the repayment commits; one that fails is retried by `servicing:payouts` each minute.
        DB::afterCommit(function () use ($repaymentId): void {
            try {
                $this->payouts->payOut($repaymentId);
            } catch (Throwable $exception) {
                report($exception);
            }
        });

        return $expectedRevision + 1;
    }

    public function current(string $businessId): ?array
    {
        $this->assertSimulating();
        $repaid = null;
        foreach (DB::table('business_campaigns')->where('business_id', $businessId)->orderBy('id')->pluck('id') as $campaignId) {
            $schedule = $this->schedules->find((string) $campaignId);
            if ($schedule !== null && $schedule->businessId === $businessId) {
                $statement = $this->statement($schedule);
                if ($statement['servicing']['state'] !== 'repaid') {
                    return $statement;
                }
                $repaid = $statement;
            }
        }

        return $repaid;
    }

    /** @return Statement */
    private function statement(RetainedNoteSchedule $schedule): array
    {
        $noteId = strtolower($schedule->campaignId);
        $title = $this->titles->title($schedule->campaignId);
        $today = self::today();
        $paid = $this->paid($noteId);
        $repayments = BusinessRepayment::query()->where('note_id', $noteId)->orderBy('id')->get();
        $payouts = DB::table('servicing_payouts')->where('note_id', $noteId)->groupBy('repayment_id')
            ->selectRaw('repayment_id, count(DISTINCT party_id) AS investors')->pluck('investors', 'repayment_id');
        $settled = [];
        foreach ($repayments as $repayment) {
            foreach ($paid as $index => $paidBy) {
                if ($paidBy === $repayment->id) {
                    $settled[$index] = ['on' => CarbonImmutable::parse((string) $repayment->payload['recorded_at'])->setTimezone('Africa/Kigali')->toDateString(),
                        'allocated' => isset($payouts[$repayment->id])];
                }
            }
        }

        $rows = [];
        $repaid = WalletMoney::zero();
        $oldestOverdue = null;
        $dueToday = false;
        foreach ($schedule->instalments as $instalment) {
            $dueOn = substr($instalment->dueDate, 0, 10);
            $scheduled = self::components($instalment->principal, $instalment->contractualReturn);
            $done = $settled[$instalment->index] ?? null;
            if ($done !== null) {
                $repaid = $repaid->plus(WalletMoney::of($instalment->gross));
                $status = $done['allocated'] ? 'paid' : 'processing';
                $rows[] = ['index' => $instalment->index, 'due_on' => $dueOn, 'scheduled' => $scheduled, 'paid' => $scheduled,
                    'outstanding' => self::components('0', '0'), 'status' => $status, 'dpd' => null, 'paid_on' => $done['on'], 'late_fees' => []];

                continue;
            }
            $dpd = $dueOn <= $today ? (int) CarbonImmutable::parse($dueOn)->diffInDays(CarbonImmutable::parse($today)) : null;
            $status = $dueOn < $today ? 'overdue' : ($dueOn === $today ? 'due' : 'upcoming');
            $oldestOverdue ??= $status === 'overdue' ? $dpd : null;
            $dueToday = $dueToday || $status === 'due';
            $rows[] = ['index' => $instalment->index, 'due_on' => $dueOn, 'scheduled' => $scheduled, 'paid' => self::components('0', '0'),
                'outstanding' => $scheduled, 'status' => $status, 'dpd' => $dpd, 'paid_on' => null, 'late_fees' => []];
        }

        $total = WalletMoney::of($schedule->gross);
        $options = $this->options($schedule, $paid);
        $dueNow = array_values(array_filter($options, fn (array $option): bool => $option['key'] === 'due_now'))[0]['amounts'] ?? null;
        $next = array_values(array_filter($rows, fn (array $row): bool => $row['status'] !== 'paid' && $row['status'] !== 'processing'))[0] ?? null;
        $state = match (true) {
            $next === null => 'repaid',
            $oldestOverdue !== null => 'overdue',
            $dueToday => 'due_today',
            default => 'current',
        };
        $made = count($settled);
        $effective = (string) ($schedule->closing->effectiveDate ?? substr($schedule->instalments[0]->dueDate, 0, 10));

        return ['note' => ['id' => $noteId, 'title' => $title, 'disbursement_effective_date' => $effective,
            'due_day' => (int) substr($schedule->instalments[0]->dueDate, 8, 2)],
            'servicing' => ['note_id' => $noteId, 'revision' => $this->revision($noteId), 'state' => $state, 'dpd' => $oldestOverdue,
                'progress' => ['repaid' => $repaid->money(), 'total' => $total->money(), 'remaining' => $total->minus($repaid)->money(),
                    'repaid_pct' => (string) BigDecimal::of($repaid->amount())->multipliedBy(100)->dividedBy($total->amount(), 1, RoundingMode::HalfUp),
                    'payments_made' => $made, 'payments_total' => count($schedule->instalments), 'remaining_instalments' => count($schedule->instalments) - $made],
                'next' => $next === null ? null : ['index' => $next['index'], 'due_on' => $next['due_on'], 'amounts' => $next['outstanding']],
                'due_now' => $dueNow, 'next_late_fee' => null, 'restriction' => null, 'autocollect' => null],
            'schedule' => $rows, 'options' => $options,
            'recent' => array_map(fn (BusinessRepayment $repayment): array => $this->recent($repayment, $schedule, $title, $payouts[$repayment->id] ?? null),
                array_reverse(array_slice($repayments->all(), -5)))];
    }

    /**
     * What may be paid now: every unpaid instalment due on or before today, and the next unpaid
     * instalment not yet due, early at its scheduled amount.
     *
     * @param  array<int, string>  $paid
     * @return list<array{key: 'due_now'|'next_instalment', amounts: Components, instalment_indexes: list<int>}>
     */
    private function options(RetainedNoteSchedule $schedule, array $paid): array
    {
        $today = self::today();
        $unpaid = array_values(array_filter($schedule->instalments, fn (RetainedNoteInstalment $row): bool => ! isset($paid[$row->index])));
        $due = array_values(array_filter($unpaid, fn (RetainedNoteInstalment $row): bool => substr($row->dueDate, 0, 10) <= $today));
        $ahead = array_values(array_filter($unpaid, fn (RetainedNoteInstalment $row): bool => substr($row->dueDate, 0, 10) > $today))[0] ?? null;
        $options = [];
        if ($due !== []) {
            $options[] = ['key' => 'due_now', 'amounts' => self::sum($due), 'instalment_indexes' => array_map(fn (RetainedNoteInstalment $row): int => $row->index, $due)];
        }
        if ($ahead !== null) {
            $options[] = ['key' => 'next_instalment', 'amounts' => self::sum([$ahead]), 'instalment_indexes' => [$ahead->index]];
        }

        return $options;
    }

    /**
     * Each paid instalment index and the repayment that paid it.
     *
     * @return array<int, string>
     */
    private function paid(string $noteId): array
    {
        $paid = [];
        foreach (BusinessRepayment::query()->where('note_id', $noteId)->orderBy('id')->get() as $repayment) {
            /** @var list<int> $indexes */
            $indexes = $repayment->payload['instalment_indexes'];
            foreach ($indexes as $index) {
                $paid[$index] ??= $repayment->id;
            }
        }

        return $paid;
    }

    /**
     * The stored campaign ID for a note ID. Campaign IDs are kept in upper case, while servicing
     * records (and the pay request) carry the lower-case form the repayment schema requires.
     */
    public static function campaignId(string $noteId): ?string
    {
        $campaignId = DB::table('business_campaigns')->whereRaw('lower(id) = ?', [strtolower($noteId)])->value('id');

        return $campaignId === null ? null : (string) $campaignId;
    }

    private function revision(string $noteId, ?string $except = null): int
    {
        return BusinessRepayment::query()->where('note_id', $noteId)->when($except !== null, fn ($query) => $query->whereKeyNot($except))->count() + 1;
    }

    /** @return Recent */
    private function recent(BusinessRepayment $repayment, RetainedNoteSchedule $schedule, string $title, mixed $investors): array
    {
        /** @var list<int> $indexes */
        $indexes = $repayment->payload['instalment_indexes'];
        $rows = array_values(array_filter($schedule->instalments, fn (RetainedNoteInstalment $row): bool => in_array($row->index, $indexes, true)));
        $amount = WalletMoney::of($repayment->amount);
        $applied = self::sum($rows);

        return ['id' => $repayment->id, 'request_id' => $repayment->request_id, 'revision' => $repayment->servicing_revision, 'note_title' => $title,
            'amount' => $amount->money(), 'applied' => $applied, 'unapplied' => $amount->minus(WalletMoney::of($applied['total']['amount']))->money(),
            'allocation' => $investors === null ? 'allocating' : 'allocated', 'investors_paid' => $investors === null ? null : (int) $investors,
            'receipt' => ['receipt_id' => $repayment->id, 'operation_id' => $repayment->operation_id, 'request_id' => $repayment->request_id,
                'code' => 'REPAYMENT_RECEIVED', 'recorded_at' => $repayment->payload['recorded_at'], 'amount' => $amount->money(), 'units' => null,
                'reference' => 'RZR-'.strtoupper(substr($repayment->id, -10)), 'revision' => $repayment->servicing_revision + 1]];
    }

    /**
     * @param  list<RetainedNoteInstalment>  $rows
     * @return Components
     */
    private static function sum(array $rows): array
    {
        $principal = WalletMoney::zero();
        $return = WalletMoney::zero();
        foreach ($rows as $row) {
            $principal = $principal->plus(WalletMoney::of($row->principal));
            $return = $return->plus(WalletMoney::of($row->contractualReturn));
        }

        return self::components($principal->amount(), $return->amount());
    }

    /** @return Components */
    private static function components(string $principal, string $return): array
    {
        $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];

        return ['principal' => $money($principal), 'return' => $money($return), 'late_fees' => $money('0'),
            'total' => $money(WalletMoney::of($principal)->plus(WalletMoney::of($return))->amount())];
    }

    private static function today(): string
    {
        return CarbonImmutable::now('Africa/Kigali')->toDateString();
    }

    private function assertSimulating(): void
    {
        if (! $this->simulation->active()) {
            throw new LogicException('UAT_SIMULATION_ONLY: simulated servicing runs only on UAT with its money simulation on.');
        }
    }
}
