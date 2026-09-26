import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatMonthYearLong } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { C3DealCard, C3DealDetail } from '@/types/investor';

/**
 * Why a listing cannot be bought right now (C3 v2 §2b): a restriction and the campaign's lifecycle
 * are separate facts, so a restricted campaign still says whether it is raising, funded or in
 * flight. Rendered in the design's info-box style; it names the actual state and never invents a
 * next listing date.
 */
export function DealStatusNotice({
    deal,
    className,
}: {
    deal: Pick<C3DealCard, 'lifecycle' | 'restriction'>;
    className?: string;
}) {
    const { t, locale } = useTranslation();

    return (
        <>
            {deal.restriction !== null && (
                <InfoNotice
                    title={t(
                        `investor.deal.restriction.${deal.restriction.code}`,
                    )}
                    body={t('investor.deal.restriction.body', {
                        date: formatDate(deal.restriction.since, locale),
                    })}
                    className={className}
                />
            )}
            {deal.lifecycle !== 'live' && (
                <InfoNotice
                    title={t(`investor.deal.notice.${deal.lifecycle}.title`)}
                    body={t(`investor.deal.notice.${deal.lifecycle}.body`)}
                    className={className}
                />
            )}
        </>
    );
}

/** The design's blue info box (L4389 rationale style) carrying a state and its explanation. */
export function InfoNotice({
    title,
    body,
    className,
}: {
    title: string;
    body: string;
    className?: string;
}) {
    return (
        <div
            role="status"
            className={cn(
                'mb-3.5 flex gap-2.5 rounded-xl border border-[rgba(30,58,255,.10)] bg-[rgba(30,58,255,.06)] px-[13px] py-[11px] dark:border-rz-border dark:bg-rz-accent-soft',
                className,
            )}
        >
            <span className="mt-px shrink-0 text-base">
                <Icon name="info" />
            </span>
            <div>
                <p className="text-[12.5px] font-bold text-rz-ink">{title}</p>
                <p className="mt-0.5 text-xs leading-normal text-rz-slate">
                    {body}
                </p>
            </div>
        </div>
    );
}

/** A monthly report that missed the 7th (design L966–971). */
export function OverdueReport({
    deal,
}: {
    deal: Pick<C3DealDetail, 'overdue_report'>;
}) {
    const { t, locale } = useTranslation();

    if (deal.overdue_report === null) {
        return null;
    }

    return (
        <div className="mt-[11px] flex items-start gap-[11px] rounded-2xl border border-rz-border bg-rz-surface p-[13px]">
            <span className="shrink-0 text-base">
                <Icon name="warning" />
            </span>
            <div>
                <p className="text-[12.5px] font-bold text-rz-ink">
                    {t('investor.deal.overdue_title', {
                        month: formatMonthYearLong(
                            deal.overdue_report.month,
                            locale,
                        ),
                    })}
                </p>
                <p className="mt-[3px] text-[11.5px] leading-normal text-[#7b6428] dark:text-[#f0a060]">
                    {t('investor.deal.overdue_body')}
                </p>
            </div>
        </div>
    );
}
