import { Head, Link, router, useHttp } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { LogoLockup } from '@/components/rozine/logo';
import { RoleIcon } from '@/components/rozine/role-icon';
import { useAccessRefresh } from '@/hooks/use-access-refresh';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard, logout } from '@/routes';
import { home as adminHome } from '@/routes/admin';
import { store } from '@/routes/identity/active-role';
import { resume } from '@/routes/identity/roles';
import { edit as security } from '@/routes/security';
import { notice as verificationNotice } from '@/routes/verification';
import type {
    IdentityCode,
    IdentityContext,
    RoleApp,
    RouteLink,
    SelectActiveRoleInput,
    StaffAccess,
} from '@/types';

type LauncherProps = {
    identity: IdentityContext;
    staff_access?: StaffAccess;
    /** Supplied only by local/testing synthetic fixture previews. */
    preview_links?: Partial<Record<RoleApp, RouteLink>>;
};
type Blocker = Exclude<IdentityCode, 'IDENTITY_READY'>;
const blockerAction = (code: Blocker) =>
    code === 'EMAIL_VERIFICATION_REQUIRED'
        ? {
              href: verificationNotice().url,
              label: 'suite.blocker.action.verify_email' as const,
          }
        : {
              href: 'mailto:hello@rozine.rw',
              label: 'suite.blocker.action.contact' as const,
          };

export default function Launcher({
    identity,
    staff_access,
    preview_links,
}: LauncherProps) {
    const { t } = useTranslation();
    const command = useHttp<SelectActiveRoleInput, { data: IdentityContext }>();
    const pending = useRef<SelectActiveRoleInput | null>(null);
    const [failure, setFailure] = useState<string | null>(null);
    const [opening, setOpening] = useState(false);
    const refreshing = useAccessRefresh(
        ['identity', 'staff_access'],
        preview_links === undefined,
    );
    const canSelect = identity.allowed_actions.includes('identity.select_role');
    const canView = identity.allowed_actions.includes('identity.view_role');
    const canAdmin =
        staff_access?.can_open_admin === true &&
        staff_access.allowed_actions.includes('admin.open');
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
                <Link
                    href={logout()}
                    as="button"
                    className="ml-auto text-sm font-semibold text-rz-body"
                >
                    {t('suite.sign_out')}
                </Link>
            </header>
            <main className="mx-auto mt-3.5 flex w-full max-w-[1320px] flex-1 flex-col justify-center gap-[30px]">
                <section
                    aria-labelledby="suite-apps"
                    className="animate-rz-rise [animation-delay:.1s]"
                >
                    <div className="mb-3.5 flex items-center gap-[9px]">
                        <h1
                            id="suite-apps"
                            className="text-xs font-bold tracking-[.12em] text-rz-muted uppercase"
                        >
                            {t('suite.section.apps')}
                        </h1>
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
                                    onClick={() =>
                                        void selectRole(pending.current!)
                                    }
                                >
                                    {t('suite.retry')}
                                </button>
                            )}
                            {failure === 'MFA_REQUIRED' && (
                                <Link href={security()}>
                                    {t('suite.mfa_setup')}
                                </Link>
                            )}
                            <Link href={dashboard()} preserveState={false}>
                                {t('suite.refresh_access')}
                            </Link>
                        </div>
                    ) : (
                        <>
                            {identity.code === 'IDENTITY_READY' ? (
                                <ul className="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                                    {identity.available_roles.map((role) => (
                                        <li
                                            key={role}
                                            data-audience={role}
                                            className="flex"
                                        >
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
                                                className="rz-app-card flex w-full flex-col rounded-[18px] border border-rz-hairline bg-rz-surface px-[18px] pt-[18px] pb-4 text-left disabled:cursor-not-allowed disabled:opacity-60"
                                            >
                                                <span className="flex items-center gap-[11px]">
                                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-rz-accent-tint text-rz-accent-text">
                                                        <RoleIcon
                                                            role={role}
                                                            className="size-[22px]"
                                                        />
                                                    </span>
                                                    <span className="text-base font-semibold text-rz-ink">
                                                        {t(
                                                            `suite.app.${role}.title`,
                                                        )}
                                                    </span>
                                                </span>
                                                <span className="mt-3 text-[12.5px] leading-normal text-rz-body">
                                                    {t(
                                                        `suite.app.${role}.description`,
                                                    )}
                                                </span>
                                                <span className="mt-3 text-[12.5px] font-semibold text-rz-accent-text">
                                                    {t('suite.app.open')}
                                                </span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                !canAdmin && (
                                    <BlockerCard code={identity.code} />
                                )
                            )}
                            {mfaRequired && (
                                <div
                                    role="status"
                                    className="mt-4 flex flex-col gap-3 text-sm"
                                >
                                    <p>{t('suite.mfa_required')}</p>
                                    <Link href={security()}>
                                        {t('suite.mfa_setup')}
                                    </Link>
                                </div>
                            )}
                            {canAdmin && (
                                <Link
                                    href={adminHome()}
                                    className="mt-4 inline-block rounded-[18px] border border-rz-hairline bg-rz-surface p-5 font-semibold text-rz-ink"
                                >
                                    {t('suite.admin')}
                                </Link>
                            )}
                            {opening && (
                                <p
                                    role="status"
                                    className="mt-4 text-sm text-rz-muted"
                                >
                                    {t('suite.opening')}
                                </p>
                            )}
                        </>
                    )}
                </section>
            </main>
        </div>
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
