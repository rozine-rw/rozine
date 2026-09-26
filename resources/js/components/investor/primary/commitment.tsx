import { Link } from '@inertiajs/react';
import {
    formatOrdinals,
    RightsTable,
} from '@/components/investor/primary/rights';
import { POSITIVE_TEXT } from '@/components/investor/tokens';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { Commitment, CommitmentSummary } from '@/types/investor';
import type { Receipt } from '@/types/settlement';

type StatusKey =
    | 'confirmed'
    | 'awaiting_disbursement'
    | 'in_flight_pending'
    | 'in_flight_unknown'
    | 'issued'
    | 'cancelled'
    | 'expired'
    | 'failed_closing';

/**
 * Where a commitment stands, in the Investor's coarse terms (H2, H15). Funded commitments show only
 * whether the payout to the business is awaited or not yet confirmed; `unknown` is never paid,
 * issued, failed or refunded.
 */
export function commitmentStatus(
    commitment: Pick<Commitment, 'state' | 'closing'>,
): StatusKey {
    if (commitment.state !== 'funded') {
        return commitment.state;
    }

    if (commitment.closing?.stage === 'in_flight') {
        return commitment.closing.provider === 'unknown'
            ? 'in_flight_unknown'
            : 'in_flight_pending';
    }

    return 'awaiting_disbursement';
}

const STATUS_TONE: Record<StatusKey, string> = {
    confirmed: 'text-rz-accent-app-text',
    awaiting_disbursement: 'text-rz-accent-app-text',
    in_flight_pending: 'text-[#a55418] dark:text-[#f0a060]',
    in_flight_unknown: 'text-[#a55418] dark:text-[#f0a060]',
    issued: POSITIVE_TEXT,
    cancelled: 'text-rz-secondary',
    expired: 'text-rz-secondary',
    failed_closing: 'text-rz-secondary',
};

export function CommitmentStatus({
    commitment,
    className,
}: {
    commitment: Pick<Commitment, 'state' | 'closing'>;
    className?: string;
}) {
    const { t } = useTranslation();
    const status = commitmentStatus(commitment);

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 text-[11.5px] font-semibold',
                STATUS_TONE[status],
                className,
            )}
        >
            <span className="size-1.5 shrink-0 rounded-full bg-current" />
            {t(`investor.primary.status.${status}`)}
        </span>
    );
}

