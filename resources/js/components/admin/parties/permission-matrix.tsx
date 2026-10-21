import {
    HeadCell,
    InfoTip,
    ROW_RULE,
    TABLE_HEAD,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { PermissionMatrixRow, StaffRole } from '@/types/admin';

/** The console's roles from most to least privileged, as the server orders them. */
const ROLES: StaffRole[] = [
    'superadmin',
    'compliance',
    'approver',
    'treasury',
    'analyst',
];

/** The design's role colours on the column heads. */
const ROLE_TEXT: Record<StaffRole, string> = {
    superadmin: 'text-[#1e3aff] dark:text-[#99a3ff]',
    compliance: 'text-[#c2661f] dark:text-[#f0a060]',
    approver: 'text-[#1d9e75] dark:text-[#3fcda0]',
    treasury: 'text-[#1428a8] dark:text-[#99a3ff]',
    analyst: 'text-[#7c3aed] dark:text-[#b199fb]',
};

const GRID =
    'grid grid-cols-[minmax(0,1.5fr)_minmax(0,2fr)_repeat(5,84px)] items-center gap-3.5 px-5';

/**
 * Role permissions (design Staff & Roles): every capability a role can grant, what it allows, and a
 * mark under each role that grants it. Read from the server's role definitions, so the table is
 * the same rule every staff command checks. Read-only: roles are not edited here.
 */
export function PermissionMatrix({ rows }: { rows: PermissionMatrixRow[] }) {
    const { t } = useTranslation();

    return (
        <section aria-labelledby="permission-matrix-title" className="mt-6">
            <div className="mb-2.5 flex items-center gap-[7px]">
                <h2
                    id="permission-matrix-title"
                    className="text-[15px] font-semibold text-rz-ink"
                >
                    {t('admin.matrix.title')}
                </h2>
                <InfoTip label={t('admin.matrix.title')}>
                    {t('admin.matrix.info')}
                </InfoTip>
            </div>
            <TableCard label={t('admin.matrix.title')} minWidth="min-w-[860px]">
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[11px]')}>
                    <HeadCell>{t('admin.matrix.col.capability')}</HeadCell>
                    <HeadCell>{t('admin.matrix.col.detail')}</HeadCell>
                    {ROLES.map((role) => (
                        <span
                            key={role}
                            role="columnheader"
                            className={cn(
                                'truncate text-center text-[10px] font-bold uppercase',
                                ROLE_TEXT[role],
                            )}
                        >
                            {t(`admin.role.${role}`)}
                        </span>
                    ))}
                </div>
                {rows.map((row) => (
                    <div
                        key={row.key}
                        role="row"
                        className={cn(GRID, ROW_RULE, 'py-[11px]')}
                    >
                        <span
                            role="cell"
                            className="truncate text-[12.5px] font-semibold text-rz-ink"
                        >
                            {t(`admin.permission.${row.key}`)}
                        </span>
                        <span
                            role="cell"
                            className="truncate text-[11.5px] text-rz-muted"
                        >
                            {t(`admin.permission_detail.${row.key}`)}
                        </span>
                        {ROLES.map((role) => {
                            const granted = row.roles.includes(role);

                            return (
                                <span
                                    key={role}
                                    role="cell"
                                    aria-label={t(
                                        granted
                                            ? 'admin.matrix.granted'
                                            : 'admin.matrix.withheld',
                                    )}
                                    className={cn(
                                        'text-center text-[13px] font-extrabold',
                                        granted
                                            ? 'text-[#1d9e75] dark:text-[#3fcda0]'
                                            : 'text-[#cbd5e6] dark:text-rz-muted',
                                    )}
                                >
                                    <span aria-hidden>
                                        {granted ? '✓' : '–'}
                                    </span>
                                </span>
                            );
                        })}
                    </div>
                ))}
            </TableCard>
        </section>
    );
}
