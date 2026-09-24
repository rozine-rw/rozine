import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { BusinessShell } from '@/components/business/business-shell';
import { HomeBody } from '@/components/business/home/home-body';
import { ColumnSheet } from '@/components/rozine/column-sheet';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessPublishProps, PaymentSourceKey } from '@/types/business';

const SOURCE_ICON: Record<PaymentSourceKey, IconName> = {
    wallet: 'wallet',
    mtn: 'phone',
    airtel: 'phone',
    card: 'card',
};

/**
 * "Publish to the Investor feed" (listing, design L2090–2120), opened from the approved
 * application on Home. The server decides the fee; while it is zero there is nothing to pay, so
 * the sheet discloses the zero fee and publishes without asking for a payment source.
 */
export default function BusinessPublish({
    home,
    application,
    fee,
    sources,
    links,
    actions,
}: BusinessPublishProps) {
    const { t } = useTranslation();
    const charged = sources.length > 0;
    const form = useForm<{ source: PaymentSourceKey | null }>({
        source: sources[0]?.key ?? null,
    });
    const selected = sources.find((source) => source.key === form.data.source);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(actions.publish.url, { preserveScroll: true });
    };

    const sheet = (
        <ColumnSheet
            label={t('business.publish.title')}
            close={links.close}
            fraction={0.8}
        >
            <form
                onSubmit={submit}
                className="rz-scroll min-h-0 flex-1 overflow-y-auto px-5 pt-4 pb-[22px]"
            >
                <div className="flex items-center gap-3">
                    <span className="flex size-[46px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-2xl">
                        <Icon name="rocket" tone="green" />
                    </span>
                    <h2 className="text-[19px] leading-[1.25] font-semibold text-rz-ink">
                        {t('business.publish.title')}
                    </h2>
                </div>
                <p className="mt-3 text-[12.5px] leading-normal text-rz-secondary">
                    {charged
                        ? t('business.publish.body_fee', {
                              title: application.title,
                          })
                        : t('business.publish.body_free', {
                              title: application.title,
                          })}
                </p>
                <div className="mt-4 rounded-2xl border border-[#eef2f9] bg-[#f6f9fd] px-[15px] py-1.5 dark:border-rz-divider dark:bg-rz-surface-sunken">
                    <div className="flex items-center justify-between border-b border-[#eef2f9] py-[11px] dark:border-rz-divider">
                        <span className="text-[13px] text-rz-secondary">
                            {t('business.publish.target')}
                        </span>
                        <span className="text-[13px] font-semibold text-rz-ink">
                            {formatRwf(application.target)}
                        </span>
                    </div>
                    <div className="flex items-center justify-between pt-3 pb-[11px]">
                        <span className="text-[13px] font-bold text-rz-ink">
                            {t('business.publish.fee')}
                        </span>
                        <span className="text-base font-bold text-rz-accent-app-text">
                            {formatRwf(fee)}
                        </span>
                    </div>
                </div>

                {charged && (
                    <>
                        <p className="mt-4 text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                            {t('business.publish.pay_with')}
                        </p>
                        <div
                            role="radiogroup"
                            aria-label={t('business.publish.pay_with')}
                            className="mt-2 flex gap-1.5"
                        >
                            {sources.map((source) => {
                                const on = source.key === form.data.source;

                                return (
                                    <button
                                        key={source.key}
                                        type="button"
                                        role="radio"
                                        aria-checked={on}
                                        onClick={() =>
                                            form.setData('source', source.key)
                                        }
                                        className={cn(
                                            'relative flex min-w-0 flex-1 flex-col items-center gap-1.5 rounded-xl border-[1.5px] px-1 py-2.5',
                                            on
                                                ? 'border-[#d6e4ff] bg-rz-page text-rz-investor-text dark:border-rz-investor'
                                                : 'border-rz-border bg-rz-surface text-[#46526b] dark:text-rz-secondary',
                                        )}
                                    >
                                        <span className="text-xl">
                                            <Icon
                                                name={SOURCE_ICON[source.key]}
                                            />
                                        </span>
                                        <span className="text-[10px] font-semibold whitespace-nowrap">
                                            {t(
                                                `business.publish.source.${source.key}`,
                                            )}
                                        </span>
                                        {on && (
                                            <span
                                                aria-hidden
                                                className="absolute top-[5px] right-[5px] flex size-3.5 items-center justify-center rounded-full bg-rz-accent-fill text-[10px] text-white"
                                            >
                                                ✓
                                            </span>
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                        <p className="mt-2 text-center text-[11px] text-rz-secondary">
                            {selected?.detail}
                        </p>
                    </>
                )}

                <button
                    type="submit"
                    disabled={form.processing}
                    aria-busy={form.processing || undefined}
                    className="mt-4 flex h-[52px] w-full items-center justify-center gap-[9px] rounded-xl bg-rz-accent-fill text-[15px] font-semibold text-white"
                >
                    {form.processing && (
                        <span className="size-[17px] animate-spin rounded-full border-[2.5px] border-white/40 border-t-white" />
                    )}
                    {form.processing
                        ? t('business.publish.publishing')
                        : charged
                          ? t('business.publish.pay_and_publish')
                          : t('business.publish.publish')}
                </button>
                <Link
                    href={links.close}
                    className="mt-2.5 flex h-[46px] w-full items-center justify-center rounded-xl border border-rz-border text-sm font-semibold text-rz-slate"
                >
                    {t('business.publish.not_yet')}
                </Link>
                {form.errors.source && (
                    <p className="mt-2 text-center text-xs font-semibold text-rz-danger-text">
                        {form.errors.source}
                    </p>
                )}
            </form>
        </ColumnSheet>
    );

    return (
        <BusinessShell
            title={t('business.publish.title')}
            tab="home"
            links={home.links}
        >
            <HomeBody {...home} overlay={{ column: 'left', content: sheet }} />
        </BusinessShell>
    );
}