function ReceiptRows({ title, receipt }: { title: string; receipt: Receipt }) {
    const { t, locale } = useTranslation();

    return (
        <section aria-label={title} className="mt-3">
            <p className="text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase">
                {title}
            </p>
            <dl className="mt-1.5 rounded-xl border border-rz-border bg-rz-surface px-3 py-1">
                {(
                    [
                        [
                            'investor.primary.receipt.amount',
                            formatRwf(receipt.amount),
                        ],
                        [
                            'investor.primary.receipt.recorded',
                            formatDateTime(receipt.recorded_at, locale),
                        ],
                        [
                            'investor.primary.receipt.reference',
                            receipt.reference,
                        ],
                    ] as const
                ).map(([label, value], index) => (
                    <div
                        key={label}
                        className={cn(
                            'flex justify-between gap-3 py-1.5 text-xs',
                            index < 2 &&
                                'border-b border-[#eef2f9] dark:border-rz-divider',
                        )}
                    >
                        <dt className="text-rz-secondary">{t(label)}</dt>
                        <dd className="text-right font-semibold text-rz-ink">
                            {value}
                        </dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}

/**
 * One commitment in full (C3 v2 §2c): what was committed, the exact units and their undated rights,
 * the purchased terms and the immutable confirmation receipt, with the refund receipt once there is
 * one. A commitment is never a Holding: it links to its Holding only once one is issued.
 */
export function CommitmentCard({ commitment }: { commitment: Commitment }) {
    const { t } = useTranslation();
    const rows = [
        [
            'investor.primary.units',
            t('investor.primary.units_value', {
                count: Number(commitment.units),
                ordinals: formatOrdinals(commitment.ordinals),
            }),
        ],
        ['investor.primary.principal', formatRwf(commitment.principal)],
        [
            'investor.primary.terms',
            t('investor.checkout.deal_line', {
                rate: commitment.terms.rate_pct,
                count: commitment.terms.term_months,
            }),
        ],
        [
            'investor.checkout.payout_fee',
            formatRwf(commitment.terms.payout_fee),
        ],
        ['investor.primary.maturity', t('investor.primary.maturity_at_issue')],
        [
            'investor.primary.versions',
            `${commitment.terms.policy_version} · ${commitment.terms.disclosure_version}`,
        ],
    ] as const;

    return (
        <div>
            <div className="flex items-center justify-between gap-2">
                <p className="text-[15px] font-semibold text-rz-ink">
                    {commitment.deal_name}
                </p>
                <CommitmentStatus commitment={commitment} />
            </div>
            <p className="mt-1 text-xs leading-[1.5] text-rz-secondary">
                {t(
                    `investor.primary.status_body.${commitmentStatus(commitment)}`,
                    {
                        by: t(
                            `investor.primary.cancelled_by.${commitment.cancelled_by ?? 'none'}`,
                        ),
                    },
                )}
            </p>
            <dl className="mt-3 rounded-xl border border-rz-border bg-rz-surface px-3 py-1">
                {rows.map(([label, value], index) => (
                    <div
                        key={label}
                        className={cn(
                            'flex justify-between gap-3 py-1.5 text-xs',
                            index < rows.length - 1 &&
                                'border-b border-[#eef2f9] dark:border-rz-divider',
                        )}
                    >
                        <dt className="text-rz-secondary">{t(label)}</dt>
                        <dd className="text-right font-semibold break-words text-rz-ink">
                            {value}
                        </dd>
                    </div>
                ))}
            </dl>
            <RightsTable rights={commitment.rights} className="mt-3" />
            <ReceiptRows
                title={t('investor.primary.receipt.confirmation')}
                receipt={commitment.confirmation}
            />
            {commitment.refund !== null && (
                <ReceiptRows
                    title={t('investor.primary.receipt.refund')}
                    receipt={commitment.refund}
                />
            )}
            {commitment.holding !== null && (
                <Link
                    href={commitment.holding.link}
                    className="mt-3 flex h-11 w-full items-center justify-center rounded-xl bg-rz-accent-fill text-sm font-semibold text-white"
                >
                    {t('investor.primary.view_holding')}
                </Link>
            )}
        </div>
    );
}

/**
 * The portfolio's "Awaiting issue" section (C3 v2 §2d): confirmed and funded commitments, kept apart
 * from holdings and never counted among them.
 */
export function AwaitingIssue({
    commitments,
}: {
    commitments: CommitmentSummary[];
}) {
    const { t } = useTranslation();

    if (commitments.length === 0) {
        return null;
    }

    return (
        <section aria-label={t('investor.primary.awaiting_issue')}>
            <div className="flex items-center gap-2">
                <h2 className="text-sm font-semibold text-rz-ink">
                    {t('investor.primary.awaiting_issue')}
                </h2>
                <span className="text-[11px] text-rz-secondary">
                    {t('investor.primary.awaiting_issue_note')}
                </span>
            </div>
            <ul className="mt-2 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {commitments.map((commitment) => (
                    <li
                        key={commitment.id}
                        className="border-b border-[#eef2f9] last:border-0 dark:border-rz-divider"
                    >
                        <Link
                            href={commitment.link}
                            className="flex items-center gap-[11px] px-[13px] py-3"
                        >
                            <span className="flex size-8 shrink-0 items-center justify-center rounded-[10px] bg-rz-accent-soft text-[15px]">
                                <Icon name="hourglass" />
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-[12.5px] font-semibold text-rz-ink">
                                    {commitment.deal_name}
                                </span>
                                <CommitmentStatus
                                    commitment={commitment}
                                    className="mt-0.5"
                                />
                            </span>
                            <span className="text-right">
                                <span className="block text-[12.5px] font-semibold whitespace-nowrap text-rz-ink">
                                    {formatRwf(commitment.principal)}
                                </span>
                                <span className="block text-[11px] text-rz-secondary">
                                    {t('investor.primary.units_short', {
                                        count: Number(commitment.units),
                                    })}
                                </span>
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}
