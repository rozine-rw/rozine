import { formatOrdinals } from '@/components/investor/primary/rights';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatDateTime, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { IssuedHolding } from '@/types/investor';

const HEADING = 'text-sm font-semibold text-rz-ink';

/**
 * How a Holding was issued (C3 v2 §2d): the HOLDING_ISSUED receipt, the exact units, the local issue
 * time kept apart from the disbursement's authenticated effective instant and its Kigali effective
 * date, the purchased terms, and the schedule — the rights bound at confirmation, with the dates
 * attached at issue (H3, H17).
 */
export function IssueRecord({ issue }: { issue: IssuedHolding }) {
    const { t, locale } = useTranslation();
    const rows = [
        [
            'investor.primary.units',
            t('investor.primary.units_value', {
                count: Number(issue.units),
                ordinals: formatOrdinals(issue.ordinals),
            }),
        ],
        ['investor.primary.principal', formatRwf(issue.principal)],
        [
            'investor.holding.issue.issued_at',
            formatDateTime(issue.issued_at, locale),
        ],
        [
            'investor.holding.issue.effective_at',
            formatDateTime(issue.disbursement_effective_at, locale),
        ],
        [
            'investor.holding.issue.effective_date',
            formatDate(issue.effective_date, locale),
        ],
        [
            'investor.primary.terms',
            t('investor.checkout.deal_line', {
                rate: issue.terms.rate_pct,
                count: issue.terms.term_months,
            }),
        ],
        [
            'investor.primary.rights.total_return',
            formatRwf(issue.terms.total_return),
        ],
        [
            'investor.primary.versions',
            `${issue.terms.policy_version} · ${issue.terms.disclosure_version}`,
        ],
        ['investor.primary.receipt.reference', issue.issue_receipt.reference],
    ] as const;

    return (
        <section aria-label={t('investor.holding.issue.title')}>
            <h2 className={cn(HEADING, 'mt-[18px]')}>
                {t('investor.holding.issue.title')}
            </h2>
            <dl className="mt-2.5 rounded-2xl border border-rz-border bg-rz-surface px-3.5 py-1">
                {rows.map(([label, value], index) => (
                    <div
                        key={label}
                        className={cn(
                            'flex justify-between gap-3 py-2 text-xs',
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
            <h2 className={cn(HEADING, 'mt-[18px]')}>
                {t('investor.holding.issue.schedule')}
            </h2>
            <table className="mt-2.5 w-full overflow-hidden rounded-2xl border border-rz-border bg-rz-surface text-left text-xs">
                <thead>
                    <tr className="text-rz-secondary">
                        <th scope="col" className="px-3.5 py-2 font-semibold">
                            {t('investor.holding.issue.due')}
                        </th>
                        <th
                            scope="col"
                            className="px-3.5 py-2 text-right font-semibold"
                        >
                            {t('investor.primary.rights.principal')}
                        </th>
                        <th
                            scope="col"
                            className="px-3.5 py-2 text-right font-semibold"
                        >
                            {t('investor.primary.rights.return')}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {issue.schedule.map((row) => (
                        <tr
                            key={row.index}
                            className="border-t border-[#eef2f9] dark:border-rz-divider"
                        >
                            <td className="px-3.5 py-2 text-rz-slate">
                                {formatDate(row.due_on, locale)}
                            </td>
                            <td className="px-3.5 py-2 text-right font-semibold text-rz-ink tabular-nums">
                                {formatRwf(row.principal)}
                            </td>
                            <td className="px-3.5 py-2 text-right font-semibold text-rz-ink tabular-nums">
                                {formatRwf(row.return)}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </section>
    );
}
