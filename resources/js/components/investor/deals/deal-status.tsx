import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatMonthYearLong } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { DealCard, DealDetail } from '@/types/investor';

/**
 * Why a listing cannot be bought right now (crosswalk MVP-INVESTOR-SCR-01-ST-02 and
 * SCR-02-ST-01..03: sold out, frozen, withdrawn). Rendered in the design's info-box style; it
 * names the actual state and never invents a next listing date.
 */
export function DealStatusNotice({
    deal,
    className,
}: {
    deal: Pick<DealCard, 'status'>;
    className?: string;
}) {
    const { t } = useTranslation();

    if (deal.status === 'open') {
        return null;
    }

    return (
        <InfoNotice
            title={t(`investor.deal.notice.${deal.status}.title`)}
            body={t(`investor.deal.notice.${deal.status}.body`)}
            className={className}
        />
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
export function OverdueReport({ deal }: { deal: DealDetail }) {
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
