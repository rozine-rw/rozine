import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import {
    avatarColor,
    formatTimestamp,
    initialOf,
} from '@/components/admin/format';
import {
    UserKycChip,
    UserRoleChips,
} from '@/components/admin/parties/user-roles';
import { MicroBadge, useRelativeLabel } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { MessageCode } from '@/lib/i18n/types';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { UserDetail } from '@/types/admin';

const SECTION =
    'text-[12px] font-bold tracking-[.05em] text-[#636e86] uppercase dark:text-rz-muted';
const BOX = 'rounded-[13px] border border-rz-hairline bg-rz-surface';

/** The identity history actions the drawer names; any other reads as its code. */
const ACTIONS = [
    'account.close',
    'investor.self_grant',
    'membership.mandate_grant',
    'membership.change',
    'role.select',
    'staff.configure',
    'staff.superadmin.request',
    'staff.person.record',
    'operator.configure',
    'organization.resolve',
] as const;

const actionLabel = (
    action: string,
    t: (code: MessageCode) => string,
): string =>
    (ACTIONS as readonly string[]).includes(action)
        ? t(`admin.users.action.${action as (typeof ACTIONS)[number]}`)
        : action;

function Fact({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="min-w-0 rounded-[11px] border border-rz-hairline bg-rz-surface px-3 py-2.5">
            <dt className="truncate text-[10.5px] font-semibold text-rz-muted">
                {label}
            </dt>
            <dd className="mt-[3px] text-[13px] font-bold break-words text-rz-ink">
                {children}
            </dd>
        </div>
    );
}

function DirectoryLink({ link, label }: { link: RouteLink; label: string }) {
    return (
        <Link
            href={link}
            className="rounded-[10px] border border-rz-hairline bg-rz-surface px-3 py-[7px] text-[12px] font-semibold text-rz-accent-app-text"
        >
            {label}
        </Link>
    );
}

/**
 * One account in the console drawer: who they are, every role they hold with its status, their
 * identity (KYC) state, how they sign in, the access they asked for and their identity history,
 * newest first. Read-only: the links open the directories that act on each role.
 */
