import { Link } from '@inertiajs/react';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { BusinessProfileProps } from '@/types/business';

/**
 * Security center (design L1742–1772): whether two-factor sign-in is on, and the way to change the
 * password, both managed on the account's security settings. The design's other toggles and its
 * account deletion have no command for a Business, so they are left out.
 */
export function SecurityCenter({
    security,
    settings,
}: {
    security: BusinessProfileProps['security'];
    settings: RouteLink;
}) {
    const { t } = useTranslation();
    const glyph =
        'flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-page text-base';

    return (
        <>
            <div className="mt-5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                <Link
                    href={settings}
                    className="flex w-full items-center gap-3 p-4 text-left"
                >
                    <span className={glyph}>
                        <Icon name="lock" />
                    </span>
                    <span className="min-w-0 flex-1">
                        <span className="block text-sm font-semibold text-rz-ink">
                            {t('business.profile.security.two_factor')}
                        </span>
                        <span className="block text-xs text-rz-secondary">
                            {t(
                                security.two_factor
                                    ? 'business.profile.security.two_factor_on'
                                    : 'business.profile.security.two_factor_off',
                            )}
                        </span>
                    </span>
                    <span
                        aria-hidden
                        className={cn(
                            'relative h-6 w-11 shrink-0 rounded-xl',
                            security.two_factor
                                ? 'bg-rz-accent-fill'
                                : 'bg-[#5b6578]',
                        )}
                    >
                        <span
                            className={cn(
                                'absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow-[0_1px_3px_rgba(0,0,0,.25)]',
                                security.two_factor && 'translate-x-5',
                            )}
                        />
                    </span>
                </Link>
            </div>
            <div className="mt-3.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                <Link
                    href={settings}
                    className="flex w-full items-center gap-[13px] p-[15px] text-left"
                >
                    <span className={glyph}>
                        <Icon name="key" />
                    </span>
                    <span className="flex-1 text-sm font-semibold text-rz-ink">
                        {t('business.profile.security.password')}
                    </span>
                    <span aria-hidden className="text-rz-secondary">
                        ›
                    </span>
                </Link>
            </div>
        </>
    );
}

const AVATAR = ['#17795a', '#1e3aff', '#7c3aed', '#c2661f'];

/**
 * Permissions & roles (design L1774–1836): everyone on the mandate with their roles and what they
 * may do. There is no invite, removal or ownership-transfer command and no activity log read, so
 * the list is read-only and those parts of the design are left out.
 */
export function TeamPermissions({
    team,
}: {
    team: BusinessProfileProps['team'];
}) {
    const { t } = useTranslation();

    return (
        <ul className="mt-5 flex flex-col gap-[11px]">
            {team.map((person, index) => (
                <li
                    key={`${person.name}-${index}`}
                    className="flex items-center gap-[13px] rounded-2xl border border-rz-border bg-rz-surface p-3.5"
                >
                    <span
                        aria-hidden
                        style={{ background: AVATAR[index % AVATAR.length] }}
                        className="flex size-10 shrink-0 items-center justify-center rounded-full text-[13px] font-semibold text-white"
                    >
                        {person.name
                            .split(/\s+/u)
                            .slice(0, 2)
                            .map((part) => part.charAt(0))
                            .join('')
                            .toUpperCase()}
                    </span>
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-[7px]">
                            <p className="text-sm font-semibold text-rz-ink">
                                {person.name}
                            </p>
                            {person.roles.map((role) => (
                                <span
                                    key={role}
                                    className="inline-flex items-center rounded-[10px] bg-rz-accent-soft px-[9px] py-[3px] text-[10.5px] font-bold text-rz-accent-app-text"
                                >
                                    {t(
                                        `business.profile.registration.role.${role}`,
                                    )}
                                </span>
                            ))}
                        </div>
                        <p className="mt-0.5 text-[11.5px] leading-[1.4] text-rz-secondary">
                            {person.permissions.length === 0
                                ? t('business.profile.permissions.none')
                                : person.permissions
                                      .map((permission) =>
                                          t(
                                              `business.profile.permissions.${permission}`,
                                          ),
                                      )
                                      .join(' · ')}
                        </p>
                    </div>
                </li>
            ))}
        </ul>
    );
}

const PENDING_ICON: Record<
    'linked' | 'support' | 'terms' | 'privacy',
    IconName
> = {
    linked: 'card',
    support: 'question',
    terms: 'document',
    privacy: 'lock-key',
};

/**
 * A section with nothing to show yet: no linked payout account read, and no approved support
 * answers or legal text. The design draws each with content, so this is its empty state.
 */
export function PendingSection({
    kind,
}: {
    kind: 'linked' | 'support' | 'terms' | 'privacy';
}) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col items-center px-[22px] py-16 text-center">
            <span
                aria-hidden
                className="flex size-[72px] items-center justify-center rounded-[20px] bg-rz-accent-soft text-[26px]"
            >
                <Icon name={PENDING_ICON[kind]} />
            </span>
            <p className="mt-4 text-[17px] font-semibold text-rz-ink">
                {t(`business.profile.pending.${kind}_title`)}
            </p>
            <p className="mt-1.5 max-w-[260px] text-[13px] leading-normal text-rz-secondary">
                {t(`business.profile.pending.${kind}_body`)}
            </p>
        </div>
    );
}
