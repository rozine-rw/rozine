import { Link } from '@inertiajs/react';
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
 * After the seal (co-signature status, MVP-AUDITOR-SCR-06-ST-02). The design has no screen for a
 * sealed report waiting on the business, so this follows the Business app's submitted timeline:
 * sealed, co-signed, published — each from the server's record. A sealed report is immutable; a
 * correction is a linked amendment (AC-03).
 */
export function SealedStatus({ stage }: { stage: SealedStage }) {
    const { t, locale } = useTranslation();
    const { cosign } = stage;
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
                    {t('auditor.sealed.body', {
                        party: cosign.party,
                        date: formatDate(cosign.due_on, locale),
                    })}
                </p>
            </div>

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
                <p className="mt-1.5 text-[10.5px] leading-[1.5] text-[#1e3aff] dark:text-rz-investor-text">
                    {t('auditor.sealed.licence', { licence: stage.licence })}
                </p>
            </div>

            {stage.amend !== null && (
                <Link
                    href={stage.amend}
                    className="mt-3.5 flex h-11 w-full items-center justify-center rounded-xl border border-rz-border bg-rz-surface text-[13px] font-bold text-rz-slate"
                >
                    {t('auditor.sealed.amend')}
                </Link>
            )}
        </>
    );
}
