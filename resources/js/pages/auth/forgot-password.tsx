// Components
import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    const { t } = useTranslation();

    /*
     * The auth frame keeps the props an earlier page set (register or log in name their own), so
     * this page names its heading again: straight after signing up it must not still say so.
     */
    setLayoutProps({
        title: 'auth.forgot_password.layout_title',
        description: 'auth.forgot_password.layout_description',
        eyebrow: '',
        audience: null,
    });

    return (
        <>
            <Head title={t('auth.forgot_password.head_title')} />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <div className="space-y-6">
                <Form {...email.form()}>
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t('auth.forgot_password.email_label')}
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="off"
                                    autoFocus
                                    placeholder={t(
                                        'auth.forgot_password.email_placeholder',
                                    )}
                                />

                                <InputError message={errors.email} />
                            </div>

                            <div className="my-6 flex items-center justify-start">
                                <Button
                                    className="w-full"
                                    disabled={processing}
                                    data-test="email-password-reset-link-button"
                                >
                                    {processing && (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    )}
                                    {t('auth.forgot_password.submit')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>{t('auth.forgot_password.return_prefix')}</span>
                    <TextLink href={login()}>
                        {t('auth.forgot_password.return_link')}
                    </TextLink>
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: 'auth.forgot_password.layout_title',
    description: 'auth.forgot_password.layout_description',
};
