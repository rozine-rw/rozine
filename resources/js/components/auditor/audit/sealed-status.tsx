import { Link } from '@inertiajs/react';
import { DisputePanel } from '@/components/auditor/audit/dispute-panel';
import { formatKigaliTime } from '@/components/auditor/clock';
import { StatusPill, Tick } from '@/components/auditor/ui';
import type { PillTone } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { CosignState, SealedStage } from '@/types/auditor';

const COSIGN_TONE: Record<CosignState, PillTone> = {
    pending: 'blue',
    signed: 'green',
    declined: 'red',
    overdue: 'red',
};

type Stage = {
    key: 'sealed' | 'cosigned' | 'published';
    done: boolean;
    when: string | null;
};

/**
 * After the seal (co-signature status, MVP-AUDITOR-SCR-06-ST-02; auditor-filing-v1 point 6). The
 * design has no screen for a sealed report waiting on the business, so this follows the Business
 * app's submitted timeline: sealed, co-signed, published — each from the server's record — with
 * the seal's opaque report, key and signature references as sent. A sealed report is immutable; a
 * correction is a new linked amendment, and this report stays as it is (AC-03). A seal that can no
 * longer be verified (a revoked signing key) keeps all of this visible and says so, never implying
 * the seal verifies now; a Flash report has no co-sign date, so none is shown. A Business dispute
 * (N6) shows its own panel with the retained proof, where the CPA may uphold the findings.
 */
