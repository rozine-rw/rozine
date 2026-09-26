import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { C3Notice } from '@/components/rozine/c3-notice';
import { Icon } from '@/components/rozine/icon';
import { LogoMark } from '@/components/rozine/logo';
import { useC3Command } from '@/hooks/use-c3-command';
import { useTranslation } from '@/hooks/use-translation';
import { formatCompactBare } from '@/lib/investor/format';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    C3InvestorWalletProps,
    PayoutAccountKind,
} from '@/types/investor';

/** Mobile-money and bank marks, drawn rather than loaded (the design's logo files are missing). */
const METHOD_TILE: Record<PayoutAccountKind, string> = {
    mtn: 'bg-[#ffcb05] text-[#0c1830]',
    airtel: 'bg-[#e4002b] text-white',
    bank: 'bg-[#0c1830] text-white dark:bg-rz-surface-muted',
};

export function MethodMark({
    kind,
    size = 'md',
}: {
    kind: PayoutAccountKind;
    size?: 'sm' | 'md';
}) {
    return (
        <span
            className={cn(
                'flex shrink-0 items-center justify-center rounded-[9px]',
                size === 'md' ? 'size-[30px] text-base' : 'size-[26px] text-sm',
                METHOD_TILE[kind],
            )}
        >
            <Icon
                name={kind === 'bank' ? 'bank' : 'phone'}
                tone={kind === 'mtn' ? 'blue' : 'white'}
            />
        </span>
    );
}

const BUCKETS = ['available', 'held', 'committed'] as const;

/**
 * The blue balance card (design L2846–2860), in C3's ledger terms (H5): the total, then its
 * breakdown, where only "available" is spendable. Pending deposits are listed beneath and are not
 * part of the total: nothing is credited before a verified success.
 */
export function BalanceCard({
    wallet,
}: {
    wallet: C3InvestorWalletProps['wallet'];
}) {
    const { t, locale } = useTranslation();

    return (
        <section
            aria-label={t('investor.wallet.c3.total')}
            className="relative mt-3.5 shrink-0 overflow-hidden rounded-[20px] bg-[linear-gradient(135deg,#2440ff_0%,#1e3aff_58%,#10228a_100%)] p-5"
        >
            <span className="pointer-events-none absolute -top-[42px] -right-8 size-[150px] rounded-full bg-[radial-gradient(circle,rgba(255,255,255,.16),rgba(255,255,255,0)_70%)]" />
            <div className="relative flex items-center justify-between">
                <span className="text-[10.5px] font-bold tracking-[.08em] text-white uppercase">
                    {t('investor.wallet.c3.total')}
                </span>
                <span className="inline-flex items-center gap-[5px] rounded-[10px] bg-white/[.14] px-[9px] py-1 text-[10px] font-semibold text-white">
                    <span
                        className={cn(
                            'size-1.5 rounded-full',
                            wallet.status === 'active'
                                ? 'bg-[#6ae8bf]'
                                : 'bg-[#ffc44f]',
                        )}
                    />
                    {t(`investor.wallet.status.${wallet.status}`)}
                </span>
            </div>
            <p className="relative mt-2.5 text-[30px] font-bold tracking-[-.6px] text-white">
                {formatRwf(wallet.total)}
            </p>
            <dl className="relative mt-3 grid grid-cols-3 gap-2 border-t border-white/[.16] pt-3">
                {BUCKETS.map((bucket) => (
                    <div key={bucket} className="min-w-0">
                        <dt className="text-[10px] font-semibold text-white/80">
                            {t(`investor.wallet.c3.bucket.${bucket}`)}
                        </dt>
                        <dd className="mt-0.5 truncate text-[13px] font-bold text-white">
                            {formatRwf(wallet.breakdown[bucket])}
                        </dd>
                    </div>
                ))}
            </dl>
            <p className="relative mt-2 text-[11px] text-white/85">
                {t('investor.wallet.c3.spendable')}
            </p>
            {wallet.restriction !== null && (
                <p
                    role="status"
                    className="relative mt-2 rounded-[10px] bg-white/[.14] px-2.5 py-1.5 text-[11px] text-white"
                >
                    {t('investor.wallet.c3.restricted', {
                        date: formatDate(wallet.restriction.since, locale),
                    })}
                </p>
            )}
            <div className="relative mt-3 flex items-center justify-between border-t border-white/[.16] pt-[11px]">
                <span className="text-[11.5px] text-white">
                    {wallet.pending_deposits.amount === '0'
                        ? t('investor.wallet.c3.no_pending')
                        : t('investor.wallet.c3.pending_deposits', {
                              amount: formatRwf(wallet.pending_deposits),
                          })}
                </span>
                <span className="inline-flex items-center gap-1.5 text-[11px] font-semibold text-white/90">
                    <LogoMark className="size-[15px] text-white/90" />
                    {t('investor.wallet.rozine_wallet')}
                </span>
            </div>
        </section>
    );
}

/** How long an entered amount must settle before the server prices it. */
const FUNDING_DEBOUNCE_MS = 300;

