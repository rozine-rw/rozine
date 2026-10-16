// Components
import { Form, Head } from '@inertiajs/react';
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
import { logout } from '@/routes';
import { code as verifyCode, send } from '@/routes/verification';

/** The email verification code is six digits, as the onboarding email shows it. */
const CODE_LENGTH = 6;

export default function VerifyEmail({ status }: { status?: string }) {
    const [code, setCode] = useState<string>('');

    return (
        <>
            <Head title="Email verification" />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    A new code has been sent to the email address you signed up
                    with. Earlier codes no longer work.
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
                            Verify email
                        </Button>
                    </div>
                )}
            </Form>

            <Form {...send.form()} className="mt-6 space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            Send a new code
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            Log out
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Verify your email',
    description:
        'Enter the 6-digit code we emailed you. It expires in 10 minutes.',
};
