import { useState } from 'react';
import type { ReactNode } from 'react';
import { StepHeading } from '@/components/business/apply/step-heading';
import { ErrorBanner, FieldError } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatAmount, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    ApplicationQuote,
    ScheduleInstalment,
    TermMonths,
    UseOfFunds,
} from '@/types/business';

type ReadyQuote = Extract<ApplicationQuote, { status: 'ready' }>;

export type RaiseFields = {
    title: string;
    /** Whole francs as typed digits; the server owns every figure derived from it. */
    target: string;
    term_months: TermMonths | null;
    use_of_funds: UseOfFunds[];
    story: string;
};

const TERMS: TermMonths[] = [3, 4, 5, 6];

const USES: UseOfFunds[] = [
    'inventory',
    'expansion',
    'equipment',
    'hiring',
    'working_capital',
    'other',
];

const SHOWCASE = [
    { slot: 'products', icon: 'box' },
    { slot: 'facilities', icon: 'factory' },
    { slot: 'team', icon: 'people' },
    { slot: 'operations', icon: 'settings' },
    { slot: 'customers', icon: 'handshake' },
    { slot: 'impact', icon: 'globe' },
    { slot: 'brand', icon: 'sparkle' },
] as const satisfies readonly { slot: string; icon: IconName }[];

const TITLE_LIMIT = 40;

const countWords = (text: string): number =>
    text.trim() === '' ? 0 : text.trim().split(/\s+/u).length;

/** Groups a decimal integer string for display ("6783" → "6,783") without reading it as a number. */
const groupDigits = (digits: string): string =>
    digits.replace(/\B(?=(\d{3})+(?!\d))/gu, ',');

type StepRaiseProps = {
    fields: RaiseFields;
    errors: Partial<Record<keyof RaiseFields, string>>;
    quote: ApplicationQuote | null;
    quoting: boolean;
    onChange: <K extends keyof RaiseFields>(
        field: K,
        value: RaiseFields[K],
    ) => void;
};

function SectionLabel({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <p
            className={cn(
                'text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase',
                className,
            )}
        >
            {children}
        </p>
    );
}

/** The design's italic "i" button (Georgia, 17px) that opens an explainer above a box. */
function InfoButton({
    label,
    open,
    onToggle,
}: {
    label: string;
    open: boolean;
    onToggle: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onToggle}
            aria-label={label}
            aria-expanded={open}
            className={cn(
                'flex size-[17px] shrink-0 items-center justify-center rounded-full border border-[#cfe0ff] p-0 font-[Georgia,serif] text-[10px] leading-none font-bold italic',
                open
                    ? 'bg-rz-investor text-white'
                    : 'bg-rz-surface text-rz-investor-text',
            )}
        >
            i
        </button>
    );
}

function Popover({
    children,
    onClose,
    wide = false,
}: {
    children: ReactNode;
    onClose: () => void;
    wide?: boolean;
}) {
    const { t } = useTranslation();

    return (
        <>
            <button
                type="button"
                aria-label={t('business.capital.close')}
                onClick={onClose}
                className="fixed inset-0 z-40 cursor-default"
            />
            <div
                role="tooltip"
                className={cn(
                    'absolute top-[calc(100%+10px)] z-50 rounded-xl border border-[#e3e9f2] bg-rz-surface shadow-[0_14px_34px_-10px_rgba(12,24,48,.28)] dark:border-rz-border',
                    wide
                        ? 'left-1/2 w-[272px] -translate-x-1/2 p-[15px]'
                        : 'right-0 w-[190px] p-3.5',
                )}
            >
                {children}
            </div>
        </>
    );
}

/**
 * Step 2 — "Your raise" (design L450–556). The business names the raise, sets a target and term,
 * and pitches it. Every economic figure shown here is the server's quote for exactly these inputs.
 */
