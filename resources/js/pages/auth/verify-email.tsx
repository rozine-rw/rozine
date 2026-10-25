// Components
import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useState } from 'react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import { logout } from '@/routes';
import { code as verifyCode, send } from '@/routes/verification';

/** The email verification code is six digits, as the onboarding email shows it. */
const CODE_LENGTH = 6;

export default function VerifyEmail({ status }: { status?: string }) {
    const { t } = useTranslation();

    /*
     * The auth frame keeps the props an earlier page set (register or log in name their own), so
     * this page names its heading again: straight after signing up it must not still say so.
     */
    setLayoutProps({
        title: 'auth.verify_email.layout_title',
        description: 'auth.verify_email.layout_description',
        eyebrow: '',
        audience: null,
    });
    const [code, setCode] = useState<string>('');

    return (
        <>
            <Head title={t('auth.verify_email.head_title')} />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {t('auth.verify_email.resent')}
                </div>
            )}

            <Form {...verifyCode.form()} className="space-y-4">
                {({ errors, processing }) => (
                    <div className="flex flex-col items-center gap-3 text-center">
                        <InputOTP
                            name="code"
                            maxLength={CODE_LENGTH}
                            value={code}
                            onChange={(value) => setCode(value)}
                            disabled={processing}
                            pattern={REGEXP_ONLY_DIGITS}
                            autoFocus
                        >
                            <InputOTPGroup>
                                {Array.from(
                                    { length: CODE_LENGTH },
                                    (_, index) => (
                                        <InputOTPSlot
                                            key={index}
                                            index={index}
                                        />
                                    ),
                                )}
                            </InputOTPGroup>
                        </InputOTP>
                        <InputError message={errors.code} />

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing || code.length < CODE_LENGTH}
                        >
                            {processing && <Spinner />}
                            {t('auth.verify_email.submit')}
                        </Button>
                    </div>
                )}
            </Form>

            <Form {...send.form()} className="mt-6 space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            {t('auth.verify_email.resend')}
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            {t('auth.verify_email.log_out')}
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'auth.verify_email.layout_title',
    description: 'auth.verify_email.layout_description',
};
