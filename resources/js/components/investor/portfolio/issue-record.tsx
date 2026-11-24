import { disclosureRow } from '@/components/investor/primary/commitment';
import { earningsFeeRate } from '@/components/investor/primary/earnings-fee';
import { formatOrdinals } from '@/components/investor/primary/rights';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatDateTime, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { HoldingScheduleRow, IssuedHolding } from '@/types/investor';

const HEADING = 'text-sm font-semibold text-rz-ink';

/**
 * How a Holding was issued (C3 v2 §2d): the HOLDING_ISSUED receipt, the exact units, the local issue
 * time kept apart from the disbursement's authenticated effective instant and its Kigali effective
 * date, the purchased terms, and the schedule — the rights bound at confirmation, with the dates
 * attached at issue (H3, H17). Units since sold on the market are shown apart, with those still held.
 */
export function IssueRecord({
    issue,
    resold = '0',
    schedule,
}: {
    issue: IssuedHolding;
    /** Units of the Holding sold on the market since issue. */
    resold?: string;
    /**
     * The schedule as it stands now: paid instalments with what was paid and when, the rest for
     * the units still held. Without it the table shows the schedule as issued.
     */
    schedule?: HoldingScheduleRow[];
}) {
    const { t, locale } = useTranslation();
    const rowsNow: HoldingScheduleRow[] =
        schedule ??
        issue.schedule.map((row) => ({ ...row, paid_on: null, net: null }));
    const rows: [string, string][] = [
        [
            t('investor.primary.units'),
            t('investor.primary.units_value', {
                count: Number(issue.units),
                ordinals: formatOrdinals(issue.ordinals),
            }),
        ],
        ...(resold === '0'
            ? []
            : ([
                  [
                      t('investor.holding.issue.resold'),
                      t('investor.holding.issue.resold_value', {
                          count: Number(resold),
                          held: Number(issue.units) - Number(resold),
                      }),
                  ],
              ] as [string, string][])),
        [t('investor.primary.principal'), formatRwf(issue.principal)],
        [
            t('investor.holding.issue.issued_at'),
            formatDateTime(issue.issued_at, locale),
        ],
        [
            t('investor.holding.issue.effective_at'),
            formatDateTime(issue.disbursement_effective_at, locale),
        ],
        [
            t('investor.holding.issue.effective_date'),
            formatDate(issue.effective_date, locale),
        ],
        [
            t('investor.primary.terms'),
            t('investor.checkout.deal_line', {
                rate: issue.terms.rate_pct,
                count: issue.terms.term_months,
            }),
        ],
        [
            t('investor.primary.rights.total_return'),
            formatRwf(issue.terms.total_return),
        ],
        [t('investor.plus.fee'), earningsFeeRate(t, issue.terms.earnings_fee)],
        ...disclosureRow(t, locale, issue.terms.disclosure_version),
        [
            t('investor.primary.receipt.reference'),
            issue.issue_receipt.reference,
        ],
    ];

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
                        <dt className="text-rz-secondary">{label}</dt>
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
                    {rowsNow.map((row) => (
                        <tr
                            key={row.index}
                            className="border-t border-[#eef2f9] dark:border-rz-divider"
                        >
                            <td className="px-3.5 py-2 text-rz-slate">
                                {formatDate(row.due_on, locale)}
                                {row.paid_on !== null && (
                                    <span className="block text-[10.5px] font-semibold text-[#157357] dark:text-[#3fcda0]">
                                        {t(
                                            row.paid_on < row.due_on
                                                ? 'investor.holding.issue.paid_early'
                                                : 'investor.holding.issue.paid_on',
                                            {
                                                date: formatDate(
                                                    row.paid_on,
                                                    locale,
                                                ),
                                            },
                                        )}
                                        {row.net !== null &&
                                            ` · ${t('investor.holding.issue.paid_net', { amount: formatRwf(row.net) })}`}
                                    </span>
                                )}
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
