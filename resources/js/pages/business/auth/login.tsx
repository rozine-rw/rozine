import { Form, Link } from '@inertiajs/react';
import { BusinessAuthFrame } from '@/components/business/business-auth-frame';
import {
    ErrorBanner,
    FieldLabel,
    PrimaryButton,
    TextField,
} from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { store } from '@/routes/login';
import type { RouteLink } from '@/types';

type BusinessLoginProps = {
    status?: string;
    links: { register: RouteLink };
};

/** "Welcome back" — the Business app sign-in (design L1534–1537), backed by the shared Fortify login. */
export default function BusinessLogin({ status, links }: BusinessLoginProps) {
    const { t } = useTranslation();

    return (
        <BusinessAuthFrame
            headTitle={t('business.auth.login.head_title')}
            title={t('business.auth.login.title')}
            subtitle={t('business.auth.login.subtitle')}
        >
            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col"
            >
                {({ processing, errors }) => {
                    const failure = errors.email ?? errors.password;

                    return (
                        <>
                            <div className="mt-6 flex flex-col gap-[13px]">
                                <div>
                                    <FieldLabel htmlFor="email">
                                        {t('business.auth.login.email_label')}
                                    </FieldLabel>
                                    <TextField
                                        id="email"
                                        name="email"
                                        type="email"
                                        inputMode="email"
                                        autoComplete="email"
                                        required
                                        placeholder={t(
                                            'business.auth.login.email_placeholder',
                                        )}
                                        invalid={errors.email !== undefined}
                                    />
                                </div>
                                <div>
                                    <FieldLabel htmlFor="password">
                                        {t(
                                            'business.auth.login.password_label',
                                        )}
                                    </FieldLabel>
                                    <TextField
                                        id="password"
                                        name="password"
                                        type="password"
                                        autoComplete="current-password"
                                        required
                                        placeholder={t(
                                            'business.auth.login.password_placeholder',
                                        )}
                                        invalid={errors.password !== undefined}
                                    />
                                </div>
                            </div>

                            {failure && (
                                <div className="mt-[13px]">
                                    <ErrorBanner>{failure}</ErrorBanner>
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

                            <div className="mt-[22px]">
                                <PrimaryButton busy={processing}>
                                    {processing
                                        ? t('business.auth.please_wait')
                                        : t('business.auth.login.submit')}
                                </PrimaryButton>
                            </div>
                            <p className="mt-[18px] text-center text-[13px] text-rz-secondary">
                                {t('business.auth.login.switch_prompt')}{' '}
                                <Link
                                    href={links.register}
                                    className="font-semibold text-rz-accent-app-text"
                                >
                                    {t('business.auth.login.switch_action')}
                                </Link>
                            </p>
                        </>
                    );
                }}
            </Form>
        </BusinessAuthFrame>
    );
}
