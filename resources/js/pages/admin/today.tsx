import { Link } from '@inertiajs/react';
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
import { useTranslation } from '@/hooks/use-translation';
import type { AdminTodayProps } from '@/types/admin';

/**
 * Today (MVP-ADMIN-SCR-01, design Dashboard T192–346): the headline figures, what needs a human,
 * reconciliation breaks with their age and owner, and the book at a glance. Every figure is the
 * server's; the page never totals or ranks anything itself.
 */
export default function AdminToday(props: AdminTodayProps) {
    const { t } = useTranslation();
    const commands = [
        ['review_queue', props.nav.applications, 'bg-[#7c3aed]'],
        ['release_queue', props.nav.disbursements, 'bg-[#1d9e75]'],
        ['audit_trail', props.nav.events, 'bg-[#0c1830] dark:bg-[#93a1bd]'],
    ] as const;

    return (
        <AdminFrame section="today" {...props}>
            <nav
                aria-label={t('admin.today.commands')}
                className="mb-4 flex flex-wrap gap-[9px]"
            >
                {commands.map(([key, link, accent]) => (
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
                ))}
            </nav>

            <TodayKpis kpis={props.kpis} />
            <AttentionTiles tiles={props.attention} />
            <BreaksCard
                breaks={props.breaks}
                viewer={props.viewer}
                ledger={props.nav.ledger}
            />

            <div className="mt-4 grid gap-4 lg:grid-cols-[2fr_1fr]">
                <CapitalRaisedPanel chart={props.capital_raised} />
                <PortfolioHealthPanel health={props.portfolio_health} />
            </div>
            <div className="mt-4 grid gap-4 lg:grid-cols-[1.5fr_1fr]">
                <ActivityPanel
                    items={props.activity}
                    ledger={props.nav.ledger}
                    serverTime={props.server_time}
                />
                <LifecyclePanel funnel={props.funnel} />
            </div>
            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <PendingApplicationsPanel items={props.pending_applications} />
                <SectorPanel sectors={props.sector_exposure} />
            </div>
            <div className="mt-4 grid gap-4 lg:grid-cols-[2fr_1fr]">
                <TreasuryPanel treasury={props.treasury} />
                <CollectionsPanel collections={props.collections} />
            </div>
        </AdminFrame>
    );
}
