import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { BusinessShell } from '@/components/business/business-shell';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatDateTime,
    formatDayMonth,
    formatMonthYearLong,
    formatRwf,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    BusinessMonthlyFiguresProps,
    MonthlyChange,
    MonthlyCostCategory,
    MonthlyFigures,
} from '@/types/business';

const INPUT =
    'w-full rounded-xl border border-rz-border bg-rz-field px-3.5 py-[13px] text-sm text-rz-ink outline-none placeholder:text-rz-placeholder focus:border-rz-focus-border';

const CHANGES = [
    'owners',
    'premises',
    'borrowing',
    'customers',
] as const satisfies readonly MonthlyChange[];

const STEPS = [
    'money_in',
    'costs',
    'paid',
    'forecast',
    'changes',
    'summary',
] as const;

type Step = (typeof STEPS)[number];

/** A forecast this far from the pre-fill, as a share of it, needs a signed order (VIS-R04). */
const ORDER_THRESHOLD = 0.25;

const rwf = (amount: string) => formatRwf({ currency: 'RWF', amount });

const digits = (value: string) => value.replace(/\D/gu, '').slice(0, 15);

const sum = (values: string[]) =>
    values
        .reduce((total, value) => total + BigInt(value === '' ? 0 : value), 0n)
        .toString();

/** Whether the forecast moves more than 25% from a basis above zero (the server decides). */
const needsOrder = (forecast: string, basis: string | null) => {
    if (basis === null || forecast === '' || BigInt(basis) === 0n) {
        return false;
    }

    const change = BigInt(forecast) - BigInt(basis);
    const distance = change < 0n ? -change : change;

    return distance * 100n > BigInt(basis) * BigInt(ORDER_THRESHOLD * 100);
};

/** The step a field the server refused is entered on. */
const stepOf = (field: string): Step => {
    if (field.startsWith('costs')) {
        return 'costs';
    }

    if (field.startsWith('changes')) {
        return 'changes';
    }

    if (field === 'paid_to_owners') {
        return 'paid';
    }

    if (field === 'forecast' || field === 'order') {
        return 'forecast';
    }

    return field === 'money_in' ? 'money_in' : 'summary';
};

function Amount({
    id,
    label,
    value,
    error,
    onChange,
}: {
    id: string;
    label: string;
    value: string;
    error?: string;
    onChange: (value: string) => void;
}) {
    return (
        <div>
            <label
                htmlFor={id}
                className="mb-1.5 block text-xs font-semibold text-rz-label uppercase"
            >
                {label}
            </label>
            <input
                id={id}
                inputMode="numeric"
                autoComplete="off"
                value={value}
                onChange={(event) => onChange(digits(event.target.value))}
                placeholder="0"
                aria-describedby={`${id}-error`}
                aria-invalid={error !== undefined || undefined}
                className={INPUT}
            />
            <FieldError id={`${id}-error`}>{error}</FieldError>
        </div>
    );
}

/** What was sent for the month, line by line. */
function Sent({
    figures,
    categories,
    forecastMonth,
}: {
    figures: MonthlyFigures;
    categories: MonthlyCostCategory[];
    forecastMonth: string;
}) {
    const { t } = useTranslation();
    const rows: [string, string][] = [
        [t('business.monthly_figures.money_in.label'), rwf(figures.money_in)],
        ...categories.map((category): [string, string] => [
            t(`business.monthly_figures.costs.${category}`),
            rwf(figures.costs[category]),
        ]),
        [t('business.monthly_figures.costs.total'), rwf(figures.costs_total)],
        [t('business.monthly_figures.paid.label'), rwf(figures.paid_to_owners)],
        [
            t('business.monthly_figures.forecast.label', {
                month: forecastMonth,
            }),
            rwf(figures.forecast),
        ],
        ...(figures.order === null
            ? []
            : [
                  [
                      t('business.monthly_figures.forecast.order'),
                      figures.order,
                  ] as [string, string],
              ]),
        ...CHANGES.map((change): [string, string] => [
            t(`business.monthly_figures.changes.${change}`),
            t(
                figures.changes[change]
                    ? 'business.monthly_figures.changes.yes'
                    : 'business.monthly_figures.changes.no',
            ),
        ]),
    ];

    return (
        <dl className="mt-3 rounded-2xl border border-rz-border bg-rz-surface px-4 py-1">
            {rows.map(([label, value], index) => (
                <div
                    key={label}
                    className={cn(
                        'flex items-start justify-between gap-3 py-2.5 text-[13px]',
                        index > 0 &&
                            'border-t border-[#eef2f9] dark:border-rz-divider',
                    )}
                >
                    <dt className="min-w-0 text-rz-secondary">{label}</dt>
                    <dd className="text-right font-semibold text-rz-ink">
                        {value}
                    </dd>
                </div>
            ))}
        </dl>
    );
}

