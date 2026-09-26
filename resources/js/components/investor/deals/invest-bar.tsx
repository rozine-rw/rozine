import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { POSITIVE_TEXT } from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import { formatAmount, formatCount } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type {
    C3DealCard,
    InvestGate,
    InvestorAllowedAction,
    PrimaryQuote,
} from '@/types/investor';

/** The design's `.rz-qty` range: a 4px track and a 22px accent thumb ringed in white. */
export const RANGE_CLASS =
    'h-1 min-w-0 flex-1 cursor-pointer appearance-none rounded-[3px] bg-[#e2e8f2] outline-none dark:bg-rz-surface-muted [&::-moz-range-thumb]:size-[18px] [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-4 [&::-moz-range-thumb]:border-white [&::-moz-range-thumb]:bg-rz-accent-fill [&::-webkit-slider-thumb]:size-[22px] [&::-webkit-slider-thumb]:cursor-grab [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-4 [&::-webkit-slider-thumb]:border-white [&::-webkit-slider-thumb]:bg-rz-accent-fill [&::-webkit-slider-thumb]:shadow-[0_2px_8px_rgba(30,58,255,.4)] active:[&::-webkit-slider-thumb]:shadow-[0_0_0_7px_rgba(30,58,255,.10)]';

/**
 * Whether a deal may be reserved now (C3 v2 §2b): the server lists `primary.reserve`, the campaign
 * is live with no restriction, the Investor is eligible and the quote allows at least one unit.
 */
export function canReserve({
    deal,
    gate,
    allowed,
    quote,
}: {
    deal: Pick<C3DealCard, 'lifecycle' | 'restriction'>;
    gate: InvestGate;
    allowed: InvestorAllowedAction[];
    quote: PrimaryQuote | null;
}): boolean {
    return (
        allowed.includes('primary.reserve') &&
        deal.lifecycle === 'live' &&
        deal.restriction === null &&
        gate.status === 'eligible' &&
        quote !== null &&
        Number(quote.capacity.max_units) >= 1
    );
}

/** Names the cap that binds once the chosen quantity reaches `max_units` (H9 `capacity.binding`). */
export function CapNote({
    quote,
    units,
    className,
}: {
    quote: PrimaryQuote | null;
    units: number;
    className?: string;
}) {
    const { t } = useTranslation();

    if (quote === null) {
        return null;
    }

    const max = Number(quote.capacity.max_units);

    if (units < max) {
        return null;
    }

    const reason = t(`investor.deal.cap.reason.${quote.capacity.binding}`);

    return (
        <p
            role="status"
            className={cn(
                'text-center text-[11px] leading-[1.4] text-rz-secondary',
                className,
            )}
        >
            {max === 0
                ? t('investor.deal.cap.none', { reason })
                : t('investor.deal.cap.max', { count: max, reason })}
        </p>
    );
}

type InvestBarProps = {
    deal: C3DealCard;
    /** The server's quote for this deal, or null while it is not the focused one. */
    quote: PrimaryQuote | null;
    /** The quantity the investor has chosen; a whole number of notes. */
    units: number;
    onUnits: (units: number) => void;
    /** True while the server re-quotes a new quantity. */
    quoting: boolean;
    gate: InvestGate;
    /** Checkout for this deal and quantity, or null while the deal cannot be bought. */
    checkout: RouteLink | null;
    size: 'phone' | 'desk';
};

/**
 * The persistent invest bar (phone L650–690, desk L335–372): how many notes, of how many left,
 * the term; then what the ticket costs, its flat return and what comes back — all from the
 * server's quote for exactly that quantity — then the slider and Invest.
 */
