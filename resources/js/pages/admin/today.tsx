import { Deferred, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { AdminFrame } from '@/components/admin/admin-frame';
import {
    ActivityPanel,
    CapitalRaisedPanel,
    CollectionsPanel,
    LifecyclePanel,
    PendingApplicationsPanel,
    PortfolioHealthPanel,
    SectorPanel,
    TreasuryPanel,
} from '@/components/admin/today/today-panels';
import {
    AttentionTiles,
    BreaksCard,
    TodayKpis,
} from '@/components/admin/today/today-queues';
import { CardTitle, Panel } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { AdminTodayProps } from '@/types/admin';

/**
 * Today (MVP-ADMIN-SCR-01, design Dashboard T192–346): the headline figures, what needs a human,
 * reconciliation breaks with their age and owner, and the book at a glance. Every figure is the
 * server's; the page never totals or ranks anything itself. A figure or panel the platform does
 * not record yet arrives as null and reads as not tracked. The quick commands are the design's that
 * open a page: Review queue, Audit desk (Reports) and Policies, each shown only when the server
 * sends its link. Broadcast and Run cycle have no command yet, so they are left out.
 */
export default function AdminToday(props: AdminTodayProps) {
    const { t } = useTranslation();
    const commands = [
        ['review_queue', props.nav.applications, 'bg-[#7c3aed]'],
        ['audit_desk', props.nav.reports, 'bg-[#c2661f]'],
        ['policies', props.nav.policies, 'bg-[#0c1830] dark:bg-[#93a1bd]'],
    ] as const;

    return (
        <AdminFrame section="today" {...props}>
            <nav
                aria-label={t('admin.today.commands')}
                className="mb-4 flex flex-wrap gap-[9px]"
            >
                {commands.map(
                    ([key, link, accent]) =>
                        link && (
                            <Link
                                key={key}
                                href={link}
                                className="flex h-9 items-center gap-2 rounded-[10px] border border-[#eaeef6] bg-rz-surface px-3.5 text-[12.5px] font-bold text-rz-ink shadow-[0_1px_2px_rgba(16,32,58,.04)] transition-[border-color,transform] duration-100 hover:-translate-y-px hover:border-[#1e3aff] dark:border-rz-border"
                            >
                                <span
                                    className={`size-2 shrink-0 rounded-[3px] ${accent}`}
                                />
                                {t(`admin.today.command.${key}`)}
                            </Link>
                        ),
                )}
            </nav>

            <TodayKpis kpis={props.kpis} />
            <AttentionTiles tiles={props.attention} />
            <BreaksCard
                breaks={props.breaks}
                viewer={props.viewer}
                ledger={props.nav.ledger}
            />

            <div
                data-testid="panel-row"
                className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-[2fr_1fr]"
            >
                <CapitalRaisedPanel chart={props.capital_raised} />
                <PortfolioHealthPanel health={props.portfolio_health} />
            </div>
            <div
                data-testid="panel-row"
                className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-[1.5fr_1fr]"
            >
                <ActivitySection
                    activity={props.activity}
                    ledger={props.nav.ledger}
                    serverTime={props.server_time}
                />
                <LifecyclePanel funnel={props.funnel} />
            </div>
            <div
                data-testid="panel-row"
                className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2"
            >
                <PendingApplicationsPanel
                    items={props.pending_applications}
                    canReview={props.nav.applications !== null}
                />
                <SectorPanel sectors={props.sector_exposure} />
            </div>
            <div
                data-testid="panel-row"
                className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-[2fr_1fr]"
            >
                <TreasuryPanel treasury={props.treasury} />
                <CollectionsPanel collections={props.collections} />
            </div>
        </AdminFrame>
    );
}

function ActivityLoading() {
    const { t } = useTranslation();

    return (
        <Panel label={t('admin.today.activity.title')} className="min-h-64">
            <CardTitle>{t('admin.today.activity.title')}</CardTitle>
            <div
                role="status"
                aria-label={t('admin.today.activity.loading')}
                className="mt-4 space-y-4 motion-safe:animate-pulse"
            >
                {[0, 1, 2, 3].map((row) => (
                    <div key={row} className="h-8 rounded-lg bg-rz-hairline" />
                ))}
            </div>
        </Panel>
    );
}

function ActivitySection({
    activity,
    ledger,
    serverTime,
}: {
    activity: AdminTodayProps['activity'] | undefined;
    ledger: AdminTodayProps['nav']['ledger'];
    serverTime: string;
}) {
    const { t } = useTranslation();
    const [failed, setFailed] = useState(false);
    const [retrying, setRetrying] = useState(false);
    const [refreshing, setRefreshing] = useState(false);
    const previousActivity = useRef(activity);
    const pending = useRef(new Set<object>());

    useEffect(() => {
        const offStart = router.on('start', ({ detail: { visit } }) => {
            if (
                visit.url.pathname === window.location.pathname &&
                (visit.only.length === 0 || visit.only.includes('activity'))
            ) {
                pending.current.add(visit);
                setRefreshing(true);
            }
        });
        const offFinish = router.on('finish', ({ detail: { visit } }) => {
            if (pending.current.delete(visit)) {
                setRefreshing(pending.current.size > 0);
            }
        });
        const fail = () => {
            if (pending.current.size > 0) {
                setFailed(true);

                return false;
            }
        };
        const offNetwork = router.on('networkError', fail);
        const offHttp = router.on('httpException', fail);

        return () => {
            offStart();
            offFinish();
            offNetwork();
            offHttp();
        };
    }, []);

    useEffect(() => {
        if (previousActivity.current !== activity) {
            previousActivity.current = activity;
            setFailed(false);
        }
    }, [activity]);

    const retry = () => {
        setRetrying(true);
        router.reload({
            only: ['activity'],
            onSuccess: () => setFailed(false),
            onFinish: () => setRetrying(false),
        });
    };
    const error = (
        <Panel label={t('admin.today.activity.title')} className="min-h-64">
            <CardTitle>{t('admin.today.activity.title')}</CardTitle>
            <p role="alert" className="mt-4 text-sm text-rz-muted">
                {t('admin.today.activity.failed')}
            </p>
            <button
                type="button"
                className="mt-4 rounded-lg border border-rz-hairline px-3 py-2 text-sm font-semibold text-rz-ink focus-visible:outline-2"
                onClick={retry}
            >
                {t('admin.today.activity.retry')}
            </button>
        </Panel>
    );

    if (retrying || refreshing) {
        return <ActivityLoading />;
    }

    if (failed) {
        return error;
    }

    return (
        <Deferred data="activity" fallback={<ActivityLoading />} rescue={error}>
            {({ reloading }) =>
                reloading ? (
                    <ActivityLoading />
                ) : (
                    <ActivityPanel
                        items={activity ?? null}
                        ledger={activity === null ? null : ledger}
                        serverTime={serverTime}
                    />
                )
            }
        </Deferred>
    );
}
