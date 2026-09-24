import type { ReactNode } from 'react';
import { PhotoFill } from '@/components/investor/deals/deal-bits';
import { useTimeLeft } from '@/components/investor/deals/time-left';
import { POSITIVE_TEXT, RATING_STYLE } from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { DealDetail, UseOfFunds } from '@/types/investor';

export type Variant = 'phone' | 'desk';

type BadgeKind =
    | 'business'
    | 'business_blue'
    | 'audited'
    | 'rozine'
    | 'verified';

const BADGE_CLASS: Record<BadgeKind, string> = {
    business:
        'border border-rz-border bg-[#eef2f8] text-rz-slate dark:bg-rz-surface-muted',
    business_blue: 'bg-rz-accent-soft text-rz-accent-app-text',
    audited: cn('border border-rz-border bg-rz-surface', POSITIVE_TEXT),
    rozine: 'border border-[rgba(30,58,255,.2)] bg-rz-accent-soft text-rz-accent-app-text',
    verified: cn(
        'bg-[rgba(29,158,117,.1)] dark:bg-[rgba(63,205,160,.14)]',
        POSITIVE_TEXT,
    ),
};

/** Provenance badges: who stands behind a section (design "Reported by business" etc.). */
export function SourceBadge({
    kind,
    children,
    variant = 'phone',
}: {
    kind: BadgeKind;
    children: string;
    variant?: Variant;
}) {
    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center gap-[5px] rounded-[10px] font-bold',
                variant === 'phone'
                    ? 'px-2 py-[3px] text-[10.5px] tracking-[.02em]'
                    : 'px-2 py-[3px] text-[10px]',
                BADGE_CLASS[kind],
            )}
        >
            {kind === 'business' && (
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden
                    className="size-2.5"
                >
                    <path
                        d="M5 21V5a2 2 0 0 1 2-2h7l4 4v14"
                        stroke="currentColor"
                        strokeWidth="1.9"
                        strokeLinejoin="round"
                    />
                    <path
                        d="M14 3v5h4"
                        stroke="currentColor"
                        strokeWidth="1.9"
                        strokeLinejoin="round"
                    />
                </svg>
            )}
            {(kind === 'audited' || kind === 'verified') && (
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden
                    className="size-2.5"
                >
                    <path
                        d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z"
                        stroke="currentColor"
                        strokeWidth="1.9"
                        strokeLinejoin="round"
                    />
                </svg>
            )}
            {kind === 'rozine' && variant === 'phone' && (
                <svg viewBox="0 0 24 24" aria-hidden className="size-2.5">
                    <path
                        d="M12 2.5l2.4 6.1 6.1 2.4-6.1 2.4L12 19.5l-2.4-6.1L3.5 11l6.1-2.4L12 2.5Z"
                        fill="currentColor"
                    />
                </svg>
            )}
            {children}
        </span>
    );
}

/** A detail section: title, provenance badge, body. */
export function Section({
    title,
    badge,
    variant,
    children,
}: {
    title: string;
    badge?: ReactNode;
    variant: Variant;
    children: ReactNode;
}) {
    return (
        <section
            aria-label={title}
            className={variant === 'phone' ? 'px-3.5 pt-[22px]' : 'mt-4'}
        >
            <div className="flex items-center justify-between gap-2">
                <h3
                    className={cn(
                        'text-sm text-rz-ink',
                        variant === 'phone' ? 'font-semibold' : 'font-bold',
                    )}
                >
                    {title}
                </h3>
                {badge}
            </div>
            {children}
        </section>
    );
}

/** Funding progress with the live badge (phone L783–798, desk L385–393). */
export function FundingProgress({
    deal,
    serverTime,
    variant,
}: {
    deal: DealDetail;
    serverTime: string;
    variant: Variant;
}) {
    const { t } = useTranslation();
    const clock = useTimeLeft(deal.closes_at, serverTime);
    const phone = variant === 'phone';
    const label = cn(
        'text-[10.5px] text-rz-secondary uppercase',
        phone ? 'font-semibold' : 'font-bold tracking-[.05em] text-rz-slate',
    );
    const value = cn(
        'mt-0.5 text-sm text-rz-ink',
        phone ? 'font-semibold' : 'font-bold',
    );

    return (
        <div
            className={cn(
                'rounded-2xl border bg-rz-surface',
                phone
                    ? 'border-rz-border p-[13px]'
                    : 'border-[#eef1f7] p-[15px] dark:border-rz-border',
            )}
        >
            <div className="flex items-center justify-between">
                <span
                    className={cn(
                        'text-sm text-rz-ink',
                        phone ? 'font-semibold' : 'font-bold',
                    )}
                >
                    {t('investor.deal.funding_progress')}
                </span>
                <span
                    className={cn(
                        'inline-flex items-center gap-[5px] text-[11px]',
                        phone ? 'font-semibold' : 'font-bold',
                        deal.status === 'open'
                            ? POSITIVE_TEXT
                            : 'text-rz-secondary',
                    )}
                >
                    <span className="size-[7px] rounded-full bg-current" />
                    {t(`investor.deal.status.${deal.status}`)}
                </span>
            </div>
            <div
                role="progressbar"
                aria-label={t('investor.deals.funded_label', {
                    name: deal.name,
                })}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={Number(deal.funded_pct)}
                className={cn(
                    'h-2 overflow-hidden',
                    phone
                        ? 'mt-2.5 rounded-[5px] bg-rz-border'
                        : 'mt-[11px] rounded-full bg-[#eef2f9] dark:bg-rz-surface-muted',
                )}
            >
                <div
                    className="h-full rounded-[5px] bg-[linear-gradient(90deg,#1e3aff,#17795a)]"
                    style={{ width: `${deal.funded_pct}%` }}
                />
            </div>
            <div
                className={cn(
                    'flex justify-between',
                    phone ? 'mt-[9px]' : 'mt-[13px]',
                )}
            >
                <div>
                    <p className={label}>{t('investor.deal.raised')}</p>
                    <p className={value}>{formatRwfShort(deal.raised)}</p>
                </div>
                <div className="text-center">
                    <p className={label}>{t('investor.deal.target')}</p>
                    <p className={value}>{formatRwfShort(deal.target)}</p>
                </div>
                <div className="text-right">
                    <p className={label}>{t('investor.deal.time_left')}</p>
                    <p className={cn(value, 'tabular-nums')}>{clock.label}</p>
                </div>
            </div>
            <div
                className={cn(
                    'flex justify-between border-t text-xs text-rz-secondary',
                    phone
                        ? 'mt-2.5 border-[#eef2f9] pt-2.5 dark:border-rz-divider'
                        : 'mt-3 border-[#eef1f7] pt-[11px] dark:border-rz-divider',
                )}
            >
                <span>
                    <span
                        className={cn(!phone && 'font-extrabold text-rz-ink')}
                    >
                        {formatCount(deal.investors)}
                    </span>{' '}
                    {t('investor.deals.investors')}
                </span>
                <span>
                    {t(
                        phone
                            ? 'investor.deal.notes_sold_of'
                            : 'investor.deal.notes_of',
                        {
                            sold: formatCount(deal.units_sold),
                            total: formatCount(deal.units_total),
                        },
                    )}
                </span>
            </div>
        </div>
    );
}