export function SealedStatus({
    stage,
    amend,
    uphold,
}: {
    stage: SealedStage;
    /** Starts a linked amendment, when the server allows one. */
    amend: { run: () => void; disabled: boolean } | null;
    /** Opens the uphold sheet for the dispute, when the server offers it. */
    uphold: { open: () => void; disabled: boolean } | null;
}) {
    const { t, locale } = useTranslation();
    const references = [
        { label: t('auditor.sealed.report_id'), value: stage.report_id },
        {
            label: t('auditor.sealed.signature_ref'),
            value: stage.signature_ref,
        },
        { label: t('auditor.sealed.key_id'), value: stage.key_id },
    ];
    const { cosign } = stage;

    /*
     * Where the filing stands, from the server's publication and co-sign facts — never from the
     * due date alone. An overdue co-signature is closed to signing and is never approved for the
     * business; nothing publishes without it.
     */
    const intro = (): string => {
        if (stage.published_at !== null) {
            /* Staff published it over an upheld dispute: nobody co-signed it. */
            return t(
                stage.dispute?.outcome === 'upheld'
                    ? 'auditor.sealed.body_published_staff'
                    : 'auditor.sealed.body_published',
                {
                    date: formatDate(stage.published_at, locale),
                },
            );
        }

        /* An unpublished report you amended is replaced by its amendment: nobody co-signs it. */
        if (stage.amended_by !== null) {
            return t('auditor.sealed.body_amended');
        }

        /* An open dispute pauses the Business's window; nothing publishes meanwhile. */
        if (stage.dispute !== null && stage.dispute.status !== 'resolved') {
            return t('auditor.sealed.body_disputed', { party: cosign.party });
        }

        switch (cosign.state) {
            case 'signed':
                return t('auditor.sealed.body_signed', { party: cosign.party });
            case 'declined':
                return t('auditor.sealed.body_declined', {
                    party: cosign.party,
                });
            case 'overdue':
                return t('auditor.sealed.body_overdue', {
                    party: cosign.party,
                });
            case 'pending':
                return cosign.due_on === null
                    ? t('auditor.sealed.body_undated', { party: cosign.party })
                    : t('auditor.sealed.body', {
                          party: cosign.party,
                          date: formatDate(cosign.due_on, locale),
                      });
        }
    };
    const stages: Stage[] = [
        { key: 'sealed', done: true, when: stage.sealed_at },
        {
            key: 'cosigned',
            done: cosign.state === 'signed',
            when: cosign.signed_at,
        },
        {
            key: 'published',
            done: stage.published_at !== null,
            when: stage.published_at,
        },
    ];

    return (
        <>
            <div className="text-center">
                <div className="mx-auto flex size-20 animate-[rz-pop_.4s_ease] items-center justify-center rounded-full bg-[rgba(29,158,117,.10)]">
                    <div className="flex size-14 items-center justify-center rounded-full bg-[#17795a] text-white">
                        <Tick className="size-7" strokeWidth={3} />
                    </div>
                </div>
                <h3 className="mt-[18px] text-[21px] font-bold text-rz-ink">
                    {t('auditor.sealed.title')}
                </h3>
                <p className="mt-2 text-[13.5px] leading-[1.55] text-rz-secondary">
                    {intro()}
                </p>
            </div>

            {stage.dispute !== null && (
                <DisputePanel dispute={stage.dispute} uphold={uphold} />
            )}

            {stage.seal_status === 'unavailable' && (
                <p
                    role="note"
                    className="mt-5 rounded-2xl border border-[#f2d69a] bg-rz-surface px-3.5 py-3 text-[12px] leading-[1.5] font-semibold text-[#8a6d2b] dark:border-[rgba(240,160,96,.3)] dark:text-[#e3b56a]"
                >
                    {t('auditor.sealed.unavailable')}
                </p>
            )}

            <ol
                aria-label={t('auditor.sealed.timeline')}
                className="mt-5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface"
            >
                {stages.map((item) => (
                    <li
                        key={item.key}
                        className="flex items-center gap-[11px] border-b border-[#eef2f9] px-[15px] py-3 last:border-b-0 dark:border-rz-divider"
                    >
                        <span
                            className={cn(
                                'flex size-5 shrink-0 items-center justify-center rounded-[10px]',
                                item.done
                                    ? 'bg-[#17795a] text-white'
                                    : 'border border-rz-border bg-rz-surface',
                            )}
                        >
                            {item.done && (
                                <Tick className="size-3" strokeWidth={3} />
                            )}
                        </span>
                        <span className="flex-1 text-[12.5px] text-rz-slate">
                            {t(`auditor.sealed.stage.${item.key}`)}
                        </span>
                        {item.when !== null ? (
                            <span className="text-[11.5px] font-bold text-rz-ink">
                                {formatDate(item.when, locale)} ·{' '}
                                {formatKigaliTime(item.when)}
                            </span>
                        ) : item.key === 'cosigned' ? (
                            <StatusPill tone={COSIGN_TONE[cosign.state]}>
                                {t(`auditor.sealed.cosign.${cosign.state}`)}
                            </StatusPill>
                        ) : (
                            <span className="text-[11.5px] text-rz-secondary">
                                —
                            </span>
                        )}
                    </li>
                ))}
            </ol>

            <div className="mt-3.5 rounded-2xl bg-rz-page px-3.5 py-[13px] dark:bg-rz-surface-muted">
                <p className="text-[10.5px] font-bold tracking-[.06em] text-[#1e3aff] uppercase dark:text-rz-investor-text">
                    {t('auditor.sealed.seal')}
                </p>
                <p className="mt-[5px] text-[12px] break-all text-rz-ink tabular-nums">
                    {stage.digest}
                </p>
                <dl className="mt-2">
                    {references.map((row) => (
                        <div
                            key={row.label}
                            className="flex items-baseline justify-between gap-3 py-[3px]"
                        >
                            <dt className="shrink-0 text-[10.5px] text-rz-secondary">
                                {row.label}
                            </dt>
                            <dd className="min-w-0 text-right text-[11px] font-semibold break-all text-rz-ink tabular-nums">
                                {row.value}
                            </dd>
                        </div>
                    ))}
                </dl>
                <p className="mt-1.5 text-[10.5px] leading-[1.5] text-[#1e3aff] dark:text-rz-investor-text">
                    {t('auditor.sealed.licence', { licence: stage.licence })}
                </p>
                {stage.verification != null && (
                    <Link
                        href={stage.verification}
                        className="mt-2 inline-block text-[12px] font-bold text-rz-accent-app-text"
                    >
                        {t('auditor.sealed.verify')}
                    </Link>
                )}
            </div>

            {stage.amended_by !== null && (
                <p className="mt-3.5 rounded-2xl border border-rz-border bg-rz-surface px-3.5 py-3 text-[12px] leading-[1.5] text-rz-slate">
                    {t('auditor.sealed.amended_by', {
                        report: stage.amended_by.report_id,
                    })}{' '}
                    <Link
                        href={stage.amended_by.link}
                        className="font-bold text-rz-accent-app-text"
                    >
                        {t('auditor.sealed.open_amendment')}
                    </Link>
                </p>
            )}

            {amend !== null && (
                <button
                    type="button"
                    onClick={amend.run}
                    disabled={amend.disabled}
                    className="mt-3.5 flex h-11 w-full items-center justify-center rounded-xl border border-rz-border bg-rz-surface text-[13px] font-bold text-rz-slate disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {t('auditor.sealed.amend')}
                </button>
            )}
        </>
    );
}
