import { router, useHttp } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { readErrorCode } from '@/lib/rozine/operation';
import { cn } from '@/lib/utils';
import type { InvestorRiskStatements } from '@/types/investor';
import type { RouteAction } from '@/types/routing';

/**
 * The five risk statements before a first investment (BRS v2.0 KYC-R05), one tick each. The
 * statements are the server's exact words, since the acknowledgement binds their version and
 * digest. Once every box is ticked the Investor accepts them all at once; the checkout then
 * reloads and offers Reserve. A wording change comes back as `RISK_STATEMENTS_STALE` and
 * reloads the new statements to tick again.
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
    const [ticked, setTicked] = useState<ReadonlySet<string>>(new Set());
    const [busy, setBusy] = useState(false);
    const [failed, setFailed] = useState(false);
    const all = risks.statements.every((item) => ticked.has(item.key));

    const toggle = (key: string, checked: boolean) => {
        const next = new Set(ticked);

        if (checked) {
            next.add(key);
        } else {
            next.delete(key);
        }

        setTicked(next);
    };

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
                        setTicked(new Set());
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
            className="mb-[11px] rounded-xl border border-rz-border bg-rz-surface p-3"
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
            <ul className="mt-2 flex flex-col gap-2">
                {risks.statements.map((item) => (
                    <li key={item.key}>
                        <label className="flex items-start gap-2 text-[12px] leading-[1.45] text-rz-ink">
                            <input
                                type="checkbox"
                                checked={ticked.has(item.key)}
                                onChange={(event) =>
                                    toggle(item.key, event.target.checked)
                                }
                                className="mt-[2px] size-4 shrink-0"
                            />
                            <span>{item.text}</span>
                        </label>
                    </li>
                ))}
            </ul>
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
                disabled={!all || busy}
                aria-busy={busy || undefined}
                className={cn(
                    'mt-3 h-[44px] w-full rounded-xl text-[13.5px] font-semibold',
                    all && !busy
                        ? 'bg-rz-accent-fill text-white'
                        : 'cursor-not-allowed bg-rz-disabled text-rz-secondary',
                )}
            >
                {t('investor.checkout.risks.accept')}
            </button>
        </section>
    );
}
