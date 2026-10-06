import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount, formatMillions } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessCapital } from '@/types/business';

type TileKey = 'raised' | 'investors' | 'repaid' | 'on_time';

type TileProps = {
    tile: TileKey;
    value: ReactNode;
    open: boolean;
    onToggle: () => void;
    align: 'left' | 'right';
    /** The design opens a popover above its tile when there is room, else below. */
    above: boolean;
};

function Tile({ tile, value, open, onToggle, align, above }: TileProps) {
    const { t } = useTranslation();
    const label = t(`business.capital.${tile}.label`);

    return (
        <div className="relative min-w-0 rounded-2xl border border-rz-border bg-rz-surface px-[13px] py-3 lg:px-[11px] lg:py-[9px]">
            <div className="flex items-center justify-between gap-1.5">
                <span className="text-[10.5px] font-bold tracking-[.04em] whitespace-nowrap text-rz-slate uppercase">
                    {label}
                </span>
                <button
                    type="button"
                    onClick={onToggle}
                    aria-expanded={open}
                    aria-label={t('business.capital.about', { label })}
                    className="flex size-3.5 shrink-0 items-center justify-center rounded-full border border-[#d5deea] p-0 text-[10.5px] leading-none font-semibold text-rz-secondary dark:border-rz-border"
                >
                    i
                </button>
            </div>
            <div className="mt-[5px] text-lg font-bold tracking-[-.4px] whitespace-nowrap text-rz-ink lg:text-base">
                {value}
            </div>
            {open && (
                <div
                    role="tooltip"
                    className={cn(
                        'absolute z-50 w-[210px] rounded-[10px] border border-rz-border bg-rz-surface px-[13px] py-[11px] text-[11px] leading-normal text-[#5a647a] shadow-[0_12px_28px_-8px_rgba(12,24,48,.3)] dark:text-rz-secondary',
                        align === 'left' ? 'left-0' : 'right-0',
                        above
                            ? 'bottom-[calc(100%+9px)]'
                            : 'top-[calc(100%+9px)]',
                    )}
                >
                    <span
                        aria-hidden
                        className={cn(
                            'absolute size-[11px] rotate-45 bg-rz-surface',
                            align === 'left' ? 'left-4' : 'right-4',
                            above
                                ? '-bottom-[6px] border-r border-b border-rz-border'
                                : '-top-[6px] border-t border-l border-rz-border',
                        )}
                    />
                    <span className="font-bold text-rz-ink">{label}</span> —{' '}
                    {t(`business.capital.${tile}.about`)}
                </div>
            )}
        </div>
    );
}

/** "Your capital" — the all-time track record (design L244–269). */
export function CapitalSection({ capital }: { capital: BusinessCapital }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState<TileKey | null>(null);
    const toggle = (tile: TileKey) => () =>
        setOpen((current) => (current === tile ? null : tile));

    useEffect(() => {
        if (open === null) {
            return;
        }

        const close = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(null);
            }
        };

        window.addEventListener('keydown', close);

        return () => window.removeEventListener('keydown', close);
    }, [open]);

    const currency = (
        <span className="text-[10px] font-semibold text-rz-secondary">
            {t('common.currency.rwf')}{' '}
        </span>
    );

    return (
        <section
            aria-labelledby="business-capital"
            className="relative mt-7 lg:mt-0.5"
        >
            <div className="flex items-baseline justify-between">
                <div>
                    <h2
                        id="business-capital"
                        className="text-[17px] font-bold tracking-[-.2px] text-rz-ink"
                    >
                        {t('business.capital.title')}
                    </h2>
                    <p className="mt-0.5 text-xs text-rz-secondary lg:hidden">
                        {t('business.capital.subtitle')}
                    </p>
                </div>
                <span className="inline-flex shrink-0 items-center gap-[5px] text-[11px] font-bold text-rz-accent-app-text">
                    <span className="size-1.5 rounded-full bg-rz-accent-app-text shadow-[0_0_0_3px_rgba(29,158,117,.10)]" />
                    {t('business.capital.active_notes', {
                        count: capital.active_notes,
                    })}
                </span>
            </div>

            {open !== null && (
                <button
                    type="button"
                    aria-label={t('business.capital.close')}
                    onClick={() => setOpen(null)}
                    className="fixed inset-0 z-40 cursor-default"
                />
            )}

            <div className="relative mt-[13px] grid grid-cols-2 gap-2.5">
                <Tile
                    tile="raised"
                    value={
                        <>
                            {currency}
                            {formatMillions(capital.raised)}
                        </>
                    }
                    open={open === 'raised'}
                    onToggle={toggle('raised')}
                    above={false}
                    align="left"
                />
                <Tile
                    tile="investors"
                    value={formatCount(capital.investors)}
                    open={open === 'investors'}
                    onToggle={toggle('investors')}
                    above={false}
                    align="right"
                />
                <Tile
                    tile="repaid"
                    value={
                        <>
                            {currency}
                            {formatMillions(capital.repaid)}
                        </>
                    }
                    open={open === 'repaid'}
                    onToggle={toggle('repaid')}
                    above={true}
                    align="left"
                />
                <Tile
                    tile="on_time"
                    value={
                        capital.on_time_pct === null
                            ? '—'
                            : `${capital.on_time_pct}%`
                    }
                    open={open === 'on_time'}
                    onToggle={toggle('on_time')}
                    above={true}
                    align="right"
                />
            </div>
        </section>
    );
}
