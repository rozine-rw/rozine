import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { formatTimestamp } from '@/components/admin/format';
import { EXPLAIN } from '@/components/admin/ui';
import { useStepUp } from '@/components/auditor/audit/use-step-up';
import { useRefusalText } from '@/components/rozine/c3-notice';
import { useTranslation } from '@/hooks/use-translation';
import { useServerNow } from '@/lib/investor/server-clock';
import { fallbackCode, isUncertainStatus } from '@/lib/rozine/operation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type {
    C3DisbursementDetail,
    DisbursementStepUpProof,
    DisbursementStepUpRequest,
} from '@/types/admin';

/** A proof held for one binding: the disbursement, revision, exact amount, destination and digest. */
type HeldProof = DisbursementStepUpProof & { binding: string | null };

/**
 * What the code entry tells the checker. A code is never kept after its request, and a lost
 * step-up answer is never retried: every state after an attempt asks for a new code.
 */
type Entry =
    | { kind: 'ready' }
    | { kind: 'message'; text: string }
    | { kind: 'cleared'; reason: 'expired' | 'changed' };

/** The facts an approval's proof is bound to, as one comparable key; null when nothing binds. */
export const bindingKey = (
    disbursement: C3DisbursementDetail,
): string | null => {
    const binding = disbursement.approval_binding;

    return binding === null
        ? null
        : [
              disbursement.id,
              disbursement.revision,
              binding.revision,
              binding.amount.currency,
              binding.amount.amount,
              binding.destination,
              binding.intent_digest,
          ].join('|');
};

/**
 * The staff approval's step-up (#96 answer 5), in the Auditor seal exchange's pattern: the
 * checker's authenticator code goes to the props' own `step_up.route` in one request and nowhere
 * else, and the answer is an opaque, single-use `{proof, expires_at}`. The proof is held only in
 * this transient state — never the operation journal, the lookup or storage — and only for the
 * binding it was given for: a reload that brings a new revision, amount, destination or digest
 * clears it, and so does its expiry against `server_time`. `take()` hands it to approve exactly
 * once. A lost step-up answer asks for a fresh step-up; a lost approve answer is the approval
 * command's own lookup, which carries no proof.
 */
export function useApprovalStepUp({
    disbursement,
    serverTime,
}: {
    disbursement: C3DisbursementDetail;
    serverTime: string;
}) {
    const { t } = useTranslation();
    const refusalText = useRefusalText();
    const stepUp = useStepUp<DisbursementStepUpRequest>();
    const binding = bindingKey(disbursement);
    const [held, setHeld] = useState<HeldProof | null>(null);
    const [entry, setEntry] = useState<Entry>({ kind: 'ready' });
    const now = useServerNow(serverTime, held !== null);
    /* The binding an answer must still match when it arrives; a late proof for another is dropped. */
    const current = useRef(binding);
    const changed = held !== null && held.binding !== binding;
    const expired = held !== null && now >= Date.parse(held.expires_at);

    useEffect(() => {
        current.current = binding;
    }, [binding]);

    /* A proof whose binding changed or which expired is cleared as soon as it is seen. */
    if (changed || expired) {
        setHeld(null);
        setEntry({
            kind: 'cleared',
            reason: changed ? 'changed' : 'expired',
        });
    }

    const proof = changed || expired ? null : held;

    const verify = async (route: RouteAction, code: string) => {
        /* The entry is offered only while an approval binding exists. */
        const approval = disbursement.approval_binding as NonNullable<
            C3DisbursementDetail['approval_binding']
        >;
        const sentFor = binding;

        setEntry({ kind: 'ready' });

        const result = await stepUp.verify(route, {
            request_id: crypto.randomUUID(),
            expected_revision: approval.revision,
            intent_digest: approval.intent_digest,
            code,
        });

        /* The binding changed while the code was checked: the answer is for other facts. */
        if (current.current !== sentFor) {
            setEntry({ kind: 'cleared', reason: 'changed' });

            return;
        }

        switch (result.kind) {
            case 'proof':
                setHeld({
                    proof: result.proof.proof,
                    expires_at: result.proof.expires_at,
                    binding: sentFor,
                });

                return;
            case 'invalid':
                setEntry({
                    kind: 'message',
                    text:
                        result.message ??
                        t('admin.disbursements.step_up.wrong_code'),
                });

                return;
            case 'unreachable':
                setEntry({
                    kind: 'message',
                    text: t('admin.disbursements.step_up.lost'),
                });

                return;
        }

        /* A 5xx or a timeout is a lost answer too: a fresh step-up, never a resend. */
        setEntry({
            kind: 'message',
            text: isUncertainStatus(result.status)
                ? t('admin.disbursements.step_up.lost')
                : refusalText(result.code ?? fallbackCode(result.status)),
        });
    };

    /**
     * Hands the proof to approve once: single-use, so it is dropped as it is sent. Approve is sent
     * only while a proof is held.
     */
    const take = (): string => {
        const taken = proof as HeldProof;

        setHeld(null);
        setEntry({ kind: 'ready' });

        return taken.proof;
    };

    return {
        proof,
        entry,
        checking: stepUp.checking,
        verify,
        take,
    };
}

