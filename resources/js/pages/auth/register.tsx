import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import { audienceQuery, authBranding } from '@/layouts/auth-layout';
import { login } from '@/routes';
import { store } from '@/routes/register';
import type { AuthAudience } from '@/types';

type Props = {
    passwordRules: string;
    /** The CMA sandbox notice, word for word, with the version and digest the acknowledgement binds. */
    sandboxNotice: { version: string; text: string; sha256: string };
    audience?: AuthAudience;
};

export default function Register({
    passwordRules,
    sandboxNotice,
    audience = null,
}: Props) {
    const { t } = useTranslation();
    const brand = authBranding(audience, 'register');
    /*
     * The sign-up form opens only once the sandbox notice is acknowledged (BRS KYC-R01). The
     * acknowledgement holds the digest of the notice read, so a newer notice the server sends back
     * (say after a refusal) is shown again before the form.
     */
    const [acknowledgedSha, setAcknowledgedSha] = useState<string | null>(null);
    const acknowledged = acknowledgedSha === sandboxNotice.sha256;

    setLayoutProps({
        title: brand.title,
        description: brand.description,
        eyebrow: brand.eyebrow ?? '',
        audience,
    });

    if (!acknowledged) {
        return (
            <>
                <Head title={t('auth.register.head_title')} />
                <section
                    aria-labelledby="sandbox-notice-title"
                    className="flex flex-col gap-4 rounded-xl border p-4"
                >
                    <h2
                        id="sandbox-notice-title"
                        className="text-base font-semibold"
                    >
                        {t('auth.register.sandbox.title')}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {sandboxNotice.text}
                    </p>
                    <Button
                        type="button"
                        className="w-full"
                        autoFocus
                        onClick={() => setAcknowledgedSha(sandboxNotice.sha256)}
                    >
                        {t('auth.register.sandbox.acknowledge')}
                    </Button>
                </section>
            </>
        );
    }

    return (
        <>
            <Head title={t('auth.register.head_title')} />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input
                            type="hidden"
                            name="sandbox_notice_version"
                            value={sandboxNotice.version}
                        />
                        <input
                            type="hidden"
                            name="sandbox_notice_sha256"
                            value={sandboxNotice.sha256}
                        />
                        <InputError
                            message={
                                errors.sandbox_notice_version ??
                                errors.sandbox_notice_sha256
                            }
                        />
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">
                                    {t('auth.register.name_label')}
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder={t(
                                        'auth.register.name_placeholder',
                                    )}
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t(brand.emailLabel)}
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    placeholder={t(brand.emailPlaceholder)}
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    {t('auth.register.password_label')}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder={t(
                                        'auth.register.password_placeholder',
                                    )}
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    {t('auth.register.confirm_label')}
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder={t(
                                        'auth.register.confirm_placeholder',
                                    )}
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={5}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                {t('auth.register.submit')}
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            {t(brand.switchText)}{' '}
                            <TextLink
                                href={login(audienceQuery(audience))}
                                tabIndex={6}
                            >
                                {t(brand.switchCta)}
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'auth.register.layout_title',
    description: 'auth.register.layout_description',
};
