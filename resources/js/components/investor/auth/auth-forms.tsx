import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import { ErrorBanner } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { InvestorAuthProps } from '@/types/investor';

type Variant = 'phone' | 'desk';

const FIELD: Record<Variant, string> = {
    phone: 'w-full rounded-xl border border-rz-border bg-rz-field px-3.5 py-[13px] text-sm text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border',
    desk: 'box-border w-full rounded-xl border border-rz-border bg-rz-surface-sunken px-3.5 py-[13px] text-sm text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border',
};

const LABEL: Record<Variant, string> = {
    phone: 'mb-1.5 block text-xs font-semibold text-rz-label uppercase',
    desk: 'mb-1.5 block text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase',
};

/**
 * Log in with an email or phone and a password or 4-digit PIN (phone L3489–3552, desk L3253–3265).
 * The design's biometric tab and demo accounts are prototype affordances and are not built.
 */
export function LoginFields({
    action,
    variant,
    status,
}: {
    action: InvestorAuthProps['actions']['login'];
    variant: Variant;
    status?: string;
}) {
    const { t } = useTranslation();
    const [secret, setSecret] = useState<'password' | 'pin'>('password');
    const phone = variant === 'phone';

    return (
        <Form
            action={action.url}
            method={action.method}
            resetOnSuccess={['password', 'pin']}
        >
            {({ processing, errors }) => {
                const failure = errors.login ?? errors.password ?? errors.pin;

                return (
                    <>
                        {phone && (
                            <div
                                role="tablist"
                                aria-label={t('investor.auth.login.method')}
                                className="mt-6 flex gap-1.5 rounded-xl border border-rz-border bg-[#f3f6fc] p-1 dark:bg-rz-surface-muted"
                            >
                                {(['password', 'pin'] as const).map((key) => (
                                    <button
                                        key={key}
                                        type="button"
                                        role="tab"
                                        aria-selected={secret === key}
                                        onClick={() => setSecret(key)}
                                        className={cn(
                                            'flex-1 rounded-[10px] py-2.5 text-[13px] font-semibold',
                                            secret === key
                                                ? 'bg-rz-accent-fill text-white'
                                                : 'text-rz-secondary',
                                        )}
                                    >
                                        {t(`investor.auth.login.tab_${key}`)}
                                    </button>
                                ))}
                            </div>
                        )}
                        <div
                            className={cn(
                                'flex flex-col gap-[13px]',
                                phone ? 'mt-5' : 'mt-4',
                            )}
                        >
                            <div>
                                <label
                                    htmlFor={`login-${variant}`}
                                    className={LABEL[variant]}
                                >
                                    {t('investor.auth.login.id')}
                                </label>
                                <input
                                    id={`login-${variant}`}
                                    name="login"
                                    autoComplete="username"
                                    required
                                    placeholder={t(
                                        'investor.auth.email_placeholder',
                                    )}
                                    aria-invalid={
                                        errors.login !== undefined || undefined
                                    }
                                    className={FIELD[variant]}
                                />
                            </div>
                            <div>
                                <div className="mb-1.5 flex items-center justify-between">
                                    <label
                                        htmlFor={`secret-${variant}`}
                                        className={cn(LABEL[variant], 'mb-0')}
                                    >
                                        {t(
                                            secret === 'password'
                                                ? 'investor.auth.login.password'
                                                : 'investor.auth.login.pin',
                                        )}
                                    </label>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setSecret(
                                                secret === 'password'
                                                    ? 'pin'
                                                    : 'password',
                                            )
                                        }
                                        className={cn(
                                            'font-semibold text-rz-accent-app-text',
                                            phone ? 'text-xs' : 'text-[11.5px]',
                                        )}
                                    >
                                        {t(
                                            secret === 'password'
                                                ? phone
                                                    ? 'investor.auth.login.use_pin'
                                                    : 'investor.auth.login.use_pin_short'
                                                : phone
                                                  ? 'investor.auth.login.use_password'
                                                  : 'investor.auth.login.use_password_short',
                                        )}
                                    </button>
                                </div>
                                {secret === 'password' ? (
                                    <input
                                        id={`secret-${variant}`}
                                        key="password"
                                        name="password"
                                        type="password"
                                        autoComplete="current-password"
                                        required
                                        placeholder="••••••••"
                                        className={FIELD[variant]}
                                    />
                                ) : (
                                    <input
                                        id={`secret-${variant}`}
                                        key="pin"
                                        name="pin"
                                        type="password"
                                        inputMode="numeric"
                                        maxLength={4}
                                        autoComplete="one-time-code"
                                        required
                                        placeholder="••••"
                                        className={cn(
                                            FIELD[variant],
                                            'text-lg tracking-[.5em]',
                                        )}
                                    />
                                )}
                            </div>
                            {secret === 'pin' && phone && (
                                <p className="text-xs text-rz-secondary">
                                    {t('investor.auth.login.pin_note')}
                                </p>
                            )}
                        </div>
                        {failure !== undefined && (
                            <div className="mt-3.5">
                                <ErrorBanner>{failure}</ErrorBanner>
                            </div>
                        )}
                        {status !== undefined && (
                            <p
                                role="status"
                                className="mt-3.5 text-[12.5px] font-semibold text-rz-accent-app-text"
                            >
                                {status}
                            </p>
                        )}
                        <button
                            type="submit"
                            disabled={processing}
                            aria-busy={processing || undefined}
                            className={cn(
                                'w-full rounded-2xl bg-rz-accent-fill font-semibold text-white disabled:opacity-70',
                                phone
                                    ? 'mt-5 h-[52px] text-[15px]'
                                    : 'mt-5 h-[50px] text-[15px]',
                            )}
                        >
                            {processing
                                ? t('investor.auth.please_wait')
                                : t('investor.auth.log_in')}
                        </button>
                    </>
                );
            }}
        </Form>
    );
}

