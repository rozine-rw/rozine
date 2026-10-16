import { Form } from '@inertiajs/react';
import { LinkAccountForm } from '@/components/investor/profile/profile-parts';
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
 * "Linked accounts" (design L1829–1840): the Business's own verified accounts. Where the server
 * offers it, linking sends the details with a fresh `request_id`, and the server verifies the account
 * is held in the business's registered name (BRS v2.0 KYC-R03) before it answers; unlinking revokes
 * one. Without those commands the accounts are only listed.
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
                            <Form
                                action={account.unlink.url}
                                method={account.unlink.method}
                                transform={(data) => ({
                                    ...data,
                                    request_id: crypto.randomUUID(),
                                })}
                                options={{ preserveScroll: true }}
                            >
                                <button
                                    type="submit"
                                    aria-label={t(
                                        'business.profile.linked.unlink_named',
                                        { name: account.name },
                                    )}
                                    className="text-[12.5px] font-semibold text-rz-danger-text"
                                >
                                    {t('business.profile.linked.unlink')}
                                </button>
                            </Form>
                        )}
                    </li>
                ))}
                {linked.accounts.length === 0 && (
                    <li className="rounded-2xl border border-dashed border-[#dbe3f0] px-4 py-5 text-center text-[12.5px] text-rz-secondary dark:border-rz-border">
                        {t(
                            linked.add === null
                                ? 'business.profile.linked.empty'
                                : 'business.profile.linked.none',
                        )}
                    </li>
                )}
            </ul>
            {linked.add !== null && (
                <LinkAccountForm
                    banks={linked.banks}
                    action={linked.add}
                    hasAccounts={linked.accounts.length > 0}
                    addLabel="business.profile.linked.add"
                    footnote="business.profile.linked.footnote"
                />
            )}
        </>
    );
}
