import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { ReceiptFacts } from '@/components/admin/disbursements/panels';
import { formatTimestamp } from '@/components/admin/format';
import { CAPTION, Chip, EXPLAIN } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    Allocation,
    AllocationLine,
    ReconciliationCheck,
    RepaymentDetail,
    RepaymentState,
    ServicingSnapshot,
    Tone,
} from '@/types/admin';
import type { ProviderOutcomeState } from '@/types/settlement';

const HEADING =
    'mb-2.5 text-[12px] font-bold tracking-[.05em] text-[#7b8699] uppercase dark:text-rz-muted';

const BOX = 'rounded-[13px] border border-rz-hairline bg-rz-surface px-4 py-3';

const BLOCKED =
    'mt-2.5 rounded-xl border border-[#fdeaea] bg-[rgba(255,77,79,.06)] px-3.5 py-2.5 text-[12.5px] leading-[1.5] font-semibold text-[#c4373c] dark:border-[rgba(255,107,111,.25)] dark:text-[#ff6b6f]';

export const REPAYMENT_TONE: Record<RepaymentState, Tone> = {
    received: 'blue',
    allocated: 'green',
    exception: 'red',
};

const CHECK_TONE: Record<ReconciliationCheck['state'], Tone> = {
    unreconciled: 'grey',
    matched: 'green',
    exception: 'red',
};

/** Pending and unknown are "not yet confirmed": never the green or red of a verified outcome. */
const PROVIDER_TONE: Record<ProviderOutcomeState, Tone> = {
    pending: 'grey',
    unknown: 'amber',
    succeeded: 'green',
    failed: 'red',
};

export function RepaymentStateChip({ state }: { state: RepaymentState }) {
    const { t } = useTranslation();

    return (
        <Chip tone={REPAYMENT_TONE[state]}>
            {t(`admin.repayments.state.${state}`)}
        </Chip>
    );
}

function Block({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section aria-label={title} className="mt-5">
            <h3 className={HEADING}>{title}</h3>
            {children}
        </section>
    );
}

function Facts({ children }: { children: ReactNode }) {
    return (
        <dl className="grid grid-cols-[minmax(0,.9fr)_minmax(0,1.4fr)] gap-x-3 gap-y-1.5 text-[12px]">
            {children}
        </dl>
    );
}

/** A term and its value, or a dash when the server has none. */
function Fact({
    label,
    value,
    mono = false,
}: {
    label: string;
    value: string | null;
    mono?: boolean;
}) {
    return (
        <>
            <dt className={CAPTION}>{label}</dt>
            <dd
                className={cn(
                    'min-w-0 font-semibold break-all text-rz-ink',
                    mono && 'font-mono',
                )}
            >
                {value ?? '—'}
            </dd>
        </>
    );
}

const at = (iso: string | null): string | null =>
    iso === null ? null : formatTimestamp(iso);

/** The note's servicing as the server recorded it before this receipt, and after it posted. */
export function ServicingPanel({
    before,
    after,
}: {
    before: ServicingSnapshot;
    after: ServicingSnapshot | null;
}) {
    const { t } = useTranslation();

    const column = (label: string, snapshot: ServicingSnapshot | null) => (
        <div className={BOX}>
            <p className={cn('mb-1.5 text-[11px] font-semibold', CAPTION)}>
                {label}
            </p>
            {snapshot === null ? (
                <p className={cn('text-[12px]', EXPLAIN)}>
                    {t('admin.repayments.servicing.not_posted')}
                </p>
            ) : (
                <Facts>
                    <Fact
                        label={t('admin.repayments.servicing.state')}
                        value={t(
                            `admin.repayments.servicing.status.${snapshot.state}`,
                        )}
                    />
                    <Fact
                        label={t('admin.repayments.dpd')}
                        value={
                            snapshot.dpd === null ? null : String(snapshot.dpd)
                        }
                    />
                    <Fact
                        label={t('admin.repayments.component.principal')}
                        value={formatRwf(snapshot.outstanding.principal)}
                    />
                    <Fact
                        label={t('admin.repayments.component.return')}
                        value={formatRwf(snapshot.outstanding.return)}
                    />
                    <Fact
                        label={t('admin.repayments.component.late_fees')}
                        value={formatRwf(snapshot.outstanding.late_fees)}
                    />
                    <Fact
                        label={t('admin.repayments.component.service_fee')}
                        value={formatRwf(snapshot.outstanding.service_fee)}
                    />
                    <Fact
                        label={t('admin.repayments.component.total')}
                        value={formatRwf(snapshot.outstanding.total)}
                    />
                </Facts>
            )}
        </div>
    );

    return (
        <Block title={t('admin.repayments.servicing.title')}>
            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                {column(t('admin.repayments.servicing.before'), before)}
                {column(t('admin.repayments.servicing.after'), after)}
            </div>
        </Block>
    );
}

