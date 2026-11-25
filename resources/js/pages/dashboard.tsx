import { Head, Link, router, useHttp } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useRef, useState } from 'react';
import {
    HubAside,
    HubOverview,
    HubWork,
    LivePill,
    UrgentFlashcards,
} from '@/components/rozine/dashboard-hub';
import { LogoLockup } from '@/components/rozine/logo';
import { RoleIcon } from '@/components/rozine/role-icon';
import { useAccessRefresh } from '@/hooks/use-access-refresh';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard, logout } from '@/routes';
import { version as dashboardVersion } from '@/routes/dashboard';
import { store } from '@/routes/identity/active-role';
import { resume } from '@/routes/identity/roles';
import {
    deals as investorDeals,
    verification as investorVerification,
} from '@/routes/investor';
import { edit as profile } from '@/routes/profile';
import { show as roleApplication } from '@/routes/role-application';
import { edit as security } from '@/routes/security';
import { dashboard as staffDashboard } from '@/routes/staff';
import { notice as verificationNotice } from '@/routes/verification';
import type {
    AddInvestorAccessInput,
    IdentityCode,
    IdentityContext,
    InvestorAccessOffer,
    OnboardingHub,
    RequestableRole,
    RoleAccessOffer,
    RoleApp,
    RoleApplicationOffer,
    RouteLink,
    SelectActiveRoleInput,
    StaffAccess,
} from '@/types';

type LauncherProps = {
    identity: IdentityContext;
    staff_access?: StaffAccess;
    /**
     * Where a person asked to verify stands: their check is with Compliance ('pending') or not
     * sent yet ('required'). Absent or null for everyone else.
     */
    verification?: 'pending' | 'required' | null;
    /** Sent while a verified Business member may add Investor access to their own Party (#380). */
    investor_access?: InvestorAccessOffer | null;
    /**
     * Business and Auditor access asked for on the card itself (#616): which roles may be asked
     * for and which were already sent. Absent in previews, where those cards explain only.
     */
    access_requests?: RoleAccessOffer;
    /**
     * Where the person's own KYC and their application to switch to Business or Auditor stand
     * (23 Nov 2026): everyone starts as an Investor and may apply once their KYC is sent.
     */
    role_application?: RoleApplicationOffer | null;
    /**
     * The progress hub (24 Nov 2026): setup steps, what needs the person now, what happens next,
     * what is done and their newest onboarding updates. Absent for staff and in previews.
     */
    hub?: OnboardingHub | null;
    /** The fingerprint of the facts on screen, compared with `dashboard.version` to stay live. */
    pulse?: string;
    /** Supplied only by local/testing synthetic fixture previews. */
    preview_links?: Partial<Record<RoleApp, RouteLink>>;
};
/** Every marketplace app, in the launcher's order. */
const APPS: RoleApp[] = ['investor', 'business', 'auditor'];
const SUPPORT = 'mailto:hello@rozine.rw';
type Listed =
    | 'IDENTITY_READY'
    | 'IDENTITY_VERIFICATION_REQUIRED'
    | 'ROLE_MEMBERSHIP_REQUIRED';
type Blocker = Exclude<IdentityCode, Listed>;
/**
 * A person sees every app while ready, while their identity is verified or before any role is
 * granted: an app opens when its role is available, the Investor deals open read-only while the
 * identity is verified, and Business or Auditor access is asked for on its card. Other codes need
 * their fix first.
 */
const listsApps = (code: IdentityCode): code is Listed =>
    code === 'IDENTITY_READY' ||
    code === 'IDENTITY_VERIFICATION_REQUIRED' ||
    code === 'ROLE_MEMBERSHIP_REQUIRED';
const blockerAction = (code: Blocker) =>
    code === 'EMAIL_VERIFICATION_REQUIRED'
        ? {
              href: verificationNotice().url,
              label: 'suite.blocker.action.verify_email' as const,
          }
        : { href: SUPPORT, label: 'suite.blocker.action.contact' as const };
