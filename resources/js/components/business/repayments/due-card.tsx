import { ComponentRows } from '@/components/business/repayments/components';
import { Figure } from '@/components/business/repayments/progress-card';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessRepaymentsProps } from '@/types/business';

/**
 * What is owed now, or the next instalment when nothing is (design L1095–1132, C4 v1 §4a). Every
 * amount, DPD and date is the server's; a coming late fee is labelled as a projection, and the
 * automatic wallet collection (#99 R7) says when it will next try.
 */
export function DueCard({
    servicing,
    basis,
}: {
    servicing: BusinessRepaymentsProps['servicing'];
    basis: BusinessRepaymentsProps['bases']['due_now'];
}) {
    const { t, locale } = useTranslation();
    const { due_now: dueNow, next, state } = servicing;

    if (state === 'repaid') {
        return (
            <p
                role="status"
                className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4 text-sm font-semibold text-rz-accent-app-text"
            >
                {t('business.servicing.repay.repaid')}
            </p>
        );
    }

    const overdue = state === 'overdue';

    return (
        <section
            aria-label={t('business.servicing.repay.due_title')}
            className={cn(
                'mt-4 rounded-2xl border bg-rz-surface p-4',
                overdue ? 'border-[rgba(229,72,77,.35)]' : 'border-rz-border',
            )}
        >
            {servicing.restriction !== null && (
                <p
                    role="status"
                    className="mb-3 flex items-start gap-2 rounded-xl bg-[rgba(229,72,77,.08)] px-3 py-2.5 text-xs leading-[1.5] text-rz-danger-text"
                >
                    <Icon name="warning" tone="red" />
                    {t(
                        `business.servicing.repay.restriction.${servicing.restriction.code}`,
                        {
                            date: formatDate(
                                servicing.restriction.since,
                                locale,
                            ),
                        },
                    )}
                </p>
            )}
            {dueNow !== null ? (
                <>
                    <h2 className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('business.servicing.repay.due_title')}
                    </h2>
                    <p
                        className={cn(
                            'mt-1 text-2xl font-bold tracking-[-.5px]',
                            overdue ? 'text-rz-danger-text' : 'text-rz-ink',
                        )}
                    >
                        <Figure basis={basis}>{formatRwf(dueNow.total)}</Figure>
                    </p>
                    <p className="mt-0.5 text-xs text-rz-secondary">
                        {!overdue
                            ? t('business.servicing.repay.due_today')
                            : servicing.dpd === null
                              ? t('business.servicing.repay.state.overdue')
                              : t('business.servicing.repay.dpd', {
                                    count: servicing.dpd,
                                })}
                    </p>
                    <ComponentRows amounts={dueNow} className="mt-3" />
                </>
            ) : (
                next !== null && (
                    <>
                        <h2 className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                            {t('business.servicing.repay.next_title', {
                                index: next.index,
                            })}
                        </h2>
                        <p className="mt-1 text-2xl font-bold tracking-[-.5px] text-rz-ink">
                            {formatRwf(next.amounts.total)}
                        </p>
                        <p className="mt-0.5 text-xs text-rz-secondary">
                            {t('business.servicing.repay.due_on', {
                                date: formatDate(next.due_on, locale),
                            })}
                        </p>
                        <ComponentRows
                            amounts={next.amounts}
                            className="mt-3"
                        />
                    </>
                )
            )}
            {servicing.next_late_fee !== null && (
                <p className="mt-3 rounded-xl bg-[rgba(194,102,31,.08)] px-3 py-2.5 text-[11.5px] leading-[1.5] text-rz-ink">
                    {t('business.servicing.repay.next_late_fee', {
                        date: formatDate(
                            servicing.next_late_fee.applies_on,
                            locale,
                        ),
                        amount: formatRwf(servicing.next_late_fee.projected),
                    })}
                </p>
            )}
            {servicing.autocollect !== null && (
                <p className="mt-3 flex items-start gap-2 text-[11.5px] leading-[1.5] text-rz-secondary">
                    <Icon name="wallet" />
                    {servicing.autocollect.mode === 'off' ||
                    servicing.autocollect.next_attempt_on === null
                        ? t('business.servicing.repay.autocollect_off')
                        : t('business.servicing.repay.autocollect', {
                              date: formatDate(
                                  servicing.autocollect.next_attempt_on,
                                  locale,
                              ),
                          })}
                </p>
            )}
        </section>
    );
}
