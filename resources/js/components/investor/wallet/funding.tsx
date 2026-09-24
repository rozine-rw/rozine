import { Link, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { Icon } from '@/components/rozine/icon';
import { LogoMark } from '@/components/rozine/logo';
import { useTranslation } from '@/hooks/use-translation';
import { formatCompactBare } from '@/lib/investor/format';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    FundingKind,
    InvestorWalletProps,
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

/** The blue available-balance card (design L2846–2860). */
export function BalanceCard({
    wallet,
}: {
    wallet: InvestorWalletProps['wallet'];
}) {
    const { t } = useTranslation();

    return (
        <section
            aria-label={t('investor.wallet.available')}
            className="relative mt-3.5 shrink-0 overflow-hidden rounded-[20px] bg-[linear-gradient(135deg,#2440ff_0%,#1e3aff_58%,#10228a_100%)] p-5"
        >
            <span className="pointer-events-none absolute -top-[42px] -right-8 size-[150px] rounded-full bg-[radial-gradient(circle,rgba(255,255,255,.16),rgba(255,255,255,0)_70%)]" />
            <span className="pointer-events-none absolute -bottom-[54px] -left-6 size-[132px] rounded-full bg-[radial-gradient(circle,rgba(255,255,255,.09),rgba(255,255,255,0)_70%)]" />
            <div className="relative flex items-center justify-between">
                <span className="text-[10.5px] font-bold tracking-[.08em] text-white uppercase">
                    {t('investor.wallet.available')}
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
                {formatRwf(wallet.available)}
            </p>
            {wallet.pending_withdrawal !== null && (
                <p className="relative mt-1 text-[11.5px] text-white/85">
                    {t('investor.wallet.pending_withdrawal', {
                        amount: formatRwf(wallet.pending_withdrawal),
                    })}
                </p>
            )}
            <div className="relative mt-4 flex items-center justify-between border-t border-white/[.16] pt-[13px]">
                <span className="text-[11.5px] text-white">
                    {t('investor.wallet.instant')}
                </span>
                <span className="inline-flex items-center gap-1.5 text-[11px] font-semibold text-white/90">
                    <LogoMark className="size-[15px] text-white/90" />
                    {t('investor.wallet.rozine_wallet')}
                </span>
            </div>
        </section>
    );
}

const KIND_GLYPH: Record<FundingKind, string> = {
    deposit: 'M12 4v10M8 11l4 4 4-4M5 20h14',
    withdraw: 'M12 20V10M8 13l4-4 4 4M5 4h14',
};

/** Deposit / Withdraw switch (design L2862–2878); the open one wears its filled pill. */
export function FundingSwitch({
    kind,
    links,
}: {
    kind: FundingKind | null;
    links: InvestorWalletProps['links'];
}) {
    const { t } = useTranslation();

    return (
        <div className="mt-3.5 grid shrink-0 grid-cols-2 gap-[11px]">
            {(['deposit', 'withdraw'] as const).map((key) => {
                const on = key === kind;

                return (
                    <Link
                        key={key}
                        href={links[key]}
                        preserveScroll
                        aria-current={on ? 'true' : undefined}
                        className={cn(
                            'relative flex items-center justify-center gap-[7px] rounded-2xl border py-[13px] text-[13px] font-semibold shadow-[0_5px_14px_-8px_rgba(20,45,95,.3)]',
                            on
                                ? cn(
                                      'animate-[rz-pillin_.2s_ease] border-transparent text-white',
                                      key === 'deposit'
                                          ? 'bg-rz-accent-fill'
                                          : 'bg-[#7d420f]',
                                  )
                                : 'border-rz-border bg-rz-surface text-rz-ink',
                        )}
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                            className="size-[15px]"
                        >
                            <path
                                d={KIND_GLYPH[key]}
                                stroke={
                                    on
                                        ? '#fff'
                                        : key === 'deposit'
                                          ? '#1e3aff'
                                          : '#b05c1a'
                                }
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </svg>
                        {t(`investor.wallet.${key}`)}
                    </Link>
                );
            })}
        </div>
    );
}

type FundingPanelProps = {
    kind: FundingKind;
    funding: InvestorWalletProps['funding'];
    actions: InvestorWalletProps['actions'];
    close: InvestorWalletProps['links']['close'] | null;
    wide: boolean;
};

/** How long an entered amount must settle before the server prices it. */
const FUNDING_DEBOUNCE_MS = 300;

/**
 * The roll-down deposit / withdrawal panel (design L2880–2923): amount, quick amounts, the linked
 * method, then the server's fee and result for that amount. No withdrawal fee is assumed (crosswalk
 * SCR-08: the design's 0.5% is rejected); whatever the server charges is shown before confirm.
 */
export function FundingPanel({
    kind,
    funding,
    actions,
    close,
    wide,
}: FundingPanelProps) {
    const { t } = useTranslation();
    const quote =
        funding.quote !== null && funding.quote.kind === kind
            ? funding.quote
            : null;
    const form = useForm({
        amount: quote?.amount.amount ?? '',
        method: funding.methods[0]?.id ?? '',
    });
    const [pricing, setPricing] = useState(false);
    const timer = useRef<number | undefined>(undefined);

    useEffect(() => () => window.clearTimeout(timer.current), []);

    const setAmount = (raw: string) => {
        const digits = raw.replace(/[^0-9]/gu, '');

        form.setData('amount', digits);
        window.clearTimeout(timer.current);
        timer.current = window.setTimeout(() => {
            router.reload({
                only: ['funding'],
                data: { kind, amount: digits },
                onStart: () => setPricing(true),
                onFinish: () => setPricing(false),
            });
        }, FUNDING_DEBOUNCE_MS);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(actions[kind].url, { preserveScroll: true });
    };

    const ready =
        quote !== null &&
        quote.refusal === null &&
        form.data.amount !== '' &&
        form.data.method !== '' &&
        !pricing;
    const refusal = quote?.refusal ?? null;
    const label =
        'text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase';

    return (
        <form
            onSubmit={submit}
            aria-label={t(`investor.wallet.panel.${kind}`)}
            className={cn(
                'origin-top animate-[rz-rolldown_.34s_cubic-bezier(.34,1.12,.5,1)_both] rounded-2xl border-[1.5px] bg-rz-surface shadow-[0_16px_38px_-18px_rgba(20,45,95,.3)]',
                kind === 'withdraw'
                    ? 'border-rz-ink'
                    : 'border-[#d6e4ff] dark:border-rz-investor',
                wide
                    ? 'mt-[11px] flex min-h-0 flex-1 flex-col overflow-hidden p-3.5'
                    : 'mt-2.5 p-[15px]',
            )}
        >
            {!wide && (
                <div className="flex items-center justify-between">
                    <h2 className="text-[13.5px] font-semibold text-rz-ink">
                        {t(`investor.wallet.panel.${kind}`)}
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
            )}
            <div
                className={cn(
                    wide && 'rz-scroll min-h-0 flex-1 overflow-y-auto pr-0.5',
                )}
            >
                <label
                    htmlFor={`amount-${kind}`}
                    className={cn(label, 'block', wide ? 'mt-0.5' : 'mt-3')}
                >
                    {t('investor.wallet.amount')}
                </label>
                <div
                    className={cn(
                        'mt-1.5 flex items-center rounded-xl border border-rz-accent-fill bg-[#f8fafc] dark:bg-rz-surface-sunken',
                        wide ? 'px-3 py-2' : 'px-[13px] py-[11px]',
                    )}
                >
                    <span className="text-[13px] font-semibold text-rz-secondary">
                        {t('investor.money.rwf')}
                    </span>
                    <input
                        id={`amount-${kind}`}
                        value={
                            form.data.amount === ''
                                ? ''
                                : Number(form.data.amount).toLocaleString(
                                      'en-US',
                                  )
                        }
                        onChange={(event) => setAmount(event.target.value)}
                        inputMode="numeric"
                        placeholder="0"
                        className={cn(
                            'min-w-0 flex-1 border-0 bg-transparent text-right font-semibold text-rz-ink outline-none placeholder:text-rz-ink',
                            wide ? 'text-lg' : 'text-xl',
                        )}
                    />
                </div>
                <div className={cn('flex gap-1.5', wide ? 'mt-[7px]' : 'mt-2')}>
                    {funding.picks.map((pick) => (
                        <button
                            key={pick.amount}
                            type="button"
                            onClick={() => setAmount(pick.amount)}
                            className={cn(
                                'flex-1 rounded-[10px] border border-rz-border bg-[#f3f6fc] text-[11px] font-semibold text-rz-slate dark:bg-rz-surface-muted',
                                wide ? 'py-1.5' : 'py-2',
                            )}
                        >
                            {formatCompactBare(pick)}
                        </button>
                    ))}
                </div>
                <p className={cn(label, wide ? 'mt-2.5' : 'mt-3.5')}>
                    {t(
                        kind === 'deposit'
                            ? 'investor.wallet.method'
                            : 'investor.wallet.method_to',
                    )}
                </p>
                <div
                    role="radiogroup"
                    aria-label={t(
                        kind === 'deposit'
                            ? 'investor.wallet.method'
                            : 'investor.wallet.method_to',
                    )}
                    className={cn(
                        'mt-1.5 flex',
                        wide ? 'gap-1.5' : 'flex-col gap-2',
                    )}
                >
                    {funding.methods.map((method) => {
                        const on = method.id === form.data.method;

                        return (
                            <button
                                key={method.id}
                                type="button"
                                role="radio"
                                aria-checked={on}
                                onClick={() =>
                                    form.setData('method', method.id)
                                }
                                className={cn(
                                    'border-[1.5px]',
                                    on
                                        ? 'border-[#d6e4ff] bg-rz-page dark:border-rz-investor'
                                        : 'border-rz-border bg-rz-surface',
                                    wide
                                        ? 'flex min-w-0 flex-1 flex-col items-center gap-[5px] rounded-[10px] px-1 py-2'
                                        : 'flex items-center gap-[11px] rounded-xl px-[11px] py-[9px] text-left',
                                )}
                            >
                                <MethodMark kind={method.kind} />
                                {wide ? (
                                    <span
                                        className={cn(
                                            'max-w-full truncate text-[10.5px] font-semibold',
                                            on
                                                ? 'text-rz-accent-app-text'
                                                : 'text-rz-slate',
                                        )}
                                    >
                                        {method.label}
                                    </span>
                                ) : (
                                    <>
                                        <span className="min-w-0 flex-1">
                                            <span className="block text-[13px] font-semibold text-rz-ink">
                                                {method.label}
                                            </span>
                                            <span className="block text-[11px] text-rz-secondary">
                                                {method.masked}
                                            </span>
                                        </span>
                                        <span
                                            className={cn(
                                                'size-[17px] shrink-0 rounded-full border-2 border-[#d6e4ff] dark:border-rz-border',
                                                on && 'bg-rz-accent-fill',
                                            )}
                                        />
                                    </>
                                )}
                            </button>
                        );
                    })}
                </div>
                <dl
                    aria-busy={pricing || undefined}
                    className={cn(
                        'rounded-xl border border-[#eef2f9] bg-[#f8fafc] px-[13px] transition-opacity dark:border-rz-divider dark:bg-rz-surface-sunken',
                        wide ? 'mt-2.5' : 'mt-3',
                        pricing && 'opacity-60',
                    )}
                >
                    <div
                        className={cn(
                            'flex justify-between gap-2.5 border-b border-[#eef2f9] dark:border-rz-divider',
                            wide ? 'py-1.5' : 'py-[9px]',
                        )}
                    >
                        <dt className="text-xs text-rz-secondary">
                            {t('investor.wallet.fee')}
                        </dt>
                        <dd className="text-xs font-semibold text-rz-ink">
                            {quote === null ? '—' : formatRwf(quote.fee)}
                        </dd>
                    </div>
                    <div
                        className={cn(
                            'flex justify-between gap-2.5',
                            wide ? 'py-1.5' : 'py-[9px]',
                        )}
                    >
                        <dt className="text-xs text-rz-secondary">
                            {t(`investor.wallet.result.${kind}`)}
                        </dt>
                        <dd className="text-xs font-bold text-[#17795a] dark:text-[#3fcda0]">
                            {quote === null ? '—' : formatRwf(quote.result)}
                        </dd>
                    </div>
                </dl>
                {(refusal !== null || form.errors.amount !== undefined) && (
                    <div
                        role="alert"
                        className="mt-[11px] flex items-center gap-2 rounded-[10px] border border-[#fdeaea] bg-[rgba(229,72,77,.08)] px-3 py-2.5 text-xs font-semibold text-rz-danger-text dark:border-[rgba(255,107,111,.25)]"
                    >
                        <Icon name="warning" tone="red" />
                        {refusal !== null
                            ? t(`investor.wallet.refusal.${refusal}`)
                            : form.errors.amount}
                    </div>
                )}
            </div>
            <button
                type="submit"
                disabled={!ready || form.processing}
                aria-busy={form.processing || undefined}
                className={cn(
                    'w-full shrink-0 rounded-xl text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60',
                    kind === 'withdraw'
                        ? 'bg-[#0c1830] dark:bg-rz-slate dark:text-rz-page'
                        : 'bg-rz-accent-fill',
                    wide ? 'mt-2.5 h-11' : 'mt-[13px] h-[50px]',
                )}
            >
                {form.processing
                    ? t('investor.wallet.processing')
                    : t(`investor.wallet.confirm.${kind}`)}
            </button>
        </form>
    );
}
