import { router, useHttp } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { readErrorCode } from '@/lib/rozine/operation';
import { cn } from '@/lib/utils';
import type { InvestorRiskStatements } from '@/types/investor';
import type { RouteAction } from '@/types/routing';

/**
 * The five risk statements (BRS v3.0 KYC-R05) on one screen with a single tick, "I understand all
 * of the above", asked once at app open and never inside checkout. The statements are the
 * server's exact words, since the acknowledgement binds their version and digest. Accepting
 * reloads the page; a wording change comes back as `RISK_STATEMENTS_STALE` and reloads the new
 * statements to tick again.
 */
export function RiskStatementsStep({
    risks,
    action,
    identityRevision,
}: {
    risks: InvestorRiskStatements;
    action: RouteAction;
    identityRevision: number;
}) {
    const { t } = useTranslation();
    const http = useHttp<Record<string, never>, unknown>();
    const [understood, setUnderstood] = useState(false);
    const [busy, setBusy] = useState(false);
    const [failed, setFailed] = useState(false);
    const accept = async () => {
        setBusy(true);
        setFailed(false);
        http.transform(() => ({
            request_id: crypto.randomUUID(),
            identity_context_revision: identityRevision,
            version: risks.version,
            statements_sha256: risks.statements_sha256,
            acknowledged: true,
        }));

        try {
            await http.submit(action, {
                onHttpException: (response) => {
                    if (
                        readErrorCode(response.data) === 'RISK_STATEMENTS_STALE'
                    ) {
                        setUnderstood(false);
                    }
                },
            });
            router.reload();
        } catch {
            setFailed(true);
        } finally {
            setBusy(false);
        }
    };

    return (
        <section
            aria-labelledby="risk-statements-title"
            className="rounded-xl border border-rz-border bg-rz-surface p-4"
        >
            <h3
                id="risk-statements-title"
                className="text-[13px] font-semibold text-rz-ink"
            >
                {t('investor.checkout.risks.title')}
            </h3>
            <p className="mt-1 text-[11px] leading-[1.45] text-rz-secondary">
                {t('investor.checkout.risks.lead')}
            </p>
            <ul className="mt-2 flex list-disc flex-col gap-1.5 pl-5">
                {risks.statements.map((item) => (
                    <li
                        key={item.key}
                        className="text-[12px] leading-[1.45] text-rz-ink"
                    >
                        {item.text}
                    </li>
                ))}
            </ul>
            <label className="mt-3 flex items-start gap-2 text-[12.5px] leading-[1.45] font-semibold text-rz-ink">
                <input
                    type="checkbox"
                    checked={understood}
                    onChange={(event) => setUnderstood(event.target.checked)}
                    className="mt-[2px] size-4 shrink-0"
                />
                <span>{t('investor.checkout.risks.understood')}</span>
            </label>
            {failed && (
                <p
                    role="alert"
                    className="mt-2 text-[11px] text-rz-danger-text"
                >
                    {t('investor.checkout.risks.failed')}
                </p>
            )}
            <button
                type="button"
                onClick={() => void accept()}
                disabled={!understood || busy}
                aria-busy={busy || undefined}
                className={cn(
                    'mt-3 h-[44px] w-full rounded-xl text-[13.5px] font-semibold',
                    understood && !busy
                        ? 'bg-rz-accent-fill text-white'
                        : 'cursor-not-allowed bg-rz-disabled text-rz-secondary',
                )}
            >
                {t('investor.checkout.risks.accept')}
            </button>
        </section>
    );
}
