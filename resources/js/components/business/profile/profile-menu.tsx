import { Link } from '@inertiajs/react';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { BusinessProfileProps, ProfileSection } from '@/types/business';

/**
 * The design's menu also lists Security center, Permissions & roles and Support center; those are
 * beyond the MVP, so they are left out rather than shipped as dead ends.
 */
const ITEMS: { key: ProfileSection; icon: IconName }[] = [
    { key: 'company', icon: 'building' },
    { key: 'linked', icon: 'card' },
    { key: 'terms', icon: 'document' },
    { key: 'privacy', icon: 'lock-key' },
];

type ProfileMenuProps = Pick<
    BusinessProfileProps,
    'business' | 'section' | 'links'
>;

/** The Profile tab (design L1460–1479): identity card, menu and Sign out. */
export function ProfileMenu({ business, section, links }: ProfileMenuProps) {
    const { t } = useTranslation();

    return (
        <>
            <h1 className="text-2xl font-semibold text-rz-ink lg:hidden">
                {t('business.profile.title')}
            </h1>
            <div className="mt-[18px] flex items-center gap-[15px] rounded-2xl border border-rz-border bg-rz-surface p-5 lg:mt-0">
                <span
                    aria-hidden
                    className="flex size-16 items-center justify-center rounded-2xl bg-rz-accent-fill text-2xl font-semibold text-white"
                >
                    {business.name.trim().charAt(0).toUpperCase()}
                </span>
                <div className="flex-1">
                    <p className="text-[17px] font-semibold text-rz-ink">
                        {business.name}
                    </p>
                    <p className="mt-[3px] text-[13px] text-rz-secondary">
                        {business.address_line}
                    </p>
                    <p className="mt-[7px] flex gap-1.5">
                        {business.verified && (
                            <span className="rounded-[10px] bg-rz-accent-soft px-2 py-[3px] text-[11px] font-semibold text-rz-accent-app-text">
                                {t('business.profile.verified')}
                            </span>
                        )}
                        <span className="rounded-[10px] bg-rz-accent-soft px-2 py-[3px] text-[11px] font-semibold text-rz-accent-app-text">
                            {t('business.profile.score', {
                                score: business.rating.score,
                            })}
                        </span>
                    </p>
                </div>
            </div>
            <nav
                aria-label={t('business.profile.menu')}
                className="mt-3.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface"
            >
                {ITEMS.map(({ key, icon }) => (
                    <Link
                        key={key}
                        href={links.sections[key]}
                        aria-current={section === key ? 'page' : undefined}
                        className={cn(
                            'flex w-full items-center gap-[13px] border-b border-[#eef2f9] p-[15px] text-left last:border-b-0 dark:border-rz-divider',
                            section === key && 'lg:bg-rz-page',
                        )}
                    >
                        <span className="flex size-[34px] items-center justify-center rounded-xl bg-rz-page">
                            <Icon name={icon} />
                        </span>
                        <span className="flex-1 text-[14.5px] font-semibold text-rz-ink">
                            {t(`business.profile.section.${key}`)}
                        </span>
                        <span aria-hidden className="text-rz-secondary">
                            ›
                        </span>
                    </Link>
                ))}
            </nav>
            <Link
                href={links.sign_out}
                as="button"
                className="mt-4 block w-full rounded-2xl border border-[#fdeaea] bg-[rgba(229,72,77,.08)] p-3.5 text-center text-sm font-semibold text-rz-danger-text dark:border-[rgba(255,107,111,.25)]"
            >
                {t('business.profile.sign_out')}
            </Link>
        </>
    );
}