export function StepRaise({
    fields,
    errors,
    quote,
    quoting,
    onChange,
}: StepRaiseProps) {
    const { t } = useTranslation();
    const [info, setInfo] = useState<'rate' | 'notes' | null>(null);
    const ready = quote?.status === 'ready' ? quote : null;
    const words = countWords(fields.story);
    const titleLength = [...fields.title].length;
    const toggle = (which: 'rate' | 'notes') => () =>
        setInfo((open) => (open === which ? null : which));

    return (
        <>
            <StepHeading
                step={2}
                title={t('business.apply.raise.title')}
                subtitle={t('business.apply.raise.subtitle')}
            />

            <SectionLabel className="mt-5">
                {t('business.apply.raise.fundraise')}
            </SectionLabel>
            <div className="mt-4 flex flex-col gap-3">
                <div>
                    <div className="mb-1.5 flex items-center justify-between">
                        <label
                            htmlFor="title"
                            className="text-xs font-semibold text-rz-label uppercase"
                        >
                            {t('business.apply.raise.note_title')}
                        </label>
                        <span
                            className={cn(
                                'text-[11px] font-semibold',
                                titleLength >= TITLE_LIMIT
                                    ? 'text-rz-danger-text'
                                    : 'text-rz-secondary',
                            )}
                        >
                            {titleLength}/{TITLE_LIMIT}
                        </span>
                    </div>
                    <input
                        id="title"
                        value={fields.title}
                        maxLength={TITLE_LIMIT}
                        onChange={(event) =>
                            onChange('title', event.target.value)
                        }
                        placeholder={t(
                            'business.apply.raise.note_title_placeholder',
                        )}
                        aria-invalid={errors.title !== undefined || undefined}
                        aria-describedby="title-help"
                        className="w-full rounded-xl border border-rz-field-border bg-rz-field px-3.5 py-[13px] text-[15px] font-semibold text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border"
                    />
                    <p
                        id="title-help"
                        className="mt-1.5 text-[11px] leading-[1.45] text-rz-secondary"
                    >
                        {t('business.apply.raise.note_title_help')}
                    </p>
                    <FieldError id="title-error">{errors.title}</FieldError>
                </div>

                <div>
                    <label
                        htmlFor="target"
                        className="mb-1.5 block text-xs font-semibold text-rz-label uppercase"
                    >
                        {t('business.apply.raise.target')}
                    </label>
                    <input
                        id="target"
                        inputMode="numeric"
                        value={
                            fields.target === ''
                                ? ''
                                : formatAmount({
                                      currency: 'RWF',
                                      amount: fields.target,
                                  })
                        }
                        onChange={(event) =>
                            onChange(
                                'target',
                                event.target.value
                                    .replace(/\D/gu, '')
                                    .replace(/^0+/u, ''),
                            )
                        }
                        aria-invalid={
                            errors.target !== undefined ||
                            quote?.status === 'refused' ||
                            undefined
                        }
                        className="w-full rounded-xl border border-rz-field-border bg-rz-field px-3.5 py-[13px] text-[15px] font-semibold text-rz-ink outline-none focus:border-rz-focus-border"
                    />
                    <FieldError id="target-error">{errors.target}</FieldError>
                </div>

                {quote?.status === 'refused' && (
                    <ErrorBanner>{quote.message}</ErrorBanner>
                )}

                <div className="flex gap-2" aria-busy={quoting || undefined}>
                    <div className="min-w-0 flex-1">
                        <p className="mb-1.5 flex h-[17px] items-center text-[10.5px] font-semibold tracking-[.02em] text-rz-secondary uppercase">
                            {t('business.apply.raise.term')}
                        </p>
                        <p className="truncate rounded-xl border border-rz-border bg-rz-surface p-3 text-[13px] text-rz-ink">
                            {fields.term_months === null
                                ? '—'
                                : t('business.apply.raise.term_months', {
                                      months: fields.term_months,
                                  })}
                        </p>
                    </div>
                    <div className="relative min-w-0 flex-1">
                        <div className="mb-1.5 flex h-[17px] items-center gap-[5px]">
                            <span className="text-[10.5px] font-semibold tracking-[.02em] text-rz-secondary uppercase">
                                {t('business.apply.raise.rate')}
                            </span>
                            <InfoButton
                                label={t('business.apply.raise.rate_info')}
                                open={info === 'rate'}
                                onToggle={toggle('rate')}
                            />
                        </div>
                        <p className="rounded-xl border border-rz-border bg-rz-surface p-3 text-[13px] font-semibold whitespace-nowrap text-rz-accent-app-text">
                            {ready ? `${ready.rate_pct}%` : '—'}
                        </p>
                        {info === 'rate' && (
                            <Popover onClose={() => setInfo(null)} wide>
                                <div className="flex items-center justify-between gap-2">
                                    <span className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                                        {t('business.apply.raise.rate_how')}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => setInfo(null)}
                                        aria-label={t('business.capital.close')}
                                        className="flex size-[22px] shrink-0 items-center justify-center rounded-full bg-[#f2f4f8] p-0 text-sm leading-none text-rz-secondary dark:bg-rz-surface-muted"
                                    >
                                        ×
                                    </button>
                                </div>
                                <p className="mt-[11px] text-xs leading-[1.6] text-rz-slate">
                                    {t('business.apply.raise.rate_one_charge')}
                                </p>
                                {ready ? (
                                    <>
                                        <div className="mt-[13px] flex flex-col gap-3">
                                            <RateRow
                                                label={t(
                                                    'business.apply.raise.rate_yours',
                                                )}
                                                detail={
                                                    ready.rate_basis.band ===
                                                    null
                                                        ? t(
                                                              'business.rating.pending',
                                                          )
                                                        : t(
                                                              'business.apply.raise.rate_rated',
                                                              {
                                                                  band: t(
                                                                      `business.rating.band.${ready.rate_basis.band}`,
                                                                  ),
                                                              },
                                                          )
                                                }
                                                value={`${ready.rate_pct}%`}
                                            />
                                            <RateRow
                                                label={t(
                                                    'business.apply.raise.rate_best',
                                                )}
                                                detail={t(
                                                    'business.apply.raise.rate_best_detail',
                                                )}
                                                value={`${ready.rate_basis.floor_pct}%`}
                                                accent
                                            />
                                            <RateRow
                                                label={t(
                                                    'business.apply.raise.term',
                                                )}
                                                detail={t(
                                                    'business.apply.raise.rate_term_detail',
                                                    {
                                                        ratio: `${ready.rate_basis.term_premium.numerator}/${ready.rate_basis.term_premium.denominator}`,
                                                    },
                                                )}
                                                value={t(
                                                    'business.apply.raise.term_months',
                                                    {
                                                        months: ready.term_months,
                                                    },
                                                )}
                                            />
                                        </div>
                                        <div className="mt-[13px] flex items-center justify-between gap-3 border-t border-[#eef2f9] pt-[11px] dark:border-rz-divider">
                                            <span className="text-[11px] text-rz-secondary">
                                                {t(
                                                    'business.apply.raise.rate_cap',
                                                    {
                                                        cap: ready.rate_basis
                                                            .cap_pct,
                                                    },
                                                )}
                                            </span>
                                            <span className="text-sm font-extrabold text-rz-accent-app-text">
                                                {ready.rate_pct}%
                                            </span>
                                        </div>
                                    </>
                                ) : (
                                    <p className="mt-[13px] text-xs text-rz-secondary">
                                        {t(
                                            'business.apply.raise.quote_pending',
                                        )}
                                    </p>
                                )}
                            </Popover>
                        )}
                    </div>
                    <div className="relative min-w-0 flex-1">
                        <div className="mb-1.5 flex h-[17px] items-center gap-[5px]">
                            <span className="text-[10.5px] font-semibold tracking-[.02em] text-rz-secondary uppercase">
                                {t('business.apply.raise.notes')}
                            </span>
                            <InfoButton
                                label={t('business.apply.raise.notes_info')}
                                open={info === 'notes'}
                                onToggle={toggle('notes')}
                            />
                        </div>
                        <p className="truncate rounded-xl border border-rz-border bg-rz-surface p-3 text-[13px] font-semibold text-rz-ink">
                            {ready ? groupDigits(ready.units) : '—'}
                        </p>
                        {info === 'notes' && (
                            <Popover onClose={() => setInfo(null)}>
                                <p className="text-xs leading-[1.55] text-rz-slate">
                                    {ready
                                        ? t('business.apply.raise.note_unit', {
                                              price: formatRwf(
                                                  ready.unit_price,
                                              ),
                                          })
                                        : t(
                                              'business.apply.raise.quote_pending',
                                          )}
                                </p>
                            </Popover>
                        )}
                    </div>
                </div>

                <div>
                    <p className="mb-1.5 text-xs font-semibold text-rz-label uppercase">
                        {t('business.apply.raise.term_label')}{' '}
                        <span className="font-medium normal-case">
                            {t('business.apply.raise.term_any')}
                        </span>
                    </p>
                    <div
                        role="group"
                        aria-label={t('business.apply.raise.term')}
                        className="grid grid-cols-4 gap-[5px]"
                    >
                        {TERMS.map((months) => (
                            <button
                                key={months}
                                type="button"
                                aria-pressed={fields.term_months === months}
                                onClick={() => onChange('term_months', months)}
                                className={cn(
                                    'rounded-[10px] border py-[9px] text-xs font-bold tabular-nums',
                                    fields.term_months === months
                                        ? 'border-[#cfe9d8] bg-rz-accent-fill text-white dark:border-rz-accent-fill'
                                        : 'border-rz-border bg-rz-surface text-rz-slate',
                                )}
                            >
                                {months}
                            </button>
                        ))}
                    </div>
                    <FieldError id="term-error">
                        {errors.term_months}
                    </FieldError>
                </div>

                <Economics quote={ready} quoting={quoting} />

                <div>
                    <p className="mb-1.5 text-xs font-semibold text-rz-label uppercase">
                        {t('business.apply.raise.use_of_funds')}
                    </p>
                    <div
                        role="group"
                        aria-label={t('business.apply.raise.use_of_funds')}
                        className="flex flex-wrap gap-2"
                    >
                        {USES.map((use) => {
                            const on = fields.use_of_funds.includes(use);

                            return (
                                <button
                                    key={use}
                                    type="button"
                                    aria-pressed={on}
                                    onClick={() =>
                                        onChange(
                                            'use_of_funds',
                                            on
                                                ? fields.use_of_funds.filter(
                                                      (item) => item !== use,
                                                  )
                                                : [...fields.use_of_funds, use],
                                        )
                                    }
                                    className={cn(
                                        'rounded-[10px] border px-[13px] py-2 text-[12.5px] font-semibold',
                                        on
                                            ? 'border-[#d6e4ff] bg-[rgba(30,58,255,.10)] text-rz-investor-text dark:border-transparent'
                                            : 'border-rz-border bg-rz-surface text-rz-slate',
                                    )}
                                >
                                    {t(`business.apply.raise.use.${use}`)}
                                </button>
                            );
                        })}
                    </div>
                </div>
            </div>

            <SectionLabel className="mt-6">
                {t('business.apply.raise.show_investors')}
            </SectionLabel>
            <div className="mt-[11px] flex items-center gap-3.5">
                <div className="flex size-[66px] shrink-0 flex-col items-center justify-center rounded-2xl border-[1.5px] border-dashed border-[#dbe3f0] dark:border-rz-border">
                    <span
                        aria-hidden
                        className="text-[19px] text-rz-accent-app-text"
                    >
                        ＋
                    </span>
                    <span className="mt-0.5 text-[10.5px] text-rz-secondary">
                        {t('business.apply.raise.logo')}
                    </span>
                </div>
                <p className="text-[12.5px] leading-normal text-rz-secondary">
                    {t('business.apply.raise.photos_help')}
                </p>
            </div>
            <div className="mt-3 grid grid-cols-3 gap-2.5">
                {SHOWCASE.map(({ slot, icon }, index) => (
                    <div
                        key={slot}
                        className={cn(
                            'flex aspect-square flex-col items-center justify-center rounded-2xl border-[1.5px] border-dashed border-[#dbe3f0] dark:border-rz-border',
                            index < 2
                                ? 'bg-[rgba(30,58,255,.08)]'
                                : 'bg-[#f3f6fc] dark:bg-rz-surface-sunken',
                        )}
                    >
                        <span className="text-lg">
                            <Icon name={icon} tone="green" />
                        </span>
                        <span className="mt-[3px] text-[10.5px] text-rz-secondary">
                            {t(`business.apply.raise.slot.${slot}`)}
                        </span>
                    </div>
                ))}
            </div>

            <SectionLabel className="mt-6">
                {t('business.apply.raise.story')}
            </SectionLabel>
            <label
                htmlFor="story"
                className="mt-2 block text-[12.5px] text-rz-secondary"
            >
                {t('business.apply.raise.story_prompt')}
            </label>
            <textarea
                id="story"
                value={fields.story}
                onChange={(event) => onChange('story', event.target.value)}
                placeholder={t('business.apply.raise.story_placeholder')}
                className="mt-[11px] h-[170px] w-full resize-none rounded-2xl border border-rz-border bg-rz-field p-[15px] text-sm leading-[1.6] text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border"
            />
            <p
                className={cn(
                    'mt-2 text-right text-xs',
                    words > 100
                        ? 'text-rz-danger-text'
                        : words >= 60
                          ? 'text-rz-accent-app-text'
                          : 'text-rz-secondary',
                )}
            >
                {t('business.apply.raise.story_count', { count: words })}
            </p>
            <FieldError id="story-error">{errors.story}</FieldError>
            <div className="h-24 lg:hidden" />
        </>
    );
}

