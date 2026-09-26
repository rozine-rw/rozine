import { Link } from '@inertiajs/react';
import { useTimeLeft } from '@/components/investor/deals/time-left';
import { LocalSheet, SheetClose } from '@/components/investor/local-sheet';
import { POSITIVE_TEXT } from '@/components/investor/tokens';
import { Icon } from '@/components/rozine/icon';
import type { IconName, IconTone } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatDateTime, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    C3InvestorWalletProps,
    CheckoutHold,
    DepositIntent,
    WalletEntry,
} from '@/types/investor';
import type { ProviderOutcomeState, Receipt } from '@/types/settlement';

/** A deposit intent's state as the Investor may see it: pending and unknown are "not yet confirmed". */
const INTENT_TONE: Record<ProviderOutcomeState, string> = {
    pending: 'text-[#a55418] dark:text-[#f0a060]',
    unknown: 'text-[#a55418] dark:text-[#f0a060]',
    succeeded: POSITIVE_TEXT,
    failed: 'text-rz-secondary',
};

const ENTRY_ICON: Record<
    WalletEntry['kind'],
    { icon: IconName; tint: string; tone: IconTone }
> = {
    deposit: { icon: 'money-out', tint: 'bg-rz-accent-soft', tone: 'blue' },
    hold: {
        icon: 'hourglass',
        tint: 'bg-[rgba(194,102,31,.10)]',
        tone: 'amber',
    },
    hold_release: { icon: 'undo', tint: 'bg-rz-accent-soft', tone: 'blue' },
    commitment: {
        icon: 'handshake',
        tint: 'bg-[rgba(29,158,117,.10)]',
        tone: 'green',
    },
    commitment_refund: {
        icon: 'undo',
        tint: 'bg-rz-accent-soft',
        tone: 'blue',
    },
};

function EntryTile({
    kind,
    large = false,
}: {
    kind: WalletEntry['kind'];
    large?: boolean;
}) {
    const spec = ENTRY_ICON[kind];

    return (
        <span
            className={cn(
                'flex shrink-0 items-center justify-center rounded-[10px]',
                large ? 'size-10 text-[19px]' : 'size-8 text-[15px]',
                spec.tint,
            )}
        >
            <Icon name={spec.icon} tone={spec.tone} />
        </span>
    );
}

function HoldRow({
    hold,
    serverTime,
}: {
    hold: CheckoutHold;
    serverTime: string;
}) {
    const { t } = useTranslation();
    const clock = useTimeLeft(hold.clock.expires_at, serverTime);

    return (
        <li className="border-b border-[#eef2f9] last:border-0 dark:border-rz-divider">
            <Link
                href={hold.link}
                className="flex items-center gap-[11px] px-[13px] py-3"
            >
                <EntryTile kind="hold" />
                <span className="min-w-0 flex-1">
                    <span className="block truncate text-[12.5px] font-semibold text-rz-ink">
                        {t('investor.wallet.c3.hold_line', {
                            name: hold.deal_name,
                            count: Number(hold.units),
                        })}
                    </span>
                    <span className="mt-px block text-[11px] text-rz-secondary tabular-nums">
                        {t('investor.wallet.c3.hold_expires', {
                            time: clock.label,
                        })}
                    </span>
                </span>
                <span className="text-[12.5px] font-semibold whitespace-nowrap text-rz-slate">
                    {formatRwf(hold.amount)}
                </span>
            </Link>
        </li>
    );
}

/** Live checkout reservations holding wallet cash, each counting down against the server clock. */
export function HoldsList({
    holds,
    serverTime,
}: {
    holds: CheckoutHold[];
    serverTime: string;
}) {
    const { t } = useTranslation();

    if (holds.length === 0) {
        return null;
    }

    return (
        <section aria-label={t('investor.wallet.c3.holds')} className="mt-4">
            <h2 className="text-sm font-semibold text-rz-ink">
                {t('investor.wallet.c3.holds')}
            </h2>
            <ul className="mt-2 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {holds.map((hold) => (
                    <HoldRow
                        key={hold.reservation_id}
                        hold={hold}
                        serverTime={serverTime}
                    />
                ))}
            </ul>
        </section>
    );
}

/**
 * Recorded deposit intents. `pending` and `unknown` read "not yet confirmed" and are never shown as
 * credited or failed; the Investor sees no provider reference (H15).
 */