export function InvestBar({
    deal,
    quote,
    units,
    onUnits,
    quoting,
    gate,
    checkout,
    size,
}: InvestBarProps) {
    const { t } = useTranslation();
    const [draft, setDraft] = useState<string | null>(null);
    const current = quote;
    const max = Math.max(Number(current?.capacity.max_units ?? '1'), 1);
    const soldOut = deal.lifecycle !== 'live' || deal.restriction !== null;
    const phone = size === 'phone';
    const micro = cn(
        'font-bold tracking-[.08em] whitespace-nowrap text-[#7c869a] uppercase dark:text-rz-muted',
        phone ? 'text-[9.5px] leading-3' : 'text-[10px] leading-[13px]',
    );
    const value = cn(
        'mt-1 font-bold whitespace-nowrap tabular-nums',
        phone ? 'text-sm' : 'text-[15px]',
    );

    const commit = (raw: string) => {
        const parsed = Number.parseInt(raw.replace(/[^0-9]/gu, ''), 10);

        setDraft(null);

        if (Number.isNaN(parsed) || parsed < 1) {
            return;
        }

        onUnits(Math.min(parsed, max));
    };

    const investLabel =
        deal.lifecycle !== 'live'
            ? t(`investor.deal.lifecycle.${deal.lifecycle}`)
            : deal.restriction !== null
              ? t('investor.deals.paused')
              : t('investor.deals.invest');
    const investClass = cn(
        'flex shrink-0 items-center justify-center whitespace-nowrap',
        phone
            ? 'h-12 min-w-28 rounded-[14px] px-[18px] text-[15px] font-semibold'
            : 'h-12 min-w-[132px] rounded-[14px] px-5 text-[15.5px] font-bold',
        soldOut || checkout === null
            ? 'cursor-not-allowed bg-[#eef2f9] text-rz-secondary dark:bg-rz-surface-muted'
            : 'bg-rz-accent-fill text-white',
    );

    return (
        <section
            aria-label={t('investor.deals.invest_bar', { name: deal.name })}
            className={cn(
                'border border-rz-border bg-rz-surface',
                phone
                    ? 'mx-2 mt-0.5 rounded-[20px] px-3.5 pt-3 pb-[11px] shadow-[0_14px_34px_-16px_rgba(20,45,95,.26)]'
                    : 'mt-3.5 shrink-0 rounded-[20px] px-3.5 pt-[13px] pb-3 shadow-[0_6px_18px_-10px_rgba(20,45,95,.22)]',
            )}
        >
            <div
                className={cn(
                    'flex items-center justify-between',
                    phone ? 'gap-2.5' : 'gap-3',
                )}
            >
                <div
                    className={cn(
                        'flex min-w-0 items-center',
                        phone ? 'gap-2' : 'gap-[9px]',
                    )}
                >
                    <label className="flex shrink-0 items-center overflow-hidden rounded-[10px] border border-[#dfe6f2] bg-rz-surface dark:border-rz-border">
                        <input
                            value={draft ?? String(units)}
                            onChange={(event) => setDraft(event.target.value)}
                            onBlur={(event) => commit(event.target.value)}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter') {
                                    commit(event.currentTarget.value);
                                }
                            }}
                            onFocus={(event) => event.target.select()}
                            inputMode="numeric"
                            disabled={soldOut}
                            aria-label={t('investor.deals.notes_quantity')}
                            className={cn(
                                'border-0 bg-transparent p-0 text-center font-bold text-rz-ink tabular-nums outline-none',
                                phone
                                    ? 'h-7 w-[52px] text-[15px]'
                                    : 'h-[30px] w-[58px] text-base',
                            )}
                        />
                        <span
                            aria-hidden
                            className={cn(
                                'flex items-center border-l border-[#eef2f8] bg-rz-surface-sunken font-bold tracking-[.08em] whitespace-nowrap text-[#7c869a] dark:border-rz-divider dark:text-rz-muted',
                                phone
                                    ? 'h-7 px-2 text-[9.5px]'
                                    : 'h-[30px] px-[9px] text-[10px]',
                            )}
                        >
                            {t('investor.deals.notes_suffix')}
                        </span>
                    </label>
                    <span
                        className={cn(
                            'truncate whitespace-nowrap text-[#9aa3b4] dark:text-rz-faint',
                            phone ? 'text-[11px]' : 'text-[11.5px]',
                        )}
                    >
                        {t('investor.deals.of_left', {
                            count: formatCount(Number(deal.units.available)),
                        })}
                    </span>
                </div>
                <div
                    className={cn(
                        'flex shrink-0 items-center',
                        phone ? 'gap-[5px]' : 'gap-1.5',
                    )}
                >
                    <span
                        className={cn(
                            'font-bold tracking-[.08em] text-[#7c869a] dark:text-rz-muted',
                            phone ? 'text-[9.5px]' : 'text-[10px]',
                        )}
                    >
                        {t('investor.deals.term')}
                    </span>
                    <span
                        className={cn(
                            'font-bold whitespace-nowrap text-rz-ink',
                            phone ? 'text-[12.5px]' : 'text-[13.5px]',
                        )}
                    >
                        {t('investor.deals.months_short', {
                            count: deal.term_months,
                        })}
                    </span>
                </div>
            </div>

            <div
                aria-busy={quoting || undefined}
                className={cn(
                    'grid grid-cols-[1fr_auto_auto_auto_1fr] items-start border-t border-[#eef2f8] transition-opacity dark:border-rz-divider',
                    phone
                        ? 'mt-[11px] gap-x-0.5 pt-[11px]'
                        : 'mt-3 gap-x-[3px] pt-3',
                    quoting && 'opacity-60',
                )}
            >
                <div className="min-w-0">
                    <p className={micro}>{t('investor.deals.bar_invest')}</p>
                    <p className={cn(value, 'text-rz-ink')}>
                        <span className="mr-[3px]">
                            {t('investor.money.rwf')}
                        </span>
                        {current === null ? '—' : formatAmount(current.amount)}
                    </p>
                </div>
                <span
                    aria-hidden
                    className={cn(
                        'self-end font-bold text-[#a3abbb]',
                        phone ? 'pl-1.5 text-sm' : 'pl-2 text-[15px]',
                    )}
                >
                    +
                </span>
                <div className="text-center">
                    <p className={micro}>{t('investor.deals.bar_interest')}</p>
                    <p className={cn(value, 'text-rz-accent-app-text')}>
                        {deal.rate_pct}%
                    </p>
                </div>
                <span
                    aria-hidden
                    className={cn(
                        'self-end font-bold text-[#a3abbb]',
                        phone ? 'pr-1.5 text-sm' : 'pr-2 text-[15px]',
                    )}
                >
                    =
                </span>
                <div className="min-w-0 text-right">
                    <p className={micro}>{t('investor.deals.bar_get_back')}</p>
                    <p className={cn(value, POSITIVE_TEXT)}>
                        <span className="mr-[3px]">
                            {t('investor.money.rwf')}
                        </span>
                        {current === null
                            ? '—'
                            : formatAmount(current.maturity_value)}
                    </p>
                </div>
            </div>

            <div
                className={cn(
                    'flex items-center',
                    phone ? 'mt-2.5 gap-[11px]' : 'mt-3.5 gap-3.5',
                )}
            >
                <input
                    type="range"
                    min={1}
                    max={max}
                    step={1}
                    value={Math.min(units, max)}
                    disabled={soldOut}
                    aria-label={t('investor.deals.notes_quantity')}
                    onChange={(event) => onUnits(Number(event.target.value))}
                    className={RANGE_CLASS}
                />
                {gate.status === 'verification_required' ? (
                    <Link href={gate.link} className={investClass}>
                        {t('investor.deals.verify_to_invest')}
                    </Link>
                ) : soldOut ||
                  checkout === null ||
                  gate.status !== 'eligible' ? (
                    <button type="button" disabled className={investClass}>
                        {investLabel}
                    </button>
                ) : (
                    <Link href={checkout} className={investClass}>
                        {investLabel}
                    </Link>
                )}
            </div>
            {gate.status !== 'eligible' ? (
                <p
                    role="status"
                    className="mt-2 text-center text-[11px] leading-[1.4] text-rz-secondary"
                >
                    {t(`investor.deals.gate.${gate.status}`)}
                </p>
            ) : (
                !soldOut && (
                    <CapNote quote={current} units={units} className="mt-2" />
                )
            )}
        </section>
    );
}
