import { Chip } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import type {
    StaffRole,
    Tone,
    UserKyc,
    UserMembership,
    UserRow,
} from '@/types/admin';

const STAFF_ROLES: string[] = [
    'analyst',
    'approver',
    'compliance',
    'treasury',
    'founder',
    'superadmin',
] satisfies StaffRole[];

export const USER_KYC_TONE: Record<UserKyc, Tone> = {
    verified: 'green',
    in_review: 'blue',
    in_progress: 'amber',
    rejected: 'red',
    not_started: 'grey',
};

const ROLE_TONE: Record<UserMembership['role'], Tone> = {
    investor: 'green',
    business: 'blue',
    auditor: 'purple',
};

/** An active role reads in its role colour; any other status says so in amber or red. */
const membershipTone = (membership: UserMembership): Tone =>
    membership.status === 'active'
        ? ROLE_TONE[membership.role]
        : membership.status === 'pending'
          ? 'amber'
          : 'red';

/**
 * Every role an account holds, one chip each: its Investor, Business and Auditor memberships with
 * their status, its staff roles (struck through while the staff account is disabled), and the
 * roles it asked for and does not hold yet. An account with none says so.
 */
export function UserRoleChips({
    row,
    current = false,
}: {
    row: Pick<UserRow, 'memberships' | 'staff' | 'requested' | 'institution'>;
    /** Only the roles held now; the drawer lists revoked ones with their dates underneath. */
    current?: boolean;
}) {
    const { t } = useTranslation();
    /* An institution's active Investor role reads once, as "Investor for {institution}" (QA #826). */
    const memberships = (
        current
            ? row.memberships.filter(
                  (membership) => membership.status !== 'revoked',
              )
            : row.memberships
    ).filter(
        (membership) =>
            !(
                row.institution &&
                membership.role === 'investor' &&
                membership.status === 'active'
            ),
    );
    const none =
        memberships.length === 0 &&
        row.staff === null &&
        row.requested.length === 0 &&
        !row.institution;

    return (
        <span className="flex flex-wrap gap-1">
            {memberships.map((membership) => (
                <Chip key={membership.role} tone={membershipTone(membership)}>
                    {membership.status === 'active'
                        ? t(`admin.users.role.${membership.role}`)
                        : t('admin.users.role_with_status', {
                              role: t(`admin.users.role.${membership.role}`),
                              status: t(
                                  `admin.users.membership.${membership.status}`,
                              ),
                          })}
                </Chip>
            ))}
            {row.staff !== null && (
                <Chip
                    tone={row.staff.enabled ? 'grey' : 'red'}
                    className={row.staff.enabled ? undefined : 'line-through'}
                >
                    {row.staff.roles.length === 0
                        ? t('admin.users.role.staff')
                        : t('admin.users.staff_roles', {
                              roles: row.staff.roles
                                  .map((role) =>
                                      STAFF_ROLES.includes(role as StaffRole)
                                          ? t(`admin.role.${role as StaffRole}`)
                                          : role,
                                  )
                                  .join(', '),
                          })}
                </Chip>
            )}
            {row.institution && (
                <Chip tone="blue">
                    {t('admin.users.invests_for', { name: row.institution })}
                </Chip>
            )}
            {row.requested.map((role) => (
                <Chip
                    key={`requested-${role}`}
                    tone="grey"
                    className="border border-dashed border-rz-hairline bg-transparent"
                >
                    {role === 'institution'
                        ? t('admin.users.institution_applying')
                        : t('admin.users.requested', {
                              role: t(`admin.users.role.${role}`),
                          })}
                </Chip>
            ))}
            {none && (
                <span className="text-[12px] text-rz-faint">
                    {t('admin.users.no_role')}
                </span>
            )}
        </span>
    );
}

export function UserKycChip({ kyc }: { kyc: UserKyc }) {
    const { t } = useTranslation();

    return <Chip tone={USER_KYC_TONE[kyc]}>{t(`admin.users.kyc.${kyc}`)}</Chip>;
}