export function DepositIntents({ deposits }: { deposits: DepositIntent[] }) {
    const { t, locale } = useTranslation();

    if (deposits.length === 0) {
        return null;
    }

    return (
        <section aria-label={t('investor.wallet.c3.deposits')} className="mt-4">
            <h2 className="text-sm font-semibold text-rz-ink">
                {t('investor.wallet.c3.deposits')}
            </h2>
            <ul className="mt-2 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {deposits.map((deposit) => (
                    <li
                        key={deposit.id}
                        className="border-b border-[#eef2f9] last:border-0 dark:border-rz-divider"
                    >
                        <Link
                            href={deposit.link}
                            preserveScroll
                            className="flex items-center gap-[11px] px-[13px] py-3"
                        >
                            <EntryTile kind="deposit" />
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-[12.5px] font-semibold text-rz-ink">
                                    {t('investor.wallet.c3.deposit_from', {
                                        method: deposit.method.label,
                                    })}
                                </span>
                                <span className="mt-px block text-[11px] text-rz-secondary">
                                    {formatDate(deposit.created_at, locale)} ·{' '}
                                    <span
                                        className={cn(
                                            'font-semibold',
                                            INTENT_TONE[deposit.state],
                                        )}
                                    >
                                        {t(
                                            `investor.wallet.c3.intent.${deposit.state}`,
                                        )}
                                    </span>
                                </span>
                            </span>
                            <span
                                className={cn(
                                    'text-[12.5px] font-semibold whitespace-nowrap',
                                    deposit.credit_receipt === null
                                        ? 'text-rz-secondary'
                                        : POSITIVE_TEXT,
                                )}
                            >
                                {formatRwf(deposit.amount)}
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}

function entryLabel(
    t: ReturnType<typeof useTranslation>['t'],
    entry: WalletEntry,
): string {
    if (entry.movement === 'external') {
        return t(`investor.wallet.c3.entry.deposit_${entry.direction}`, {
            counterparty: entry.counterparty,
        });
    }

    return t(`investor.wallet.c3.entry.${entry.kind}`, {
        name: entry.deal_name,
    });
}

/**
 * The wallet history (design L2981–3095), with external cash movements and internal transfers
 * (holds, commitments, refunds) as separate views (H5). Amounts are shown as a magnitude with a
 * direction, never as a sign the client computed. Newest first; older entries page on.
 */
export function WalletHistory({
    history,
}: {
    history: C3InvestorWalletProps['history'];
}) {
    const { t, locale } = useTranslation();

    return (
        <section aria-label={t('investor.wallet.tx.title')} className="mt-5">
            <h2 className="text-sm font-semibold text-rz-ink">
                {t('investor.wallet.tx.title')}
            </h2>
            <nav
                aria-label={t('investor.wallet.c3.movement')}
                className="mt-[11px] flex shrink-0 gap-[7px]"
            >
                {history.filters.map((filter) => (
                    <Link
                        key={filter.key}
                        href={filter.link}
                        preserveScroll
                        aria-current={filter.active ? 'true' : undefined}
                        className={cn(
                            'shrink-0 rounded-[10px] border px-[13px] py-[7px] text-xs font-semibold whitespace-nowrap',
                            filter.active
                                ? 'border-[#d6e4ff] bg-rz-accent-fill text-white dark:border-rz-investor'
                                : 'border-rz-border bg-rz-surface text-rz-slate',
                        )}
                    >
                        {t(`investor.wallet.c3.filter.${filter.key}`)}
                    </Link>
                ))}
            </nav>
            <ul className="mt-2.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {history.items.map((entry) => (
                    <li
                        key={entry.id}
                        className="border-b border-[#eef2f9] last:border-0 dark:border-rz-divider"
                    >
                        <Link
                            href={entry.link}
                            preserveScroll
                            className="flex w-full items-center gap-[11px] px-[13px] py-3 text-left"
                        >
                            <EntryTile kind={entry.kind} />
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-[12.5px] font-semibold text-rz-ink">
                                    {entryLabel(t, entry)}
                                </span>
                                <span className="mt-px block text-[11px] text-rz-secondary">
                                    {formatDate(entry.occurred_at, locale)}
                                    {entry.movement === 'internal' &&
                                        ` · ${t('investor.wallet.c3.transfer', {
                                            from: t(
                                                `investor.wallet.c3.bucket.${entry.from}`,
                                            ),
                                            to: t(
                                                `investor.wallet.c3.bucket.${entry.to}`,
                                            ),
                                        })}`}
                                </span>
                            </span>
                            <span className="text-[12.5px] font-semibold whitespace-nowrap text-rz-slate">
                                {formatRwf(entry.amount)}
                            </span>
                        </Link>
                    </li>
                ))}
                {history.items.length === 0 && (
                    <li className="px-4 py-[26px] text-center text-[12.5px] text-rz-secondary">
                        {t('investor.wallet.tx.empty')}
                    </li>
                )}
                {history.pagination.next !== null && (
                    <li>
                        <Link
                            href={history.pagination.next}
                            preserveScroll
                            className="block w-full bg-[#fafbfd] py-3 text-center text-[12.5px] font-semibold text-rz-accent-app-text dark:bg-rz-surface-sunken"
                        >
                            {t('investor.wallet.c3.older')}
                        </Link>
                    </li>
                )}
            </ul>
        </section>
    );
}

