import { BusinessShell } from '@/components/business/business-shell';
import { BusinessHero } from '@/components/business/home/business-hero';
import { CapitalSection } from '@/components/business/home/capital-section';
import { GrowSection } from '@/components/business/home/grow-section';
import { LiveRaiseCard } from '@/components/business/home/live-raise-card';
import { NotesSection } from '@/components/business/home/notes-section';
import { TodaySection } from '@/components/business/home/today-section';
import { TopRow } from '@/components/business/home/top-row';
import { useTranslation } from '@/hooks/use-translation';
import type { BusinessHomeProps } from '@/types/business';

/** Desktop columns are two scrolling white cards; on a phone they dissolve into one flow. */
const COLUMN =
    'lg:flex lg:min-h-0 lg:min-w-0 lg:flex-[0_0_calc(50%-8px)] lg:flex-col lg:overflow-y-auto lg:rounded-2xl lg:border lg:border-rz-border lg:bg-rz-surface';

/**
 * Business Home (MVP-BUSINESS-SCR-01, design L99–346): identity and rating, the live raise,
 * what needs doing today, the all-time record, every note, and the way into a new raise.
 */
export default function BusinessHome(props: BusinessHomeProps) {
    const { t } = useTranslation();

    return (
        <BusinessShell
            title={t('business.home.head_title')}
            tab="home"
            links={props.links}
        >
            <div className="pb-[92px] lg:flex lg:h-full lg:flex-col lg:gap-3.5 lg:px-5 lg:pt-4 lg:pb-5">
                <div className="px-5 pt-[calc(env(safe-area-inset-top)+4px)] lg:shrink-0 lg:p-0">
                    <TopRow
                        available={props.wallet.available}
                        unread={props.unread_notifications}
                        links={props.links}
                    />
                </div>
                <div className="lg:flex lg:min-h-0 lg:flex-1 lg:gap-4">
                    <div data-rzcol className={`rz-scroll ${COLUMN}`}>
                        <div className="px-5 lg:px-[18px] lg:pt-[18px] lg:pb-4">
                            <div className="mt-3.5 lg:mt-0">
                                <BusinessHero
                                    business={props.business}
                                    rating={props.rating}
                                    ratingLink={props.links.rating}
                                />
                            </div>
                            {props.live_raise && (
                                <LiveRaiseCard raise={props.live_raise} />
                            )}
                            <TodaySection items={props.today} />
                        </div>
                    </div>
                    <div data-rzcol className={`rz-scroll ${COLUMN}`}>
                        <div className="px-5 lg:px-[18px] lg:pt-[18px]">
                            <CapitalSection capital={props.capital} />
                        </div>
                        <NotesSection notes={props.notes} />
                        <GrowSection
                            headroom={props.headroom}
                            links={props.links}
                        />
                    </div>
                </div>
            </div>
        </BusinessShell>
    );
}
