import { Form, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { InfoNotice } from '@/components/investor/deals/deal-status';
import { POSITIVE_TEXT } from '@/components/investor/tokens';
import { MethodMark } from '@/components/investor/wallet/funding';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatMonthYearLong, formatMonthYear } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { InvestorProfileProps, KycState } from '@/types/investor';

const KYC_PILL: Record<KycState, string> = {
    verified: cn(
        'bg-[rgba(29,158,117,.10)] dark:bg-[rgba(63,205,160,.14)]',
        POSITIVE_TEXT,
    ),
    pending: 'bg-[rgba(194,102,31,.10)] text-[#a55418] dark:text-[#f0a060]',
    unverified: 'bg-[rgba(229,72,77,.10)] text-rz-danger-text',
    expired: 'bg-[rgba(229,72,77,.10)] text-rz-danger-text',
};

/** Identity card (design L2730–2741): initials, name, email, verification and investor type. */
export function IdentityCard({
    identity,
}: {
    identity: InvestorProfileProps['identity'];
}) {
    const { t, locale } = useTranslation();
    const initials = identity.name
        .split(/\s+/u)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();

    return (
        <>
            <div className="mt-[18px] flex items-center gap-[15px] rounded-2xl border border-rz-border bg-rz-surface p-5">
                <span className="flex size-16 shrink-0 items-center justify-center rounded-full bg-[linear-gradient(135deg,#1e3aff,#17795a)] text-2xl font-semibold text-white">
                    {initials}
                </span>
                <div className="min-w-0 flex-1">
                    <p className="truncate text-lg font-semibold text-rz-ink">
                        {identity.name}
                    </p>
                    <p className="mt-[3px] truncate text-[13px] text-rz-secondary">
                        {identity.email}
                    </p>
                    <div className="mt-[7px] flex flex-wrap gap-1.5">
                        <span
                            className={cn(
                                'inline-flex items-center gap-1 rounded-[10px] px-2 py-[3px] text-[11px] font-semibold',
                                KYC_PILL[identity.kyc],
                            )}
                        >
                            {t(`investor.profile.kyc.${identity.kyc}`)}
                        </span>
                        <span className="rounded-[10px] bg-rz-accent-soft px-2 py-[3px] text-[11px] font-semibold text-rz-accent-app-text">
                            {t(
                                `investor.profile.type.${identity.investor_type}`,
                            )}
                        </span>
                    </div>
                </div>
            </div>
            <p className="mt-3.5 text-xs font-semibold text-rz-secondary">
                {t('investor.profile.member_since', {
                    date: formatMonthYearLong(identity.member_since, locale),
                })}
            </p>
        </>
    );
}

type MenuKey = Exclude<
    InvestorProfileProps['section'],
    'overview' | 'automation'
>;

const MENU: { key: MenuKey; icon: IconName }[] = [
    { key: 'personal', icon: 'person' },
    { key: 'plan', icon: 'flash' },
    { key: 'security', icon: 'lock' },
    { key: 'linked', icon: 'card' },
    { key: 'statements', icon: 'receipt' },
    { key: 'help', icon: 'question' },
    { key: 'terms', icon: 'document' },
    { key: 'privacy', icon: 'lock-key' },
];

/**
 * The profile menu (design L2800–2809, rows L9933–9941): the design's eight rows in its order. A
 * person not yet verified also gets a row back to their verification, which the design (drawn for
 * a verified investor) has no place for.
 */
export function ProfileMenu({
    section,
    kyc,
    links,
}: {
    section: InvestorProfileProps['section'];
    kyc: KycState;
    links: InvestorProfileProps['links'];
}) {
    const { t } = useTranslation();
    const verification =
        kyc === 'verified' || links.verification === null
            ? null
            : links.verification;

    return (
        <nav
            aria-label={t('investor.profile.menu')}
            className="mt-3.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface"
        >
            {verification !== null && (
                <MenuRow
                    href={verification}
                    icon="shield"
                    label={t('investor.profile.item.verification')}
                    active={false}
                    alert
                />
            )}
            {MENU.map((item) => (
                <MenuRow
                    key={item.key}
                    href={links[item.key]}
                    icon={item.icon}
                    label={t(`investor.profile.item.${item.key}`)}
                    active={section === item.key}
                />
            ))}
        </nav>
    );
}

