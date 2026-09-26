import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AMBER_TEXT, POSITIVE_TEXT } from '@/components/investor/tokens';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type {
    C4HoldingDetail,
    C4InvestorHoldingProps,
    HoldingServicing,
    LateFeeBreakdown,
    Payout,
    SecondaryEligibility,
} from '@/types/investor';
import type {
    Bps,
    InstalmentStatus,
    LateFeeStatus,
    ServicingState,
} from '@/types/settlement';

const HEADING =
    'text-[13px] font-semibold tracking-[.04em] text-rz-secondary uppercase';

const CARD = 'mt-2.5 rounded-2xl border border-rz-border bg-rz-surface';

const PILL = 'shrink-0 rounded-[10px] px-[9px] py-1 text-[10.5px] font-bold';

const GOOD = cn('bg-[rgba(29,158,117,.10)]', POSITIVE_TEXT);
const WAIT = cn('bg-[rgba(194,102,31,.10)]', AMBER_TEXT);
const BAD = 'bg-[rgba(229,72,77,.10)] text-rz-danger-text';
const QUIET = 'bg-rz-surface-muted text-rz-secondary';

const STATE_TONE: Record<ServicingState, string> = {
    current: GOOD,
    due_today: WAIT,
    overdue: BAD,
    repaid: GOOD,
    defaulted: BAD,
};

/** `processing` is amber: the Business paid, but nothing is paid to the Investor yet. */
const INSTALMENT_TONE: Record<InstalmentStatus, string> = {
    upcoming: QUIET,
    due: WAIT,
    overdue: BAD,
    processing: WAIT,
    partially_paid: WAIT,
    paid: GOOD,
};

const LATE_FEE_TONE: Record<LateFeeStatus, string> = {
    projected: QUIET,
    assessed: WAIT,
    partially_collected: WAIT,
    collected: GOOD,
    waived: QUIET,
};

/** A rate in basis points as a percentage: 500 → "5". A rate, never an amount of money. */
const percent = (bps: Bps): string => String(bps / 100);

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section aria-label={title} className="mt-[18px]">
            <h2 className={HEADING}>{title}</h2>
            {children}
        </section>
    );
}

/** A figure and its value, with the drill-down to its basis when the server sends one. */
function Figure({
    label,
    value,
    basis,
    note,
}: {
    label: string;
    value: string;
    basis?: RouteLink;
    note?: string;
}) {
    const { t } = useTranslation();

    return (
        <div className="flex items-start justify-between gap-3 py-2">
            <dt className="min-w-0 text-[12.5px] text-rz-secondary">
                {label}
                {note !== undefined && (
                    <span className="block text-[10.5px] text-rz-slate">
                        {note}
                    </span>
                )}
            </dt>
            <dd className="shrink-0 text-right text-[12.5px] font-semibold text-rz-ink">
                {value}
                {basis !== undefined && (
                    <Link
                        href={basis}
                        aria-label={t('investor.servicing.basis_for', {
                            figure: label,
                        })}
                        className="block text-[10.5px] font-bold text-rz-accent-app-text"
                    >
                        {t('investor.servicing.basis')}
                    </Link>
                )}
            </dd>
        </div>
    );
}

/**
 * Where this holding's repayment stands (C4 v1 §4c): the server's state and days past due, every
 * independent restriction, what was received so far by component, and what is still scheduled —
 * labelled a projection, never a promise.
 */
