import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { avatarColor, initialOf } from '@/components/admin/format';
import {
    Chip,
    EmptyState,
    HeadCell,
    MicroBadge,
    ROW_RULE,
    RatingPill,
    TABLE_HEAD,
    TONE_TEXT,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type {
    AuditorPartyRow,
    BusinessPartyRow,
    InvestorPartyRow,
    KycState,
    PartyDirectory,
    StaffPartyRow,
    Tone,
} from '@/types/admin';

const GRIDS = {
    business:
        'grid grid-cols-[minmax(0,2.2fr)_minmax(104px,0.7fr)_minmax(0,0.9fr)_minmax(0,1fr)_minmax(0,1.1fr)_minmax(0,1.3fr)_minmax(0,0.9fr)] gap-3 px-5',
    investor:
        'grid grid-cols-[minmax(0,2.1fr)_minmax(0,0.9fr)_minmax(0,1.1fr)_minmax(0,1.1fr)_minmax(0,0.8fr)_minmax(0,0.9fr)_minmax(0,1fr)] gap-3 px-5',
    auditor:
        'grid grid-cols-[minmax(0,1.9fr)_minmax(0,1fr)_minmax(0,0.8fr)_minmax(0,0.8fr)_minmax(0,1fr)_minmax(132px,0.95fr)] gap-3 px-5',
    staff: 'grid grid-cols-[minmax(0,1.7fr)_minmax(0,1.1fr)_minmax(0,0.9fr)] gap-3 px-5',
} as const;

const HOVER = {
    business: 'hover:bg-[#e6edf9] dark:hover:bg-rz-surface-sunken',
    investor: 'hover:bg-[#e6edf9] dark:hover:bg-rz-surface-sunken',
    auditor: 'hover:bg-[#fdf8f0] dark:hover:bg-rz-surface-sunken',
    staff: 'hover:bg-[#f5f8fd] dark:hover:bg-rz-surface-sunken',
} as const;

export const KYC_TONE: Record<KycState, Tone> = {
    verified: 'green',
    pending: 'amber',
    overdue: 'red',
    rejected: 'red',
};

const HEALTH_TONE: Record<BusinessPartyRow['health'], Tone> = {
    healthy: 'green',
    watch: 'amber',
    distressed: 'red',
};

const STANDING_TONE: Record<AuditorPartyRow['standing'], Tone> = {
    active: 'green',
    pending: 'amber',
    licence_expired: 'red',
    suspended: 'red',
};

const utilFill = (pct: number): string =>
    pct >= 100
        ? 'bg-[#e5484d]'
        : pct >= 85
          ? 'bg-[#c2661f]'
          : 'bg-[#1e3aff] dark:bg-[#5b74ff]';

/** The party cell: avatar, name with its state badges, and a secondary line. The name opens the record. */
function PartyCell({
    id,
    name,
    sub,
    link,
    avatar,
    badges,
}: {
    id: string;
    name: string;
    sub: string;
    link: RouteLink;
    avatar?: string;
    badges: ReactNode;
}) {
    return (
        <div role="cell" className="flex min-w-0 items-center gap-[11px]">
            <div
                className="flex size-9 shrink-0 items-center justify-center rounded-[10px] text-[14px] font-bold text-white"
                style={{ background: avatar ?? avatarColor(id) }}
            >
                {initialOf(name)}
            </div>
            <div className="min-w-0">
                <div className="flex min-w-0 items-center gap-[7px]">
                    {badges}
                    <Link
                        href={link}
                        className="truncate text-[13.5px] font-bold text-rz-ink after:absolute after:inset-0"
                    >
                        {name}
                    </Link>
                </div>
                <div className="truncate text-[11.5px] text-rz-muted">
                    {sub}
                </div>
            </div>
        </div>
    );
}

function BusinessRow({ row }: { row: BusinessPartyRow }) {
    const { t } = useTranslation();

    return (
        <>
            <PartyCell
                id={row.id}
                name={row.name}
                sub={row.sector}
                link={row.link}
                badges={
                    <>
                        {row.frozen && (
                            <MicroBadge>
                                {t('admin.parties.badge.frozen')}
                            </MicroBadge>
                        )}
                        {row.kyc === 'overdue' && (
                            <MicroBadge>
                                {t('admin.parties.badge.kyc_overdue')}
                            </MicroBadge>
                        )}
                    </>
                }
            />
            <span role="cell">
                <RatingPill rating={row.rating} />
            </span>
            <span
                role="cell"
                className="text-[12.5px] font-semibold text-rz-slate"
            >
                {row.active_notes}
            </span>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {formatCount(row.investors)}
            </span>
            <span role="cell" className="text-[12.5px] font-bold text-rz-ink">
                {formatRwfShort(row.raised)}
            </span>
            <div role="cell" className="flex items-center gap-2">
                <div
                    role="progressbar"
                    aria-label={t('admin.parties.col.capacity')}
                    aria-valuenow={row.capacity_used_pct}
                    aria-valuemin={0}
                    aria-valuemax={100}
                    className="h-1.5 flex-1 overflow-hidden rounded-[3px] bg-[#e2e8f2] dark:bg-rz-surface-muted"
                >
                    <div
                        className={cn(
                            'h-full rounded-[3px]',
                            utilFill(row.capacity_used_pct),
                        )}
                        style={{
                            width: `${Math.min(100, row.capacity_used_pct)}%`,
                        }}
                    />
                </div>
                <span className="w-[30px] shrink-0 text-[11px] text-rz-muted">
                    {row.capacity_used_pct}%
                </span>
            </div>
            <span
                role="cell"
                className={cn(
                    'text-right text-[12px] font-bold',
                    TONE_TEXT[HEALTH_TONE[row.health]],
                )}
            >
                {t(`admin.parties.health.${row.health}`)}
            </span>
        </>
    );
}

function InvestorRow({ row }: { row: InvestorPartyRow }) {
    const { t } = useTranslation();
    const status = row.frozen
        ? 'frozen'
        : row.restricted
          ? 'restricted'
          : row.kyc === 'verified'
            ? 'verified'
            : 'pending';
    const tone: Tone =
        status === 'verified'
            ? 'green'
            : status === 'pending'
              ? 'amber'
              : 'red';

    return (
        <>
            <PartyCell
                id={row.id}
                name={row.name}
                sub={row.country}
                link={row.link}
                badges={
                    <>
                        {row.frozen && (
                            <MicroBadge>
                                {t('admin.parties.badge.frozen')}
                            </MicroBadge>
                        )}
                        {row.restricted && (
                            <MicroBadge>
                                {t('admin.parties.badge.restricted')}
                            </MicroBadge>
                        )}
                    </>
                }
            />
            <span role="cell">
                <Chip
                    tone={KYC_TONE[row.kyc]}
                    className="rounded-md px-2 py-[3px]"
                >
                    {t(`admin.parties.kyc.${row.kyc}`)}
                </Chip>
            </span>
            <span role="cell" className="text-[12.5px] font-bold text-rz-ink">
                {formatRwfShort(row.portfolio)}
            </span>
            <span role="cell" className="text-[12.5px] text-rz-slate">
                {formatRwfShort(row.wallet)}
            </span>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {row.holdings}
            </span>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {row.businesses}
            </span>
            <span
                role="cell"
                className={cn(
                    'text-right text-[12px] font-bold',
                    TONE_TEXT[tone],
                )}
            >
                {t(`admin.parties.status.${status}`)}
            </span>
        </>
    );
}

function AuditorRow({ row }: { row: AuditorPartyRow }) {
    const { t } = useTranslation();

    return (
        <>
            <PartyCell
                id={row.id}
                name={row.name}
                sub={`${row.firm} · ${row.licence}`}
                link={row.link}
                avatar="#c2661f"
                badges={
                    row.frozen && (
                        <MicroBadge>
                            {t('admin.parties.badge.frozen')}
                        </MicroBadge>
                    )
                }
            />
            <span role="cell" className="text-[12.5px] text-rz-slate">
                {row.district}
            </span>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {row.active_engagements}
            </span>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {row.on_time_pct === null ? '—' : `${row.on_time_pct}%`}
            </span>
            <span role="cell" className="text-[12.5px] font-bold text-rz-ink">
                {formatRwfShort(row.share_mtd)}
            </span>
            <span role="cell">
                <Chip
                    tone={STANDING_TONE[row.standing]}
                    className="rounded-md px-2 py-[3px]"
                >
                    {t(`admin.parties.standing.${row.standing}`)}
                </Chip>
            </span>
        </>
    );
}

function StaffRow({ row }: { row: StaffPartyRow }) {
    const { t } = useTranslation();

    return (
        <>
            <div role="cell" className="flex min-w-0 items-center gap-2.5">
                <div className="flex size-8 shrink-0 items-center justify-center rounded-[9px] bg-[#1e3aff] text-[12px] font-bold text-white dark:bg-[#3d57ff]">
                    {row.initials}
                </div>
                <div className="min-w-0">
                    <div className="flex items-center gap-1.5">
                        <Link
                            href={row.link}
                            className="truncate text-[13px] font-bold text-rz-ink after:absolute after:inset-0"
                        >
                            {row.name}
                        </Link>
                        {row.you && (
                            <span className="rounded-[5px] bg-[rgba(30,58,255,.1)] px-1.5 py-px text-[9px] font-bold text-rz-accent-app-text">
                                {t('admin.parties.you')}
                            </span>
                        )}
                    </div>
                    <div className="truncate text-[11px] text-rz-muted">
                        {row.email}
                    </div>
                </div>
            </div>
            <span role="cell">
                <Chip tone="blue" className="text-[11px]">
                    {t(`admin.role.${row.role}`)}
                </Chip>
            </span>
            <span role="cell">
                <Chip
                    tone={row.frozen ? 'red' : 'green'}
                    className="rounded-md px-2 py-[3px]"
                >
                    {t(
                        row.frozen
                            ? 'admin.parties.status.frozen'
                            : 'admin.parties.status.active',
                    )}
                </Chip>
            </span>
        </>
    );
}

const HEADS = {
    business: [
        'business',
        'rating',
        'active_notes',
        'investors',
        'raised',
        'capacity',
        'status',
    ],
    investor: [
        'investor',
        'kyc',
        'portfolio',
        'wallet',
        'holdings',
        'businesses',
        'status',
    ],
    auditor: [
        'partner',
        'district',
        'engagements',
        'on_time',
        'share_mtd',
        'status',
    ],
    staff: ['operator', 'role', 'status'],
} as const;

/** One directory table per party type (design T468–501, T530–563, T710–736, T766–791). */
export function DirectoryTable({ directory }: { directory: PartyDirectory }) {
    const { t } = useTranslation();
    const kind = directory.kind;
    const heads = HEADS[kind];
    const rowClass = cn(
        GRIDS[kind],
        ROW_RULE,
        HOVER[kind],
        'relative items-center py-[13px]',
    );

    const rows = (() => {
        switch (directory.kind) {
            case 'business':
                return directory.rows.map((row) => (
                    <div key={row.id} role="row" className={rowClass}>
                        <BusinessRow row={row} />
                    </div>
                ));
            case 'investor':
                return directory.rows.map((row) => (
                    <div key={row.id} role="row" className={rowClass}>
                        <InvestorRow row={row} />
                    </div>
                ));
            case 'auditor':
                return directory.rows.map((row) => (
                    <div key={row.id} role="row" className={rowClass}>
                        <AuditorRow row={row} />
                    </div>
                ));
            case 'staff':
                return directory.rows.map((row) => (
                    <div key={row.id} role="row" className={rowClass}>
                        <StaffRow row={row} />
                    </div>
                ));
        }
    })();

    return (
        <TableCard
            label={t(`admin.parties.table.${kind}`)}
            minWidth={kind === 'staff' ? 'min-w-[560px]' : 'min-w-[860px]'}
        >
            <div
                role="row"
                className={cn(GRIDS[kind], TABLE_HEAD, 'py-[13px]')}
            >
                {heads.map((head, index) => (
                    <HeadCell
                        key={head}
                        end={
                            index === heads.length - 1 &&
                            kind !== 'auditor' &&
                            kind !== 'staff'
                        }
                    >
                        {t(`admin.parties.col.${head}`)}
                    </HeadCell>
                ))}
            </div>
            {rows}
            {directory.rows.length === 0 && (
                <EmptyState title={t(`admin.parties.empty.${kind}`)} />
            )}
        </TableCard>
    );
}