const CARD =
    'rz-app-card flex w-full flex-col rounded-[18px] border border-rz-hairline bg-rz-surface px-[18px] pt-[18px] pb-4 text-left';

export default function Launcher({
    identity,
    staff_access,
    verification,
    investor_access,
    access_requests,
    role_application,
    hub,
    pulse,
    preview_links,
}: LauncherProps) {
    const { t } = useTranslation();
    const command = useHttp<SelectActiveRoleInput, { data: IdentityContext }>();
    const pending = useRef<SelectActiveRoleInput | null>(null);
    const [failure, setFailure] = useState<string | null>(null);
    const [opening, setOpening] = useState(false);
    const [addingInvestor, setAddingInvestor] = useState(false);
    const refreshing = useAccessRefresh(
        [
            'identity',
            'staff_access',
            'verification',
            'investor_access',
            'access_requests',
            'role_application',
            'hub',
            'pulse',
        ],
        preview_links === undefined,
        true,
        { url: dashboardVersion().url, shown: pulse ?? null },
    );
    const canSelect = identity.allowed_actions.includes('identity.select_role');
    const canView = identity.allowed_actions.includes('identity.view_role');
    const canAdmin =
        staff_access?.can_open_admin === true &&
        staff_access.allowed_actions.includes('admin.open');
    const investorOffer =
        identity.allowed_actions.includes('identity.add_investor') &&
        preview_links === undefined
            ? (investor_access ?? null)
            : null;
    const accessOffer = access_requests ?? null;
    /** With the hub the greeting is the page's heading, so the apps title steps down a level. */
    const HeadingTag = hub ? 'h2' : 'h1';
    const mfaRequired =
        identity.available_roles.includes('auditor') && !canSelect;

    async function selectRole(payload: SelectActiveRoleInput) {
        let errorCode = 'NETWORK_ERROR';
        setOpening(true);
        setFailure(null);
        command.transform(() => payload);

        try {
            const response = await command.submit(store(), {
                onHttpException: (response) => {
                    try {
                        const { code } = JSON.parse(response.data) as {
                            code?: unknown;
                        };
                        errorCode =
                            typeof code === 'string' ? code : 'REQUEST_FAILED';
                    } catch {
                        errorCode = 'REQUEST_FAILED';
                    }
                },
                onError: () => {
                    errorCode = 'VALIDATION_ERROR';
                },
            });

            if (
                response.data.active_role !== payload.role ||
                !response.data.allowed_actions.includes('identity.view_role')
            ) {
                setFailure('ACTIVE_ROLE_REVISION_CONFLICT');
                pending.current = null;

                return;
            }

            router.visit(resume(payload.role));
        } catch {
            setFailure(errorCode);

            if (errorCode !== 'NETWORK_ERROR') {
                pending.current = null;
            }
        } finally {
            setOpening(false);
        }
    }

    function openRole(role: RoleApp) {
        if (opening || command.processing || refreshing) {
            return;
        }

        const preview = preview_links?.[role];

        if (preview) {
            router.visit(preview);

            return;
        }

        if (identity.active_role === role && canView) {
            router.visit(resume(role));

            return;
        }

        pending.current = {
            role,
            expected_revision: identity.context_revision,
            request_id: crypto.randomUUID(),
        };
        void selectRole(pending.current);
    }

    /** With the hub the apps sit under it in the main column, as designed; alone they fill the page. */
    const unread = hub?.recent.filter((notice) => !notice.read).length ?? 0;
    const initials = (hub?.name ?? '')
        .split(/\s+/u)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
    const appsSection = (
        <section
            aria-labelledby="suite-apps"
            className="animate-rz-rise [animation-delay:.1s]"
        >
            <div className="mb-3.5 flex items-center gap-[9px]">
                <HeadingTag
                    id="suite-apps"
                    className="text-xs font-bold tracking-[.12em] text-rz-muted uppercase"
                >
                    {t('suite.section.apps')}
                </HeadingTag>
                <div className="h-px flex-1 bg-rz-divider" />
            </div>
            {preview_links && (
                <p className="mb-4 text-sm text-rz-muted">
                    {t('suite.preview')}
                </p>
            )}
            {failure ? (
                <div
                    role="alert"
                    className="flex max-w-xl flex-col gap-4 rounded-[18px] border border-rz-hairline bg-rz-surface p-5"
                >
                    <p>
                        {t(
                            failure === 'MFA_REQUIRED'
                                ? 'suite.mfa_required'
                                : failure === 'NETWORK_ERROR'
                                  ? 'suite.network_error'
                                  : failure === 'IDEMPOTENCY_KEY_REUSED'
                                    ? 'suite.command_conflict'
                                    : 'suite.access_changed',
                        )}
                    </p>
                    {failure === 'NETWORK_ERROR' && (
                        <button
                            type="button"
                            disabled={opening}
                            className="text-left font-semibold text-rz-accent-text"
                            onClick={() => void selectRole(pending.current!)}
                        >
                            {t('suite.retry')}
                        </button>
                    )}
                    {failure === 'MFA_REQUIRED' && (
                        <Link
                            href={security()}
                            className="font-semibold text-rz-accent-text"
                        >
                            {t('suite.mfa_setup')}{' '}
                            <span aria-hidden="true">→</span>
                        </Link>
                    )}
                    <Link href={dashboard()} preserveState={false}>
                        {t('suite.refresh_access')}
                    </Link>
                </div>
            ) : (
                <>
                    {/* A staff account is dedicated to Admin (ADR-0001): no marketplace apps. */}
                    {canAdmin ? null : listsApps(identity.code) ? (
                        <>
                            <ul
                                className={`grid grid-cols-1 gap-3.5 sm:grid-cols-2 ${hub ? 'xl:grid-cols-3' : 'lg:grid-cols-4'}`}
                            >
                                {APPS.map((role) => (
                                    <li
                                        key={role}
                                        data-audience={role}
                                        className="flex"
                                    >
                                        {identity.available_roles.includes(
                                            role,
                                        ) ? (
                                            <button
                                                type="button"
                                                aria-label={t(
                                                    `suite.app.${role}.title`,
                                                )}
                                                disabled={
                                                    opening ||
                                                    command.processing ||
                                                    refreshing ||
                                                    (!canSelect &&
                                                        !(
                                                            canView &&
                                                            identity.active_role ===
                                                                role
                                                        ))
                                                }
                                                onClick={() => openRole(role)}
                                                className={`${CARD} disabled:cursor-not-allowed disabled:opacity-60`}
                                            >
                                                <AppCardBody
                                                    role={role}
                                                    summary={`suite.app.${role}.description`}
                                                    action="suite.app.open"
                                                />
                                            </button>
                                        ) : role === 'investor' &&
                                          investorOffer !== null ? (
                                            <button
                                                type="button"
                                                aria-expanded={addingInvestor}
                                                aria-controls="add-investor-access"
                                                onClick={() =>
                                                    setAddingInvestor(true)
                                                }
                                                className={CARD}
                                            >
                                                <AppCardBody
                                                    role={role}
                                                    summary="suite.app.investor.add"
                                                    action="suite.app.add_access"
                                                />
                                            </button>
                                        ) : role === 'investor' &&
                                          identity.code ===
                                              'IDENTITY_VERIFICATION_REQUIRED' ? (
                                            <Link
                                                href={investorDeals()}
                                                className={CARD}
                                            >
                                                <AppCardBody
                                                    role={role}
                                                    summary="suite.app.investor.preview"
                                                    action="suite.app.browse"
                                                />
                                            </Link>
                                        ) : role !== 'investor' &&
                                          role_application ? (
                                            <RoleSwitchCard
                                                role={role}
                                                application={role_application}
                                            />
                                        ) : (
                                            <div className={CARD}>
                                                <AppCardBody
                                                    role={role}
                                                    summary={unavailableSummary(
                                                        role,
                                                        accessOffer,
                                                    )}
                                                    action="suite.app.unavailable"
                                                />
                                            </div>
                                        )}
                                    </li>
                                ))}
                            </ul>
                            {investorOffer !== null && addingInvestor && (
                                <AddInvestorAccess
                                    key={investorOffer.terms.sha256}
                                    offer={investorOffer}
                                    expectedRevision={identity.context_revision}
                                    onCancel={() => setAddingInvestor(false)}
                                    onAdded={(context) => {
                                        pending.current = {
                                            role: 'investor',
                                            expected_revision:
                                                context.context_revision,
                                            request_id: crypto.randomUUID(),
                                        };
                                        void selectRole(pending.current);
                                    }}
                                />
                            )}
                            {identity.code ===
                                'IDENTITY_VERIFICATION_REQUIRED' &&
                                verification === 'pending' && (
                                    <p
                                        role="status"
                                        className="mt-4 text-sm text-rz-body"
                                    >
                                        {t(
                                            'suite.blocker.IDENTITY_VERIFICATION_PENDING.body',
                                        )}
                                    </p>
                                )}
                            {identity.code ===
                                'IDENTITY_VERIFICATION_REQUIRED' &&
                                verification !== 'pending' && (
                                    <p
                                        role="status"
                                        className="mt-4 text-sm text-rz-body"
                                    >
                                        {t(
                                            'suite.blocker.IDENTITY_VERIFICATION_REQUIRED.title',
                                        )}{' '}
                                        <a
                                            href={investorVerification().url}
                                            className="font-semibold text-rz-accent-text"
                                        >
                                            {t(
                                                'suite.blocker.action.verify_identity',
                                            )}
                                        </a>
                                    </p>
                                )}
                        </>
                    ) : (
                        <BlockerCard code={identity.code} />
                    )}
                    {mfaRequired && (
                        <div
                            role="status"
                            className="mt-4 flex flex-col gap-3 text-sm"
                        >
                            <p className="text-rz-body">
                                {t('suite.mfa_required')}
                            </p>
                            <Link
                                href={security()}
                                className="inline-flex w-fit items-center rounded-xl bg-rz-accent px-4 py-2.5 font-semibold text-white"
                            >
                                {t('suite.mfa_setup')}{' '}
                                <span aria-hidden="true">→</span>
                            </Link>
                        </div>
                    )}
                    {canAdmin && (
                        <Link
                            href={staffDashboard()}
                            className="mt-4 inline-block rounded-[18px] border border-rz-hairline bg-rz-surface p-5 font-semibold text-rz-ink"
                        >
                            {t('suite.admin')}
                        </Link>
                    )}
                    {opening && (
                        <p role="status" className="mt-4 text-sm text-rz-muted">
                            {t('suite.opening')}
                        </p>
                    )}
                </>
            )}
        </section>
    );

    return (
        <div className="rz-surface rz-launcher-bg flex min-h-screen flex-col px-4 pb-10 sm:px-10">
            <Head title={t('suite.head_title')} />
            {/* Fixed on scroll and set apart from the page, with the Live state, updates and profile (design, 24 Nov 2026). */}
            <header className="sticky top-0 z-30 -mx-4 flex animate-rz-rise items-center gap-3 border-b border-rz-hairline bg-rz-surface px-4 py-3 sm:-mx-10 sm:gap-[15px] sm:px-10">
                <LogoLockup
                    title={t('common.brand.name')}
                    className="block h-9 w-auto shrink-0 text-rz-brand-mark"
                />
                <span className="ml-auto" />
                {preview_links === undefined && <LivePill />}
                {hub && (
                    <a
                        href="#hub-recent"
                        aria-label={t('suite.header.updates', {
                            count: unread,
                        })}
                        className="relative flex size-10 items-center justify-center rounded-xl border border-rz-hairline bg-rz-surface text-rz-ink"
                    >
                        <Bell aria-hidden="true" className="size-[18px]" />
                        {unread > 0 && (
                            <span className="absolute -top-1 -right-1 flex min-w-[18px] items-center justify-center rounded-full bg-rz-danger px-1 text-[10px] font-bold text-white">
                                {unread}
                            </span>
                        )}
                    </a>
                )}
                {hub && (
                    <Link
                        href={profile()}
                        aria-label={t('suite.header.profile')}
                        data-audience="investor"
                        className="flex size-10 items-center justify-center rounded-full bg-rz-accent-fill text-sm font-bold text-white"
                    >
                        {initials}
                    </Link>
                )}
                <Link
                    href={logout()}
                    as="button"
                    className="text-sm font-semibold text-rz-body"
                >
                    {t('suite.sign_out')}
                </Link>
            </header>
            <main
                className={`mx-auto mt-6 flex w-full max-w-[1320px] flex-1 flex-col gap-[30px] ${hub ? 'pt-4 pb-40' : 'justify-center'}`}
            >
                {hub && (
                    /* The launcher has no app scope, so the hub takes the Investor accent every account starts with. */
                    <div
                        data-audience="investor"
                        data-testid="dashboard-hub"
                        className="flex animate-rz-rise flex-col gap-6"
                    >
                        <HubOverview hub={hub} />
                        <div className="flex flex-wrap items-start gap-6">
                            <div className="flex min-w-0 flex-[999_1_560px] flex-col gap-6">
                                <HubWork hub={hub} />
                                {appsSection}
                            </div>
                            <aside className="flex min-w-0 flex-[1_1_320px] flex-col gap-6">
                                <HubAside hub={hub} />
                            </aside>
                        </div>
                    </div>
                )}
                {!hub && appsSection}
            </main>
            {hub && (
                <div data-audience="investor" data-testid="dashboard-urgent">
                    <UrgentFlashcards actions={hub.actions} />
                </div>
            )}
        </div>
    );
}