/**
 * Where the money came from: an internal wallet movement (no provider, no staff step), or an
 * authenticated inbound receipt whose provider outcome staff may see, references and all.
 */
export function SourcePanel({
    source,
}: {
    source: RepaymentDetail['source_detail'];
}) {
    const { t } = useTranslation();

    if (source.kind === 'wallet') {
        return (
            <Block title={t('admin.repayments.source.title')}>
                <div className={BOX}>
                    <Facts>
                        <Fact
                            label={t('admin.repayments.source.kind')}
                            value={t('admin.repayments.source.wallet')}
                        />
                        <Fact
                            label={t('admin.repayments.source.wallet_entry')}
                            value={source.wallet_entry_id}
                            mono
                        />
                    </Facts>
                </div>
                <p className={cn('mt-2 text-[12px] leading-[1.5]', EXPLAIN)}>
                    {t('admin.repayments.source.wallet_note')}
                </p>
            </Block>
        );
    }

    const { provider } = source;

    return (
        <Block title={t('admin.repayments.source.title')}>
            <div className={BOX}>
                <Facts>
                    <Fact
                        label={t('admin.repayments.source.kind')}
                        value={t('admin.repayments.source.inbound_receipt')}
                    />
                    <Fact
                        label={t('admin.repayments.source.receipt')}
                        value={source.receipt_id}
                        mono
                    />
                    <dt className={CAPTION}>
                        {t('admin.repayments.provider.state')}
                    </dt>
                    <dd>
                        <Chip tone={PROVIDER_TONE[provider.state]}>
                            {t(
                                `admin.disbursements.provider.${provider.state}`,
                            )}
                        </Chip>
                    </dd>
                    <Fact
                        label={t('admin.disbursements.outcome.reference')}
                        value={provider.provider_reference}
                        mono
                    />
                    <Fact
                        label={t('admin.disbursements.outcome.error_code')}
                        value={provider.error_code}
                        mono
                    />
                    <Fact
                        label={t('admin.disbursements.outcome.observed_at')}
                        value={at(provider.observed_at)}
                    />
                    <Fact
                        label={t('admin.disbursements.outcome.effective_at')}
                        value={at(provider.effective_at)}
                    />
                    <Fact
                        label={t('admin.disbursements.operation')}
                        value={provider.operation_id}
                        mono
                    />
                </Facts>
            </div>
            <p className={cn('mt-2 text-[12px] leading-[1.5]', EXPLAIN)}>
                {t('admin.repayments.provider.requery_note')}
            </p>
        </Block>
    );
}

/**
 * The reconciliation check at the policy tolerance (#99 N7). It is matched only when the server
 * says so; an exception stays blocked and is never called reconciled (H13), and nothing here
 * resolves it: that is Phase 2.
 */