/**
 * The wide-screen sign-up start (design L3268–3276): names, email and password, then the rest of
 * the sign-up steps. Institutions give an entity name and a representative.
 */
export function StartFields({
    action,
    investorType,
}: {
    action: InvestorAuthProps['actions']['start'];
    investorType: InvestorAuthProps['investor_type'];
}) {
    const { t } = useTranslation();
    const institution = investorType === 'institution';

    return (
        <Form action={action.url} method={action.method}>
            {({ processing, errors }) => {
                const failure = Object.values(errors)[0];

                return (
                    <>
                        <input
                            type="hidden"
                            name="investor_type"
                            value={investorType}
                        />
                        <div className="mt-4 flex flex-col gap-[13px]">
                            <div className="flex gap-[11px]">
                                <div className="min-w-0 flex-1">
                                    <label
                                        htmlFor="first_name"
                                        className={LABEL.desk}
                                    >
                                        {t(
                                            institution
                                                ? 'investor.auth.entity_name'
                                                : 'investor.auth.first_name',
                                        )}
                                    </label>
                                    <input
                                        id="first_name"
                                        name="first_name"
                                        required
                                        placeholder={t(
                                            institution
                                                ? 'investor.auth.entity_placeholder'
                                                : 'investor.auth.first_placeholder',
                                        )}
                                        className={FIELD.desk}
                                    />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <label
                                        htmlFor="last_name"
                                        className={LABEL.desk}
                                    >
                                        {t(
                                            institution
                                                ? 'investor.auth.representative'
                                                : 'investor.auth.last_name',
                                        )}
                                    </label>
                                    <input
                                        id="last_name"
                                        name="last_name"
                                        required
                                        placeholder={t(
                                            institution
                                                ? 'investor.auth.representative_placeholder'
                                                : 'investor.auth.last_placeholder',
                                        )}
                                        className={FIELD.desk}
                                    />
                                </div>
                            </div>
                            <div>
                                <label htmlFor="email" className={LABEL.desk}>
                                    {t('investor.auth.email')}
                                </label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    required
                                    placeholder={t(
                                        'investor.auth.email_placeholder',
                                    )}
                                    className={FIELD.desk}
                                />
                            </div>
                            <div>
                                <label
                                    htmlFor="password"
                                    className={LABEL.desk}
                                >
                                    {t('investor.auth.login.password')}
                                </label>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autoComplete="new-password"
                                    required
                                    placeholder={t(
                                        'investor.auth.password_placeholder',
                                    )}
                                    className={FIELD.desk}
                                />
                            </div>
                        </div>
                        {failure !== undefined && (
                            <div className="mt-[13px]">
                                <ErrorBanner>{failure}</ErrorBanner>
                            </div>
                        )}
                        <button
                            type="submit"
                            disabled={processing}
                            aria-busy={processing || undefined}
                            className="mt-5 h-[50px] w-full rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white disabled:opacity-70"
                        >
                            {processing
                                ? t('investor.auth.please_wait')
                                : t('investor.auth.continue')}
                        </button>
                    </>
                );
            }}
        </Form>
    );
}

/** "Already have an account? Log in" / "New here? Create an account". */
export function SwitchLine({
    prompt,
    action,
    href,
    className,
}: {
    prompt: string;
    action: string;
    href: InvestorAuthProps['links']['login'];
    className?: string;
}) {
    return (
        <p className={cn('text-center text-rz-secondary', className)}>
            {prompt}{' '}
            <Link href={href} className="font-semibold text-rz-accent-app-text">
                {action}
            </Link>
        </p>
    );
}