const UNAVAILABLE_REASON = {
    business: {
        ROLE_ON_RECORD: 'suite.app.unavailable_reason.business_on_record',
        AUDITOR_ACCOUNT: 'suite.app.unavailable_reason.auditor_account',
    },
    auditor: {
        ROLE_ON_RECORD: 'suite.app.unavailable_reason.auditor_on_record',
        AUDITOR_ONLY_ROLE: 'suite.app.unavailable_reason.auditor_only_role',
    },
} as const;

type UnavailableSummary = {
    [
        Role in RequestableRole
    ]: (typeof UNAVAILABLE_REASON)[Role][keyof (typeof UNAVAILABLE_REASON)[Role]];
}[RequestableRole];

/**
 * What a card the person cannot open or ask for says (#622): why, when the launcher knows (an
 * Auditor keeps no other role, or the role is already on record with staff), else what it does.
 */
function unavailableSummary(
    role: RoleApp,
    offer: RoleAccessOffer | null,
): `suite.app.${RoleApp}.description` | UnavailableSummary {
    if (role === 'investor') {
        return 'suite.app.investor.description';
    }

    const reasons: Partial<Record<string, UnavailableSummary>> =
        UNAVAILABLE_REASON[role];
    const reason = offer?.unavailable[role];

    return (
        (reason === undefined ? undefined : reasons[reason]) ??
        `suite.app.${role}.description`
    );
}