export function ReconciliationPanel({ check }: { check: ReconciliationCheck }) {
    const { t } = useTranslation();

    return (
        <Block title={t('admin.repayments.reconciliation.title')}>
            <div className={BOX}>
                <div className="mb-2">
                    <Chip tone={CHECK_TONE[check.state]}>
                        {t(`admin.repayments.reconciliation.${check.state}`)}
                    </Chip>
                </div>
                <Facts>
                    <Fact
                        label={t('admin.repayments.reconciliation.expected')}
                        value={formatRwf(check.expected)}
                    />
                    <Fact
                        label={t('admin.repayments.reconciliation.observed')}
                        value={formatRwf(check.observed)}
                    />
                    <Fact
                        label={t('admin.repayments.reconciliation.difference')}
                        value={formatRwf(check.difference)}
                    />
                    <Fact
                        label={t('admin.repayments.reconciliation.tolerance')}
                        value={formatRwf(check.tolerance)}
                    />
                    <Fact
                        label={t('admin.repayments.reconciliation.checked_at')}
                        value={at(check.checked_at)}
                    />
                    <Fact
                        label={t('admin.repayments.reconciliation.policy')}
                        value={check.policy_version}
                        mono
                    />
                </Facts>
                {check.causes.length > 0 && (
                    <ul
                        aria-label={t('admin.disbursements.causes')}
                        className="mt-2.5 flex flex-wrap gap-1.5"
                    >
                        {check.causes.map((cause) => (
                            <li
                                key={cause}
                                className="rounded-md bg-[rgba(255,77,79,.08)] px-2 py-0.5 font-mono text-[11px] font-semibold text-[#c4373c] dark:text-[#ff6b6f]"
                            >
                                {cause}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
            {check.state === 'exception' && (
                <p role="alert" className={BLOCKED}>
                    {t('admin.repayments.reconciliation.blocked')}
                </p>
            )}
        </Block>
    );
}

function Lines({ title, lines }: { title: string; lines: AllocationLine[] }) {
    const { t } = useTranslation();

    return (
        <table className="mt-2 w-full text-left text-[12px]">
            <caption
                className={cn('text-left text-[11px] font-semibold', CAPTION)}
            >
                {title}
            </caption>
            <thead className="sr-only">
                <tr>
                    <th scope="col">
                        {t('admin.repayments.allocation.col.kind')}
                    </th>
                    <th scope="col">
                        {t('admin.repayments.allocation.col.account')}
                    </th>
                    <th scope="col">
                        {t('admin.repayments.allocation.col.amount')}
                    </th>
                </tr>
            </thead>
            <tbody>
                {lines.map((line) => (
                    <tr
                        key={`${line.kind}-${line.account_code}-${line.instalment_index ?? 'none'}`}
                        className="border-b border-[#f0f4fa] last:border-b-0 dark:border-rz-divider"
                    >
                        <td className="py-1.5 font-semibold text-rz-ink">
                            {t(`admin.repayments.allocation.kind.${line.kind}`)}
                            {line.instalment_index !== null && (
                                <span className="ml-1 text-rz-muted">
                                    {t(
                                        'admin.repayments.allocation.instalment',
                                        {
                                            index: line.instalment_index,
                                        },
                                    )}
                                </span>
                            )}
                            {line.holders !== null && (
                                <span className="ml-1 text-rz-muted">
                                    {t('admin.repayments.allocation.holders', {
                                        count: line.holders,
                                    })}
                                </span>
                            )}
                        </td>
                        <td className="py-1.5 font-mono text-[11px] text-rz-body">
                            {line.account_code}
                        </td>
                        <td className="py-1.5 text-right font-semibold text-rz-ink">
                            {formatRwf(line.amount)}
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

/**
 * Where the receipt went, as the core posted it: what it paid (§11.4 order, plus anything
 * unapplied), then what was distributed. Whether it balances is the core's own check.
 */
export function AllocationPanel({
    allocation,
}: {
    allocation: Allocation | null;
}) {
    const { t, locale } = useTranslation();

    return (
        <Block title={t('admin.repayments.allocation.title')}>
            {allocation === null ? (
                <p className={cn(BOX, 'text-[12px]', EXPLAIN)}>
                    {t('admin.repayments.allocation.pending')}
                </p>
            ) : (
                <div className={BOX}>
                    <div className="mb-1 flex flex-wrap items-center gap-2">
                        <Chip tone={allocation.balanced ? 'green' : 'red'}>
                            {t(
                                allocation.balanced
                                    ? 'admin.repayments.allocation.balanced'
                                    : 'admin.repayments.allocation.unbalanced',
                            )}
                        </Chip>
                        <span className="text-[12px] font-semibold text-rz-ink">
                            {t('admin.repayments.allocation.receipt', {
                                amount: formatRwf(allocation.receipt_amount),
                            })}
                        </span>
                    </div>
                    <Lines
                        title={t('admin.repayments.allocation.paid')}
                        lines={allocation.paid}
                    />
                    <Lines
                        title={t('admin.repayments.allocation.distributed')}
                        lines={allocation.distributed}
                    />
                    <p
                        className={cn(
                            'mt-2 text-[12px] leading-[1.5]',
                            EXPLAIN,
                        )}
                    >
                        {t('admin.repayments.allocation.entitlements', {
                            count: allocation.entitlements.holdings,
                            date: formatDate(
                                allocation.entitlements.record_date,
                                locale,
                            ),
                        })}
                    </p>
                    <p className={cn('mt-1 text-[12px]', CAPTION)}>
                        {t('admin.repayments.allocation.posted_at', {
                            at: at(allocation.posted_at) ?? '—',
                        })}
                    </p>
                    {allocation.ledger !== null && (
                        <Link
                            href={allocation.ledger}
                            className="mt-1.5 inline-block text-[12px] font-bold text-rz-accent-app-text"
                        >
                            {t('admin.repayments.allocation.ledger')}
                        </Link>
                    )}
                </div>
            )}
        </Block>
    );
}

/** Each immutable receipt the repayment recorded, as recorded. */
export function ReceiptsPanel({
    receipts,
}: {
    receipts: RepaymentDetail['receipts'];
}) {
    const { t } = useTranslation();

    if (receipts.length === 0) {
        return null;
    }

    return (
        <Block title={t('admin.repayments.receipts')}>
            <div className="flex flex-col gap-2">
                {receipts.map((receipt) => (
                    <div key={receipt.receipt_id} className={BOX}>
                        <ReceiptFacts receipt={receipt} />
                    </div>
                ))}
            </div>
        </Block>
    );
}