function MenuRow({
    href,
    icon,
    label,
    active,
    alert = false,
}: {
    href: RouteLink;
    icon: IconName;
    label: string;
    active: boolean;
    alert?: boolean;
}) {
    return (
        <Link
            href={href}
            aria-current={active ? 'page' : undefined}
            className={cn(
                'flex w-full items-center gap-[13px] border-b border-[#eef2f9] p-[15px] text-left last:border-0 dark:border-rz-divider',
                active && 'bg-rz-accent-soft',
            )}
        >
            <span
                className={cn(
                    'flex size-[34px] items-center justify-center rounded-[10px] text-base',
                    active ? 'bg-rz-accent-soft' : 'bg-rz-page',
                )}
            >
                <Icon name={icon} />
            </span>
            <span
                className={cn(
                    'flex-1 text-[14.5px] font-semibold',
                    active ? 'text-rz-accent-app-text' : 'text-rz-ink',
                )}
            >
                {label}
            </span>
            {alert && <span className="size-2 rounded-full bg-rz-danger" />}
            <span aria-hidden className="text-rz-secondary">
                ›
            </span>
        </Link>
    );
}

/**
 * Personal information (design L3893–3916), read-only: there is no command to change these
 * details, so the design's inputs are shown as fields and its "Save changes" is left out. The ID
 * number arrives already masked; phone and address are not collected yet.
 */
export function PersonalInformation({
    personal,
}: {
    personal: InvestorProfileProps['personal'];
}) {
    const { t } = useTranslation();
    const idType = personal.id_type ?? 'national_id';

    return (
        <dl className="mt-5 flex flex-col gap-3.5">
            <Field
                label={t('investor.profile.personal.name')}
                value={personal.name}
            />
            <Field
                label={t('investor.profile.personal.email')}
                value={personal.email}
            />
            <Field
                label={t('investor.profile.personal.phone')}
                value={personal.phone}
            />
            <div className="flex gap-3">
                <Field
                    label={t('investor.profile.personal.id_type')}
                    value={
                        personal.id_type === null
                            ? null
                            : t(`investor.signup.id.${personal.id_type}`)
                    }
                    className="flex-[0_0_40%]"
                />
                <Field
                    label={t(
                        idType === 'passport'
                            ? 'investor.profile.personal.passport_number'
                            : 'investor.profile.personal.id_number',
                    )}
                    value={personal.id_number}
                    className="min-w-0 flex-1"
                />
            </div>
            <p className="mt-1.5 text-xs font-semibold tracking-[.05em] text-rz-secondary">
                {t('investor.profile.personal.address')}
            </p>
            <Field
                label={t('investor.profile.personal.country')}
                value={personal.address}
            />
            <div className="flex gap-3">
                <Field
                    label={t('investor.profile.personal.province')}
                    value={personal.address}
                    className="flex-1"
                />
                <Field
                    label={t('investor.profile.personal.district')}
                    value={personal.address}
                    className="flex-1"
                />
            </div>
            <div className="flex gap-3">
                <Field
                    label={t('investor.profile.personal.sector')}
                    value={personal.address}
                    className="flex-1"
                />
                <Field
                    label={t('investor.profile.personal.cell')}
                    value={personal.address}
                    className="flex-1"
                />
            </div>
        </dl>
    );
}

function Field({
    label,
    value,
    className,
}: {
    label: string;
    value: string | null;
    className?: string;
}) {
    const { t } = useTranslation();

    return (
        <div className={className}>
            <dt className="mb-1.5 text-xs font-semibold text-rz-secondary">
                {label}
            </dt>
            <dd
                className={cn(
                    'truncate rounded-xl border border-rz-border bg-rz-surface px-3.5 py-[13px] text-sm',
                    value === null ? 'text-rz-muted' : 'text-rz-ink',
                )}
            >
                {value ?? t('investor.profile.personal.not_provided')}
            </dd>
        </div>
    );
}

/**
 * Security center (design L3934–3975): whether two-factor sign-in is on, and the way to change the
 * password. Both are managed on the account's security settings, so each row opens them. The
 * design's login alerts and account deletion have no command for an Investor, so they are left
 * out.
 */