export function ServicingSummary({
    servicing,
    bases,
}: {
    servicing: HoldingServicing;
    bases: C4InvestorHoldingProps['bases'];
}) {
    const { t, locale } = useTranslation();

    return (
        <Section title={t('investor.servicing.title')}>
            <div className={cn(CARD, 'px-[15px] py-3')}>
                <div className="flex flex-wrap items-center gap-2">
                    <span className={cn(PILL, STATE_TONE[servicing.state])}>
                        {t(`investor.servicing.state.${servicing.state}`)}
                    </span>
                    {servicing.dpd !== null && servicing.dpd > 0 && (
                        <span className="text-[11.5px] font-semibold text-rz-danger-text">
                            {t('investor.servicing.dpd', {
                                count: servicing.dpd,
                            })}
                        </span>
                    )}
                </div>
                {servicing.restrictions.length > 0 && (
                    <ul className="mt-2.5 flex flex-col gap-1.5">
                        {servicing.restrictions.map((restriction) => (
                            <li
                                key={restriction.code}
                                className="flex items-start gap-2 rounded-xl bg-[rgba(229,72,77,.06)] px-3 py-2 text-[11.5px] leading-normal text-rz-ink"
                            >
                                <Icon name="blocked" tone="red" />
                                <span className="flex-1">
                                    {t(
                                        `investor.servicing.restriction.${restriction.code}`,
                                        {
                                            date: formatDate(
                                                restriction.since,
                                                locale,
                                            ),
                                        },
                                    )}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
                <dl className="mt-1 divide-y divide-[#eef2f9] dark:divide-rz-divider">
                    <Figure
                        label={t('investor.servicing.received.principal')}
                        value={formatRwf(servicing.received.principal)}
                    />
                    <Figure
                        label={t('investor.servicing.received.return')}
                        value={formatRwf(servicing.received.return)}
                    />
                    <Figure
                        label={t('investor.servicing.received.late_fees')}
                        value={formatRwf(servicing.received.late_fees)}
                    />
                    <Figure
                        label={t('investor.servicing.received.fees')}
                        value={formatRwf(servicing.received.fees)}
                    />
                    <Figure
                        label={t('investor.servicing.received.net')}
                        value={formatRwf(servicing.received.net)}
                        basis={bases.received}
                    />
                    <Figure
                        label={t('investor.servicing.outstanding_principal')}
                        value={formatRwf(servicing.outstanding_principal)}
                        basis={bases.outstanding_principal}
                    />
                    <Figure
                        label={t('investor.servicing.remaining_projected')}
                        note={t('investor.servicing.projection')}
                        value={formatRwf(servicing.remaining_projected)}
                        basis={bases.remaining_projected}
                    />
                    <Figure
                        label={t('investor.servicing.next_payment')}
                        note={t('investor.servicing.projection')}
                        value={
                            servicing.next_payment === null
                                ? '—'
                                : t('investor.servicing.next_payment_value', {
                                      amount: formatRwf(
                                          servicing.next_payment.entitled,
                                      ),
                                      date: formatDate(
                                          servicing.next_payment.due_on,
                                          locale,
                                      ),
                                  })
                        }
                    />
                </dl>
            </div>
        </Section>
    );
}

/**
 * This holding's instalments and their component rights. `processing` means the Business paid and
 * the allocation has not posted: it reads "not yet paid to you" until a payout is credited.
 */
export function InstalmentList({
    instalments,
}: {
    instalments: C4HoldingDetail['instalments'];
}) {
    const { t, locale } = useTranslation();

    return (
        <Section title={t('investor.servicing.instalments')}>
            <ol className={cn(CARD, 'px-[15px] py-1')}>
                {instalments.map((instalment) => (
                    <li
                        key={instalment.index}
                        className="border-b border-[#eef2f9] py-2.5 last:border-b-0 dark:border-rz-divider"
                    >
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-[12.5px] font-semibold text-rz-ink">
                                {t('investor.servicing.instalment', {
                                    index: instalment.index,
                                    date: formatDate(instalment.due_on, locale),
                                })}
                            </span>
                            <span
                                className={cn(
                                    PILL,
                                    INSTALMENT_TONE[instalment.status],
                                )}
                            >
                                {t(
                                    `investor.servicing.instalment_status.${instalment.status}`,
                                )}
                            </span>
                        </div>
                        <p className="mt-1 text-[11.5px] text-rz-secondary">
                            {t('investor.servicing.entitled', {
                                principal: formatRwf(
                                    instalment.entitled.principal,
                                ),
                                return: formatRwf(instalment.entitled.return),
                            })}
                        </p>
                        <p className="mt-0.5 text-[11.5px] text-rz-secondary">
                            {t('investor.servicing.paid', {
                                principal: formatRwf(instalment.paid.principal),
                                return: formatRwf(instalment.paid.return),
                            })}
                        </p>
                        {instalment.payout !== null && (
                            <Link
                                href={instalment.payout.link}
                                className="mt-1 inline-block text-[11.5px] font-bold text-rz-accent-app-text"
                            >
                                {t('investor.servicing.payout_net', {
                                    amount: formatRwf(instalment.payout.net),
                                })}
                            </Link>
                        )}
                    </li>
                ))}
            </ol>
        </Section>
    );
}

/**
 * This holding's share of each late fee, line by line (#99 C7). Every line says it is payable only
 * if the business pays it and that Rozine does not guarantee it; nothing here is a promise.
 */
export function LateFeeBreakdownPanel({
    breakdown,
}: {
    breakdown: LateFeeBreakdown | null;
}) {
    const { t, locale } = useTranslation();

    if (breakdown === null) {
        return null;
    }

    return (
        <Section title={t('investor.servicing.late_fees.title')}>
            <ul className={cn(CARD, 'px-[15px] py-1')}>
                {breakdown.lines.map((line) => (
                    <li
                        key={line.id}
                        className="border-b border-[#eef2f9] py-2.5 last:border-b-0 dark:border-rz-divider"
                    >
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-[12.5px] font-semibold text-rz-ink">
                                {t('investor.servicing.late_fees.line', {
                                    index: line.instalment_index,
                                    step: t(
                                        `investor.servicing.late_fees.step.${line.step}`,
                                    ),
                                    rate: percent(line.rate_bps),
                                })}
                            </span>
                            <span
                                className={cn(PILL, LATE_FEE_TONE[line.status])}
                            >
                                {t(
                                    `investor.servicing.late_fees.status.${line.status}`,
                                )}
                            </span>
                        </div>
                        <p className="mt-0.5 text-[11px] text-rz-slate">
                            {t('investor.servicing.late_fees.applies', {
                                date: formatDate(line.applies_on, locale),
                            })}
                        </p>
                        <dl className="mt-1.5 grid grid-cols-3 gap-2 text-[11.5px]">
                            {(
                                [
                                    'assessed',
                                    'collected',
                                    'outstanding',
                                ] as const
                            ).map((key) => (
                                <div key={key} className="min-w-0">
                                    <dt className="text-rz-secondary">
                                        {t(
                                            `investor.servicing.late_fees.${key}`,
                                        )}
                                    </dt>
                                    <dd className="font-semibold text-rz-ink">
                                        {formatRwf(line.your_share[key])}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                        <ul
                            aria-label={t('investor.servicing.late_fees.terms')}
                            className="mt-1.5 flex flex-wrap gap-1.5"
                        >
                            <li className="rounded-md bg-[rgba(194,102,31,.09)] px-2 py-0.5 text-[10.5px] font-semibold text-[#a55418] dark:text-[#f0a060]">
                                {t(
                                    'investor.servicing.late_fees.collection_only',
                                )}
                            </li>
                            <li className="rounded-md bg-[rgba(194,102,31,.09)] px-2 py-0.5 text-[10.5px] font-semibold text-[#a55418] dark:text-[#f0a060]">
                                {t(
                                    'investor.servicing.late_fees.not_guaranteed',
                                )}
                            </li>
                        </ul>
                        {line.payout !== null && (
                            <Link
                                href={line.payout.link}
                                className="mt-1.5 inline-block text-[11.5px] font-bold text-rz-accent-app-text"
                            >
                                {t('investor.servicing.late_fees.payout')}
                            </Link>
                        )}
                    </li>
                ))}
            </ul>
            <dl className={cn(CARD, 'grid grid-cols-3 gap-2 px-[15px] py-3')}>
                {(['assessed', 'collected', 'outstanding'] as const).map(
                    (key) => (
                        <div key={key} className="min-w-0 text-[11.5px]">
                            <dt className="text-rz-secondary">
                                {t(`investor.servicing.late_fees.total.${key}`)}
                            </dt>
                            <dd className="font-bold text-rz-ink">
                                {formatRwf(breakdown.totals[key])}
                            </dd>
                        </div>
                    ),
                )}
            </dl>
            <p className="mt-2 text-[11px] leading-normal text-rz-secondary">
                {t('investor.servicing.late_fees.disclosure', {
                    version: breakdown.disclosure.version,
                })}
            </p>
        </Section>
    );
}

/** Payouts credited to the wallet, newest first: gross, the server's fee and its basis, and net. */
export function PayoutList({ payouts }: { payouts: Payout[] }) {
    const { t, locale } = useTranslation();

    return (
        <Section title={t('investor.servicing.payouts.title')}>
            {payouts.length === 0 ? (
                <p
                    className={cn(
                        CARD,
                        'px-[15px] py-3 text-xs text-rz-secondary',
                    )}
                >
                    {t('investor.servicing.payouts.empty')}
                </p>
            ) : (
                <ul className={cn(CARD, 'px-[15px] py-1')}>
                    {payouts.map((payout) => (
                        <li
                            key={payout.id}
                            className="border-b border-[#eef2f9] py-2.5 last:border-b-0 dark:border-rz-divider"
                        >
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-[12.5px] font-semibold text-rz-ink">
                                    {t('investor.servicing.payouts.row', {
                                        index: payout.instalment_index,
                                        date: formatDate(
                                            payout.credited_at,
                                            locale,
                                        ),
                                    })}
                                </span>
                                <span
                                    className={cn(
                                        'text-[12.5px] font-bold',
                                        POSITIVE_TEXT,
                                    )}
                                >
                                    {formatRwf(payout.net)}
                                </span>
                            </div>
                            <dl className="mt-1 grid grid-cols-[minmax(0,1fr)_auto] gap-x-3 gap-y-0.5 text-[11.5px]">
                                <dt className="text-rz-secondary">
                                    {t('investor.servicing.payouts.gross')}
                                </dt>
                                <dd className="text-right text-rz-ink">
                                    {formatRwf(payout.gross.total)}
                                </dd>
                                <dt className="text-rz-secondary">
                                    {t('investor.servicing.payouts.fee', {
                                        name: t(
                                            `investor.servicing.payouts.fee_code.${payout.fee_basis.code}`,
                                        ),
                                        rate: percent(
                                            payout.fee_basis.rate_bps,
                                        ),
                                    })}
                                </dt>
                                <dd className="text-right text-rz-ink">
                                    {formatRwf(payout.fee)}
                                </dd>
                                <dt className="text-rz-secondary">
                                    {t('investor.servicing.payouts.net')}
                                </dt>
                                <dd className="text-right font-semibold text-rz-ink">
                                    {formatRwf(payout.net)}
                                </dd>
                            </dl>
                            <Link
                                href={payout.link}
                                className="mt-1 inline-block text-[11.5px] font-bold text-rz-accent-app-text"
                            >
                                {t('investor.servicing.payouts.receipt', {
                                    reference: payout.receipt.reference,
                                })}
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </Section>
    );
}

/**
 * Exit options (XW SCR-05): the server's eligibility causes, in its order, and no action. Resale
 * is not open in C4, so this never offers a sale and never infers eligibility itself.
 */
export function ExitOptions({
    eligibility,
}: {
    eligibility: SecondaryEligibility;
}) {
    const { t, locale } = useTranslation();

    return (
        <Section title={t('investor.servicing.exit.title')}>
            <div className={cn(CARD, 'px-[15px] py-3')}>
                <p className="text-[12.5px] font-semibold text-rz-ink">
                    {t('investor.servicing.exit.unavailable')}
                </p>
                <ul className="mt-2 flex flex-col gap-1">
                    {eligibility.causes.map((cause) => (
                        <li
                            key={cause}
                            className="flex items-center gap-2 text-[11.5px] text-rz-secondary"
                        >
                            <span className="size-1.5 shrink-0 rounded-full bg-rz-slate" />
                            {t(`investor.servicing.exit.cause.${cause}`)}
                        </li>
                    ))}
                </ul>
                {eligibility.next_record_date !== null && (
                    <p className="mt-2 text-[11px] text-rz-slate">
                        {t('investor.servicing.exit.record_date', {
                            date: formatDate(
                                eligibility.next_record_date,
                                locale,
                            ),
                        })}
                    </p>
                )}
            </div>
        </Section>
    );
}
