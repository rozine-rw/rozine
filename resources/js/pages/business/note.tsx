import { Link } from '@inertiajs/react';
import { BusinessShell } from '@/components/business/business-shell';
import { DetailSheet } from '@/components/business/detail-sheet';
import { HomeBody } from '@/components/business/home/home-body';
import { PerformanceTrend } from '@/components/business/note/performance-trend';
import { PhotoStrip } from '@/components/business/note/photo-strip';
import { ProgressSummary } from '@/components/business/note/progress-summary';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { BusinessNoteProps } from '@/types/business';

/**
 * A published note's dashboard (MVP-BUSINESS-SCR-04, design L632–709), opened from Your notes. On a
 * wide screen it is the design's detail sheet over Home. A raising campaign has its own page
 * (`business/campaign`); this one previews the servicing (C4) phases. No Investor is named, typed or
 * given an amount here (H16): `recent_investors` is not rendered and there is no investor list.
 */
export default function BusinessNote({ home, note, links }: BusinessNoteProps) {
    const { t } = useTranslation();

    const sheet = (
        <DetailSheet
            label={note.title}
            close={links.close}
            closeLabel={t('business.note.close')}
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+2px)] pb-6 lg:pt-[18px]">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.close}
                        aria-label={t('business.note.back')}
                        className="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <div>
                        <h1 className="text-lg font-semibold text-rz-ink">
                            {note.title}
                        </h1>
                        <p className="text-xs text-rz-secondary">
                            {note.id} ·{' '}
                            <span
                                className={cn(
                                    note.status === 'failed'
                                        ? 'text-rz-danger-text'
                                        : 'text-rz-accent-app-text',
                                )}
                            >
                                {t(`business.note.status.${note.status}`)}
                            </span>
                        </p>
                    </div>
                </div>
                <ProgressSummary progress={note.progress} pay={links.pay} />
                <PhotoStrip photos={note.photos} />
                {note.performance !== null && (
                    <PerformanceTrend
                        ranges={{
                            six_months: note.performance.six_months,
                            twelve_months: note.performance.twelve_months,
                        }}
                    />
                )}
            </div>
        </DetailSheet>
    );

    return (
        <BusinessShell
            title={note.title}
            tab="home"
            links={home.links}
            showTabBar={false}
        >
            <HomeBody
                {...home}
                backdrop
                overlay={{ column: 'right', content: sheet }}
            />
        </BusinessShell>
    );
}
