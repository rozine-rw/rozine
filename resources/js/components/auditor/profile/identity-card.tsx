import { Avatar } from '@/components/auditor/home/hero';
import { INSET } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AuditorIdentity } from '@/types/auditor';

function Tile({
    label,
    value,
    tone,
}: {
    label: string;
    value: string;
    tone: string;
}) {
    return (
        <div className={cn('flex-1 rounded-[10px] px-[11px] py-[9px]', INSET)}>
            <p className="text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase">
                {label}
            </p>
            <p className={cn('mt-0.5 text-[15px] font-bold', tone)}>{value}</p>
        </div>
    );
}

/**
 * The profile identity card (design L600–616). The design's "Pass rate" tile becomes on-time
 * closing: partners record findings, they do not pass or fail anyone (MVP-AUDITOR-AC-02).
 */
export function IdentityCard({
    auditor,
    qualityScore,
    onTimePct,
    jobsDone,
}: {
    auditor: AuditorIdentity;
    qualityScore: number | null;
    onTimePct: number | null;
    jobsDone: number;
}) {
    const { t } = useTranslation();

    return (
        <div className="relative overflow-hidden rounded-[20px] border border-rz-border bg-rz-surface p-[17px] shadow-[0_10px_26px_-20px_rgba(20,45,95,.5)]">
            <div className="pointer-events-none absolute -top-[52px] -right-[46px] size-[190px] rounded-full bg-[radial-gradient(circle_at_40%_40%,rgba(194,102,31,.10),transparent_68%)]" />
            <div className="relative flex items-center gap-[13px]">
                <Avatar
                    auditor={auditor}
                    className="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-[#f2f5fa] bg-rz-accent-soft text-[18px] font-bold text-rz-accent-app-text shadow-[0_6px_16px_-8px_rgba(16,40,90,.45)] dark:border-rz-border"
                />
                <div className="min-w-0 flex-1">
                    <p className="text-[16px] leading-[1.25] font-bold text-rz-ink">
                        {auditor.name}
                    </p>
                    <p className="mt-0.5 truncate text-[11.5px] text-rz-secondary">
                        {auditor.firm} · {auditor.accreditation}
                    </p>
                </div>
                {qualityScore !== null && (
                    <div
                        role="img"
                        aria-label={t('auditor.rating.label', {
                            score: qualityScore,
                        })}
                        className="flex size-[52px] shrink-0 items-center justify-center rounded-full"
                        style={{
                            background: `conic-gradient(#c2661f ${(qualityScore / 100) * 360}deg, var(--rz-divider) 0)`,
                        }}
                    >
                        <div className="flex size-[41px] flex-col items-center justify-center rounded-full bg-rz-surface">
                            <span className="text-[15px] leading-none font-bold text-rz-ink">
                                {qualityScore}
                            </span>
                            <span className="text-[10px] font-bold tracking-[.06em] text-rz-slate uppercase">
                                {t('auditor.rating.word')}
                            </span>
                        </div>
                    </div>
                )}
            </div>
            <div className="relative mt-3.5 flex gap-2">
                <Tile
                    label={t('auditor.profile.on_time')}
                    value={onTimePct === null ? '—' : `${onTimePct}%`}
                    tone="text-rz-positive"
                />
                <Tile
                    label={t('auditor.profile.jobs')}
                    value={String(jobsDone)}
                    tone="text-rz-ink"
                />
                <Tile
                    label={t('auditor.profile.since')}
                    value={String(auditor.since_year)}
                    tone="text-rz-ink"
                />
            </div>
        </div>
    );
}
