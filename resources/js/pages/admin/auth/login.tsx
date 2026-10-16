import { Form, Head, Link } from '@inertiajs/react';
import { Icon, IconGradients } from '@/components/rozine/icon';
import { LogoLockup } from '@/components/rozine/logo';
import { useTranslation } from '@/hooks/use-translation';
import { store } from '@/routes/login';
import type { RouteLink } from '@/types';

type AdminLoginProps = {
    status?: string;
    links: { launcher: RouteLink };
};

const FIELD =
    'w-full rounded-[11px] border border-rz-hairline bg-rz-surface px-3.5 py-3 text-[14px] text-rz-ink outline-none placeholder:text-rz-muted focus:border-rz-focus-border focus:shadow-[0_0_0_3.5px_var(--rz-focus-ring)] aria-invalid:border-rz-danger';

const LABEL =
    'mb-1.5 block text-[12px] font-semibold text-[#7b8699] uppercase dark:text-rz-muted';

/**
 * The console sign-in (design T62–84): staff only, backed by the shared Fortify login. The
 * server decides whether the signed-in account holds a staff role; this page only collects the
 * credentials.
 */
export default function AdminLogin({ status, links }: AdminLoginProps) {
    const { t } = useTranslation();

    return (
        <div
            data-audience="admin"
            className="rz-surface flex min-h-svh items-center justify-center bg-[radial-gradient(900px_600px_at_50%_-10%,var(--rz-launcher-glow)_0%,var(--rz-page)_45%,var(--rz-launcher-end)_100%)] p-6"
        >
            <Head title={t('admin.auth.head_title')} />
            <IconGradients />
            <div className="w-full max-w-[400px]">
                <div className="flex justify-center">
                    <LogoLockup
                        title={t('common.brand.name')}
                        className="block h-auto w-[119px] text-rz-accent-lockup"
                    />
                </div>
                <div className="mt-[30px] rounded-[18px] border border-rz-hairline bg-[#eef3fb] p-7 dark:bg-rz-surface">
                    <h1 className="text-[20px] font-semibold text-rz-ink">
                        {t('admin.auth.title')}
                    </h1>
                    <p className="mt-[5px] text-[13px] text-[#7b8699] dark:text-rz-muted">
                        {t('admin.auth.subtitle')}
                    </p>
                    <Form {...store.form()} resetOnSuccess={['password']}>
                        {({ processing, errors }) => {
                            const failure = errors.email ?? errors.password;

                            return (
                                <>
                                    <div className="mt-5">
                                        <label
                                            htmlFor="email"
                                            className={LABEL}
                                        >
                                            {t('admin.auth.email_label')}
                                        </label>
                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            inputMode="email"
                                            autoComplete="username"
                                            required
                                            placeholder={t(
                                                'admin.auth.email_placeholder',
                                            )}
                                            aria-invalid={
                                                errors.email !== undefined ||
                                                undefined
                                            }
                                            className={FIELD}
                                        />
                                    </div>
                                    <div className="mt-3.5">
                                        <label
                                            htmlFor="password"
                                            className={LABEL}
                                        >
                                            {t('admin.auth.password_label')}
                                        </label>
                                        <input
                                            id="password"
                                            name="password"
                                            type="password"
                                            autoComplete="current-password"
                                            required
                                            placeholder={t(
                                                'admin.auth.password_placeholder',
                                            )}
                                            aria-invalid={
                                                errors.password !== undefined ||
                                                undefined
                                            }
                                            className={FIELD}
                                        />
                                    </div>
                                    {failure && (
                                        <div
                                            role="alert"
                                            className="mt-[13px] flex items-center gap-2 rounded-[10px] border border-[#fdeaea] bg-[rgba(255,77,79,.08)] px-3 py-2.5 text-[12.5px] font-semibold text-[#e5484d] dark:border-[rgba(255,107,111,.25)] dark:text-[#ff8285]"
                                        >
                                            <Icon name="warning" tone="red" />
                                            {failure}
                                        </div>
                                    )}
                                    {status && (
                                        <p
                                            role="status"
                                            className="mt-[13px] text-[12.5px] font-semibold text-rz-accent-app-text"
                                        >
                                            {status}
                                        </p>
                                    )}
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        aria-busy={processing || undefined}
                                        className="mt-5 h-12 w-full rounded-xl bg-rz-accent-fill text-[14.5px] font-semibold text-white disabled:opacity-80"
                                    >
                                        {processing
                                            ? t('admin.auth.submitting')
                                            : t('admin.auth.submit')}
                                    </button>
                                </>
                            );
                        }}
                    </Form>
                </div>
                <div className="mt-4 text-center">
                    <Link
                        href={links.launcher}
                        className="text-[13px] font-semibold text-rz-muted"
                    >
                        {t('admin.auth.back')}
                    </Link>
                </div>
            </div>
        </div>
    );
}
