import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { MethodTile } from '@/components/business/wallet/method-tile';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatAmount, formatChip, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type { WalletFlow, WalletMethod, WalletQuote } from '@/types/business';
import type { Money } from '@/types/money';

type FlowPanelProps = {
    flow: WalletFlow;
    methods: WalletMethod[];
    quickAmounts: Money[];
    quote: WalletQuote | null;
    action: RouteAction;
    onClose: () => void;
};

/**
 * "Add money to wallet" / "Withdraw to account" (design L1348–1386). The fee and what lands are
 * the server's quote for the amount and method on screen, refreshed as the business types.
 */
export function FlowPanel({
    flow,
    methods,
    quickAmounts,
    quote,
    action,
    onClose,
}: FlowPanelProps) {
    const { t } = useTranslation();
    const form = useForm({ amount: '', method: methods[0].key });
    const { data } = form;
    const first = useRef(true);
    const withdraw = flow === 'withdraw';
    const current = quote !== null && quote.flow === flow ? quote : null;
    const error =
        form.errors.amount ??
        form.errors.method ??
        (current?.status === 'refused' ? current.message : undefined);

    useEffect(() => {
        if (first.current) {
            first.current = false;

            return;
        }

        const timer = window.setTimeout(() => {
            router.reload({
                only: ['quote'],
                data: { flow, amount: data.amount, method: data.method },
            });
        }, 450);

        return () => window.clearTimeout(timer);
    }, [flow, data.amount, data.method]);

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post(action.url, {
                    preserveScroll: true,
                    onSuccess: onClose,
                });
            }}
            aria-label={t(`business.wallet.${flow}.title`)}
            className={cn(
                'mt-2.5 origin-top animate-[rz-rolldown_.34s_cubic-bezier(.34,1.12,.5,1)_both] rounded-2xl border-[1.5px] bg-rz-surface p-[15px] shadow-[0_16px_38px_-18px_rgba(20,45,95,.3)]',
                withdraw
                    ? 'border-rz-ink'
                    : 'border-[#1e3aff] dark:border-[#3d57ff]',
            )}
        >
            <div className="flex items-center justify-between">
                <h2 className="text-[13.5px] font-semibold text-rz-ink">
                    {t(`business.wallet.${flow}.title`)}
                </h2>
                <button
                    type="button"
                    onClick={onClose}
                    aria-label={t('business.wallet.close_panel')}
                    className="flex size-[26px] items-center justify-center rounded-[10px] border border-rz-border bg-[#f3f6fc] text-sm text-rz-secondary dark:bg-rz-page"
                >
                    <span aria-hidden>✕</span>
                </button>
            </div>
            <label
                htmlFor="wallet-amount"
                className="mt-3 block text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase"
            >
                {t('business.wallet.amount')}
            </label>
            <div
                className={cn(
                    'mt-[7px] flex items-center rounded-xl border bg-[#f8fafc] px-[13px] py-[11px] dark:bg-rz-page',
                    withdraw
                        ? 'border-rz-ink'
                        : 'border-[#1e3aff] dark:border-[#3d57ff]',
                )}
            >
                <span className="text-[13px] font-semibold text-rz-secondary">
                    {t('common.currency.rwf')}
                </span>
                <input
                    id="wallet-amount"
                    inputMode="numeric"
                    value={
                        data.amount === ''
                            ? ''
                            : formatAmount({
                                  currency: 'RWF',
                                  amount: data.amount,
                              })
                    }
                    onChange={(event) =>
                        form.setData(
                            'amount',
                            event.target.value
                                .replace(/\D/gu, '')
                                .replace(/^0+/u, ''),
                        )
                    }
                    aria-invalid={error !== undefined || undefined}
                    className="min-w-0 flex-1 bg-transparent text-right text-xl font-semibold text-rz-ink outline-none"
                />
            </div>
            <div className="mt-2 flex gap-[7px]">
                {quickAmounts.map((amount) => (
                    <button
                        key={amount.amount}
                        type="button"
                        onClick={() => form.setData('amount', amount.amount)}
                        aria-label={formatRwf(amount)}
                        className="flex-1 rounded-[10px] border border-rz-border bg-[#f3f6fc] py-2 text-[11.5px] font-semibold text-rz-slate dark:bg-rz-page"
                    >
                        {formatChip(amount)}
                    </button>
                ))}
            </div>
            <p
                id="wallet-method-label"
                className="mt-3.5 text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase"
            >
                {t(`business.wallet.${flow}.method`)}
            </p>
            <div
                role="radiogroup"
                aria-labelledby="wallet-method-label"
                className="mt-2 flex flex-col gap-2"
            >
                {methods.map((method) => {
                    const on = data.method === method.key;

                    return (
                        <button
                            key={method.key}
                            type="button"
                            role="radio"
                            aria-checked={on}
                            onClick={() => form.setData('method', method.key)}
                            className={cn(
                                'flex items-center gap-[11px] rounded-xl border bg-rz-surface px-[11px] py-[9px] text-left',
                                !on && 'border-rz-border',
                                on &&
                                    (withdraw
                                        ? 'border-rz-ink'
                                        : 'border-[#d6e4ff] dark:border-[#3d57ff]'),
                            )}
                        >
                            <MethodTile kind={method.kind} />
                            <span className="flex-1 text-[13px] font-semibold text-rz-ink">
                                {method.name}
                            </span>
                            <span
                                aria-hidden
                                className={cn(
                                    'size-[17px] rounded-full border-2',
                                    !on && 'border-[#1e3aff]',
                                    on &&
                                        (withdraw
                                            ? 'border-rz-ink bg-rz-ink'
                                            : 'border-[#d6e4ff] bg-[#1e3aff]'),
                                )}
                            />
                        </button>
                    );
                })}
            </div>
            <dl className="mt-3 rounded-xl border border-[#eef2f9] bg-[#f8fafc] px-[13px] py-[3px] dark:border-rz-border dark:bg-rz-page">
                <div className="flex justify-between border-b border-[#eef2f9] py-[9px] dark:border-rz-divider">
                    <dt className="text-xs text-rz-secondary">
                        {t('business.wallet.fee')}
                    </dt>
                    <dd className="text-xs font-semibold text-rz-ink">
                        {current?.status === 'ready'
                            ? formatRwf(current.fee)
                            : '—'}
                    </dd>
                </div>
                <div className="flex justify-between py-[9px]">
                    <dt className="text-xs text-rz-secondary">
                        {t(`business.wallet.${flow}.net`)}
                    </dt>
                    <dd className="text-xs font-bold text-rz-accent-app-text">
                        {current?.status === 'ready'
                            ? formatRwf(
                                  withdraw
                                      ? current.receive
                                      : current.new_balance,
                              )
                            : '—'}
                    </dd>
                </div>
            </dl>
            {error !== undefined && (
                <p
                    role="alert"
                    className="mt-[11px] flex items-center gap-2 rounded-[10px] border border-[#fdeaea] bg-[rgba(229,72,77,.08)] px-3 py-2.5 text-xs font-semibold text-rz-danger-text dark:border-[rgba(255,107,111,.25)]"
                >
                    <Icon name="warning" tone="red" />
                    {error}
                </p>
            )}
            <button
                type="submit"
                disabled={form.processing}
                className={cn(
                    'mt-[13px] h-[50px] w-full rounded-xl text-[14.5px] font-semibold text-white disabled:opacity-80',
                    withdraw
                        ? 'bg-[#0c1830] dark:bg-[#2a3a5c]'
                        : 'bg-[#1e3aff] dark:bg-[#3d57ff]',
                )}
            >
                {form.processing
                    ? t('business.wallet.processing')
                    : t(`business.wallet.${flow}.confirm`)}
            </button>
        </form>
    );
}