type DepositPanelProps = Pick<
    C3InvestorWalletProps,
    | 'funding'
    | 'actions'
    | 'allowed_actions'
    | 'identity_context_revision'
    | 'preview_outcome'
> & {
    operation: C3InvestorWalletProps['links']['operation'];
    close: C3InvestorWalletProps['links']['close'] | null;
    wide: boolean;
};

/**
 * The deposit panel (design L2880–2923; withdrawal stays hidden until it has its own contract, H6).
 * The fee and what is credited come from the server's quote under a versioned deposit policy; a
 * synthetic policy says so. With no policy the panel explains that deposits aren't offered. Deposit
 * records an intent only: nothing is credited until the provider's success is verified.
 */
export function DepositPanel({
    funding,
    actions,
    allowed_actions: allowed,
    identity_context_revision: identityRevision,
    preview_outcome: preview,
    operation,
    close,
    wide,
}: DepositPanelProps) {
    const { t } = useTranslation();
    const quote = funding.quote;
    const [amount, setAmountState] = useState(quote?.amount.amount ?? '');
    const [method, setMethod] = useState(funding.methods[0]?.id ?? '');
    const [pricing, setPricing] = useState(false);
    const timer = useRef<number | undefined>(undefined);
    const command = useC3Command<'wallet.deposit'>({
        actions: { 'wallet.deposit': actions.deposit },
        lookup: operation,
        lookupQuery: { identity_context_revision: identityRevision },
        allowed,
        preview,
        only: ['wallet', 'funding', 'deposits', 'history', 'allowed_actions'],
    });

    useEffect(() => () => window.clearTimeout(timer.current), []);

    const header = !wide && (
        <div className="flex items-center justify-between">
            <h2 className="text-[13.5px] font-semibold text-rz-ink">
                {t('investor.wallet.panel.deposit')}
            </h2>
            {close !== null && (
                <Link
                    href={close}
                    preserveScroll
                    aria-label={t('app.sheet.close')}
                    className="flex size-[26px] items-center justify-center rounded-[10px] border border-rz-border bg-[#f3f6fc] text-sm text-rz-secondary dark:bg-rz-surface-muted"
                >
                    <span aria-hidden>✕</span>
                </Link>
            )}
        </div>
    );
    const frame = cn(
        'origin-top animate-[rz-rolldown_.34s_cubic-bezier(.34,1.12,.5,1)_both] rounded-2xl border-[1.5px] border-[#d6e4ff] bg-rz-surface shadow-[0_16px_38px_-18px_rgba(20,45,95,.3)] dark:border-rz-investor',
        wide ? 'mt-[11px] p-3.5' : 'mt-2.5 p-[15px]',
    );

    if (funding.policy === null) {
        return (
            <section
                aria-label={t('investor.wallet.panel.deposit')}
                className={frame}
            >
                {header}
                <p
                    role="status"
                    className="mt-2 flex items-start gap-2.5 text-xs leading-[1.55] text-rz-secondary"
                >
                    <Icon name="info" />
                    <span>{t('investor.wallet.c3.no_policy')}</span>
                </p>
            </section>
        );
    }

    const policy = funding.policy;

    const setAmount = (raw: string) => {
        const digits = raw.replace(/[^0-9]/gu, '');

        setAmountState(digits);
        window.clearTimeout(timer.current);
        timer.current = window.setTimeout(() => {
            router.reload({
                only: ['funding'],
                data: { kind: 'deposit', amount: digits },
                onStart: () => setPricing(true),
                onFinish: () => setPricing(false),
            });
        }, FUNDING_DEBOUNCE_MS);
    };

    const offered = allowed.includes('wallet.deposit');
    const refusal = quote?.refusal ?? null;
    const ready =
        offered &&
        quote !== null &&
        refusal === null &&
        amount !== '' &&
        method !== '' &&
        !pricing &&
        !command.busy &&
        !command.unresolved;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        command.send('wallet.deposit', {
            identity_context_revision: identityRevision,
            amount: { currency: 'RWF', amount },
            method_id: method,
        });
    };

    const label =
        'text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase';

    return (
        <form
            onSubmit={submit}
            aria-label={t('investor.wallet.panel.deposit')}
            className={frame}
        >
            {header}
            <C3Notice command={command} className="mt-2" />
            <label
                htmlFor="amount-deposit"
                className={cn(label, 'block', wide ? 'mt-0.5' : 'mt-3')}
            >
                {t('investor.wallet.amount')}
            </label>
            <div className="mt-1.5 flex items-center rounded-xl border border-rz-accent-fill bg-[#f8fafc] px-[13px] py-[11px] dark:bg-rz-surface-sunken">
                <span className="text-[13px] font-semibold text-rz-secondary">
                    {t('investor.money.rwf')}
                </span>
                <input
                    id="amount-deposit"
                    value={
                        amount === ''
                            ? ''
                            : Number(amount).toLocaleString('en-US')
                    }
                    onChange={(event) => setAmount(event.target.value)}
                    inputMode="numeric"
                    placeholder="0"
                    className="min-w-0 flex-1 border-0 bg-transparent text-right text-xl font-semibold text-rz-ink outline-none placeholder:text-rz-ink"
                />
            </div>
            <div className="mt-2 flex gap-1.5">
                {funding.picks.map((pick) => (
                    <button
                        key={pick.amount}
                        type="button"
                        onClick={() => setAmount(pick.amount)}
                        className="flex-1 rounded-[10px] border border-rz-border bg-[#f3f6fc] py-2 text-[11px] font-semibold text-rz-slate dark:bg-rz-surface-muted"
                    >
                        {formatCompactBare(pick)}
                    </button>
                ))}
            </div>
            <p className={cn(label, 'mt-3.5')}>{t('investor.wallet.method')}</p>
            <div
                role="radiogroup"
                aria-label={t('investor.wallet.method')}
                className="mt-1.5 flex flex-col gap-2"
            >
                {funding.methods.map((option) => {
                    const on = option.id === method;

                    return (
                        <button
                            key={option.id}
                            type="button"
                            role="radio"
                            aria-checked={on}
                            onClick={() => setMethod(option.id)}
                            className={cn(
                                'flex items-center gap-[11px] rounded-xl border-[1.5px] px-[11px] py-[9px] text-left',
                                on
                                    ? 'border-[#d6e4ff] bg-rz-page dark:border-rz-investor'
                                    : 'border-rz-border bg-rz-surface',
                            )}
                        >
                            <MethodMark kind={option.kind} />
                            <span className="min-w-0 flex-1">
                                <span className="block text-[13px] font-semibold text-rz-ink">
                                    {option.label}
                                </span>
                                <span className="block text-[11px] text-rz-secondary">
                                    {option.masked}
                                </span>
                            </span>
                            <span
                                className={cn(
                                    'size-[17px] shrink-0 rounded-full border-2 border-[#d6e4ff] dark:border-rz-border',
                                    on && 'bg-rz-accent-fill',
                                )}
                            />
                        </button>
                    );
                })}
            </div>
            <dl
                aria-busy={pricing || undefined}
                className={cn(
                    'mt-3 rounded-xl border border-[#eef2f9] bg-[#f8fafc] px-[13px] transition-opacity dark:border-rz-divider dark:bg-rz-surface-sunken',
                    pricing && 'opacity-60',
                )}
            >
                <div className="flex justify-between gap-2.5 border-b border-[#eef2f9] py-[9px] dark:border-rz-divider">
                    <dt className="text-xs text-rz-secondary">
                        {t('investor.wallet.fee')}
                    </dt>
                    <dd className="text-xs font-semibold text-rz-ink">
                        {quote === null ? '—' : formatRwf(quote.fee)}
                    </dd>
                </div>
                <div className="flex justify-between gap-2.5 py-[9px]">
                    <dt className="text-xs text-rz-secondary">
                        {t('investor.wallet.c3.credited_on_success')}
                    </dt>
                    <dd className="text-xs font-bold text-[#17795a] dark:text-[#3fcda0]">
                        {quote === null ? '—' : formatRwf(quote.credited)}
                    </dd>
                </div>
            </dl>
            <p className="mt-2 text-[10.5px] leading-[1.45] text-rz-secondary">
                {t(
                    policy.synthetic
                        ? 'investor.wallet.c3.policy_synthetic'
                        : 'investor.wallet.c3.policy',
                    { version: policy.version },
                )}
                {policy.minimum !== null &&
                    ` ${t('investor.wallet.c3.policy_minimum', { amount: formatRwf(policy.minimum) })}`}
                {policy.maximum !== null &&
                    ` ${t('investor.wallet.c3.policy_maximum', { amount: formatRwf(policy.maximum) })}`}
            </p>
            {refusal !== null && (
                <div
                    role="alert"
                    className="mt-[11px] flex items-center gap-2 rounded-[10px] border border-[#fdeaea] bg-[rgba(229,72,77,.08)] px-3 py-2.5 text-xs font-semibold text-rz-danger-text dark:border-[rgba(255,107,111,.25)]"
                >
                    <Icon name="warning" tone="red" />
                    {t(`settlement.refusal.${refusal}`)}
                </div>
            )}
            {command.errors.amount !== undefined && (
                <p
                    role="alert"
                    className="mt-2 text-xs font-semibold text-rz-danger-text"
                >
                    {command.errors.amount}
                </p>
            )}
            {offered ? (
                <button
                    type="submit"
                    disabled={!ready}
                    aria-busy={command.busy || undefined}
                    className="mt-[13px] h-[50px] w-full shrink-0 rounded-xl bg-rz-accent-fill text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {command.busy
                        ? t('investor.wallet.processing')
                        : t('investor.wallet.confirm.deposit')}
                </button>
            ) : (
                <p
                    role="status"
                    className="mt-3 text-center text-[11.5px] text-rz-secondary"
                >
                    {t('investor.wallet.c3.deposit_unavailable')}
                </p>
            )}
            <p className="mt-2 text-center text-[10.5px] leading-[1.4] text-rz-secondary">
                {t('investor.wallet.c3.intent_only')}
            </p>
        </form>
    );
}