export type ApprovalStepUp = ReturnType<typeof useApprovalStepUp>;

/**
 * The code entry for an approval's step-up, above the approval's reason stage. Once a proof is
 * held it says until when; the approval itself still needs its written reason.
 */
export function ApprovalStepUpEntry({
    stepUp,
    route,
    locked,
}: {
    stepUp: ApprovalStepUp;
    route: RouteAction;
    /** A command is out or unresolved: no new step-up is started. */
    locked: boolean;
}) {
    const { t } = useTranslation();
    const [code, setCode] = useState('');

    const submit = (event: FormEvent) => {
        event.preventDefault();

        /* The code leaves the page in this one request and is not kept for any retry. */
        const typed = code;

        setCode('');
        void stepUp.verify(route, typed);
    };

    if (stepUp.proof !== null) {
        return (
            <p
                role="status"
                className="mt-[18px] rounded-xl bg-[rgba(29,158,117,.09)] px-3.5 py-2.5 text-[12.5px] leading-[1.5] text-[#157a5b] dark:text-[#5fd3a8]"
            >
                {t('admin.disbursements.step_up.ready', {
                    time: formatTimestamp(stepUp.proof.expires_at),
                })}
            </p>
        );
    }

    const { entry } = stepUp;
    const message =
        entry.kind === 'message'
            ? entry.text
            : entry.kind === 'cleared'
              ? entry.reason === 'expired'
                  ? t('settlement.refusal.STEP_UP_EXPIRED')
                  : t('admin.disbursements.step_up.changed')
              : null;

    return (
        <form
            onSubmit={submit}
            aria-label={t('admin.disbursements.step_up.title')}
            className="mt-[18px] rounded-[15px] border-[1.5px] border-rz-hairline bg-rz-surface p-[18px]"
        >
            <div className="text-[15px] font-bold text-rz-ink">
                {t('admin.disbursements.step_up.title')}
            </div>
            <p className={cn('mt-[5px] text-[12.5px] leading-[1.55]', EXPLAIN)}>
                {t('admin.disbursements.step_up.lead')}
            </p>
            {message !== null && (
                <p
                    role="alert"
                    className="mt-2.5 rounded-xl bg-[rgba(194,102,31,.09)] px-3.5 py-2.5 text-[12.5px] leading-[1.5] text-[#a55418] dark:text-[#f0a060]"
                >
                    {message}
                </p>
            )}
            <input
                value={code}
                onChange={(event) => setCode(event.target.value)}
                inputMode="numeric"
                autoComplete="one-time-code"
                aria-label={t('admin.disbursements.step_up.code_label')}
                className="mt-3 h-[42px] w-full rounded-[11px] border border-rz-hairline bg-[#f7f9fd] px-[13px] text-[15px] tracking-[.2em] text-rz-ink tabular-nums outline-none focus:border-rz-focus-border focus:shadow-[0_0_0_3.5px_var(--rz-focus-ring)] dark:bg-rz-field"
            />
            <button
                type="submit"
                disabled={code.trim() === '' || stepUp.checking || locked}
                aria-busy={stepUp.checking || undefined}
                className="mt-3 h-[42px] w-full rounded-[11px] bg-[#1d9e75] text-[13.5px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
            >
                {t('admin.disbursements.step_up.verify')}
            </button>
        </form>
    );
}
