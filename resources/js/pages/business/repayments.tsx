import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { BusinessShell } from '@/components/business/business-shell';
import { DetailSheet } from '@/components/business/detail-sheet';
import { BlankBody, HomeBody } from '@/components/business/home/home-body';
import { DueCard } from '@/components/business/repayments/due-card';
import { LatePolicy } from '@/components/business/repayments/late-policy';
import { PayPanel } from '@/components/business/repayments/pay-panel';
import { ProgressCard } from '@/components/business/repayments/progress-card';
import {
    RecentRepayments,
    RepaymentReceipt,
} from '@/components/business/repayments/receipt';
import { ScheduleList } from '@/components/business/repayments/schedule-list';
import { C3Notice, PollStopped } from '@/components/rozine/c3-notice';
import { Icon } from '@/components/rozine/icon';
import { useBoundedPoll } from '@/hooks/use-bounded-poll';
import { useC3Command } from '@/hooks/use-c3-command';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type {
    BusinessRepaymentsEmptyProps,
    BusinessRepaymentsPageProps,
    BusinessRepaymentsProps,
    RepaymentPayOption,
} from '@/types/business';

/** What a refresh, an allocation poll or a settled command reloads: the facts and the actions. */
const RELOADS = [
    'server_time',
    'allowed_actions',
    'servicing',
    'schedule',
    'pay',
    'receipt',
    'recent',
    'actions',
];

/**
 * Repayments (MVP-BUSINESS-SCR-05, design L1060–1262; C4 v1 §4a, `business.repayments.show`),
 * opened from a repaying note. The Business pays from its Rozine wallet only: what is due now, or
 * the next instalment early (`repayment.pay`). Every amount, DPD and date is the server's. A
 * recorded payment is allocated to the note's Investors afterwards; while it is, the page polls
 * within bounds, and it never shows an instalment as paid before the server does. While no note
 * is servicing, the sheet says so and offers nothing to pay.
 */
export default function BusinessRepayments(props: BusinessRepaymentsPageProps) {
    const { t } = useTranslation();
    const sheet =
        props.servicing === null ? (
            <NothingToRepay {...props} />
        ) : (
            <ServicingSheet {...props} />
        );

    return (
        <BusinessShell
            title={t('business.repayments.title')}
            tab="home"
            links={props.shell_links}
            showTabBar={false}
        >
            {props.home === null ? (
                <BlankBody overlay={{ column: 'left', content: sheet }} />
            ) : (
                <HomeBody
                    {...props.home}
                    backdrop
                    overlay={{ column: 'left', content: sheet }}
                />
            )}
        </BusinessShell>
    );
}

/** The sheet's back link and title, shared by both states. */
function SheetHeading({
    close,
    children,
}: {
    close: BusinessRepaymentsPageProps['links']['close'];
    children?: ReactNode;
}) {
    const { t } = useTranslation();

    return (
        <div className="flex items-center gap-3">
            <Link
                href={close}
                aria-label={t('business.note.back')}
                className="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
            >
                <span aria-hidden>←</span>
            </Link>
            <div className="min-w-0">
                <h1 className="text-xl font-semibold text-rz-ink">
                    {t('business.repayments.title')}
                </h1>
                {children}
            </div>
        </div>
    );
}

/** No note is servicing yet: nothing is due, scheduled or payable, and nothing is offered. */
function NothingToRepay({ links }: BusinessRepaymentsEmptyProps) {
    const { t } = useTranslation();

    return (
        <DetailSheet
            label={t('business.repayments.title')}
            close={links.close}
            closeLabel={t('business.note.close')}
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+2px)] pb-10 lg:pt-[18px]">
                <SheetHeading close={links.close} />
                <div className="mt-4 rounded-2xl border border-rz-border bg-rz-surface px-5 py-10 text-center">
                    <div className="text-3xl">
                        <Icon name="inbox-empty" />
                    </div>
                    <p className="mt-2.5 text-[14.5px] font-semibold text-rz-ink">
                        {t('business.repayments.none.title')}
                    </p>
                    <p className="mt-1.5 text-[13px] text-rz-secondary">
                        {t('business.repayments.none.body')}
                    </p>
                </div>
            </div>
        </DetailSheet>
    );
}

/** A servicing note's sheet: progress, what is due, Pay, the schedule and recent payments. */
function ServicingSheet(props: BusinessRepaymentsProps) {
    const { t } = useTranslation();
    const { servicing, links, actions, allowed_actions: allowed } = props;
    const command = useC3Command<'repayment.pay'>({
        actions: { 'repayment.pay': actions.pay },
        lookup: links.operation,
        lookupQuery: {
            identity_context_revision: props.identity_context_revision,
        },
        allowed,
        preview: props.preview_outcome,
        only: RELOADS,
    });
    const allocating =
        props.receipt?.allocation === 'allocating' ||
        props.schedule.some((row) => row.status === 'processing');
    const poll = useBoundedPoll(allocating, RELOADS);
    const offered = actions.pay !== null && allowed.includes('repayment.pay');

    const pay = (option: RepaymentPayOption) => {
        command.send('repayment.pay', {
            identity_context_revision: props.identity_context_revision,
            note_id: props.note.id,
            option: option.key,
            expected_servicing_revision: servicing.revision,
            quoted_total: option.amounts.total,
        });
    };

    return (
        <DetailSheet
            label={t('business.repayments.title')}
            close={links.close}
            closeLabel={t('business.note.close')}
            dismissible={!command.busy}
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+2px)] pb-10 lg:pt-[18px]">
                <SheetHeading close={links.close}>
                    <p className="truncate text-xs text-rz-secondary">
                        {props.note.title} · {props.note.id} ·{' '}
                        <span
                            className={cn(
                                'font-semibold',
                                servicing.state === 'overdue'
                                    ? 'text-rz-danger-text'
                                    : 'text-rz-accent-app-text',
                            )}
                        >
                            {t(
                                `business.servicing.repay.state.${servicing.state}`,
                            )}
                        </span>
                    </p>
                </SheetHeading>
                <C3Notice command={command} className="mt-4" />
                {props.receipt !== null && (
                    <RepaymentReceipt
                        receipt={props.receipt}
                        close={links.close}
                    />
                )}
                <PollStopped {...poll} className="mt-2" />
                <ProgressCard
                    progress={servicing.progress}
                    bases={props.bases}
                />
                <DueCard servicing={servicing} basis={props.bases.due_now} />
                <PayPanel
                    key={servicing.revision}
                    pay={props.pay}
                    offered={offered}
                    topUp={links.top_up}
                    busy={command.busy}
                    locked={command.unresolved}
                    onPay={pay}
                />
                <ScheduleList schedule={props.schedule} />
                <LatePolicy ladder={props.ladder} />
                <RecentRepayments recent={props.recent} />
            </div>
        </DetailSheet>
    );
}
