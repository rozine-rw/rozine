import { Link, useForm } from '@inertiajs/react';
import { BusinessShell } from '@/components/business/business-shell';
import { DetailSheet } from '@/components/business/detail-sheet';
import { HomeBody } from '@/components/business/home/home-body';
import { LatePolicy } from '@/components/business/repayments/late-policy';
import { PayAhead } from '@/components/business/repayments/pay-ahead';
import { ProgressCard } from '@/components/business/repayments/progress-card';
import { Receipt } from '@/components/business/repayments/receipt';
import { ScheduleList } from '@/components/business/repayments/schedule-list';
import { ThisMonth } from '@/components/business/repayments/this-month';
import { FieldError } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    BusinessRepaymentsProps,
    RepaymentSource,
} from '@/types/business';

const SOURCE_ICON: Record<RepaymentSource['kind'], IconName> = {
    wallet: 'wallet',
    bank: 'bank',
    mobile_money: 'phone',
};

/**
 * Repayments (MVP-BUSINESS-SCR-05, design L1060–1262), opened from Today's repayment or a note's
 * Pay. Amounts, the schedule and the late-fee ladder are the server's; the business picks a source
 * and confirms. The design's top-up and deferral flows are not in the MVP.
 */
export default function BusinessRepayments(props: BusinessRepaymentsProps) {
    const {
        home,
        progress,
        this_month: thisMonth,
        sources,
        pay_ahead: payAhead,
        schedule,
        late_ladder: ladder,
        receipt,
        links,
        actions,
    } = props;
    const { t } = useTranslation();
    const form = useForm({ source: sources[0].key });

    const sheet = (
        <DetailSheet
            label={t('business.repayments.title')}
            close={links.close}
            closeLabel={t('business.note.close')}
            dismissible={!form.processing}
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+2px)] pb-10 lg:pt-[18px]">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.close}
                        aria-label={t('business.note.back')}
                        className="flex size-[38px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <h1 className="text-xl font-semibold text-rz-ink">
                        {t('business.repayments.title')}
                    </h1>
                </div>
                {receipt !== null ? (
                    <Receipt receipt={receipt} home={links.close} />
                ) : (
                    <>
                        <ProgressCard progress={progress} />
                        <ThisMonth thisMonth={thisMonth} />
                        {thisMonth.state !== 'paid' && (
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    form.post(actions.pay.url, {
                                        preserveScroll: true,
                                    });
                                }}
                            >
                                <p
                                    id="repay-source"
                                    className="mt-4 text-[13px] font-semibold text-rz-secondary uppercase"
                                >
                                    {t('business.repayments.source')}
                                </p>
                                <div
                                    role="radiogroup"
                                    aria-labelledby="repay-source"
                                    className="mt-2.5 flex flex-col gap-2.5"
                                >
                                    {sources.map((source) => {
                                        const on =
                                            form.data.source === source.key;

                                        return (
                                            <button
                                                key={source.key}
                                                type="button"
                                                role="radio"
                                                aria-checked={on}
                                                onClick={() =>
                                                    form.setData(
                                                        'source',
                                                        source.key,
                                                    )
                                                }
                                                className={cn(
                                                    'flex items-center gap-[13px] rounded-xl border bg-rz-surface p-3.5 text-left',
                                                    on
                                                        ? 'border-[#cfe9d8] dark:border-rz-accent-fill'
                                                        : 'border-rz-border',
                                                )}
                                            >
                                                <span className="flex size-10 items-center justify-center rounded-[10px] bg-rz-page text-lg">
                                                    <Icon
                                                        name={
                                                            SOURCE_ICON[
                                                                source.kind
                                                            ]
                                                        }
                                                    />
                                                </span>
                                                <span className="flex-1">
                                                    <span className="block text-sm font-semibold text-rz-ink">
                                                        {source.name}
                                                    </span>
                                                    <span className="block text-xs text-rz-secondary">
                                                        {source.detail}
                                                    </span>
                                                </span>
                                                <span
                                                    aria-hidden
                                                    className={cn(
                                                        'size-5 rounded-full border-2',
                                                        on
                                                            ? 'border-[#cfe9d8] bg-rz-accent-soft dark:border-rz-accent-fill'
                                                            : 'border-rz-border',
                                                    )}
                                                />
                                            </button>
                                        );
                                    })}
                                </div>
                                <FieldError id="repay-source-error">
                                    {form.errors.source}
                                </FieldError>
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="mt-[18px] h-[54px] w-full rounded-2xl bg-rz-accent-fill text-[15.5px] font-semibold text-white disabled:opacity-80"
                                >
                                    {form.processing
                                        ? t('business.wallet.processing')
                                        : t('business.repayments.confirm', {
                                              amount: formatRwf(
                                                  thisMonth.amount,
                                              ),
                                          })}
                                </button>
                            </form>
                        )}
                        {payAhead !== null && (
                            <PayAhead
                                payAhead={payAhead}
                                source={form.data.source}
                                action={actions.pay_ahead}
                            />
                        )}
                        <ScheduleList schedule={schedule} />
                        <LatePolicy
                            late_ladder={ladder}
                            defer={links.defer}
                            review={links.review}
                        />
                    </>
                )}
            </div>
        </DetailSheet>
    );

    return (
        <BusinessShell
            title={t('business.repayments.title')}
            tab="home"
            links={home.links}
            showTabBar={false}
        >
            <HomeBody
                {...home}
                backdrop
                overlay={{ column: 'left', content: sheet }}
            />
        </BusinessShell>
    );
}
