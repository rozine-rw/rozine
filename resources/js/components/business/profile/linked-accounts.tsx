import { Form, Link } from '@inertiajs/react';
import { LinkAccountForm } from '@/components/investor/profile/profile-parts';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import type { BusinessProfileProps, LinkedAccount } from '@/types/business';
import type { RouteLink } from '@/types/routing';

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
    support = null,
}: {
    linked: NonNullable<BusinessProfileProps['linked']>;
    /** The support section, offered when linking is not open yet so the business has somewhere to ask. */
    support?: RouteLink | null;
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
                    <li className="rounded-2xl border border-dashed border-[#dbe3f0] px-4 py-5 text-center text-[12.5px] leading-[1.55] text-rz-secondary dark:border-rz-border">
                        {/* Never a dead end: say why there is nothing to do yet, and where to ask (#592). */}
                        {linked.add !== null
                            ? t('business.profile.linked.none')
                            : linked.open === false
                              ? t('business.profile.linked.closed')
                              : t('business.profile.linked.not_permitted')}
                        {linked.add === null &&
                            linked.open === false &&
                            support !== null && (
                                <>
                                    {' '}
                                    <Link
                                        href={support}
                                        className="font-semibold text-rz-accent-app-text underline underline-offset-2"
                                    >
                                        {t(
                                            'business.profile.linked.ask_support',
                                        )}
                                    </Link>
                                </>
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
