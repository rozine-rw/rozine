import { useState } from 'react';
import { InvestorShell } from '@/components/investor/investor-shell';
import {
    Academy,
    AcademyCard,
    useAcademyProgress,
} from '@/components/investor/profile/academy';
import {
    HELP_GROUPS,
    HELP_UPDATED,
} from '@/components/investor/profile/help-answers';
import {
    AutomationExplainer,
    IdentityCard,
    LinkedAccounts,
    PendingContent,
    PersonalInformation,
    ProfileMenu,
    SecurityCenter,
    SignOut,
    Statements,
    SubHeading,
    SupportCard,
} from '@/components/investor/profile/profile-parts';
import { HelpCenter } from '@/components/rozine/help-center';
import { SupportChat } from '@/components/rozine/support-chat';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import type { InvestorProfileProps } from '@/types/investor';

/**
 * Profile (MVP-INVESTOR-SCR-11, design L2726–2834) with the design's sub-pages (L3884–4140):
 * personal information, Rozine Plus, security center, linked accounts (wallet readiness),
 * statements (MVP-INVESTOR-SCR-10), help center (answers and the support chat), terms and privacy note. A phone shows the menu and
 * opens each sub-page full screen; a wide screen keeps the menu beside the open sub-page, with
 * personal information as the resting one, as the design does. Auto-Deploy (MVP-INVESTOR-SCR-09)
 * stays a gated explainer sub-page; the design has no menu row for it outside Rozine Plus. The
 * Learning card under the menu opens the Investor Academy in place of the sub-page (L2219–2262).
 */
export default function InvestorProfile(props: InvestorProfileProps) {
    const { t } = useTranslation();
    const wide = useWide();
    const [supportRequest, setSupportRequest] = useState(0);
    const [academy, setAcademy] = useState(false);
    const [progress, setProgress] = useAcademyProgress();
    const sub =
        props.section === 'overview'
            ? wide
                ? 'personal'
                : null
            : props.section;

    const menu = (
        <>
            <h1 className="text-2xl font-semibold text-rz-ink">
                {t('investor.profile.title')}
            </h1>
            <IdentityCard identity={props.identity} />
            <SupportCard help={props.links.help} />
            <ProfileMenu
                section={sub ?? props.section}
                kyc={props.identity.kyc}
                links={props.links}
            />
            <AcademyCard progress={progress} onOpen={() => setAcademy(true)} />
            <SignOut action={props.actions.logout} />
        </>
    );

    const learning = academy ? (
        <Academy
            progress={progress}
            onProgress={setProgress}
            onBack={wide ? null : () => setAcademy(false)}
        />
    ) : null;

    const page =
        sub === null ? null : (
            <>
                <SubHeading
                    title={t(
                        sub === 'plan'
                            ? 'investor.profile.plan_title'
                            : `investor.profile.item.${sub}`,
                    )}
                    back={wide ? null : props.links.overview}
                />
                {sub === 'personal' && (
                    <PersonalInformation personal={props.personal} />
                )}
                {sub === 'security' && (
                    <SecurityCenter
                        security={props.security}
                        settings={props.links.security_settings}
                    />
                )}
                {(sub === 'plan' || sub === 'terms' || sub === 'privacy') && (
                    <PendingContent kind={sub} />
                )}
                {sub === 'help' && (
                    <>
                        <HelpCenter
                            intro="investor.help.intro"
                            groups={HELP_GROUPS}
                            updated={HELP_UPDATED}
                            onMessage={() => setSupportRequest((n) => n + 1)}
                        />
                        <SupportChat
                            support={props.support}
                            openRequest={supportRequest}
                        />
                    </>
                )}
                {sub === 'linked' && props.linked !== null && (
                    <LinkedAccounts
                        linked={props.linked}
                        action={props.actions.link_account}
                    />
                )}
                {sub === 'statements' && props.statements !== null && (
                    <Statements statements={props.statements} />
                )}
                {sub === 'automation' && (
                    <AutomationExplainer deals={props.links.deals} />
                )}
            </>
        );

    return (
        <InvestorShell
            title={t('investor.profile.title')}
            tab="profile"
            links={props.links}
            showTabBar={wide || (sub === null && !academy)}
        >
            {wide ? (
                <div className="flex h-full px-[30px] pt-3 pb-3.5">
                    <div className="flex min-h-0 flex-1 gap-4 px-5 py-4">
                        <div className="rz-scroll min-h-0 min-w-0 flex-1 overflow-y-auto rounded-2xl border border-rz-border bg-rz-surface px-[18px] pt-[18px] pb-6">
                            {menu}
                        </div>
                        <div className="rz-scroll min-h-0 min-w-0 flex-1 overflow-y-auto rounded-2xl border border-rz-border bg-rz-surface px-[22px] pt-5 pb-7">
                            {learning ?? page}
                        </div>
                    </div>
                </div>
            ) : (
                <div className="px-5 pt-[calc(env(safe-area-inset-top)+14px)] pb-[calc(63px+env(safe-area-inset-bottom)+24px)]">
                    {learning ?? page ?? menu}
                </div>
            )}
        </InvestorShell>
    );
}
