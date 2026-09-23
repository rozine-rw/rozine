import { StepHeading } from '@/components/business/apply/step-heading';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf, formatRwfShort } from '@/lib/rozine/format';
import type { Money } from '@/types';
import type { ApplicationEvidence } from '@/types/business';

function VerifiedChip({ children }: { children: string }) {
    return (
        <span className="inline-flex items-center gap-[5px] rounded-[10px] border border-[#cdeddb] bg-rz-accent-soft px-[11px] py-[5px] text-[11.5px] font-bold text-rz-accent-app-text dark:border-transparent">
            {children}
        </span>
    );
}

function Figure({
    label,
    value,
    tone,
}: {
    label: string;
    value: Money;
    tone: 'ink' | 'muted' | 'accent';
}) {
    const toneClass = {
        ink: 'text-rz-ink',
        muted: 'text-rz-secondary',
        accent: 'text-rz-accent-app-text',
    }[tone];

    return (
        <div className="flex-1">
            <p className="text-[10.5px] font-bold tracking-[.02em] text-rz-slate uppercase">
                {label}
            </p>
            <p className={`mt-0.5 text-[12.5px] font-semibold ${toneClass}`}>
                {formatRwfShort(value)}
            </p>
        </div>
    );
}

/**
 * Step 1 — "Business & finances" (design L365–446): the verified identity, the financial record the
 * engine rated, existing debt, and the approved capacity. Read-only: evidence is corrected through
 * its own review route, never edited in an application.
 */