function RateRow({
    label,
    detail,
    value,
    accent = false,
}: {
    label: string;
    detail: string;
    value: string;
    accent?: boolean;
}) {
    return (
        <div className="flex items-baseline justify-between gap-3">
            <span className="min-w-0">
                <span className="block text-[13px] text-rz-slate">{label}</span>
                <span className="mt-px block text-[11px] text-rz-secondary">
                    {detail}
                </span>
            </span>
            <span
                className={cn(
                    'shrink-0 text-[13px] font-bold',
                    accent ? 'text-rz-accent-app-text' : 'text-rz-ink',
                )}
            >
                {value}
            </span>
        </div>
    );
}

/**
 * The economics card (design L505–528): every line is the server quote, verbatim. The offer is
 * the server's resized and quantized principal, and the full instalment schedule shows the final
 * residual rather than a single monthly figure.
 */
function Economics({
    quote,
    quoting,
}: {
    quote: ReadyQuote | null;
    quoting: boolean;
}) {
    const { t } = useTranslation();
    const dash = '—';

    return (
        <div
            aria-live="polite"
            aria-busy={quoting || undefined}
            className={cn(
                'rounded-2xl border border-[#dbe7ff] bg-rz-surface p-4 transition-opacity dark:border-rz-border',
                quoting && 'opacity-60',
            )}
        >
            <div className="flex flex-col gap-3">
                <div>
                    <div className="flex items-baseline justify-between gap-3">
                        <span className="text-[13.5px] text-rz-slate">
                            {t('business.apply.raise.you_receive')}
                        </span>
                        <span className="text-base font-bold whitespace-nowrap text-rz-ink">
                            {quote ? formatRwf(quote.principal) : dash}
                        </span>
                    </div>
                    {quote &&
                        quote.principal.amount !==
                            quote.requested_principal.amount && (
                            <p className="mt-[3px] text-right text-[10.5px] leading-normal text-rz-secondary">
                                {t('business.apply.raise.resized', {
                                    requested: formatRwf(
                                        quote.requested_principal,
                                    ),
                                })}
                            </p>
                        )}
                </div>
                <div className="flex items-baseline justify-between gap-3">
                    <span className="text-[13.5px] text-rz-slate">
                        {t('business.apply.raise.interest')}{' '}
                        {quote && (
                            <span className="text-[11.5px] text-rz-secondary">
                                {t('business.apply.raise.interest_basis', {
                                    rate: quote.rate_pct,
                                    months: quote.term_months,
                                })}
                            </span>
                        )}
                    </span>
                    <span className="text-base font-bold whitespace-nowrap text-rz-ink">
                        {quote ? formatRwf(quote.interest) : dash}
                    </span>
                </div>
                <div className="my-0.5 h-px bg-[#dbe7ff] dark:bg-rz-divider" />
                <div className="flex items-baseline justify-between gap-2.5 whitespace-nowrap">
                    <span className="text-sm font-bold text-rz-ink">
                        {t('business.apply.raise.you_repay')}
                    </span>
                    <span className="text-lg font-extrabold tracking-[-.3px] text-rz-ink">
                        {quote ? formatRwf(quote.total) : dash}
                    </span>
                </div>
                {quote && <InstalmentSchedule schedule={quote.schedule} />}
                {quote?.reserve && (
                    <div className="mt-1 flex flex-col gap-[9px] border-t border-[#dbe7ff] pt-[11px] dark:border-rz-divider">
                        <div className="flex items-baseline justify-between gap-3">
                            <span className="min-w-0">
                                <span className="block text-[12.5px] text-rz-slate">
                                    {t('business.apply.raise.reserve')}
                                </span>
                                <span className="mt-px block text-[10.5px] text-rz-secondary">
                                    {t('business.apply.raise.reserve_detail')}
                                </span>
                            </span>
                            <span className="text-[13px] font-semibold whitespace-nowrap text-rz-ink">
                                {formatRwf(quote.reserve)}
                            </span>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

/** Every instalment as the server scheduled it; the last one carries any residual. */
export function InstalmentSchedule({
    schedule,
}: {
    schedule: ScheduleInstalment[];
}) {
    const { t } = useTranslation();
    const last = schedule.at(-1)?.instalment;

    return (
        <div className="border-t border-[#dbe7ff] pt-[11px] dark:border-rz-divider">
            <p className="text-[10.5px] font-bold tracking-[.04em] text-rz-slate uppercase">
                {t('business.apply.raise.schedule')}
            </p>
            <p className="mt-px text-[10.5px] leading-normal text-rz-secondary">
                {t('business.apply.raise.first_payment')}
            </p>
            <ol
                aria-label={t('business.apply.raise.schedule')}
                className="mt-2 flex flex-col gap-[7px]"
            >
                {schedule.map((row) => (
                    <li
                        key={row.instalment}
                        className="flex items-baseline justify-between gap-3"
                    >
                        <span className="text-[12.5px] text-rz-slate">
                            {t(
                                row.instalment === last
                                    ? 'business.apply.raise.instalment_final'
                                    : 'business.apply.raise.instalment',
                                { n: row.instalment },
                            )}
                        </span>
                        <span className="text-[13px] font-semibold whitespace-nowrap text-rz-ink">
                            {formatRwf(row.amount)}
                        </span>
                    </li>
                ))}
            </ol>
        </div>
    );
}
