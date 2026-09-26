import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Icon } from '@/components/rozine/icon';
import type { IconName, IconTone } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatDate,
    formatDayMonth,
    formatMonthYearLong,
    formatRwf,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { BusinessTodo } from '@/types/business';

type TodoCardProps = {
    href: RouteLink;
    icon: ReactNode;
    iconTile: string;
    dot: string;
    kicker: string;
    title: string;
    sub: string;
    cta: string;
    ctaTone: string;
};

/** One Today row (design L193–236): icon tile, dotted kicker, figure, detail and a call to act. */
function TodoCard({
    href,
    icon,
    iconTile,
    dot,
    kicker,
    title,
    sub,
    cta,
    ctaTone,
}: TodoCardProps) {
    return (
        <Link
            href={href}
            className="mt-[13px] flex w-full items-center gap-[13px] rounded-2xl border border-rz-border bg-rz-surface px-4 py-[15px] text-left lg:mt-[9px] lg:px-[13px] lg:py-2.5"
        >
            <span
                className={cn(
                    'flex size-11 shrink-0 items-center justify-center rounded-xl text-[22px] lg:size-9',
                    iconTile,
                )}
            >
                {icon}
            </span>
            <span className="min-w-0 flex-1">
                <span className="flex items-center gap-[7px] text-[10.5px] font-bold tracking-[.07em] text-rz-slate uppercase">
                    <span
                        className={cn('size-[7px] shrink-0 rounded-full', dot)}
                    />
                    {kicker}
                </span>
                <span className="mt-0.5 block text-lg font-bold tracking-[-.3px] text-rz-ink lg:text-base">
                    {title}
                </span>
                <span className="mt-px block text-[11.5px] text-rz-secondary">
                    {sub}
                </span>
                <span
                    className={cn(
                        'mt-2.5 inline-flex items-center gap-1.5 text-[12.5px] font-bold lg:mt-[7px]',
                        ctaTone,
                    )}
                >
                    {cta}
                    <span aria-hidden className="text-[15px] leading-none">
                        ›
                    </span>
                </span>
            </span>
        </Link>
    );
}

const lineIcon = (name: IconName, tone: IconTone = 'green') => (
    <Icon name={name} tone={tone} />
);