export function SecurityCenter({
    security,
    settings,
}: {
    security: InvestorProfileProps['security'];
    settings: RouteLink;
}) {
    const { t } = useTranslation();
    const row = 'flex w-full items-center gap-3 p-4 text-left';
    const glyph =
        'flex size-[34px] shrink-0 items-center justify-center rounded-[10px] bg-rz-page text-base';

    return (
        <>
            <div className="mt-5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                <Link href={settings} className={row}>
                    <span className={glyph}>
                        <Icon name="lock" />
                    </span>
                    <span className="min-w-0 flex-1">
                        <span className="block text-sm font-semibold text-rz-ink">
                            {t('investor.profile.security.two_factor')}
                        </span>
                        <span className="block text-xs text-rz-secondary">
                            {t(
                                security.two_factor
                                    ? 'investor.profile.security.two_factor_on'
                                    : 'investor.profile.security.two_factor_off',
                            )}
                        </span>
                    </span>
                    <span
                        aria-hidden
                        className={cn(
                            'relative h-6 w-11 shrink-0 rounded-xl',
                            security.two_factor
                                ? 'bg-[#17795a]'
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
                    className={cn(row, 'gap-[13px] p-[15px]')}
                >
                    <span className={glyph}>
                        <Icon name="key" />
                    </span>
                    <span className="flex-1 text-sm font-semibold text-rz-ink">
                        {t('investor.profile.security.password')}
                    </span>
                    <span aria-hidden className="text-rz-secondary">
                        ›
                    </span>
                </Link>
            </div>
        </>
    );
}

const PENDING_ICON: Record<'plan' | 'help' | 'terms' | 'privacy', IconName> = {
    plan: 'flash',
    help: 'question',
    terms: 'document',
    privacy: 'lock-key',
};

/**
 * Rozine Plus, the help center, the terms and the privacy note: the design's pages hold copy and
 * figures (Plus bands and charges, answers, legal text) that are not approved yet, so each shows an
 * empty state until that content is published.
 */
export function PendingContent({
    kind,
}: {
    kind: 'plan' | 'help' | 'terms' | 'privacy';
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
                {t(`investor.profile.pending.${kind}_title`)}
            </p>
            <p className="mt-1.5 max-w-[260px] text-[13px] leading-normal text-rz-secondary">
                {t(`investor.profile.pending.${kind}_body`)}
            </p>
        </div>
    );
}

/**
 * Auto-Deploy (MVP-INVESTOR-SCR-09) as an honest gated explainer (D-61, C-29): it names the missing
 * approval and the next step, and offers nothing to switch on, pause or configure.
 */
export function AutomationExplainer({ deals }: { deals: RouteLink }) {
    const { t } = useTranslation();

    return (
        <>
            <InfoNotice
                title={t('investor.profile.automation.gated_title')}
                body={t('investor.profile.automation.gated_body')}
                className="mt-4"
            />
            <p className="text-[13px] leading-[1.55] text-rz-secondary">
                {t('investor.profile.automation.intro')}
            </p>
            <p className="mt-3 text-[13px] leading-[1.55] text-rz-secondary">
                {t('investor.profile.automation.nothing_runs')}
            </p>
            <Link
                href={deals}
                className="mt-5 flex h-12 w-full items-center justify-center rounded-xl bg-rz-accent-fill text-[14.5px] font-semibold text-white"
            >
                {t('investor.profile.automation.next')}
            </Link>
        </>
    );
}

/** Sign out (design L2833), posting to the server's logout. */
export function SignOut({
    action,
}: {
    action: InvestorProfileProps['actions']['logout'];
}) {
    const { t } = useTranslation();

    return (
        <Form action={action.url} method={action.method}>
            <button
                type="submit"
                className="mt-[18px] block w-full rounded-2xl border border-[#fdeaea] bg-[rgba(229,72,77,.08)] p-3.5 text-center text-sm font-semibold text-rz-danger-text dark:border-[rgba(255,107,111,.25)]"
            >
                {t('investor.profile.sign_out')}
            </button>
        </Form>
    );
}

/** A profile sub-page heading, with the phone back link (design L3726–3729). */
export function SubHeading({
    title,
    back,
}: {
    title: string;
    back: RouteLink | null;
}) {
    const { t } = useTranslation();

    return (
        <div className="flex items-center gap-3">
            {back !== null && (
                <Link
                    href={back}
                    aria-label={t('investor.common.back')}
                    className="flex size-[38px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg leading-none text-rz-ink"
                >
                    <span aria-hidden>←</span>
                </Link>
            )}
            <h2 className="text-xl font-semibold text-rz-ink">{title}</h2>
        </div>
    );
}

type LinkedProps = {
    linked: NonNullable<InvestorProfileProps['linked']>;
    action: InvestorProfileProps['actions']['link_account'];
};

/**
 * Linked payout accounts (design L3856–3923) — wallet readiness. Accounts arrive masked; linking
 * sends the details to the server, which verifies ownership (a one-off verification debit, and
 * payouts only to an account in the investor's own name) and answers with field errors. Cards are
 * not a payout rail in the MVP, so the design's debit-card option is left out. Until the server
 * links a link or unlink command, the accounts are listed without it and nothing invites linking.
 */
export function LinkedAccounts({ linked, action }: LinkedProps) {
    const { t } = useTranslation();

    return (
        <>
            <ul className="mt-5 flex flex-col gap-[11px]">
                {linked.accounts.map((account) => (
                    <li
                        key={account.id}
                        className="flex items-center gap-[13px] rounded-2xl border border-rz-border bg-rz-surface p-[15px]"
                    >
                        <MethodMark kind={account.kind} />
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-semibold text-rz-ink">
                                {account.label}
                            </p>
                            <p className="text-xs text-rz-secondary">
                                {account.masked}
                                {!account.verified && (
                                    <span className="ml-1.5 font-semibold text-[#a55418] dark:text-[#f0a060]">
                                        ·{' '}
                                        {t(
                                            'investor.profile.linked.unverified',
                                        )}
                                    </span>
                                )}
                            </p>
                        </div>
                        {account.unlink !== null && (
                            <Form
                                action={account.unlink.url}
                                method={account.unlink.method}
                            >
                                <button
                                    type="submit"
                                    className="text-[12.5px] font-semibold text-rz-danger-text"
                                >
                                    {t('investor.profile.linked.unlink')}
                                </button>
                            </Form>
                        )}
                    </li>
                ))}
                {linked.accounts.length === 0 && (
                    <li className="rounded-2xl border border-dashed border-[#dbe3f0] px-4 py-5 text-center text-[12.5px] text-rz-secondary dark:border-rz-border">
                        {t(
                            action === null
                                ? 'investor.profile.linked.empty'
                                : 'investor.profile.linked.none',
                        )}
                    </li>
                )}
            </ul>
            {action !== null && (
                <LinkAccountForm
                    banks={linked.banks}
                    action={action}
                    hasAccounts={linked.accounts.length > 0}
                />
            )}
        </>
    );
}

type LinkAccountFormProps = {
    banks: LinkedProps['linked']['banks'];
    action: NonNullable<LinkedProps['action']>;
    hasAccounts: boolean;
};

/** The link-a-new-account form, open from the start while nothing is linked yet. */
function LinkAccountForm({ banks, action, hasAccounts }: LinkAccountFormProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(!hasAccounts);
    const form = useForm<{
        type: 'mobile' | 'bank';
        network: 'mtn' | 'airtel';
        bank: string;
        number: string;
    }>({
        type: 'mobile',
        network: 'mtn',
        bank: banks[0]?.code ?? '',
        number: '',
    });
    const field =
        'mt-[7px] box-border w-full rounded-xl border border-rz-border bg-[#f8fafc] px-[13px] py-3 text-sm font-semibold text-rz-ink outline-none dark:bg-rz-surface-sunken';
    const label =
        'mt-[13px] block text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase';
    const choice = (on: boolean) =>
        cn(
            'flex items-center justify-center gap-2 rounded-xl border-[1.5px] px-2 py-2.5 text-[12.5px] font-semibold',
            on
                ? 'border-rz-accent-fill bg-rz-accent-soft text-rz-accent-app-text'
                : 'border-rz-border bg-rz-surface text-rz-slate',
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(action.url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('number');
                setOpen(false);
            },
        });
    };

    return open ? (
        <form
            onSubmit={submit}
            aria-label={t('investor.profile.linked.new')}
            className="mt-3.5 rounded-2xl border-[1.5px] border-rz-accent-fill bg-rz-surface p-[15px] shadow-[0_16px_38px_-18px_rgba(20,45,95,.3)]"
        >
            <div className="flex items-center justify-between">
                <p className="text-[13.5px] font-semibold text-rz-ink">
                    {t('investor.profile.linked.new')}
                </p>
                {hasAccounts && (
                    <button
                        type="button"
                        aria-label={t('app.sheet.close')}
                        onClick={() => setOpen(false)}
                        className="flex size-[26px] items-center justify-center rounded-[10px] border border-rz-border bg-[#f3f6fc] text-[13px] text-rz-secondary dark:bg-rz-surface-muted"
                    >
                        <span aria-hidden>✕</span>
                    </button>
                )}
            </div>
            <p className={label}>{t('investor.profile.linked.type')}</p>
            <div
                role="radiogroup"
                aria-label={t('investor.profile.linked.type')}
                className="mt-[7px] grid grid-cols-2 gap-[7px]"
            >
                {(['mobile', 'bank'] as const).map((type) => (
                    <button
                        key={type}
                        type="button"
                        role="radio"
                        aria-checked={form.data.type === type}
                        onClick={() => form.setData('type', type)}
                        className={choice(form.data.type === type)}
                    >
                        {t(`investor.profile.linked.type_${type}`)}
                    </button>
                ))}
            </div>
            {form.data.type === 'mobile' ? (
                <>
                    <p className={label}>
                        {t('investor.profile.linked.network')}
                    </p>
                    <div
                        role="radiogroup"
                        aria-label={t('investor.profile.linked.network')}
                        className="mt-[7px] grid grid-cols-2 gap-[7px]"
                    >
                        {(['mtn', 'airtel'] as const).map((network) => (
                            <button
                                key={network}
                                type="button"
                                role="radio"
                                aria-checked={form.data.network === network}
                                onClick={() => form.setData('network', network)}
                                className={choice(
                                    form.data.network === network,
                                )}
                            >
                                <MethodMark kind={network} size="sm" />
                                {t(`investor.profile.linked.${network}`)}
                            </button>
                        ))}
                    </div>
                </>
            ) : (
                <>
                    <label htmlFor="link-bank" className={label}>
                        {t('investor.profile.linked.bank')}
                    </label>
                    <select
                        id="link-bank"
                        value={form.data.bank}
                        onChange={(event) =>
                            form.setData('bank', event.target.value)
                        }
                        className={field}
                    >
                        {banks.map((bank) => (
                            <option key={bank.code} value={bank.code}>
                                {bank.name}
                            </option>
                        ))}
                    </select>
                </>
            )}
            <label htmlFor="link-number" className={label}>
                {t(
                    form.data.type === 'mobile'
                        ? 'investor.profile.linked.mobile_number'
                        : 'investor.profile.linked.account_number',
                )}
            </label>
            <input
                id="link-number"
                value={form.data.number}
                onChange={(event) =>
                    form.setData(
                        'number',
                        event.target.value.replace(/[^0-9]/gu, ''),
                    )
                }
                inputMode="numeric"
                aria-invalid={form.errors.number !== undefined || undefined}
                placeholder={t(
                    form.data.type === 'mobile'
                        ? 'investor.profile.linked.mobile_placeholder'
                        : 'investor.profile.linked.account_placeholder',
                )}
                className={field}
            />
            {form.errors.number !== undefined && (
                <p
                    role="alert"
                    className="mt-[9px] text-[11.5px] font-semibold text-rz-danger-text"
                >
                    {form.errors.number}
                </p>
            )}
            <button
                type="submit"
                disabled={form.processing || form.data.number === ''}
                aria-busy={form.processing || undefined}
                className="mt-[13px] h-12 w-full rounded-xl bg-rz-accent-fill text-[14.5px] font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
            >
                {t('investor.profile.linked.submit')}
            </button>
            <p className="mt-[9px] text-[10.5px] leading-[1.45] text-rz-secondary">
                {t('investor.profile.linked.footnote')}
            </p>
        </form>
    ) : (
        <button
            type="button"
            onClick={() => setOpen(true)}
            className="mt-3.5 h-[50px] w-full rounded-xl border-[1.5px] border-dashed border-[#dbe3f0] bg-rz-surface text-sm font-semibold text-rz-accent-app-text dark:border-rz-border"
        >
            {t('investor.profile.linked.add')}
        </button>
    );
}

