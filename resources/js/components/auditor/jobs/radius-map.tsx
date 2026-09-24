import { useTranslation } from '@/hooks/use-translation';
import type { EligibleJob } from '@/types/auditor';

/** The ring's radius on the 170px map, in pixels. */
const RING_PX = 70;

/**
 * The dispatch radius map (design L213–216). The prototype pulls Leaflet and CARTO tiles from
 * third-party CDNs, which render "API key required" and leak the partner's position; this draws
 * the same ring, office dot and job pins locally from the server's relative positions. Those are
 * approximate — rounded by the server to 0.1 km — and the map says so (auditor-filing-v1 point 5).
 */
export function RadiusMap({
    radiusKm,
    jobs,
}: {
    radiusKm: number;
    jobs: EligibleJob[];
}) {
    const { t } = useTranslation();
    const scale = RING_PX / radiusKm;

    return (
        <div
            role="img"
            aria-label={t('auditor.jobs.map_label', {
                radius: radiusKm,
                count: jobs.length,
            })}
            className="relative mt-3.5 h-[170px] overflow-hidden rounded-2xl border border-[#dbe3ee] bg-rz-surface dark:border-rz-border"
        >
            <div className="absolute inset-0 bg-[linear-gradient(rgba(194,102,31,.06)_1px,transparent_1px),linear-gradient(90deg,rgba(194,102,31,.06)_1px,transparent_1px)] bg-[size:24px_24px]" />
            <div
                className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full border border-[rgba(12,24,48,.35)] bg-[rgba(12,24,48,.06)] dark:border-[rgba(255,255,255,.3)] dark:bg-[rgba(255,255,255,.05)]"
                style={{ width: RING_PX * 2, height: RING_PX * 2 }}
            />
            <span className="absolute top-1/2 left-1/2 size-3 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-[#c2661f] shadow-[0_1px_4px_rgba(0,0,0,.3)]" />
            {jobs.map((job) => (
                <svg
                    key={job.id}
                    viewBox="0 0 24 24"
                    aria-hidden
                    className="absolute size-[22px] -translate-x-1/2 -translate-y-full"
                    style={{
                        left: `calc(50% + ${job.map.east_km * scale}px)`,
                        top: `calc(50% - ${job.map.north_km * scale}px)`,
                    }}
                >
                    <path
                        d="M12 22s7-6.6 7-12a7 7 0 1 0-14 0c0 5.4 7 12 7 12z"
                        fill="#c2661f"
                        stroke="#fff"
                        strokeWidth="1.4"
                    />
                    <circle cx="12" cy="10" r="2.6" fill="#fff" />
                </svg>
            ))}
            <span className="absolute top-2.5 right-2.5 rounded-[10px] border border-[#e0e7f2] bg-white/92 px-[9px] py-[5px] text-[10px] font-semibold text-[#5b6a86] dark:border-rz-border dark:bg-rz-surface/90 dark:text-rz-secondary">
                {t('auditor.jobs.map_approximate')}
            </span>
            <span className="absolute bottom-2.5 left-2.5 rounded-[10px] border border-[#e0e7f2] bg-white/92 px-[9px] py-[5px] text-[10.5px] font-bold text-[#5b6a86] uppercase dark:border-rz-border dark:bg-rz-surface/90 dark:text-rz-secondary">
                {t('auditor.jobs.map_badge', {
                    radius: radiusKm,
                    count: jobs.length,
                })}
            </span>
        </div>
    );
}
