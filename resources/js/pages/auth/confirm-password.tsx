import { Form, Head, Link, setLayoutProps, usePage } from '@inertiajs/react';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import { store } from '@/routes/password/confirm';
import { edit as profileSettings } from '@/routes/profile';
import type { RouteLink } from '@/types/routing';

export default function ConfirmPassword() {
    const { t } = useTranslation();
    /** Staff are sent their way back to the console (#623); everyone else returns to settings. */
    const consoleLink = usePage<{ staff_console?: RouteLink | null }>().props
        .staff_console;

    /*
     * The auth frame keeps the props an earlier page set (register or log in name their own), so
     * this page names its heading again: straight after signing up it must not still say so.
     */
    setLayoutProps({
        title: 'auth.confirm_password.layout_title',
        description: 'auth.confirm_password.layout_description',
        eyebrow: '',
        audience: null,
    });

    return (
        <>
            <Head title={t('auth.confirm_password.head_title')} />

            <PasskeyVerify
                routes={{
                    options: confirmOptions(),
                    submit: confirmStore(),
                }}
                label={t('auth.confirm_password.passkey')}
                loadingLabel={t('auth.confirm_password.passkey_loading')}
                separator={t('auth.confirm_password.separator')}
            />

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t('auth.confirm_password.password_label')}
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder={t(
                                    'auth.confirm_password.password_placeholder',
                                )}
                                autoComplete="current-password"
                                autoFocus
                            />

                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center">
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                                data-test="confirm-password-button"
                            >
                                {processing && <Spinner />}
                                {t('auth.confirm_password.submit')}
                            </Button>
                        </div>
                    </div>
                )}
            </Form>

            <div className="mt-4 text-center text-sm">
                <Link
                    href={consoleLink ?? profileSettings()}
                    className="font-semibold text-rz-accent-text underline-offset-4 hover:underline"
                >
                    {consoleLink
                        ? t('settings.back_to_console')
                        : t('auth.confirm_password.cancel')}
                </Link>
            </div>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'auth.confirm_password.layout_title',
    description: 'auth.confirm_password.layout_description',
};