/**
 * The Business or Auditor card for a person who does not hold that role (role switch, 23 Nov 2026):
 * verify first while their own KYC is not sent; then apply, continue, fix what Compliance asked for,
 * or wait while the application is under review. A pending application for the other role leaves
 * this card explaining only, since an account holds one role at a time.
 */
function RoleSwitchCard({
    role,
    application,
}: {
    role: Exclude<RoleApp, 'investor'>;
    application: RoleApplicationOffer;
}) {
    const mine = application.role === role;
    const busyElsewhere =
        application.role !== null &&
        application.role !== role &&
        ['submitted', 'approved'].includes(application.status);

    if (
        !['submitted', 'approved'].includes(application.kyc) &&
        mine &&
        application.status === 'submitted'
    ) {
        // The application is already with Compliance and waits on the person's own identity fixes
        // (QA #766): say so, rather than suggesting they start over.
        return (
            <a href={investorVerification().url} className={CARD}>
                <AppCardBody
                    role={role}
                    summary="suite.app.switch.kyc_fix_summary"
                    action="suite.app.switch.kyc_fix"
                />
            </a>
        );
    }

    if (!['submitted', 'approved'].includes(application.kyc)) {
        return (
            <a href={investorVerification().url} className={CARD}>
                <AppCardBody
                    role={role}
                    summary={`suite.app.${role}.description`}
                    action="suite.app.verify_first"
                />
            </a>
        );
    }

    if (busyElsewhere || (mine && application.status === 'approved')) {
        return (
            <div className={CARD}>
                <AppCardBody
                    role={role}
                    summary={
                        application.status === 'approved' &&
                        application.role !== null
                            ? `suite.app.switch.held_${application.role}`
                            : 'suite.app.switch.one_role'
                    }
                    action="suite.app.unavailable"
                />
            </div>
        );
    }

    if (mine && application.status === 'submitted') {
        return (
            <div role="status" className={CARD}>
                <AppCardBody
                    role={role}
                    summary="suite.app.switch.under_review_summary"
                    action="suite.app.switch.under_review"
                />
            </div>
        );
    }

    return (
        <Link href={roleApplication({ query: { for: role } })} className={CARD}>
            <AppCardBody
                role={role}
                summary={
                    mine && application.status === 'changes_requested'
                        ? 'suite.app.switch.asked_summary'
                        : `suite.app.${role}.switch`
                }
                action={
                    mine && application.status === 'changes_requested'
                        ? 'suite.app.switch.fix'
                        : mine && application.status !== 'none'
                          ? 'suite.app.switch.continue'
                          : 'suite.app.switch.start'
                }
            />
        </Link>
    );
}

