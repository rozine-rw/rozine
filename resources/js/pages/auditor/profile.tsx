import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AuditorShell } from '@/components/auditor/auditor-shell';
import {
    AuditorCommandNotice,
    AuditorCommandProvider,
    useAuditorCommandCenter,
} from '@/components/auditor/commands';
import { EngagementBanner } from '@/components/auditor/engagement/engagement-banner';
import {
    ContactSection,
    LegalSection,
    PendingSection,
    SecuritySection,
    TelemetrySection,
} from '@/components/auditor/profile/account-sections';
import { AccreditationSection } from '@/components/auditor/profile/accreditation-section';
import { AvailabilitySection } from '@/components/auditor/profile/availability-section';
import { IdentityCard } from '@/components/auditor/profile/identity-card';
import { COLUMN } from '@/components/auditor/tab-columns';
import { DIVIDER } from '@/components/auditor/ui';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AuditorProfileProps, ProfileSection } from '@/types/auditor';

/** The design's nine Profile rows (Auditor.dc.html L3528–3536), in its order. */
const SECTIONS: { key: ProfileSection; icon: IconName }[] = [
    { key: 'earnings', icon: 'trend-up' },
    { key: 'contact', icon: 'person' },
    { key: 'accreditation', icon: 'graduation' },
    { key: 'payout', icon: 'bank' },
    { key: 'availability', icon: 'pin' },
    { key: 'telemetry', icon: 'bar-chart' },
    { key: 'security', icon: 'lock' },
    { key: 'learn', icon: 'books' },
    { key: 'legal', icon: 'document' },
];

/**
 * Profile (design L593–1013) with the design's nine sections. The menu sits under the identity
 * card, beside the chosen section on a wide screen and above it on a phone. A section with nothing
 * to read yet shows its empty state.
 */
export default function AuditorProfile(props: AuditorProfileProps) {
    const { t } = useTranslation();
    const center = useAuditorCommandCenter({
        page: props,
        lookup: props.links.operation,
        preview: props.preview_outcome,
        terms: props.engagement?.link ?? null,
    });
    const accreditation = (
        <AccreditationSection
            auditor={props.auditor}
            accreditation={props.accreditation}
            allowed={center.allowed}
            actions={props.actions}
            certificates={{
                certificate: props.links.certificate,
                submitted_certificate: props.links.submitted_certificate,
            }}
        />
    );
    const sections: Record<ProfileSection, ReactNode> = {
        earnings: <PendingSection kind="earnings" />,
        contact: <ContactSection contact={props.contact} />,
        accreditation,
        payout: <PendingSection kind="payout" />,
        availability: (
            <AvailabilitySection
                availability={props.availability}
                standing={props.standing}
            />
        ),
        telemetry: (
            <TelemetrySection
                onTimePct={props.on_time_pct}
                jobsDone={props.jobs_done}
            />
        ),
        security: (
            <SecuritySection
                security={props.security}
                settings={props.links.security_settings}
            />
        ),
        learn: <PendingSection kind="learn" />,
        legal: <LegalSection engagement={props.engagement} />,
    };

    return (
        <AuditorCommandProvider center={center}>
            <AuditorShell
                title={t('auditor.profile.head_title')}
                tab="profile"
                links={props.links}
                openJobs={props.open_jobs}
            >
                <div className="pb-[92px] lg:flex lg:h-full lg:gap-4 lg:px-5 lg:pt-4 lg:pb-5">
                    <div className="px-5 pt-[calc(env(safe-area-inset-top)+10px)] lg:flex lg:min-h-0 lg:flex-[0_0_calc(50%-8px)] lg:flex-col lg:gap-3.5 lg:p-0">
                        <h1 className="text-[24px] font-bold text-rz-ink lg:hidden">
                            {t('auditor.profile.title')}
                        </h1>
                        <EngagementBanner
                            engagement={props.engagement}
                            className="mt-3.5 lg:mt-0 lg:shrink-0"
                        />
                        <div className="mt-3.5 lg:mt-0 lg:shrink-0">
                            <IdentityCard
                                auditor={props.auditor}
                                qualityScore={props.quality_score}
                                onTimePct={props.on_time_pct}
                                jobsDone={props.jobs_done}
                            />
                        </div>
                        <nav
                            aria-label={t('auditor.profile.menu')}
                            className="mt-3.5 lg:mt-0 lg:min-h-0 lg:flex-auto lg:overflow-y-auto lg:rounded-2xl lg:border lg:border-rz-border lg:bg-rz-surface lg:px-[18px] lg:pt-[18px] lg:pb-4"
                        >
                            <div className="overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                                {SECTIONS.map(({ key, icon }) => {
                                    const on = key === props.section;

                                    return (
                                        <Link
                                            key={key}
                                            href={props.links.sections[key]}
                                            aria-current={
                                                on ? 'page' : undefined
                                            }
                                            className={cn(
                                                'flex w-full items-center gap-[13px] border-b p-[15px] last:border-b-0',
                                                DIVIDER,
                                                on && 'bg-rz-accent-soft',
                                            )}
                                        >
                                            <span
                                                className={cn(
                                                    'flex size-[34px] shrink-0 items-center justify-center rounded-[10px] text-[15px]',
                                                    on
                                                        ? 'bg-rz-accent-soft'
                                                        : 'bg-rz-page dark:bg-rz-surface-muted',
                                                )}
                                            >
                                                <Icon
                                                    name={icon}
                                                    tone="amber"
                                                />
                                            </span>
                                            <span className="flex-1 text-left text-[14.5px] font-semibold text-rz-ink">
                                                {t(
                                                    `auditor.profile.section.${key}`,
                                                )}
                                            </span>
                                            <span
                                                aria-hidden
                                                className="text-rz-secondary"
                                            >
                                                ›
                                            </span>
                                        </Link>
                                    );
                                })}
                            </div>
                        </nav>
                    </div>
                    <div className={COLUMN}>
                        <div className="rz-scroll px-5 lg:flex-1 lg:overflow-y-auto lg:px-[18px] lg:pt-[18px]">
                            <AuditorCommandNotice
                                placement="page"
                                shown={['licence', 'expires_on', 'certificate']}
                                className="mt-4 lg:mt-0 lg:mb-4"
                            />
                            <div className="mt-4 lg:mt-0">
                                {sections[props.section]}
                            </div>
                            <div className="h-4" />
                        </div>
                    </div>
                </div>
            </AuditorShell>
        </AuditorCommandProvider>
    );
}