/**
 * Statements (MVP-INVESTOR-SCR-10, design L3992–4012): the annual returns summary and each month's
 * statement, generated by the server from the verified history. A period still open is labelled so
 * (SCR-10-ST-02); with no history there is nothing to download yet (SCR-10-ST-01). No tax identifier
 * is asked for or shown.
 */
export function Statements({
    statements,
}: {
    statements: NonNullable<InvestorProfileProps['statements']>;
}) {
    const { t, locale } = useTranslation();
    const annual = statements.annual;

    return (
        <>
            <p className="mt-4 text-[13px] leading-[1.55] text-rz-secondary">
                {t('investor.profile.statements.intro')}
            </p>
            {annual !== null && (
                <div className="mt-4 flex items-center gap-[13px] rounded-2xl bg-[#1428a4] p-4">
                    <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white/[.14] text-[22px]">
                        <Icon name="receipt" tone="white" />
                    </span>
                    <div className="flex-1">
                        <p className="text-[11px] font-bold tracking-[.04em] text-white uppercase">
                            {t('investor.profile.statements.year', {
                                year: new Date(annual.period).getUTCFullYear(),
                            })}
                        </p>
                        <p className="mt-0.5 text-[15px] font-bold text-white">
                            {t('investor.profile.statements.annual')}
                        </p>
                        <p className="mt-px text-[11.5px] text-white">
                            {annual.complete
                                ? t('investor.profile.statements.annual_meta')
                                : t('investor.profile.statements.incomplete')}
                        </p>
                    </div>
                    <a
                        href={annual.link.url}
                        aria-label={t(
                            'investor.profile.statements.download_annual',
                        )}
                        className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-[#1e3aff]"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                            className="size-[19px]"
                        >
                            <path
                                d="M12 4v11m0 0l-4-4m4 4l4-4M5 20h14"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </svg>
                    </a>
                </div>
            )}
            <p className="mt-[18px] text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                {t('investor.profile.statements.monthly')}
            </p>
            <ul className="mt-2.5 flex flex-col gap-[9px]">
                {statements.monthly.map((file) => (
                    <li key={file.period}>
                        <a
                            href={file.link.url}
                            className="flex w-full items-center gap-3 rounded-2xl border border-rz-border bg-rz-surface px-3.5 py-[13px] text-left hover:border-rz-accent-fill"
                        >
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-[#f3f6fc] text-[17px] dark:bg-rz-surface-muted">
                                <Icon name="document" />
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block text-sm font-semibold text-rz-ink">
                                    {formatMonthYearLong(file.period, locale)}
                                </span>
                                <span className="mt-px block text-[11.5px] text-rz-secondary">
                                    {file.complete
                                        ? t('investor.profile.statements.pdf')
                                        : t(
                                              'investor.profile.statements.month_open',
                                              {
                                                  month: formatMonthYear(
                                                      file.period,
                                                      locale,
                                                  ),
                                              },
                                          )}
                                </span>
                            </span>
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden
                                className="size-[18px] shrink-0 text-rz-accent-app-text"
                            >
                                <path
                                    d="M12 4v11m0 0l-4-4m4 4l4-4M5 20h14"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />
                            </svg>
                        </a>
                    </li>
                ))}
                {statements.monthly.length === 0 && annual === null && (
                    <li className="rounded-2xl border border-dashed border-[#dbe3f0] px-4 py-5 text-center text-[12.5px] text-rz-secondary dark:border-rz-border">
                        {t('investor.profile.statements.none')}
                    </li>
                )}
            </ul>
            <p className="mt-[18px] rounded-2xl border border-[#eef2f9] bg-[#f5f8fc] p-3.5 text-[11.5px] leading-[1.6] text-rz-secondary dark:border-rz-divider dark:bg-rz-surface-sunken">
                {t('investor.profile.statements.disclaimer')}
            </p>
        </>
    );
}