export function StepBusiness({ evidence }: { evidence: ApplicationEvidence }) {
    const { t } = useTranslation();
    const { business } = evidence;
    const first = evidence.years.at(-1)?.year;
    const last = evidence.years.at(0)?.year;
    const degrees =
        evidence.rating === null
            ? 0
            : (Number(evidence.rating.score) / 5) * 360;

    return (
        <>
            <StepHeading
                step={1}
                title={t('business.apply.business.title')}
                subtitle={t('business.apply.business.subtitle')}
            />
            <div className="mt-4 animate-[rz-fade_.3s_ease]">
                <div className="flex flex-wrap gap-2">
                    {evidence.verified.registry && (
                        <VerifiedChip>
                            {t('business.apply.business.rdb_verified')}
                        </VerifiedChip>
                    )}
                    {evidence.verified.statements && (
                        <VerifiedChip>
                            {t('business.apply.business.statements_verified')}
                        </VerifiedChip>
                    )}
                </div>

                <div className="mt-[13px] rounded-2xl border border-rz-border bg-rz-surface p-4">
                    <div className="flex items-center gap-3">
                        <div className="flex size-[46px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-fill text-xl font-bold text-white">
                            {business.name.trim().charAt(0).toUpperCase()}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-base font-bold text-rz-ink">
                                {business.name}
                            </p>
                            <p className="mt-1 inline-flex items-center gap-[5px] rounded-[10px] border border-rz-border bg-rz-surface px-2 py-0.5 text-[11px] font-bold tracking-[.02em] text-rz-accent-app-text">
                                {t('business.home.company_code', {
                                    code: business.company_code,
                                })}
                            </p>
                            <p className="mt-[5px] text-xs text-rz-secondary">
                                {business.industry} · {business.district}
                            </p>
                        </div>
                    </div>
                    <div className="mt-[13px] flex flex-wrap gap-[7px]">
                        <span className="inline-flex items-center gap-[5px] rounded-[10px] border border-rz-border bg-[#f3f6fc] px-2.5 py-[5px] text-[11.5px] font-semibold text-rz-accent-app-text dark:bg-rz-surface-muted">
                            {t('business.apply.business.active')}
                        </span>
                        {business.established_year !== null && (
                            <span className="inline-flex items-center rounded-[10px] border border-rz-border bg-[#f3f6fc] px-2.5 py-[5px] text-[11.5px] font-semibold text-rz-slate dark:bg-rz-surface-muted">
                                {t('business.apply.business.established', {
                                    year: business.established_year,
                                })}
                            </span>
                        )}
                    </div>
                    {business.officers.length > 0 && (
                        <div className="mt-[13px] flex gap-6 border-t border-[#eef2f9] pt-[13px] dark:border-rz-divider">
                            {business.officers.map((officer) => (
                                <div key={officer.role}>
                                    <p className="text-[10px] font-bold tracking-[.02em] text-rz-slate uppercase">
                                        {t(
                                            `business.apply.business.officer.${officer.role}`,
                                        )}
                                    </p>
                                    <p className="mt-0.5 text-[13px] font-semibold text-rz-ink">
                                        {officer.name}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="mt-3 rounded-2xl border border-rz-border bg-rz-surface p-4">
                    <div className="flex items-center justify-between">
                        <span className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                            {t('business.apply.business.standing', {
                                years: evidence.years.length,
                            })}
                        </span>
                        <span className="inline-flex items-center gap-1 text-[10px] font-bold text-rz-accent-app-text uppercase">
                            <span className="size-1.5 rounded-full bg-rz-accent-app-text shadow-[0_0_0_3px_rgba(29,158,117,.10)]" />
                            {t('business.apply.business.ocr_verified')}
                        </span>
                    </div>

                    <div className="mt-[11px] rounded-2xl bg-rz-accent-fill p-4 text-white">
                        <p className="text-[10.5px] font-bold tracking-[.05em] text-white/70">
                            {first}–{last}
                        </p>
                        <div className="mt-2.5 flex items-center gap-[11px]">
                            <div
                                className="flex size-11 shrink-0 items-center justify-center rounded-full"
                                style={{
                                    background: `conic-gradient(#8fe3b0 ${degrees}deg, rgba(255,255,255,.22) 0deg)`,
                                }}
                            >
                                <div className="flex size-[34px] items-center justify-center rounded-full bg-rz-accent-fill text-[13px] font-extrabold">
                                    {evidence.rating?.score ?? '—'}
                                </div>
                            </div>
                            <div className="leading-[1.15]">
                                <p className="text-[10.5px] font-bold tracking-[.04em] uppercase">
                                    {t('business.home.rozine_rating')}
                                </p>
                                <p className="mt-0.5 text-[13px] font-extrabold">
                                    {evidence.rating === null
                                        ? t('business.rating.pending')
                                        : `${t(`business.rating.band.${evidence.rating.band}`)} · ${evidence.rating.score}`}
                                </p>
                            </div>
                        </div>
                        <div className="mt-[15px] flex gap-2">
                            {(
                                [
                                    [
                                        'revenue',
                                        evidence.totals.revenue,
                                        'text-white',
                                    ],
                                    [
                                        'costs',
                                        evidence.totals.costs,
                                        'text-white/90',
                                    ],
                                    [
                                        'net_profit',
                                        evidence.totals.net_profit,
                                        'text-white',
                                    ],
                                ] as const
                            ).map(([key, value, tone]) => (
                                <div key={key} className="min-w-0 flex-1">
                                    <p className="text-[10.5px] font-bold tracking-[.02em] uppercase">
                                        {t(`business.apply.business.${key}`)}
                                    </p>
                                    <p
                                        className={`mt-[3px] text-[13px] font-bold whitespace-nowrap ${tone}`}
                                    >
                                        {formatRwfShort(value)}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="mt-[9px] flex items-center justify-between gap-2.5 rounded-xl border border-[#eef2f9] bg-[#f6f9fd] px-3.5 py-3 dark:border-rz-divider dark:bg-rz-surface-sunken">
                        <div className="min-w-0">
                            <p className="text-[10px] font-semibold tracking-[.03em] text-rz-secondary uppercase">
                                {t('business.apply.business.existing_debt')}
                            </p>
                            <p className="mt-[3px] text-base font-bold text-rz-ink">
                                {formatRwfShort(evidence.existing_debt)}
                            </p>
                        </div>
                        {evidence.debt_verified && (
                            <span className="inline-flex shrink-0 items-center gap-[5px] rounded-[10px] border border-[#cdeddb] bg-rz-accent-soft px-2.5 py-[5px] text-[11px] font-bold text-rz-accent-app-text dark:border-transparent">
                                {t('business.apply.business.crb_verified')}
                            </span>
                        )}
                    </div>

                    <p className="mt-[15px] text-[10px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('business.apply.business.year_by_year')}
                    </p>
                    <div className="mt-2 flex flex-col gap-2">
                        {evidence.years.map((year) => (
                            <div
                                key={year.year}
                                className="overflow-hidden rounded-xl border border-rz-border bg-rz-surface"
                            >
                                <div className="border-b border-rz-border bg-[#f2f6ff] px-3.5 py-2 dark:bg-rz-surface-sunken">
                                    <span className="text-[13px] font-extrabold tracking-[.02em] text-rz-accent-app-text">
                                        {year.year}
                                    </span>
                                </div>
                                <div className="flex gap-2 px-3.5 py-[11px]">
                                    <Figure
                                        label={t(
                                            'business.apply.business.revenue',
                                        )}
                                        value={year.revenue}
                                        tone="ink"
                                    />
                                    <Figure
                                        label={t(
                                            'business.apply.business.costs',
                                        )}
                                        value={year.costs}
                                        tone="muted"
                                    />
                                    <Figure
                                        label={t(
                                            'business.apply.business.net_profit',
                                        )}
                                        value={year.net_profit}
                                        tone="accent"
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="mt-3 rounded-2xl border border-[#cdeddb] bg-rz-surface p-4 dark:border-rz-border">
                    <p className="text-[11px] font-bold tracking-[.02em] text-[#3d7a68] uppercase dark:text-rz-accent-app-text">
                        {t('business.apply.business.capacity')}{' '}
                        <span className="text-[10.5px] opacity-80">
                            {t('business.apply.business.capacity_basis')}
                        </span>
                    </p>
                    <p className="mt-[5px] text-[23px] font-bold tracking-[-.3px] text-rz-accent-app-text">
                        {evidence.capacity === null
                            ? t('business.apply.business.capacity_pending')
                            : formatRwf(evidence.capacity)}
                    </p>
                    <p className="mt-1.5 text-[11.5px] leading-normal text-rz-accent-app-text">
                        {t('business.apply.business.capacity_body')}
                    </p>
                </div>
            </div>
        </>
    );
}
