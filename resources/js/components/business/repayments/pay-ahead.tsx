import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { FieldError } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatAmount, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type { BusinessRepaymentsProps } from '@/types/business';

type PayAheadProps = {
    payAhead: NonNullable<BusinessRepaymentsProps['pay_ahead']>;
    source: string;
    action: RouteAction;
};

function Tick() {
    return (
        <span
            aria-hidden
            className="flex size-4 items-center justify-center rounded-full bg-rz-accent-fill"
        >
            <svg viewBox="0 0 24 24" fill="none" className="size-2.5">
                <path
                    d="M5 13l4 4L19 7"
                    stroke="#fff"
                    strokeWidth="3.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        </span>
    );
}

/**
 * "Get ahead on repayments" (design L1153–1188): the next few instalments, the whole balance, or
 * any amount up to what is owed. Paying early never costs a penalty; the server settles it.
 */
export function PayAhead({ payAhead, source, action }: PayAheadProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        choice: payAhead.options[0].key as string,
        amount: '',
    });
    const { data } = form;
    const chosen = payAhead.options.find(
        (option) => option.key === data.choice,
    );
    const typed = data.amount === '' ? 0 : Number(data.amount);
    const tooMuch = typed > Number(payAhead.max.amount);
    const ready = chosen !== undefined || (typed > 0 && !tooMuch);

    const label = (): string => {
        if (chosen?.key === 'full') {
            return t('business.repayments.ahead.settle_cta', {
                amount: formatRwf(chosen.amount),
            });
        }

        if (chosen !== undefined) {
            return t('business.repayments.ahead.pay_cta', {
                amount: formatRwf(chosen.amount),
            });
        }

        return typed > 0
            ? t('business.repayments.ahead.pay_cta', {
                  amount: formatRwf({ currency: 'RWF', amount: data.amount }),
              })
            : t('business.repayments.ahead.enter');
    };

    return (
        <div className="mt-4 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
            <button
                type="button"
                aria-expanded={open}
                onClick={() => setOpen((value) => !value)}
                className="flex w-full items-center gap-3 bg-rz-surface p-[15px] text-left"
            >
                <span className="flex size-9 shrink-0 items-center justify-center rounded-[10px] bg-rz-accent-soft text-[17px]">
                    <Icon name="flash" tone="green" />
                </span>
                <span className="flex-1">
                    <span className="block text-sm font-semibold text-rz-ink">
                        {t('business.repayments.ahead.title')}
                    </span>
                    <span className="mt-px block text-[11.5px] text-rz-secondary">
                        {t('business.repayments.ahead.subtitle')}
                    </span>
                </span>
                <span
                    aria-hidden
                    className={cn(
                        'text-rz-secondary transition-transform duration-200',
                        open && 'rotate-90',
                    )}
                >
                    ›
                </span>
            </button>
            {open && (
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.transform((current) => ({ ...current, source }));
                        form.post(action.url, { preserveScroll: true });
                    }}
                    className="px-[15px] pt-0.5 pb-4"
                >
                    <div className="mb-[13px] h-px bg-rz-page" />
                    <p
                        id="ahead-choice"
                        className="mb-[9px] text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase"
                    >
                        {t('business.repayments.ahead.choose')}
                    </p>
                    <div
                        role="radiogroup"
                        aria-labelledby="ahead-choice"
                        className="flex gap-[9px]"
                    >
                        {payAhead.options.map((option) => {
                            const on = data.choice === option.key;

                            return (
                                <button
                                    key={option.key}
                                    type="button"
                                    role="radio"
                                    aria-checked={on}
                                    onClick={() =>
                                        form.setData('choice', option.key)
                                    }
                                    className={cn(
                                        'relative min-w-0 flex-1 rounded-xl border-[1.5px] px-[13px] py-3 text-left',
                                        on
                                            ? 'border-[#cfe9d8] bg-[rgba(29,158,117,.08)] dark:border-rz-accent-fill'
                                            : 'border-rz-border bg-rz-surface',
                                    )}
                                >
                                    {on && (
                                        <span className="absolute top-2.5 right-[11px]">
                                            <Tick />
                                        </span>
                                    )}
                                    <span
                                        className={cn(
                                            'block text-xs font-semibold',
                                            on
                                                ? 'text-rz-accent-app-text'
                                                : 'text-rz-secondary',
                                        )}
                                    >
                                        {option.key === 'full'
                                            ? t(
                                                  'business.repayments.ahead.full',
                                              )
                                            : t(
                                                  option.months === 1
                                                      ? 'business.repayments.ahead.next_month'
                                                      : 'business.repayments.ahead.next_months',
                                                  { count: option.months },
                                              )}
                                    </span>
                                    <span className="mt-1 block text-[14.5px] font-bold tracking-[-.3px] text-rz-ink">
                                        {formatRwf(option.amount)}
                                    </span>
                                    <span className="mt-0.5 block text-[10.5px] text-rz-secondary">
                                        {option.key === 'full'
                                            ? t(
                                                  option.last
                                                      ? 'business.repayments.ahead.one_left'
                                                      : 'business.repayments.ahead.clear_note',
                                              )
                                            : t(
                                                  option.months === 1
                                                      ? 'business.repayments.ahead.upfront_one'
                                                      : 'business.repayments.ahead.upfront',
                                                  { count: option.months },
                                              )}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    <div
                        className={cn(
                            'mt-[9px] rounded-xl border-[1.5px] px-[13px] py-3',
                            data.choice === 'custom'
                                ? 'border-rz-accent-fill bg-[rgba(29,158,117,.08)]'
                                : 'border-rz-border bg-rz-surface',
                        )}
                    >
                        <div className="flex items-center justify-between">
                            <label
                                htmlFor="ahead-amount"
                                className={cn(
                                    'text-xs font-semibold',
                                    data.choice === 'custom'
                                        ? 'text-rz-accent-app-text'
                                        : 'text-rz-secondary',
                                )}
                            >
                                {t('business.repayments.ahead.custom')}
                            </label>
                            {data.choice === 'custom' && <Tick />}
                        </div>
                        <div className="mt-1.5 flex items-center gap-[7px]">
                            <span className="text-sm font-bold text-rz-secondary">
                                {t('common.currency.rwf')}
                            </span>
                            <input
                                id="ahead-amount"
                                inputMode="numeric"
                                value={
                                    data.amount === ''
                                        ? ''
                                        : formatAmount({
                                              currency: 'RWF',
                                              amount: data.amount,
                                          })
                                }
                                onFocus={() => form.setData('choice', 'custom')}
                                onChange={(event) => {
                                    const digits = event.target.value
                                        .replace(/\D/gu, '')
                                        .replace(/^0+/u, '')
                                        .slice(0, 12);

                                    form.setData((current) => ({
                                        ...current,
                                        choice: 'custom',
                                        amount: digits,
                                    }));
                                }}
                                placeholder={t(
                                    'business.repayments.ahead.placeholder',
                                )}
                                aria-invalid={tooMuch || undefined}
                                className="min-w-0 flex-1 bg-transparent text-lg font-bold tracking-[-.3px] text-rz-ink outline-none placeholder:text-rz-faint"
                            />
                        </div>
                        <p
                            className={cn(
                                'mt-[3px] text-[10.5px]',
                                tooMuch
                                    ? 'text-rz-danger-text'
                                    : 'text-rz-secondary',
                            )}
                        >
                            {t(
                                tooMuch
                                    ? 'business.repayments.ahead.too_much'
                                    : 'business.repayments.ahead.up_to',
                                { amount: formatRwf(payAhead.max) },
                            )}
                        </p>
                    </div>
                    <FieldError id="ahead-error">
                        {form.errors.amount}
                    </FieldError>
                    <p className="mt-3 rounded-xl border border-[#cfe9d8] bg-[#f0f9f3] p-3 text-[11.5px] leading-[1.55] text-[#3d7a68] dark:border-transparent dark:bg-rz-accent-soft dark:text-rz-accent-app-text">
                        {t('business.repayments.ahead.why')}
                    </p>
                    <button
                        type="submit"
                        disabled={!ready || form.processing}
                        className={cn(
                            'mt-[13px] h-[50px] w-full rounded-xl text-[14.5px] font-semibold text-white',
                            ready
                                ? 'bg-rz-accent-fill'
                                : 'cursor-not-allowed bg-[#b8c2d3] dark:bg-rz-surface-muted',
                        )}
                    >
                        {label()}
                    </button>
                </form>
            )}
        </div>
    );
}
