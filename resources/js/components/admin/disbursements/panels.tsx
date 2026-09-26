import type { ReactNode } from 'react';
import { formatTimestamp } from '@/components/admin/format';
import { CAPTION, Chip, EXPLAIN } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { C3DisbursementDetail, Tone } from '@/types/admin';
import type { Receipt } from '@/types/settlement';

/** The id the step-up explanation carries, so a withheld Approve can point at it. */
export const STEP_UP_NOTE = 'disbursement-step-up';

/** The id the hold-placer explanation carries, for a withheld Release hold. */
export const HOLD_SELF_NOTE = 'disbursement-hold-self';

const HEADING =
    'mb-2.5 text-[12px] font-bold tracking-[.05em] text-[#7b8699] uppercase dark:text-rz-muted';

const BOX = 'rounded-[13px] border border-rz-hairline bg-rz-surface px-4 py-3';

const NOTE = 'mt-2.5 rounded-xl px-3.5 py-2.5 text-[12.5px] leading-[1.5]';

const NOTE_TONE = {
    info: 'bg-[rgba(30,58,255,.07)] text-[#5f6fc8] dark:text-[#99a3ff]',
    warn: 'bg-[rgba(194,102,31,.09)] text-[#a55418] dark:text-[#f0a060]',
    block: 'border border-[#fdeaea] bg-[rgba(255,77,79,.06)] text-[#c4373c] dark:border-[rgba(255,107,111,.25)] dark:text-[#ff6b6f]',
} as const;

function Block({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section aria-label={title} className="mt-5">
            <h3 className={HEADING}>{title}</h3>
            {children}
        </section>
    );
}

function Note({
    tone,
    id,
    children,
}: {
    tone: keyof typeof NOTE_TONE;
    id?: string;
    children: ReactNode;
}) {
    return (
        <p id={id} className={cn(NOTE, NOTE_TONE[tone])}>
            {children}
        </p>
    );
}

/** A term and its value, or a dash when the server has none. */
function Fact({
    label,
    value,
    mono = false,
}: {
    label: string;
    value: string | null;
    mono?: boolean;
}) {
    return (
        <>
            <dt className={CAPTION}>{label}</dt>
            <dd
                className={cn(
                    'min-w-0 font-semibold break-all text-rz-ink',
                    mono && 'font-mono',
                )}
            >
                {value ?? '—'}
            </dd>
        </>
    );
}

function Facts({ children }: { children: ReactNode }) {
    return (
        <dl className="grid grid-cols-[minmax(0,.9fr)_minmax(0,1.4fr)] gap-x-3 gap-y-1.5 text-[12px]">
            {children}
        </dl>
    );
}

const at = (iso: string | null): string | null =>
    iso === null ? null : formatTimestamp(iso);

function Causes({ causes }: { causes: string[] }) {
    const { t } = useTranslation();

    if (causes.length === 0) {
        return null;
    }

    return (
        <div className="mt-2.5">
            <div className={cn('text-[11px] font-semibold', CAPTION)}>
                {t('admin.disbursements.causes')}
            </div>
            <ul className="mt-1 flex flex-wrap gap-1.5">
                {causes.map((cause) => (
                    <li
                        key={cause}
                        className="rounded-md bg-[rgba(255,77,79,.08)] px-2 py-0.5 font-mono text-[11px] font-semibold text-[#c4373c] dark:text-[#ff6b6f]"
                    >
                        {cause}
                    </li>
                ))}
            </ul>
        </div>
    );
}

/**
 * An immutable receipt: what the command recorded, as recorded. It never changes when the
 * disbursement's current state moves on.
 */
export function ReceiptFacts({ receipt }: { receipt: Receipt }) {
    const { t } = useTranslation();

    return (
        <Facts>
            <Fact
                label={t('admin.disbursements.receipt.code')}
                value={receipt.code}
                mono
            />
            <Fact
                label={t('admin.disbursements.receipt.reference')}
                value={receipt.reference}
                mono
            />
            <Fact
                label={t('admin.disbursements.receipt.amount')}
                value={formatRwf(receipt.amount)}
            />
            <Fact
                label={t('admin.disbursements.receipt.recorded_at')}
                value={formatTimestamp(receipt.recorded_at)}
            />
            <Fact
                label={t('admin.disbursements.receipt.revision')}
                value={String(receipt.revision)}
            />
        </Facts>
    );
}

const CHECK_TONE: Record<'not_run' | 'passed' | 'failed', Tone> = {
    not_run: 'grey',
    passed: 'green',
    failed: 'red',
};