function ReceiptRows({ rows }: { rows: [string, string][] }) {
    return (
        <dl className="px-4 pt-1.5 pb-0.5">
            {rows.map(([label, value], index) => (
                <div
                    key={label}
                    className={cn(
                        'flex items-center justify-between gap-3 py-2',
                        index < rows.length - 1 &&
                            'border-b border-[#f1f4f9] dark:border-rz-divider',
                    )}
                >
                    <dt className="text-xs text-rz-secondary">{label}</dt>
                    <dd className="text-right text-xs font-semibold break-all text-rz-ink">
                        {value}
                    </dd>
                </div>
            ))}
        </dl>
    );
}

const isIntent = (
    receipt: NonNullable<C3InvestorWalletProps['receipt']>,
): receipt is DepositIntent => 'intent_receipt' in receipt;

/**
 * A receipt (design L4637–4703): the immutable record of what was recorded, with its reference and
 * policy version. A deposit intent shows its recorded intent receipt and, only after a verified
 * success, its credit receipt; until then it reads "not yet confirmed" and nothing is credited.
 */
export function ReceiptSheet({
    receipt,
    close,
}: {
    receipt: NonNullable<C3InvestorWalletProps['receipt']>;
    close: () => void;
}) {
    const { t, locale } = useTranslation();
    const recordRows = (record: Receipt): [string, string][] => [
        [
            t('investor.wallet.receipt.when'),
            formatDateTime(record.recorded_at, locale),
        ],
        [t('investor.wallet.receipt.reference'), record.reference],
        [t('investor.wallet.c3.receipt_policy'), record.policy_version],
    ];

    const intent = isIntent(receipt) ? receipt : null;
    const title = intent
        ? t('investor.wallet.c3.deposit_from', { method: intent.method.label })
        : entryLabel(t, receipt as WalletEntry);
    const rows = intent
        ? [
              [
                  t('investor.wallet.receipt.status'),
                  t(`investor.wallet.c3.intent.${intent.state}`),
              ] as [string, string],
              ...recordRows(intent.intent_receipt),
          ]
        : recordRows((receipt as WalletEntry & { receipt: Receipt }).receipt);

    return (
        <LocalSheet
            label={t('investor.wallet.receipt.label')}
            onClose={close}
            maxHeight="lg:max-h-[88%]"
            className="lg:rounded-2xl"
        >
            <div className="flex shrink-0 items-center gap-[11px] border-b border-[#eef2f9] px-4 pt-3.5 pb-3 dark:border-rz-divider">
                <EntryTile
                    kind={
                        intent === null
                            ? (receipt as WalletEntry).kind
                            : 'deposit'
                    }
                    large
                />
                <p className="min-w-0 flex-1 text-sm font-semibold text-rz-ink">
                    {title}
                </p>
                <SheetClose onClose={close} />
            </div>
            <div className="rz-scroll min-h-0 flex-1 overflow-y-auto">
                <div className="px-4 pt-3.5 pb-1 text-center">
                    <p className="text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase">
                        {t('investor.wallet.receipt.amount')}
                    </p>
                    <p className="mt-[3px] text-2xl font-bold text-rz-ink">
                        {formatRwf(receipt.amount)}
                    </p>
                </div>
                <ReceiptRows rows={rows} />
                {intent !== null &&
                    (intent.credit_receipt === null ? (
                        <p
                            role="status"
                            className="mx-4 mt-2 rounded-xl bg-rz-surface-sunken px-3 py-2.5 text-[11.5px] leading-[1.5] text-rz-secondary"
                        >
                            {t(
                                intent.state === 'failed'
                                    ? 'investor.wallet.c3.not_credited_failed'
                                    : 'investor.wallet.c3.not_credited',
                            )}
                        </p>
                    ) : (
                        <>
                            <p className="mt-2 px-4 text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase">
                                {t('investor.wallet.c3.credit_receipt')}
                            </p>
                            <ReceiptRows
                                rows={recordRows(intent.credit_receipt)}
                            />
                        </>
                    ))}
            </div>
            <div className="shrink-0 px-4 pt-2.5 pb-[calc(env(safe-area-inset-bottom)+14px)] lg:pb-3.5">
                <button
                    type="button"
                    onClick={close}
                    className="h-[46px] w-full rounded-xl bg-rz-accent-fill text-sm font-semibold text-white"
                >
                    {t('investor.wallet.receipt.done')}
                </button>
            </div>
        </LocalSheet>
    );
}