function AppCardBody({
    role,
    summary,
    action,
}: {
    role: RoleApp;
    summary:
        | `suite.app.${RoleApp}.description`
        | UnavailableSummary
        | 'suite.app.investor.preview'
        | 'suite.app.investor.add'
        | 'suite.app.request_sent_summary'
        | `suite.app.${'business' | 'auditor'}.switch`
        | 'suite.app.switch.one_role'
        | `suite.app.switch.held_${'business' | 'auditor'}`
        | 'suite.app.switch.under_review_summary'
        | 'suite.app.switch.asked_summary'
        | 'suite.app.switch.kyc_fix_summary';
    action:
        | 'suite.app.open'
        | 'suite.app.browse'
        | 'suite.app.request_access'
        | 'suite.app.request_sent'
        | 'suite.app.verify_first'
        | 'suite.app.switch.kyc_fix'
        | 'suite.app.unavailable'
        | 'suite.app.add_access'
        | 'suite.app.switch.start'
        | 'suite.app.switch.continue'
        | 'suite.app.switch.fix'
        | 'suite.app.switch.under_review';
}) {
    const { t } = useTranslation();

    return (
        <>
            <span className="flex items-center gap-[11px]">
                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-rz-accent-tint text-rz-accent-text">
                    <RoleIcon role={role} className="size-[22px]" />
                </span>
                <span className="text-base font-semibold text-rz-ink">
                    {t(`suite.app.${role}.title`)}
                </span>
            </span>
            <span className="mt-3 text-[12.5px] leading-normal text-rz-body">
                {t(summary)}
            </span>
            <span className="mt-3 text-[12.5px] font-semibold text-rz-accent-text">
                {t(action)}
            </span>
        </>
    );
}

