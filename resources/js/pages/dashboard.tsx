import { Head, Link } from '@inertiajs/react';
import { LogoLockup } from '@/components/rozine/logo';
import { RoleIcon } from '@/components/rozine/role-icon';
import { useTranslation } from '@/hooks/use-translation';
import { useRoleSwitch } from '@/lib/rozine/use-role-switch';
import { edit as securitySettings } from '@/routes/security';
import { notice as verificationNotice } from '@/routes/verification';
import type {
    IdentityCode,
    IdentityContext,
    RoleApp,
    RouteLink,
} from '@/types';

type LauncherProps = {
    identity: IdentityContext;
    /**
     * Where each role card leads, when the server supplies it (the preview fixtures do, since the
     * role home routes are reserved but not registered yet). Otherwise the reserved paths apply.
     */
    links?: Partial<Record<RoleApp, RouteLink>>;
};

/** Where each role app starts. Server routes still authorize every request. */
const ROLE_APP_PATHS: Record<RoleApp, string> = {
    investor: '/investor',
    business: '/business',
    auditor: '/auditor',
};

const SUPPORT_MAILTO = 'mailto:hello@rozine.rw';

type Blocker = Exclude<IdentityCode, 'IDENTITY_READY'>;

/** Every identity gate has a real next step: verify the email, or reach a person. */
const blockerAction = (code: Blocker) =>
    code === 'EMAIL_VERIFICATION_REQUIRED'
        ? {
              href: verificationNotice().url,
              label: 'suite.blocker.action.verify_email' as const,
          }
        : {
              href: SUPPORT_MAILTO,
              label: 'suite.blocker.action.contact' as const,
          };

export default function Launcher({ identity, links }: LauncherProps) {
    const { t } = useTranslation();

    return (
        <div className="rz-surface rz-launcher-bg flex min-h-screen flex-col px-4 pt-6 pb-10 sm:px-10 sm:pt-[34px]">
            <Head title={t('suite.head_title')} />

            <header className="flex animate-rz-rise items-center gap-[15px]">
                <LogoLockup
                    title={t('common.brand.name')}
                    className="block h-10 w-auto shrink-0 text-rz-brand-mark"
                />
                <p className="hidden min-w-0 text-[13px] font-medium text-rz-body sm:block">
                    {t('suite.tagline')}
                </p>
                <span className="ml-auto hidden text-[11px] font-semibold tracking-[.09em] text-rz-faint uppercase lg:inline">
                    {t('suite.motto')}
                </span>
            </header>

            <main className="mx-auto mt-3.5 flex w-full max-w-[1320px] flex-1 flex-col justify-center gap-[30px]">
                <section
                    aria-labelledby="suite-apps"
                    className="animate-rz-rise [animation-delay:.1s]"
                >
                    <div className="mb-3.5 flex items-center gap-[9px]">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                            className="size-[15px] shrink-0 stroke-rz-muted"
                        >
                            <rect
                                x="6"
                                y="2.5"
                                width="12"
                                height="19"
                                rx="3"
                                strokeWidth="1.7"
                            />
                            <path
                                d="M10.5 5.3h3"
                                strokeWidth="1.7"
                                strokeLinecap="round"
                            />
                        </svg>
                        <h1
                            id="suite-apps"
                            className="text-xs font-bold tracking-[.12em] text-rz-muted uppercase"
                        >
                            {t('suite.section.apps')}
                        </h1>
                        <div className="h-px flex-1 bg-rz-divider" />
                    </div>

                    {identity.code === 'IDENTITY_READY' ? (
                        <RoleApps identity={identity} links={links} />
                    ) : (
                        <BlockerCard code={identity.code} />
                    )}
                </section>
            </main>
        </div>
    );
}

const CARD =
    'rz-app-card flex w-full flex-col rounded-[18px] border border-rz-hairline bg-rz-surface px-[18px] pt-[18px] pb-4 text-left';

function CardBody({ role, action }: { role: RoleApp; action: string }) {
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
                {t(`suite.app.${role}.description`)}
            </span>
            <span className="mt-3 text-[12.5px] font-semibold text-rz-accent-text">
                {action}
            </span>
        </>
    );
}

/**
 * The role cards. A card opens its app straight away only when that role is already active and
 * viewable; otherwise it selects the role first (identity-v2). A role that cannot be selected, as
 * when two-factor authentication is still missing, shows that next step instead.
 */
function RoleApps({ identity, links }: LauncherProps) {
    const { t } = useTranslation();
    const home = (role: RoleApp) => links?.[role] ?? ROLE_APP_PATHS[role];
    const roleSwitch = useRoleSwitch(identity, home);
    const can = (action: IdentityContext['allowed_actions'][number]) =>
        identity.allowed_actions.includes(action);
    const problem = roleSwitch.problem;

    return (
        <>
            <ul className="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                {identity.available_roles.map((role) => (
                    <li key={role} data-audience={role} className="flex">
                        {identity.active_role === role &&
                        can('identity.view_role') ? (
                            <Link href={home(role)} className={CARD}>
                                <CardBody
                                    role={role}
                                    action={t('suite.app.open')}
                                />
                            </Link>
                        ) : can('identity.select_role') ? (
                            <button
                                type="button"
                                disabled={roleSwitch.processing}
                                onClick={() => void roleSwitch.open(role)}
                                className={CARD}
                            >
                                <CardBody
                                    role={role}
                                    action={t(
                                        roleSwitch.processing
                                            ? 'suite.app.opening'
                                            : 'suite.app.open',
                                    )}
                                />
                            </button>
                        ) : (
                            <div className={`${CARD} cursor-default`}>
                                <CardBody
                                    role={role}
                                    action={t('suite.app.needs_mfa')}
                                />
                                <Link
                                    href={securitySettings()}
                                    className="mt-2 text-[12.5px] font-semibold text-rz-accent-text underline"
                                >
                                    {t('suite.switch.set_up_mfa')}
                                </Link>
                            </div>
                        )}
                    </li>
                ))}
            </ul>
            {problem !== null && (
                <div
                    role="alert"
                    className="mt-3.5 flex max-w-xl flex-wrap items-center gap-x-3 gap-y-2 rounded-xl border border-rz-hairline bg-rz-surface px-4 py-3 text-[13px] text-rz-ink"
                >
                    <span className="flex-1">
                        {t(`suite.switch.${problem.kind}`, {
                            app: t(`suite.app.${problem.role}.title`),
                        })}
                    </span>
                    {problem.kind === 'mfa' && (
                        <Link
                            href={securitySettings()}
                            className="font-semibold text-rz-brand-mark underline"
                        >
                            {t('suite.switch.set_up_mfa')}
                        </Link>
                    )}
                    {problem.kind === 'offline' && (
                        <button
                            type="button"
                            onClick={() =>
                                void roleSwitch.retry(problem.command)
                            }
                            className="font-semibold text-rz-brand-mark underline"
                        >
                            {t('suite.switch.try_again')}
                        </button>
                    )}
                    {problem.kind === 'failed' && (
                        <button
                            type="button"
                            onClick={() => void roleSwitch.open(problem.role)}
                            className="font-semibold text-rz-brand-mark underline"
                        >
                            {t('suite.switch.try_again')}
                        </button>
                    )}
                </div>
            )}
        </>
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