/** Photos the business filed (phone L805–819, desk L409–414). */
export function PhotosRow({
    deal,
    variant,
}: {
    deal: DealDetail;
    variant: Variant;
}) {
    const { t } = useTranslation();

    if (deal.photos.length === 0) {
        return null;
    }

    return (
        <Section
            title={t('investor.deal.photos')}
            variant={variant}
            badge={
                <SourceBadge
                    kind={variant === 'phone' ? 'business' : 'business_blue'}
                    variant={variant}
                >
                    {t('investor.deal.reported_by_business')}
                </SourceBadge>
            }
        >
            <div
                className={cn(
                    'rz-hscroll flex overflow-x-auto',
                    variant === 'phone'
                        ? '-mx-3.5 mt-[9px] gap-[9px] px-3.5'
                        : 'mt-2.5 gap-2.5 pb-0.5',
                )}
            >
                {deal.photos.map((photo) => (
                    <figure
                        key={photo.caption}
                        className={cn(
                            'relative shrink-0 overflow-hidden rounded-xl',
                            variant === 'phone'
                                ? 'h-28 w-[174px]'
                                : 'h-[90px] w-[132px]',
                        )}
                    >
                        <PhotoFill photo={photo} accent={deal.accent} />
                        <figcaption className="absolute inset-x-0 bottom-0 bg-[linear-gradient(180deg,rgba(10,15,28,0),rgba(10,15,28,.85))] px-[11px] pt-[18px] pb-2 text-[11px] font-semibold text-white">
                            {photo.caption}
                        </figcaption>
                    </figure>
                ))}
            </div>
        </Section>
    );
}

const FUND_DOT: Record<UseOfFunds, string> = {
    inventory: '#1e3aff',
    equipment: '#17795a',
    expansion: '#c2661f',
    hiring: '#6425c9',
    working_capital: '#046a86',
    other: '#5b6578',
};

/** Use of funds chips (phone L821–831, desk L416–419). */
export function UseOfFundsChips({
    deal,
    variant,
}: {
    deal: DealDetail;
    variant: Variant;
}) {
    const { t } = useTranslation();

    return (
        <Section
            title={t('investor.deal.use_of_funds')}
            variant={variant}
            badge={
                <SourceBadge
                    kind={variant === 'phone' ? 'business' : 'business_blue'}
                    variant={variant}
                >
                    {t('investor.deal.reported_by_business')}
                </SourceBadge>
            }
        >
            <ul
                className={cn(
                    'flex flex-wrap',
                    variant === 'phone'
                        ? 'mt-2.5 gap-[7px]'
                        : 'mt-2.5 gap-[7px]',
                )}
            >
                {deal.use_of_funds.map((use) => (
                    <li
                        key={use}
                        className={cn(
                            'inline-flex items-center rounded-[10px] border border-rz-border bg-rz-page font-semibold text-[#2c3a52] dark:text-rz-slate',
                            variant === 'phone'
                                ? 'gap-1.5 px-2.5 py-[5px] text-[11.5px]'
                                : 'gap-[7px] px-[11px] py-1.5 text-xs',
                        )}
                    >
                        <span
                            className="size-2 rounded-full"
                            style={{ background: FUND_DOT[use] }}
                        />
                        {t(`investor.deal.use.${use}`)}
                    </li>
                ))}
            </ul>
        </Section>
    );
}

/** Rating as the design's stats strip writes it: band word in its colour, then the score. */
export function RatingWord({ deal }: { deal: Pick<DealDetail, 'rating'> }) {
    const { t } = useTranslation();
    const style = RATING_STYLE[deal.rating.band];

    return (
        <span className="inline-flex items-baseline gap-[5px]">
            <span className={cn('text-sm font-bold', style.text)}>
                {t(`investor.rating.${deal.rating.band}`)}
            </span>
            <span className={cn('text-[11px] font-semibold', style.text)}>
                {deal.rating.score}
            </span>
        </span>
    );
}