/**
 * A verified Business member adds Investor access to the Party they already hold (#380): no second
 * identity check, but the Investor terms are accepted by version and digest first. Once added, the
 * launcher opens the Investor app as it would any available role.
 */
function AddInvestorAccess({
    offer,
    expectedRevision,
    onCancel,
    onAdded,
}: {
    offer: InvestorAccessOffer;
    expectedRevision: number;
    onCancel: () => void;
    onAdded: (context: IdentityContext) => void;
}) {
    const { t } = useTranslation();
    const command = useHttp<
        AddInvestorAccessInput,
        { data: IdentityContext }
    >();
    const [requestId] = useState(() => crypto.randomUUID());
    const [failure, setFailure] = useState<string | null>(null);

    async function add() {
        let errorCode = 'NETWORK_ERROR';
        setFailure(null);
        command.transform(() => ({
            expected_revision: expectedRevision,
            terms_version: offer.terms.version,
            terms_sha256: offer.terms.sha256,
            accepted: true,
            request_id: requestId,
        }));

        try {
            const response = await command.submit(offer.link, {
                onHttpException: (response) => {
                    try {
                        const { code } = JSON.parse(response.data) as {
                            code?: unknown;
                        };
                        errorCode =
                            typeof code === 'string' ? code : 'REQUEST_FAILED';
                    } catch {
                        errorCode = 'REQUEST_FAILED';
                    }
                },
                onError: () => {
                    errorCode = 'VALIDATION_ERROR';
                },
            });

            if (!response.data.available_roles.includes('investor')) {
                setFailure('INVESTOR_ACCESS_UNAVAILABLE');

                return;
            }

            onAdded(response.data);
        } catch {
            setFailure(errorCode);
        }
    }

    return (
        <section
            id="add-investor-access"
            aria-labelledby="add-investor-access-title"
            className="mt-4 flex max-w-xl flex-col gap-3 rounded-[18px] border border-rz-hairline bg-rz-surface p-5"
        >
            <h2
                id="add-investor-access-title"
                className="text-base font-semibold text-rz-ink"
            >
                {t('suite.add_investor.title')}
            </h2>
            <p className="text-sm text-rz-body">
                {t('suite.add_investor.body')}
            </p>
            <p className="rounded-xl bg-rz-accent-tint p-3 text-[13px] leading-normal text-rz-ink">
                {offer.terms.text}
            </p>
            <p className="text-[12.5px] leading-normal text-rz-body">
                {t('suite.add_investor.accept')}
            </p>
            {failure && (
                <p role="alert" className="text-sm text-rz-danger-text">
                    {t(
                        failure === 'NETWORK_ERROR'
                            ? 'suite.add_investor.network_error'
                            : failure === 'INVESTOR_TERMS_CHANGED'
                              ? 'suite.add_investor.terms_changed'
                              : 'suite.add_investor.unavailable',
                    )}
                </p>
            )}
            <div className="flex gap-2.5">
                <button
                    type="button"
                    onClick={onCancel}
                    className="h-10 rounded-[11px] border border-rz-hairline px-4 text-sm font-semibold text-rz-body"
                >
                    {t('suite.add_investor.cancel')}
                </button>
                <button
                    type="button"
                    disabled={command.processing}
                    aria-busy={command.processing || undefined}
                    onClick={() => void add()}
                    className="h-10 flex-1 rounded-[11px] bg-rz-accent px-4 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {t('suite.add_investor.confirm')}
                </button>
            </div>
        </section>
    );
}

function BlockerCard({ code }: { code: Blocker }) {
    const { t } = useTranslation();
    const action = blockerAction(code);

    return (
        <div
            data-audience="investor"
            role="status"
            className="max-w-xl rounded-[18px] border border-rz-hairline bg-rz-surface px-[18px] pt-[18px] pb-4"
        >
            <p className="text-base font-semibold text-rz-ink">
                {t(`suite.blocker.${code}.title`)}
            </p>
            <p className="mt-3 text-[12.5px] leading-normal text-rz-body">
                {t(`suite.blocker.${code}.body`)}
            </p>
            <a
                href={action.href}
                className="mt-3 inline-block text-[12.5px] font-semibold text-rz-accent-text"
            >
                {t(action.label)}
            </a>
        </div>
    );
}