/** The approved and declined decision marks, drawn as the design draws them. */
function DecisionMark({ declined }: { declined: boolean }) {
    return declined ? (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden
            className="size-[21px]"
        >
            <circle cx="12" cy="12" r="9" stroke="#c0392b" strokeWidth="1.9" />
            <path
                d="M9 9l6 6M15 9l-6 6"
                stroke="#c0392b"
                strokeWidth="1.9"
                strokeLinecap="round"
            />
        </svg>
    ) : (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden
            className="size-[21px]"
        >
            <circle cx="12" cy="12" r="9" stroke="#1d9e75" strokeWidth="1.9" />
            <path
                d="M8 12.4l2.6 2.6L16 9.6"
                stroke="#1d9e75"
                strokeWidth="2.1"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

const GREEN_TILE = 'bg-rz-accent-soft';
const AMBER_TILE = 'bg-[rgba(194,102,31,.1)]';
const RED_TILE = 'bg-[rgba(229,72,77,.1)]';
const GREEN_TEXT = 'text-rz-accent-app-text';

/** "Today" — what needs the business now (design L184–238), in the server's order. */
export function TodaySection({ items }: { items: BusinessTodo[] }) {
    const { t, locale } = useTranslation();

    const card = (item: BusinessTodo) => {
        switch (item.kind) {
            case 'application_approved':
                return (
                    <TodoCard
                        href={item.link}
                        icon={<DecisionMark declined={false} />}
                        iconTile={GREEN_TILE}
                        dot="bg-rz-accent-app-text"
                        kicker={t('business.today.approved.kicker')}
                        title={item.title}
                        sub={
                            item.fee.amount === '0'
                                ? t('business.today.approved.sub_free')
                                : t('business.today.approved.sub', {
                                      fee: formatRwf(item.fee),
                                  })
                        }
                        cta={
                            item.fee.amount === '0'
                                ? t('business.today.approved.cta_free')
                                : t('business.today.approved.cta')
                        }
                        ctaTone={GREEN_TEXT}
                    />
                );
            case 'application_declined':
                return (
                    <TodoCard
                        href={item.link}
                        icon={<DecisionMark declined />}
                        iconTile={RED_TILE}
                        dot="bg-rz-danger-text"
                        kicker={t('business.today.declined.kicker')}
                        title={item.title}
                        sub={item.reason ?? t('business.today.declined.sub')}
                        cta={t('business.today.declined.cta')}
                        ctaTone="text-rz-danger-text"
                    />
                );
            case 'disbursement_ready':
                return (
                    <TodoCard
                        href={item.link}
                        icon={lineIcon('money-out')}
                        iconTile={GREEN_TILE}
                        dot="bg-rz-accent-app-text"
                        kicker={t('business.today.disbursement.kicker')}
                        title={formatRwf(item.gross)}
                        sub={item.note_title}
                        cta={t('business.today.disbursement.cta')}
                        ctaTone={GREEN_TEXT}
                    />
                );
            case 'audit_window':
                return (
                    <TodoCard
                        href={item.link}
                        icon={lineIcon('bar-chart', 'blue')}
                        iconTile={GREEN_TILE}
                        dot="bg-rz-accent-app-text"
                        kicker={t('business.today.audit.kicker')}
                        title={t('business.today.audit.title', {
                            month: formatMonthYearLong(item.month, locale),
                        })}
                        sub={
                            item.window_open
                                ? t('business.today.audit.sub_open', {
                                      sealed: formatDayMonth(
                                          item.sealed_by,
                                          locale,
                                      ),
                                  })
                                : t('business.today.audit.sub_closing', {
                                      days: item.days_left,
                                      sealed: formatDayMonth(
                                          item.sealed_by,
                                          locale,
                                      ),
                                  })
                        }
                        cta={t('business.today.audit.cta')}
                        ctaTone={GREEN_TEXT}
                    />
                );
            case 'repayment_due':
                return (
                    <TodoCard
                        href={item.link}
                        icon={lineIcon('calendar', 'amber')}
                        iconTile={AMBER_TILE}
                        dot="bg-[#c2661f]"
                        kicker={t('business.today.repayment.kicker')}
                        title={formatRwf(item.amount)}
                        sub={t('business.today.repayment.sub', {
                            note: item.note_title,
                            date: formatDate(item.due_on, locale),
                        })}
                        cta={t('business.today.repayment.cta')}
                        ctaTone="text-rz-ink"
                    />
                );
        }
    };

    return (
        <section aria-labelledby="business-today" className="mt-6 lg:mt-3.5">
            <h2
                id="business-today"
                className="text-[17px] font-bold tracking-[-.2px] text-rz-ink"
            >
                {t('business.today.title')}
            </h2>
            <p className="mt-0.5 text-xs text-rz-secondary lg:hidden">
                {t('business.today.subtitle')}
            </p>

            {items.length === 0 ? (
                <div className="mt-[22px] rounded-2xl border border-rz-border bg-rz-surface p-[22px] text-center">
                    <div className="mx-auto flex size-12 items-center justify-center rounded-full bg-rz-accent-soft text-[22px] text-rz-accent-app-text">
                        ✓
                    </div>
                    <p className="mt-[11px] text-[15px] font-bold text-rz-ink">
                        {t('business.today.caught_up.title')}
                    </p>
                    <p className="mt-1 text-[12.5px] leading-normal text-rz-secondary">
                        {t('business.today.caught_up.body')}
                    </p>
                </div>
            ) : (
                items.map((item) => (
                    <div key={`${item.kind}-${item.link.url}`}>
                        {card(item)}
                    </div>
                ))
            )}
        </section>
    );
}
