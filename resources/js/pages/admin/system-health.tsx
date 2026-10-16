import { Link } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { SearchEmpty } from '@/components/admin/search-empty';
import { Chip } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AdminSystemHealthProps,
    HealthSignal,
    HealthStatus,
    Tone,
} from '@/types/admin';

type Translate = ReturnType<typeof useTranslation>['t'];

const STATUS_TONE: Record<HealthStatus, Tone> = {
    critical: 'red',
    watch: 'amber',
    healthy: 'green',
    untracked: 'grey',
};

/** The design's card fill and edge: tinted for critical and watch, plain otherwise. */
const CARD: Record<HealthStatus, string> = {
    critical: 'border-[#f6dcdd] bg-[#fffafa] dark:border-[rgba(229,72,77,.35)]',
    watch: 'border-[#f4e6c9] bg-[#fffdf7] dark:border-[rgba(194,102,31,.35)]',
    healthy: 'border-[#e7ecf4] bg-white dark:border-rz-divider',
    untracked: 'border-[#e7ecf4] bg-white dark:border-rz-divider',
};

const RAIL: Record<HealthStatus, string> = {
    critical: 'bg-[#e5484d]',
    watch: 'bg-[#c2661f]',
    healthy: 'bg-[#1d9e75]',
    untracked: 'bg-[#c3cad6]',
};

/** A signal's reading, its detail line, its recommendation and its link label, in the viewer's language. */
function describe(
    signal: HealthSignal,
    t: Translate,
): { value: string; detail: string; rec: string | null; action: string } {
    switch (signal.key) {
        case 'ledger':
            return {
                value:
                    signal.status === 'watch'
                        ? t('admin.health.ledger.value.watch', {
                              count: signal.facts.breaks,
                          })
                        : t(`admin.health.ledger.value.${signal.status}`),
                detail: t('admin.health.ledger.detail', {
                    count: signal.facts.accounts,
                }),
                rec: t(`admin.health.ledger.rec.${signal.status}`),
                action: t('admin.health.ledger.action'),
            };
        case 'capital':
            return {
                value: formatRwfShort({
                    currency: 'RWF',
                    amount: signal.facts.amount,
                }),
                detail: t('admin.health.capital.detail', {
                    count: signal.facts.raises,
                }),
                rec: t('admin.health.capital.rec'),
                action: '',
            };
        case 'queue':
            return {
                value: String(signal.facts.pending),
                detail: t('admin.health.queue.detail'),
                rec: t(`admin.health.queue.rec.${signal.status}`),
                action: t('admin.health.queue.action'),
            };
        case 'events':
            return {
                value: t('admin.health.events.value', {
                    count: signal.facts.last_hour,
                }),
                detail: t('admin.health.events.detail', {
                    count: signal.facts.total,
                }),
                rec: t(`admin.health.events.rec.${signal.status}`),
                action: '',
            };
        default:
            return {
                value: t('admin.health.not_tracked'),
                detail: t(`admin.health.${signal.key}.detail`),
                rec: null,
                action: '',
            };
    }
}

function SignalCard({ signal }: { signal: HealthSignal }) {
    const { t } = useTranslation();
    const { value, detail, rec, action } = describe(signal, t);

    return (
        <article
            aria-label={t(`admin.health.${signal.key}.label`)}
            className={cn(
                'relative overflow-hidden rounded-2xl border px-[18px] py-[17px] dark:bg-rz-surface',
                CARD[signal.status],
            )}
        >
            <span
                aria-hidden
                className={cn(
                    'absolute inset-y-0 left-0 w-[3px]',
                    RAIL[signal.status],
                )}
            />
            <div className="flex items-center justify-between gap-2">
                <span className="min-w-0 truncate text-[12px] font-semibold text-[#7b8699] dark:text-rz-muted">
                    {t(`admin.health.${signal.key}.label`)}
                </span>
                <Chip tone={STATUS_TONE[signal.status]}>
                    {t(`admin.health.status.${signal.status}`)}
                </Chip>
            </div>
            <div
                className={cn(
                    'mt-[11px] truncate font-bold tracking-[-.5px]',
                    signal.status === 'untracked'
                        ? 'text-[18px] text-rz-muted'
                        : 'text-[25px] text-rz-ink',
                )}
            >
                {value}
            </div>
            <div className="mt-[3px] truncate text-[11.5px] text-[#8a94a8] dark:text-rz-muted">
                {detail}
            </div>
            {rec !== null && (
                <div className="mt-3 flex items-end justify-between gap-3 border-t border-[#f1f4f9] pt-3 dark:border-rz-divider">
                    <p className="text-[12px] leading-[1.45] text-rz-body">
                        {rec}
                    </p>
                    {signal.action !== null && (
                        <Link
                            href={signal.action}
                            className="shrink-0 text-[12px] font-bold whitespace-nowrap text-rz-accent-app-text"
                        >
                            {action} →
                        </Link>
                    )}
                </div>
            )}
        </article>
    );
}

/**
 * System Health (`staff-system-health-v1`): the design's banner with the overall state and its
 * monitored signals, worst first. Signals the platform does not record yet say so instead of
 * showing an estimate. The top-bar search filters the cards by their label, reading and status.
 */
export default function AdminSystemHealth(props: AdminSystemHealthProps) {
    const { t } = useTranslation();
    const { critical, watch } = props.overall;
    const state = critical > 0 ? 'critical' : watch > 0 ? 'watch' : 'healthy';
    const term = props.search.trim().toLowerCase();
    const signals = props.signals.filter((signal) => {
        if (term === '') {
            return true;
        }

        const { value, detail } = describe(signal, t);

        return [
            t(`admin.health.${signal.key}.label`),
            value,
            detail,
            t(`admin.health.status.${signal.status}`),
        ]
            .join(' ')
            .toLowerCase()
            .includes(term);
    });

    return (
        <AdminFrame section="system_health" {...props}>
            <div
                role="status"
                className={cn(
                    'mb-[18px] flex items-center gap-3 rounded-[14px] border px-[18px] py-3.5',
                    state === 'critical' &&
                        'border-[#f6d5d5] bg-[rgba(229,72,77,.06)]',
                    state === 'watch' &&
                        'border-[#f6e7c8] bg-[rgba(194,102,31,.06)]',
                    state === 'healthy' &&
                        'border-[#cdeddb] bg-[rgba(29,158,117,.06)]',
                )}
            >
                <span
                    aria-hidden
                    className={cn('size-3 shrink-0 rounded-full', RAIL[state])}
                />
                <div className="min-w-0 flex-1">
                    <div className="text-[15px] font-bold text-rz-ink">
                        {t(`admin.health.overall.${state}`)}
                    </div>
                    <div className="mt-px text-[12.5px] text-rz-muted">
                        {state === 'critical'
                            ? t('admin.health.overall.critical.sub', {
                                  critical,
                                  watch,
                              })
                            : state === 'watch'
                              ? t('admin.health.overall.watch.sub', {
                                    count: watch,
                                })
                              : t('admin.health.overall.healthy.sub')}
                    </div>
                </div>
            </div>
            {signals.length === 0 && (
                <SearchEmpty section="system_health" term={props.search} />
            )}
            <div className="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                {signals.map((signal) => (
                    <SignalCard key={signal.key} signal={signal} />
                ))}
            </div>
        </AdminFrame>
    );
}