export function UserDrawer({
    user,
    serverTime,
}: {
    user: UserDetail;
    serverTime: string;
}) {
    const { t, locale } = useTranslation();
    const ago = useRelativeLabel(serverTime);
    const name = user.name.trim() === '' ? user.email : user.name;
    const links = (
        [
            ['investor', user.links.investor],
            ['business', user.links.business],
            ['auditor', user.links.auditor],
            ['staff', user.links.staff],
            ['access_requests', user.links.access_requests],
        ] as const
    ).filter(
        (entry): entry is [(typeof entry)[0], RouteLink] => entry[1] !== null,
    );

    return (
        <Drawer
            label={t('admin.users.drawer_label', { name })}
            close={user.links.close}
        >
            <div className="shrink-0 border-b border-rz-hairline bg-rz-surface px-[18px] py-4">
                <div className="flex items-center gap-3">
                    <div
                        className="flex size-10 shrink-0 items-center justify-center rounded-[11px] text-[16px] font-bold text-white"
                        style={{ background: avatarColor(user.id) }}
                    >
                        {initialOf(name)}
                    </div>
                    <div className="min-w-0 flex-1">
                        <div className="flex min-w-0 flex-wrap items-center gap-2">
                            <h2 className="truncate text-[17px] font-bold text-rz-ink">
                                {name}
                            </h2>
                            {user.restricted && (
                                <MicroBadge>
                                    {t('admin.users.badge.restricted')}
                                </MicroBadge>
                            )}
                        </div>
                        <p className="mt-0.5 truncate text-[12px] text-rz-muted">
                            {user.email}
                        </p>
                    </div>
                    <DrawerClose close={user.links.close} />
                </div>
            </div>

            <div
                tabIndex={0}
                className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-[18px] pb-[26px]"
            >
                {user.closed_at !== null && (
                    <div
                        role="status"
                        className="mb-5 rounded-[13px] border border-[#f4d4d4] bg-[#fff7f7] px-4 py-3 text-[12.5px] text-[#a0524f] dark:border-red-200/20 dark:bg-red-700/10 dark:text-red-200"
                    >
                        <p className="font-bold">
                            {t('admin.users.closed_on', {
                                date: formatDate(user.closed_at, locale),
                            })}
                        </p>
                        <p className="mt-1">
                            {user.closure_reason === null
                                ? t('admin.users.closed_no_reason')
                                : t('admin.users.closed_reason', {
                                      reason: user.closure_reason,
                                  })}
                        </p>
                    </div>
                )}
                <section aria-label={t('admin.users.roles_title')}>
                    <h3 className={SECTION}>{t('admin.users.roles_title')}</h3>
                    <div className={cn(BOX, 'mt-2.5 px-4 py-3.5')}>
                        <UserRoleChips row={user} />
                        {user.memberships.length > 0 && (
                            <ul className="mt-3 space-y-1.5">
                                {user.memberships.map((membership) => (
                                    <li
                                        key={membership.role}
                                        className="flex justify-between gap-3 text-[12px] text-rz-body"
                                    >
                                        <span>
                                            {t(
                                                `admin.users.role.${membership.role}`,
                                            )}{' '}
                                            ·{' '}
                                            {t(
                                                `admin.users.membership.${membership.status}`,
                                            )}
                                        </span>
                                        <span className="text-rz-muted">
                                            {t('admin.users.since', {
                                                date: formatDate(
                                                    membership.since,
                                                    locale,
                                                ),
                                            })}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                        {user.staff !== null && !user.staff.enabled && (
                            <p className="mt-2 text-[12px] text-rz-muted">
                                {t('admin.users.staff_disabled')}
                            </p>
                        )}
                    </div>
                </section>

                <section
                    aria-label={t('admin.users.account_title')}
                    className="mt-5"
                >
                    <h3 className={SECTION}>
                        {t('admin.users.account_title')}
                    </h3>
                    <dl className="mt-2.5 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <Fact label={t('admin.users.fact.kyc')}>
                            <UserKycChip kyc={user.kyc} />
                        </Fact>
                        <Fact label={t('admin.users.fact.signed_up')}>
                            {formatDate(user.signed_up_at, locale)}
                        </Fact>
                        <Fact label={t('admin.users.fact.last_seen')}>
                            {user.last_seen_at === null
                                ? t('admin.users.never')
                                : ago(user.last_seen_at)}
                        </Fact>
                        <Fact label={t('admin.users.fact.email')}>
                            {user.email_verified_at === null
                                ? t('admin.users.email_unverified')
                                : t('admin.users.email_verified_on', {
                                      date: formatDate(
                                          user.email_verified_at,
                                          locale,
                                      ),
                                  })}
                        </Fact>
                        <Fact label={t('admin.users.fact.two_factor')}>
                            {t(
                                user.two_factor
                                    ? 'admin.users.on'
                                    : 'admin.users.off',
                            )}
                        </Fact>
                        <Fact label={t('admin.users.fact.passkeys')}>
                            {user.passkeys}
                        </Fact>
                        {user.submitted_at !== null && (
                            <Fact label={t('admin.users.fact.kyc_submitted')}>
                                {formatDate(user.submitted_at, locale)}
                            </Fact>
                        )}
                        <Fact label={t('admin.users.fact.user_id')}>
                            {user.id}
                        </Fact>
                    </dl>
                </section>

                {user.requests.length > 0 && (
                    <section
                        aria-label={t('admin.users.requests_title')}
                        className="mt-5"
                    >
                        <h3 className={SECTION}>
                            {t('admin.users.requests_title')}
                        </h3>
                        <ul className={cn(BOX, 'mt-2.5 overflow-hidden')}>
                            {user.requests.map((request) => (
                                <li
                                    key={request.role}
                                    className="border-b border-[#eef2f8] px-[15px] py-3 text-[12.5px] last:border-b-0 dark:border-rz-divider"
                                >
                                    <div className="flex justify-between gap-3">
                                        <span className="font-semibold text-rz-ink">
                                            {t(
                                                `admin.users.role.${request.role}`,
                                            )}
                                            {request.organization !== '' &&
                                                ` · ${request.organization}`}
                                        </span>
                                        <span className="shrink-0 text-rz-muted">
                                            {formatDate(
                                                request.sent_at,
                                                locale,
                                            )}
                                        </span>
                                    </div>
                                    {request.reference !== null && (
                                        <div className="mt-0.5 text-[11.5px] text-rz-muted">
                                            {request.reference}
                                        </div>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {links.length > 0 && (
                    <section
                        aria-label={t('admin.users.open_title')}
                        className="mt-5"
                    >
                        <h3 className={SECTION}>
                            {t('admin.users.open_title')}
                        </h3>
                        <div className="mt-2.5 flex flex-wrap gap-2">
                            {links.map(([key, link]) => (
                                <DirectoryLink
                                    key={key}
                                    link={link}
                                    label={t(`admin.users.open.${key}`)}
                                />
                            ))}
                        </div>
                    </section>
                )}

                <section
                    aria-label={t('admin.users.history_title')}
                    className="mt-5"
                >
                    <h3 className={SECTION}>
                        {t('admin.users.history_title')}
                    </h3>
                    <ol className={cn(BOX, 'mt-2.5 overflow-hidden')}>
                        {user.history.map((entry) => (
                            <li
                                key={entry.id}
                                className="border-b border-[#eef2f8] px-[15px] py-3 last:border-b-0 dark:border-rz-divider"
                            >
                                <div className="flex justify-between gap-3 text-[12.5px]">
                                    <span className="font-semibold text-rz-ink">
                                        {actionLabel(entry.action, t)}
                                    </span>
                                    <span className="shrink-0 text-[11.5px] text-rz-muted">
                                        {formatTimestamp(entry.at)}
                                    </span>
                                </div>
                                <div className="mt-0.5 text-[11.5px] text-rz-muted">
                                    {entry.actor === ''
                                        ? t('admin.users.actor_system')
                                        : entry.actor}
                                    {entry.reason !== '' &&
                                        ` · ${entry.reason}`}
                                </div>
                            </li>
                        ))}
                        <li className="px-[15px] py-3 text-[12.5px]">
                            <span className="font-semibold text-rz-ink">
                                {t('admin.users.action.signed_up')}
                            </span>
                            <span className="ml-2 text-[11.5px] text-rz-muted">
                                {formatTimestamp(user.signed_up_at)}
                            </span>
                        </li>
                    </ol>
                </section>
            </div>
        </Drawer>
    );
}