/** The precheck the maker's authorization runs, and the policy it ran under. */
export function PrecheckPanel({
    precheck,
}: {
    precheck: C3DisbursementDetail['precheck'];
}) {
    const { t } = useTranslation();

    return (
        <Block title={t('admin.disbursements.precheck.title')}>
            <div className={BOX}>
                <Chip tone={CHECK_TONE[precheck.state]} className="mb-2">
                    {t(`admin.disbursements.check.${precheck.state}`)}
                </Chip>
                <Facts>
                    <Fact
                        label={t('admin.disbursements.checked_at')}
                        value={at(precheck.checked_at)}
                    />
                    <Fact
                        label={t('admin.disbursements.policy_version')}
                        value={precheck.policy_version}
                        mono
                    />
                </Facts>
                <Causes causes={precheck.causes} />
            </div>
        </Block>
    );
}

/**
 * What an approval binds, and the fresh step-up it needs (H11). The step-up has its own staff
 * route, still to be settled: until the server sends one, approval is not available.
 */
export function BindingPanel({
    disbursement,
}: {
    disbursement: C3DisbursementDetail;
}) {
    const { t } = useTranslation();
    const binding = disbursement.approval_binding;

    return (
        <Block title={t('admin.disbursements.binding.title')}>
            <div className={BOX}>
                {binding === null ? (
                    <p className={cn('text-[12.5px] leading-[1.5]', EXPLAIN)}>
                        {t('admin.disbursements.binding.none')}
                    </p>
                ) : (
                    <Facts>
                        <Fact
                            label={t('admin.disbursements.binding.revision')}
                            value={String(binding.revision)}
                        />
                        <Fact
                            label={t('admin.disbursements.binding.amount')}
                            value={formatRwf(binding.amount)}
                        />
                        <Fact
                            label={t('admin.disbursements.destination')}
                            value={binding.destination}
                        />
                        <Fact
                            label={t('admin.disbursements.binding.digest')}
                            value={binding.intent_digest}
                            mono
                        />
                    </Facts>
                )}
            </div>
            {(disbursement.state === 'awaiting_second_approver' ||
                disbursement.allowed_actions.includes(
                    'disbursement.approve',
                )) && (
                <Note
                    tone={disbursement.step_up.route === null ? 'warn' : 'info'}
                    id={STEP_UP_NOTE}
                >
                    {disbursement.step_up.route === null
                        ? t('admin.disbursements.step_up.unavailable')
                        : t('admin.disbursements.step_up.required')}
                </Note>
            )}
        </Block>
    );
}

/** The recorded intent (DISBURSEMENT_INTENT_RECORDED): recorded, not paid. */
export function IntentPanel({
    disbursement,
}: {
    disbursement: C3DisbursementDetail;
}) {
    const { t } = useTranslation();
    const intent = disbursement.intent;

    if (intent === null) {
        return null;
    }

    return (
        <Block title={t('admin.disbursements.intent.title')}>
            <Note tone="info">
                {t('admin.disbursements.intent.not_payment')}
            </Note>
            <div className={cn(BOX, 'mt-2.5')}>
                <ReceiptFacts receipt={intent.receipt} />
                <div className="mt-1.5">
                    <Facts>
                        <Fact
                            label={t('admin.disbursements.operation')}
                            value={intent.operation_id}
                            mono
                        />
                    </Facts>
                </div>
            </div>
            {disbursement.dispatch === null &&
                disbursement.state === 'queued' && (
                    <Note tone="info">
                        {t('admin.disbursements.intent.not_sent')}
                    </Note>
                )}
        </Block>
    );
}

/** When the worker sent it, after its own recheck made immediately before sending. */
export function DispatchPanel({
    dispatch,
}: {
    dispatch: C3DisbursementDetail['dispatch'];
}) {
    const { t } = useTranslation();

    if (dispatch === null) {
        return null;
    }

    return (
        <Block title={t('admin.disbursements.dispatch.title')}>
            <div className={BOX}>
                <Facts>
                    <Fact
                        label={t('admin.disbursements.dispatch.sent_at')}
                        value={formatTimestamp(dispatch.sent_at)}
                    />
                    <Fact
                        label={t('admin.disbursements.dispatch.recheck')}
                        value={t(
                            `admin.disbursements.check.${dispatch.recheck.state}`,
                        )}
                    />
                    <Fact
                        label={t('admin.disbursements.checked_at')}
                        value={formatTimestamp(dispatch.recheck.checked_at)}
                    />
                </Facts>
                <Causes causes={dispatch.recheck.causes} />
            </div>
        </Block>
    );
}

/**
 * The provider's outcome, for staff: references and error codes are shown here. Pending and
 * unknown are not yet confirmed — neither paid nor failed; a verified failure is closed only
 * once reconciled; an exception stays blocked and is never called reconciled (H13).
 */
