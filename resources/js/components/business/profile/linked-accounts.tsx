import { Link } from '@inertiajs/react';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import type { BusinessProfileProps, LinkedAccount } from '@/types/business';

const KIND_ICON: Record<LinkedAccount['kind'], IconName> = {
    wallet: 'wallet',
    bank: 'bank',
    mobile_money: 'phone',
};

/**
 * "Linked accounts" (design L1829–1840). The payout bank carries no Unlink: only Rozine support can
 * change it, as onboarding tells the business.
 */
export function LinkedAccounts({
    linked,
}: {
    linked: NonNullable<BusinessProfileProps['linked']>;
}) {
    const { t } = useTranslation();

    return (
        <>
            <ul className="mt-5 flex flex-col gap-[11px]">
                {linked.accounts.map((account) => (
                    <li
                        key={account.id}
                        className="flex items-center gap-[13px] rounded-2xl border border-rz-border bg-rz-surface p-[15px]"
                    >
                        <span className="flex size-10 items-center justify-center rounded-[10px] bg-rz-page text-lg">
                            <Icon name={KIND_ICON[account.kind]} />
                        </span>
                        <div className="flex-1">
                            <p className="text-sm font-semibold text-rz-ink">
                                {account.name}
                            </p>
                            <p className="text-xs text-rz-secondary">
                                {account.detail}
                            </p>
                        </div>
                        {account.unlink !== null && (
                            <Link
                                href={account.unlink}
                                as="button"
                                aria-label={t(
                                    'business.profile.linked.unlink_named',
                                    { name: account.name },
                                )}
                                className="text-[12.5px] font-semibold text-rz-danger-text"
                            >
                                {t('business.profile.linked.unlink')}
                            </Link>
                        )}
                    </li>
                ))}
            </ul>
            {linked.add !== null && (
                <Link
                    href={linked.add}
                    className="mt-3.5 flex h-[50px] w-full items-center justify-center rounded-xl border-[1.5px] border-dashed border-[#dbe3f0] bg-rz-surface text-sm font-semibold text-rz-accent-app-text dark:border-rz-border"
                >
                    {t('business.profile.linked.add')}
                </Link>
            )}
        </>
    );
}