/**
 * Monthly figures (BRS v3.0 VIS-R04), opened from "Get ready for your audit": six short screens,
 * money in, costs in the CPA's six categories, paid to owners, next month's forecast pre-filled
 * with the verified 3-month average, four yes/no changes, then a one-line summary to send. The
 * Audit Partner verifies these figures at the visit. Once sent they are final and the page shows
 * them. Every line states a fact; none asks a question.
 */
export default function BusinessMonthlyFigures({
    identity_context_revision,
    period,
    seal_by,
    forecast_period,
    forecast_basis,
    cost_categories,
    sent,
    links,
}: BusinessMonthlyFiguresProps) {
    const { t, locale } = useTranslation();
    const month = formatMonthYearLong(period, locale);
    const forecastMonth = formatMonthYearLong(forecast_period, locale);
    const [step, setStep] = useState<Step>('money_in');
    const form = useForm({
        money_in: '',
        costs: Object.fromEntries(
            cost_categories.map((category) => [category, '']),
        ) as Record<MonthlyCostCategory, string>,
        paid_to_owners: '',
        forecast: forecast_basis ?? '',
        order: '',
        changes: {} as Partial<Record<MonthlyChange, boolean>>,
    });
    const { data, errors, processing } = form;
    const fieldErrors = errors as Record<string, string | undefined>;
    const orderNeeded = needsOrder(data.forecast, forecast_basis);
    const index = STEPS.indexOf(step);
    const costsTotal = sum(
        cost_categories.map((category) => data.costs[category]),
    );
    const complete: Record<Step, boolean> = {
        money_in: data.money_in !== '',
        costs: cost_categories.every((category) => data.costs[category] !== ''),
        paid: data.paid_to_owners !== '',
        forecast:
            data.forecast !== '' && (!orderNeeded || data.order.trim() !== ''),
        changes: CHANGES.every((change) => data.changes[change] !== undefined),
        summary: true,
    };

    const submit = () => {
        form.transform((current) => ({
            ...current,
            order: needsOrder(current.forecast, forecast_basis)
                ? current.order
                : null,
            request_id: crypto.randomUUID(),
            identity_context_revision,
        }));
        form.post(links.submit.url, {
            preserveScroll: true,
            onError: (refused) => {
                const first = Object.keys(refused)[0];

                if (first !== undefined) {
                    setStep(stepOf(first));
                }
            },
        });
    };

    return (
        <BusinessShell
            title={t('business.monthly_figures.title')}
            tab="home"
            links={links}
            showTabBar={false}
        >
            <div className="mx-auto max-w-[560px] px-5 pt-[calc(env(safe-area-inset-top)+4px)] pb-10 lg:pt-6">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.close.url}
                        aria-label={t('business.note.back')}
                        className="flex size-[38px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <h1 className="text-xl font-semibold text-rz-ink">
                        {t('business.monthly_figures.title')}
                    </h1>
                </div>
                <p className="mt-3 text-[13px] leading-[1.55] text-rz-secondary">
                    {t('business.monthly_figures.intro', {
                        month,
                        date: formatDayMonth(seal_by, locale),
                    })}
                </p>

                {sent !== null ? (
                    <section aria-labelledby="monthly-figures-sent">
                        <h2
                            id="monthly-figures-sent"
                            className="mt-5 text-xs font-bold tracking-[.04em] text-rz-slate uppercase"
                        >
                            {t('business.monthly_figures.sent.title', {
                                month,
                            })}
                        </h2>
                        <p
                            role="status"
                            className="mt-2 text-[12.5px] leading-[1.55] text-rz-ink"
                        >
                            {t('business.monthly_figures.sent.body', {
                                date: formatDateTime(sent.submitted_at, locale),
                            })}
                        </p>
                        <Sent
                            figures={sent}
                            categories={cost_categories}
                            forecastMonth={forecastMonth}
                        />
                    </section>
                ) : (
                    <form
                        className="mt-5"
                        aria-labelledby="monthly-figures-step"
                        onSubmit={(event) => {
                            event.preventDefault();

                            if (step === 'summary') {
                                submit();
                            } else if (complete[step]) {
                                setStep(STEPS[index + 1]);
                            }
                        }}
                    >
                        <p className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                            {t('business.monthly_figures.step', {
                                current: index + 1,
                                total: STEPS.length,
                            })}
                        </p>
                        <h2
                            id="monthly-figures-step"
                            className="mt-1 text-[17px] font-bold text-rz-ink"
                        >
                            {t(`business.monthly_figures.${step}.title`, {
                                month,
                                forecast: forecastMonth,
                            })}
                        </h2>

                        <div className="mt-3 flex flex-col gap-3.5">
                            {step === 'money_in' && (
                                <>
                                    <p className="text-[12.5px] leading-[1.55] text-rz-secondary">
                                        {t(
                                            'business.monthly_figures.money_in.body',
                                        )}
                                    </p>
                                    <Amount
                                        id="monthly-money-in"
                                        label={t(
                                            'business.monthly_figures.money_in.label',
                                        )}
                                        value={data.money_in}
                                        error={fieldErrors.money_in}
                                        onChange={(value) =>
                                            form.setData('money_in', value)
                                        }
                                    />
                                </>
                            )}

                            {step === 'costs' && (
                                <>
                                    <p className="text-[12.5px] leading-[1.55] text-rz-secondary">
                                        {t(
                                            'business.monthly_figures.costs.body',
                                        )}
                                    </p>
                                    {cost_categories.map((category) => (
                                        <Amount
                                            key={category}
                                            id={`monthly-costs-${category}`}
                                            label={t(
                                                `business.monthly_figures.costs.${category}`,
                                            )}
                                            value={data.costs[category]}
                                            error={
                                                fieldErrors[`costs.${category}`]
                                            }
                                            onChange={(value) =>
                                                form.setData('costs', {
                                                    ...data.costs,
                                                    [category]: value,
                                                })
                                            }
                                        />
                                    ))}
                                    <p className="text-[13px] font-semibold text-rz-ink">
                                        {t(
                                            'business.monthly_figures.costs.total_line',
                                            { amount: rwf(costsTotal) },
                                        )}
                                    </p>
                                </>
                            )}

                            {step === 'paid' && (
                                <>
                                    <p className="text-[12.5px] leading-[1.55] text-rz-secondary">
                                        {t(
                                            'business.monthly_figures.paid.body',
                                        )}
                                    </p>
                                    <Amount
                                        id="monthly-paid"
                                        label={t(
                                            'business.monthly_figures.paid.label',
                                        )}
                                        value={data.paid_to_owners}
                                        error={fieldErrors.paid_to_owners}
                                        onChange={(value) =>
                                            form.setData(
                                                'paid_to_owners',
                                                value,
                                            )
                                        }
                                    />
                                </>
                            )}

                            {step === 'forecast' && (
                                <>
                                    <p className="text-[12.5px] leading-[1.55] text-rz-secondary">
                                        {forecast_basis === null
                                            ? t(
                                                  'business.monthly_figures.forecast.no_basis',
                                              )
                                            : t(
                                                  'business.monthly_figures.forecast.basis',
                                                  {
                                                      amount: rwf(
                                                          forecast_basis,
                                                      ),
                                                  },
                                              )}
                                    </p>
                                    <Amount
                                        id="monthly-forecast"
                                        label={t(
                                            'business.monthly_figures.forecast.label',
                                            { month: forecastMonth },
                                        )}
                                        value={data.forecast}
                                        error={fieldErrors.forecast}
                                        onChange={(value) =>
                                            form.setData('forecast', value)
                                        }
                                    />
                                    {orderNeeded && (
                                        <div>
                                            <label
                                                htmlFor="monthly-order"
                                                className="mb-1.5 block text-xs font-semibold text-rz-label uppercase"
                                            >
                                                {t(
                                                    'business.monthly_figures.forecast.order',
                                                )}
                                            </label>
                                            <p
                                                id="monthly-order-hint"
                                                className="mb-2 text-xs leading-normal text-rz-secondary"
                                            >
                                                {t(
                                                    'business.monthly_figures.forecast.order_hint',
                                                )}
                                            </p>
                                            <input
                                                id="monthly-order"
                                                maxLength={200}
                                                value={data.order}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'order',
                                                        event.target.value,
                                                    )
                                                }
                                                aria-describedby="monthly-order-hint monthly-order-error"
                                                aria-invalid={
                                                    fieldErrors.order !==
                                                        undefined || undefined
                                                }
                                                className={INPUT}
                                            />
                                            <FieldError id="monthly-order-error">
                                                {fieldErrors.order}
                                            </FieldError>
                                        </div>
                                    )}
                                </>
                            )}

                            {step === 'changes' &&
                                CHANGES.map((change) => (
                                    <fieldset
                                        key={change}
                                        aria-describedby={`monthly-change-${change}-error`}
                                    >
                                        <legend className="mb-1.5 text-[13px] font-semibold text-rz-ink">
                                            {t(
                                                `business.monthly_figures.changes.${change}`,
                                            )}
                                        </legend>
                                        <div className="flex gap-2">
                                            {([true, false] as const).map(
                                                (answer) => (
                                                    <label
                                                        key={String(answer)}
                                                        className={cn(
                                                            'flex flex-1 cursor-pointer items-center justify-center rounded-xl border px-3 py-2.5 text-[13px] font-semibold',
                                                            data.changes[
                                                                change
                                                            ] === answer
                                                                ? 'border-rz-accent-fill bg-rz-accent-soft text-rz-accent-app-text'
                                                                : 'border-rz-border bg-rz-surface text-rz-ink',
                                                        )}
                                                    >
                                                        <input
                                                            type="radio"
                                                            name={`change-${change}`}
                                                            className="sr-only"
                                                            checked={
                                                                data.changes[
                                                                    change
                                                                ] === answer
                                                            }
                                                            onChange={() =>
                                                                form.setData(
                                                                    'changes',
                                                                    {
                                                                        ...data.changes,
                                                                        [change]:
                                                                            answer,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                        {t(
                                                            answer
                                                                ? 'business.monthly_figures.changes.yes'
                                                                : 'business.monthly_figures.changes.no',
                                                        )}
                                                    </label>
                                                ),
                                            )}
                                        </div>
                                        <FieldError
                                            id={`monthly-change-${change}-error`}
                                        >
                                            {fieldErrors[`changes.${change}`]}
                                        </FieldError>
                                    </fieldset>
                                ))}

                            {step === 'summary' && (
                                <>
                                    <p className="rounded-2xl border border-rz-border bg-rz-surface p-3.5 text-[13px] leading-[1.55] text-rz-ink">
                                        {t(
                                            'business.monthly_figures.summary.line',
                                            {
                                                month,
                                                money_in: rwf(data.money_in),
                                                costs: rwf(costsTotal),
                                                paid: rwf(data.paid_to_owners),
                                                forecast: rwf(data.forecast),
                                                forecast_month: forecastMonth,
                                            },
                                        )}
                                    </p>
                                    <p className="text-xs leading-normal text-rz-secondary">
                                        {t(
                                            'business.monthly_figures.summary.final',
                                        )}
                                    </p>
                                    <FieldError id="monthly-form-error">
                                        {fieldErrors.form}
                                    </FieldError>
                                </>
                            )}
                        </div>

                        <div className="mt-6 flex gap-2.5">
                            {index > 0 && (
                                <button
                                    type="button"
                                    onClick={() => setStep(STEPS[index - 1])}
                                    className="flex-1 rounded-xl border border-rz-border bg-rz-surface px-4 py-3 text-sm font-semibold text-rz-ink"
                                >
                                    {t('business.monthly_figures.back')}
                                </button>
                            )}
                            <button
                                type="submit"
                                disabled={!complete[step] || processing}
                                aria-busy={processing || undefined}
                                className="flex-1 rounded-xl bg-rz-accent-fill px-4 py-3 text-sm font-semibold text-white disabled:opacity-50"
                            >
                                {step !== 'summary'
                                    ? t('business.monthly_figures.next')
                                    : processing
                                      ? t('business.monthly_figures.sending')
                                      : t('business.monthly_figures.send')}
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </BusinessShell>
    );
}