export function ProviderPanel({
    provider,
}: {
    provider: C3DisbursementDetail['provider'];
}) {
    const { t } = useTranslation();

    if (provider === null) {
        return null;
    }

    return (
        <Block title={t('admin.disbursements.outcome.title')}>
            <Note
                tone={
                    provider.state === 'failed'
                        ? 'block'
                        : provider.state === 'succeeded'
                          ? 'info'
                          : 'warn'
                }
            >
                {t(`admin.disbursements.outcome.${provider.state}`)}
            </Note>
            {provider.state === 'failed' &&
                provider.reconciliation === 'unreconciled' && (
                    <Note tone="warn">
                        {t('admin.disbursements.outcome.failed_unreconciled')}
                    </Note>
                )}
            {provider.reconciliation === 'exception' && (
                <p
                    role="alert"
                    className={cn(NOTE, NOTE_TONE.block, 'font-semibold')}
                >
                    {t('admin.disbursements.outcome.exception')}
                </p>
            )}
            <div className={cn(BOX, 'mt-2.5')}>
                <Facts>
                    <Fact
                        label={t('admin.disbursements.outcome.reference')}
                        value={provider.provider_reference}
                        mono
                    />
                    <Fact
                        label={t('admin.disbursements.outcome.error_code')}
                        value={provider.error_code}
                        mono
                    />
                    <Fact
                        label={t('admin.disbursements.outcome.observed_at')}
                        value={at(provider.observed_at)}
                    />
                    <Fact
                        label={t('admin.disbursements.outcome.effective_at')}
                        value={at(provider.effective_at)}
                    />
                    <Fact
                        label={t('admin.disbursements.outcome.reconciliation')}
                        value={t(
                            `admin.disbursements.reconciliation.${provider.reconciliation}`,
                        )}
                    />
                    <Fact
                        label={t('admin.disbursements.outcome.reconciled_at')}
                        value={at(provider.reconciled_at)}
                    />
                    <Fact
                        label={t('admin.disbursements.operation')}
                        value={provider.operation_id}
                        mono
                    />
                </Facts>
            </div>
            <p className={cn('mt-2 text-[12px] leading-[1.5]', EXPLAIN)}>
                {t('admin.disbursements.outcome.requery_note')}
            </p>
        </Block>
    );
}

/** A hold: who placed it and why. Releasing it neither approves nor pays. */
export function HoldPanel({
    disbursement,
}: {
    disbursement: C3DisbursementDetail;
}) {
    const { t } = useTranslation();
    const hold = disbursement.hold;

    if (hold === null) {
        return null;
    }

    return (
        <Block title={t('admin.disbursements.hold.title')}>
            <div className={BOX}>
                <p className="text-[12.5px] text-rz-slate">
                    {t('admin.maker_checker.by', {
                        actor: hold.placed_by.actor,
                        at: formatTimestamp(hold.placed_by.at),
                    })}
                </p>
                <p className={cn('mt-1 text-[12.5px] leading-[1.55]', EXPLAIN)}>
                    {t('admin.trail.reason', { reason: hold.reason })}
                </p>
            </div>
            <Note tone="info">
                {t('admin.disbursements.hold.release_note')}
            </Note>
            {disbursement.viewer_placed_hold && (
                <Note tone="warn" id={HOLD_SELF_NOTE}>
                    {t('admin.disbursements.hold.self')}
                </Note>
            )}
        </Block>
    );
}

/** What issue committed on a verified, reconciled success. */
export function IssuePanel({
    issue,
}: {
    issue: C3DisbursementDetail['issue'];
}) {
    const { t, locale } = useTranslation();

    if (issue === null) {
        return null;
    }

    return (
        <Block title={t('admin.disbursements.issue.title')}>
            <div className={BOX}>
                <Facts>
                    <Fact
                        label={t('admin.disbursements.issue.holdings')}
                        value={String(issue.holdings)}
                    />
                    <Fact
                        label={t('admin.disbursements.issue.issued_at')}
                        value={formatTimestamp(issue.issued_at)}
                    />
                    <Fact
                        label={t('admin.disbursements.issue.effective_date')}
                        value={formatDate(issue.effective_date, locale)}
                    />
                </Facts>
            </div>
        </Block>
    );
}

/** The refunds failed closing made, with their receipt. */
export function RefundPanel({
    refund,
}: {
    refund: C3DisbursementDetail['refund'];
}) {
    const { t } = useTranslation();

    if (refund === null) {
        return null;
    }

    return (
        <Block title={t('admin.disbursements.refund.title')}>
            <div className={BOX}>
                <Facts>
                    <Fact
                        label={t('admin.disbursements.refund.commitments')}
                        value={String(refund.commitments)}
                    />
                    <Fact
                        label={t('admin.disbursements.refund.total')}
                        value={formatRwf(refund.total)}
                    />
                </Facts>
                <div className="mt-2.5 border-t border-rz-hairline pt-2.5">
                    <ReceiptFacts receipt={refund.receipt} />
                </div>
            </div>
        </Block>
    );
}
