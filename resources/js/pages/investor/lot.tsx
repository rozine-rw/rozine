import { Link } from '@inertiajs/react';
import { InvestorShell } from '@/components/investor/investor-shell';
import { ACCENT_FILL, accentBanner } from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { InvestorLotProps, LotInstalment } from '@/types/investor';

const MICRO = 'text-[10px] font-bold tracking-[.03em] text-rz-slate uppercase';

function Tile({
    label,
    value,
    sub,
}: {
    label: string;
    value: string;
    sub: string;
}) {
    return (
        <div className="min-w-0 rounded-2xl border border-rz-border bg-rz-surface px-3 py-[11px]">
            <p className={MICRO}>{label}</p>
            <p className="mt-1 text-[15px] font-bold tracking-[-.2px] [overflow-wrap:anywhere] text-rz-ink">
                {value}
            </p>
            <p className="mt-px text-[10.5px] text-rz-secondary">{sub}</p>
        </div>
    );
}

function InstalmentRow({ row }: { row: LotInstalment }) {
    const { t, locale } = useTranslation();
    const status =
        row.status === 'paid'
            ? t('investor.lot.status.paid', {
                  date: formatDate(row.paid_on ?? row.due_on, locale),
              })
            : row.status === 'before_purchase'
              ? t('investor.lot.status.before_purchase')
              : t('investor.lot.status.upcoming', {
                    date: formatDate(row.due_on, locale),
                });

    return (
        <li className="flex items-center justify-between gap-3 border-b border-rz-hairline py-3 last:border-b-0">
            <span className="min-w-0">
                <span className="block text-[13px] font-semibold text-rz-ink">
                    {t('investor.lot.instalment', { index: row.index })}
                </span>
                <span
                    className={cn(
                        'text-[11.5px]',
                        row.status === 'paid'
                            ? 'text-[#157357] dark:text-[#4cc497]'
                            : 'text-rz-secondary',
                    )}
                >
                    {status}
                </span>
            </span>
            <span
                className={cn(
                    'shrink-0 text-[13px] font-bold',
                    row.status === 'before_purchase'
                        ? 'text-rz-muted line-through'
                        : 'text-rz-ink',
                )}
            >
                {formatRwf(row.net ?? row.expected)}
            </span>
        </li>
    );
}

/**
 * A lot bought on the market (`investor-lot-v1`, QA #663): what was paid for it, what it has been
 * paid since, its next payment and the note's schedule for the units held. Instalments that fell
 * due before the purchase went to the seller and are shown struck through.
 */
export default function InvestorLot(props: InvestorLotProps) {
    const { t, locale } = useTranslation();
    const { lot, links } = props;

    return (
        <InvestorShell
            title={lot.name}
            tab="portfolio"
            links={links}
            showTabBar={false}
            beacon={{
                link: links.changes ?? null,
                reloads: {
                    purchase: { only: ['server_time', 'lot'] },
                    wallet: { only: ['server_time', 'lot'] },
                },
            }}
        >
            <div className="mx-auto w-full max-w-[760px] pb-10">
                <div
                    className="relative h-[130px] overflow-hidden md:mx-5 md:mt-4 md:rounded-2xl"
                    style={{ background: accentBanner(lot.accent) }}
                >
                    <span className="absolute inset-0 bg-[linear-gradient(180deg,rgba(10,15,28,.32)_0%,rgba(10,15,28,0)_45%,rgba(10,15,28,.9)_100%)]" />
                    <Link
                        href={links.back}
                        aria-label={t('investor.common.back')}
                        className="absolute top-[calc(env(safe-area-inset-top)+12px)] left-4 z-[5] flex size-[38px] items-center justify-center rounded-[10px] border border-[#dbe3f0] bg-[rgba(8,11,20,.6)] text-lg text-white md:top-3.5 dark:border-white/20"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <div className="absolute right-[18px] bottom-3.5 left-[18px] flex items-center gap-3">
                        <span
                            className="flex size-[46px] shrink-0 items-center justify-center rounded-xl bg-white/95 text-[19px] font-bold"
                            style={{ color: ACCENT_FILL[lot.accent] }}
                        >
                            {lot.name.charAt(0)}
                        </span>
                        <div className="min-w-0">
                            <h1 className="truncate text-[17px] font-semibold text-white [text-shadow:0_1px_5px_rgba(0,0,0,.5)]">
                                {lot.name}
                            </h1>
                            <p className="mt-[3px] truncate text-xs text-white [text-shadow:0_1px_4px_rgba(0,0,0,.5)]">
                                {lot.industry} ·{' '}
                                {t('investor.lot.bought_on', {
                                    date: formatDate(lot.bought_at, locale),
                                })}
                            </p>
                        </div>
                    </div>
                </div>
                <div className="px-5 pt-[18px]">
                    <div className="grid grid-cols-2 gap-2">
                        <Tile
                            label={t('investor.lot.price_paid')}
                            value={formatRwf(lot.paid)}
                            sub={t('investor.lot.per_unit', {
                                price: formatRwf(lot.price),
                            })}
                        />
                        <Tile
                            label={t('investor.lot.units_held')}
                            value={lot.units}
                            sub={t('investor.lot.matures', {
                                date: formatDate(lot.matures_on, locale),
                            })}
                        />
                        <Tile
                            label={t('investor.lot.received')}
                            value={formatRwf(lot.received)}
                            sub={t('investor.lot.payments', {
                                made: lot.payments_made,
                                total: lot.payments_total,
                            })}
                        />
                        <Tile
                            label={t('investor.lot.next_payment')}
                            value={
                                lot.next_payment === null
                                    ? '—'
                                    : formatRwf(lot.next_payment.amount)
                            }
                            sub={
                                lot.next_payment === null
                                    ? t('investor.lot.no_next')
                                    : formatDate(
                                          lot.next_payment.due_on,
                                          locale,
                                      )
                            }
                        />
                    </div>
                    <section
                        aria-label={t('investor.lot.schedule')}
                        className="mt-5"
                    >
                        <h2 className="text-[14px] font-bold text-rz-ink">
                            {t('investor.lot.schedule')}
                        </h2>
                        <ul className="mt-1.5 rounded-2xl border border-rz-border bg-rz-surface px-3.5">
                            {lot.schedule.map((row) => (
                                <InstalmentRow key={row.index} row={row} />
                            ))}
                        </ul>
                    </section>
                    <Link
                        href={links.market}
                        className="mt-5 flex h-11 items-center justify-center rounded-xl border border-rz-border bg-rz-surface text-[13.5px] font-bold text-rz-ink"
                    >
                        {t('investor.lot.resell')}
                    </Link>
                </div>
            </div>
        </InvestorShell>
    );
}
