import { Link } from '@inertiajs/react';
import { useState } from 'react';
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
    WalletWithdrawal,
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
    withdrawal: {
        icon: 'bank',
        tint: 'bg-[rgba(194,102,31,.10)]',
        tone: 'amber',
    },
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
 * credited or failed; the Investor sees no provider reference (H15). A credited deposit is left to
 * the history, where its credit already shows, so it is not listed twice.
 */
export function DepositIntents({
    deposits: recorded,
}: {
    deposits: DepositIntent[];
}) {
    const { t, locale } = useTranslation();
    const deposits = recorded.filter(
        (deposit) => deposit.state !== 'succeeded',
    );

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

/**
 * Recorded withdrawals: each left the available balance when it was recorded, and reads "on its
 * way" until the provider's verified outcome pays it out or returns it (H15: no provider reference).
 */
export function WithdrawalList({
    withdrawals,
}: {
    withdrawals: WalletWithdrawal[];
}) {
    const { t, locale } = useTranslation();

    if (withdrawals.length === 0) {
        return null;
    }

    return (
        <section
            aria-label={t('investor.wallet.c3.withdrawals')}
            className="mt-4"
        >
            <h2 className="text-sm font-semibold text-rz-ink">
                {t('investor.wallet.c3.withdrawals')}
            </h2>
            <ul className="mt-2 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {withdrawals.map((withdrawal) => (
                    <li
                        key={withdrawal.id}
                        className="border-b border-[#eef2f9] last:border-0 dark:border-rz-divider"
                    >
                        <Link
                            href={withdrawal.link}
                            preserveScroll
                            className="flex items-center gap-[11px] px-[13px] py-3"
                        >
                            <EntryTile kind="withdrawal" />
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-[12.5px] font-semibold text-rz-ink">
                                    {t('investor.wallet.c3.withdrawal_to', {
                                        method: withdrawal.method.label,
                                    })}
                                </span>
                                <span className="mt-px block text-[11px] text-rz-secondary">
                                    {formatDate(withdrawal.created_at, locale)}{' '}
                                    ·{' '}
                                    <span
                                        className={cn(
                                            'font-semibold',
                                            INTENT_TONE[withdrawal.state],
                                        )}
                                    >
                                        {t(
                                            `investor.wallet.c3.withdrawal.${withdrawal.state}`,
                                        )}
                                    </span>
                                </span>
                            </span>
                            <span className="text-[12.5px] font-semibold whitespace-nowrap text-rz-slate">
                                {formatRwf(withdrawal.amount)}
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
        return t(`investor.wallet.c3.entry.${entry.kind}_${entry.direction}`, {
            counterparty: entry.counterparty,
        });
    }

    return t(`investor.wallet.c3.entry.${entry.kind}`, {
        name: entry.deal_name,
    });
}

/**
 * The statement download (design wallet history "Export"): a PDF or a spreadsheet of every
 * recorded wallet movement, generated by the server from the ledger.
 */
function StatementExport({
    exports,
}: {
    exports: NonNullable<C3InvestorWalletProps['exports']>;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const options = [
        {
            key: 'pdf',
            link: exports.pdf,
            label: t('investor.wallet.tx.pdf'),
            tint: 'bg-[rgba(229,72,77,.10)] text-[#b3383c]',
        },
        {
            key: 'csv',
            link: exports.csv,
            label: t('investor.wallet.tx.csv'),
            tint: 'bg-[rgba(29,158,117,.10)] text-[#157357]',
        },
    ];

    return (
        <div className="relative">
            <button
                type="button"
                title={t('investor.wallet.tx.export')}
                aria-label={t('investor.wallet.tx.export')}
                aria-expanded={open}
                onClick={() => setOpen(!open)}
                className="flex size-[30px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-rz-slate"
            >
                <svg
                    aria-hidden
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                >
                    <path
                        d="M12 3v12M8 11l4 4 4-4M5 20h14"
                        stroke="currentColor"
                        strokeWidth="1.9"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </button>
            {open && (
                <div className="absolute top-[37px] right-0 z-40 w-[168px] rounded-xl border border-rz-border bg-rz-surface p-[5px] shadow-[0_20px_44px_-16px_rgba(20,45,95,.34)]">
                    {options.map((option) => (
                        <a
                            key={option.key}
                            href={option.link.url}
                            download
                            onClick={() => setOpen(false)}
                            className="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-[9px] text-left"
                        >
                            <span
                                aria-hidden
                                className={cn(
                                    'flex size-[26px] items-center justify-center rounded-[10px] text-[10px] font-bold uppercase',
                                    option.tint,
                                )}
                            >
                                {option.key}
                            </span>
                            <span className="text-[12.5px] font-semibold text-rz-ink">
                                {option.label}
                            </span>
                        </a>
                    ))}
                </div>
            )}
        </div>
    );
}

/**
 * The wallet history (design L2981–3095), with external cash movements and internal transfers
 * (holds, commitments, refunds) as separate views (H5). Amounts are shown as a magnitude with a
 * direction, never as a sign the client computed. Newest first; older entries page on.
 */
export function WalletHistory({
    history,
    exports = null,
}: {
    history: C3InvestorWalletProps['history'];
    exports?: C3InvestorWalletProps['exports'];
}) {
    const { t, locale } = useTranslation();

    return (
        <section aria-label={t('investor.wallet.tx.title')} className="mt-5">
            <div className="flex items-center justify-between gap-2">
                <h2 className="text-sm font-semibold text-rz-ink">
                    {t('investor.wallet.tx.title')}
                </h2>
                {exports !== null && <StatementExport exports={exports} />}
            </div>
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

const isWithdrawal = (
    receipt: NonNullable<C3InvestorWalletProps['receipt']>,
): receipt is WalletWithdrawal => 'request_receipt' in receipt;

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
    const withdrawal = isWithdrawal(receipt) ? receipt : null;
    const entry =
        intent === null && withdrawal === null
            ? (receipt as WalletEntry & { receipt: Receipt })
            : null;
    const title =
        intent !== null
            ? t('investor.wallet.c3.deposit_from', {
                  method: intent.method.label,
              })
            : withdrawal !== null
              ? t('investor.wallet.c3.withdrawal_to', {
                    method: withdrawal.method.label,
                })
              : entryLabel(t, entry as WalletEntry);
    const rows: [string, string][] =
        intent !== null
            ? [
                  [
                      t('investor.wallet.receipt.status'),
                      t(`investor.wallet.c3.intent.${intent.state}`),
                  ],
                  ...recordRows(intent.intent_receipt),
              ]
            : withdrawal !== null
              ? [
                    [
                        t('investor.wallet.receipt.status'),
                        t(`investor.wallet.c3.withdrawal.${withdrawal.state}`),
                    ],
                    [
                        t('investor.wallet.c3.provider_charge'),
                        formatRwf(withdrawal.charge),
                    ],
                    [
                        t('investor.wallet.result.withdraw'),
                        formatRwf(withdrawal.received),
                    ],
                    ...recordRows(withdrawal.request_receipt),
                ]
              : recordRows(
                    (entry as WalletEntry & { receipt: Receipt }).receipt,
                );
    const tile: WalletEntry['kind'] =
        intent !== null
            ? 'deposit'
            : withdrawal !== null
              ? 'withdrawal'
              : (entry as WalletEntry).kind;

    return (
        <LocalSheet
            label={t('investor.wallet.receipt.label')}
            onClose={close}
            maxHeight="lg:max-h-[88%]"
            className="lg:rounded-2xl"
        >
            <div className="flex shrink-0 items-center gap-[11px] border-b border-[#eef2f9] px-4 pt-3.5 pb-3 dark:border-rz-divider">
                <EntryTile kind={tile} large />
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
                {withdrawal !== null &&
                    (withdrawal.settled_receipt === null ? (
                        <p
                            role="status"
                            className="mx-4 mt-2 rounded-xl bg-rz-surface-sunken px-3 py-2.5 text-[11.5px] leading-[1.5] text-rz-secondary"
                        >
                            {t('investor.wallet.c3.withdrawal_waiting')}
                        </p>
                    ) : (
                        <>
                            <p className="mt-2 px-4 text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase">
                                {withdrawal.state === 'succeeded'
                                    ? t(
                                          'investor.wallet.c3.settled_receipt.WITHDRAWAL_PAID',
                                      )
                                    : t(
                                          'investor.wallet.c3.settled_receipt.WITHDRAWAL_RETURNED',
                                      )}
                            </p>
                            <ReceiptRows
                                rows={recordRows(withdrawal.settled_receipt)}
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
