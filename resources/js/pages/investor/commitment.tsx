import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { InvestorShell } from '@/components/investor/investor-shell';
import { CommitmentCard } from '@/components/investor/primary/commitment';
import {
    C3Notice,
    PollStopped,
    useRefusalText,
} from '@/components/rozine/c3-notice';
import { useBoundedPoll } from '@/hooks/use-bounded-poll';
import { useC3Command } from '@/hooks/use-c3-command';
import { useTranslation } from '@/hooks/use-translation';
import type { C3InvestorCommitmentProps } from '@/types/investor';

/**
 * One commitment (`investor.commitments.show`, C3 v2 §2c–2d), opened from the portfolio's "Awaiting
 * issue" section: its state, units and rights, and its receipts. While it is `confirmed` the
 * Investor may cancel it, fee-free, if the server still lists the action; a funded commitment's
 * payout, while not yet confirmed, is polled boundedly for fresh facts. When the Investor may no
 * longer read it, the scoped refusal is all that shows.
 */
export default function InvestorCommitment({
    identity_context_revision: identityRevision,
    allowed_actions: allowed,
    commitment,
    refusal,
    links,
    preview_outcome: preview,
}: C3InvestorCommitmentProps) {
    const { t } = useTranslation();
    const refusalText = useRefusalText();
    const [confirming, setConfirming] = useState(false);
    const command = useC3Command<'primary.cancel'>({
        actions: { 'primary.cancel': commitment?.actions.cancel ?? null },
        lookup: links.operation,
        lookupQuery: { identity_context_revision: identityRevision },
        allowed: commitment?.allowed_actions ?? [],
        preview,
        only: ['commitment', 'allowed_actions'],
    });
    const inFlight = commitment?.closing?.stage === 'in_flight';
    const poll = useBoundedPoll(inFlight, ['commitment', 'allowed_actions']);
    const cancellable =
        commitment !== null &&
        allowed.includes('primary.cancel') &&
        commitment.allowed_actions.includes('primary.cancel') &&
        commitment.actions.cancel !== null;

    return (
        <InvestorShell
            title={t('investor.primary.commitment_title')}
            tab="portfolio"
            links={links}
            showTabBar={false}
        >
            <div className="mx-auto max-w-[560px] px-5 pt-[calc(env(safe-area-inset-top)+14px)] pb-10 lg:pt-6">
                <div className="flex items-center gap-[11px]">
                    <Link
                        href={links.close}
                        aria-label={t('investor.common.back')}
                        className="flex size-[34px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-base text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <h1 className="text-[17px] font-semibold text-rz-ink">
                        {t('investor.primary.commitment_title')}
                    </h1>
                </div>
                <div className="mt-4">
                    <C3Notice command={command} />
                    {commitment === null ? (
                        <div
                            role="alert"
                            className="rounded-2xl border border-rz-border bg-rz-surface p-4 text-[13px] leading-[1.55] text-rz-secondary"
                        >
                            {refusalText(refusal?.code ?? 'NOT_FOUND')}
                            <Link
                                href={links.portfolio}
                                className="mt-3 block font-semibold text-rz-accent-app-text"
                            >
                                {t('investor.primary.back_to_portfolio')}
                            </Link>
                        </div>
                    ) : (
                        <>
                            <CommitmentCard commitment={commitment} />
                            <PollStopped {...poll} className="mt-3" />
                            {cancellable &&
                                (confirming ? (
                                    <div
                                        role="group"
                                        aria-label={t(
                                            'investor.primary.cancel_title',
                                        )}
                                        className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4"
                                    >
                                        <p className="text-[13px] font-semibold text-rz-ink">
                                            {t('investor.primary.cancel_title')}
                                        </p>
                                        <p className="mt-1 text-xs leading-[1.5] text-rz-secondary">
                                            {t('investor.primary.cancel_body')}
                                        </p>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                command.send('primary.cancel', {
                                                    identity_context_revision:
                                                        identityRevision,
                                                    commitment_id:
                                                        commitment.id,
                                                    expected_commitment_revision:
                                                        commitment.revision,
                                                })
                                            }
                                            disabled={
                                                command.busy ||
                                                command.unresolved
                                            }
                                            aria-busy={
                                                command.busy || undefined
                                            }
                                            className="mt-3 flex h-11 w-full items-center justify-center rounded-xl bg-rz-danger text-sm font-semibold text-white disabled:opacity-60"
                                        >
                                            {t(
                                                'investor.primary.cancel_confirm',
                                            )}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setConfirming(false)}
                                            className="mt-2 flex h-10 w-full items-center justify-center rounded-xl border border-rz-border text-sm font-semibold text-rz-slate"
                                        >
                                            {t('investor.primary.cancel_keep')}
                                        </button>
                                    </div>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => setConfirming(true)}
                                        className="mt-4 flex h-11 w-full items-center justify-center rounded-xl border border-rz-border text-sm font-semibold text-rz-danger-text"
                                    >
                                        {t('investor.primary.cancel')}
                                    </button>
                                ))}
                        </>
                    )}
                </div>
            </div>
        </InvestorShell>
    );
}
