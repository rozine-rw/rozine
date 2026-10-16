import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { ComponentRows } from '@/components/business/repayments/components';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    BusinessRepaymentsProps,
    RepaymentPayOption,
} from '@/types/business';

/**
 * Pay from the Business wallet (C4 v1 §4a `repayment.pay`): what is due now, or the next
 * instalment early at its exact scheduled amount. Pay is offered only when the server lists
 * `repayment.pay` and the wallet covers the chosen option; when it doesn't, the panel offers a
 * top-up instead of a disabled button. The total on screen goes with the command as
 * `quoted_total`, so a total that changed meanwhile is refused rather than charged.
 */
export function PayPanel({
    pay,
    offered,
    topUp,
    busy,
    locked,
    onPay,
}: {
    pay: BusinessRepaymentsProps['pay'];
    offered: boolean;
    topUp: BusinessRepaymentsProps['links']['top_up'];
    busy: boolean;
    locked: boolean;
    onPay: (option: RepaymentPayOption) => void;
}) {
    const { t } = useTranslation();
    const [key, setKey] = useState(pay.options[0]?.key);
    const option = pay.options.find((candidate) => candidate.key === key);

    if (option === undefined) {
        return null;
    }

    const covered = pay.funding.sufficient[option.key];

    return (
        <section
            aria-label={t('business.servicing.repay.pay_title')}
            className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4"
        >
            <h2 className="text-sm font-semibold text-rz-ink">
                {t('business.servicing.repay.pay_title')}
            </h2>
            {pay.options.length > 1 && (
                <div
                    role="radiogroup"
                    aria-label={t('business.servicing.repay.pay_title')}
                    className="mt-2.5 flex flex-col gap-2"
                >
                    {pay.options.map((candidate) => (
                        <button
                            key={candidate.key}
                            type="button"
                            role="radio"
                            aria-checked={candidate.key === key}
                            onClick={() => setKey(candidate.key)}
                            className={cn(
                                'flex items-center justify-between rounded-xl border-[1.5px] px-3 py-2.5 text-left text-[13px]',
                                candidate.key === key
                                    ? 'border-rz-accent-fill'
                                    : 'border-rz-border',
                            )}
                        >
                            <span className="font-semibold text-rz-ink">
                                {t(
                                    `business.servicing.repay.option.${candidate.key}`,
                                )}
                            </span>
                            <span className="font-semibold text-rz-ink">
                                {formatRwf(candidate.amounts.total)}
                            </span>
                        </button>
                    ))}
                </div>
            )}
            <p className="mt-2 text-xs text-rz-secondary">
                {t(`business.servicing.repay.option_note.${option.key}`, {
                    list: option.instalment_indexes.join(', '),
                })}
            </p>
            <ComponentRows amounts={option.amounts} className="mt-2" />
            <p className="mt-3 text-xs text-rz-secondary">
                {t('business.servicing.repay.wallet_has', {
                    amount: formatRwf(pay.funding.available),
                })}
            </p>
            {covered && offered ? (
                <button
                    type="button"
                    onClick={() => onPay(option)}
                    disabled={busy || locked}
                    aria-busy={busy || undefined}
                    className="mt-3 h-[52px] w-full rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {busy
                        ? t('business.wallet.processing')
                        : t('business.servicing.repay.pay_cta', {
                              amount: formatRwf(option.amounts.total),
                          })}
                </button>
            ) : (
                !covered && (
                    <div
                        role="status"
                        className="mt-3 rounded-xl bg-rz-surface-sunken px-3 py-2.5 text-xs leading-[1.5] text-rz-secondary"
                    >
                        {t('business.servicing.repay.short')}{' '}
                        <Link
                            href={topUp}
                            className="font-semibold text-rz-accent-app-text"
                        >
                            {t('business.servicing.repay.top_up')}
                        </Link>
                    </div>
                )
            )}
        </section>
    );
}
