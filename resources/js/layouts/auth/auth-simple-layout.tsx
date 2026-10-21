import { Link } from '@inertiajs/react';
import { IconGradients } from '@/components/rozine/icon';
import { LogoMark } from '@/components/rozine/logo';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/**
 * The form controls inside the branded auth column, drawn as the design's sign-in fields (Investor
 * L3651–3709): uppercase field labels, 12px-radius fields, a full-width 52px primary button and
 * accent links. Checkbox labels keep their sentence case.
 */
const FORM_SKIN = [
    '[&_label]:text-xs [&_label]:font-semibold [&_label]:text-rz-label [&_label]:uppercase',
    '[&_label[for=remember]]:text-[13px] [&_label[for=remember]]:font-medium [&_label[for=remember]]:normal-case [&_label[for=remember]]:text-rz-secondary',
    '[&_input:not([type=checkbox])]:h-auto [&_input:not([type=checkbox])]:rounded-xl [&_input:not([type=checkbox])]:border-rz-border [&_input:not([type=checkbox])]:bg-rz-field [&_input:not([type=checkbox])]:px-3.5 [&_input:not([type=checkbox])]:py-[13px] [&_input:not([type=checkbox])]:text-sm [&_input:not([type=checkbox])]:text-rz-ink [&_input:not([type=checkbox])]:shadow-none',
    '[&_button[type=submit]]:h-[52px] [&_button[type=submit]]:rounded-2xl [&_button[type=submit]]:bg-rz-accent-fill [&_button[type=submit]]:text-[15px] [&_button[type=submit]]:font-semibold [&_button[type=submit]]:text-white',
    '[&_a]:font-semibold [&_a]:text-rz-accent-app-text [&_a]:no-underline',
].join(' ');

/**
 * The signed-out surface every shared auth page sits in (log in, register, password reset, email
 * verification, two-factor and password confirmation): the Rozine star centred over the title and
 * its line, then the form. A phone fills the screen; a wide screen centres the same column as a
 * card on the console frame.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div
            data-audience="investor"
            className="rz-surface flex min-h-svh flex-col bg-rz-surface lg:items-center lg:justify-center lg:bg-rz-frame lg:p-10"
        >
            <IconGradients />
            <div className="mx-auto flex w-full max-w-[412px] flex-1 flex-col px-6 pt-[calc(env(safe-area-inset-top)+56px)] pb-[calc(env(safe-area-inset-bottom)+30px)] lg:max-w-[440px] lg:flex-none lg:rounded-[20px] lg:border lg:border-rz-border lg:bg-rz-surface lg:px-10 lg:pt-10 lg:pb-9 lg:shadow-[0_24px_60px_-28px_rgba(20,45,95,.35)]">
                <div className="flex flex-col items-center text-center">
                    <Link href={home()} className="flex">
                        <LogoMark className="h-[56px] w-[54px] text-rz-accent-fill" />
                        <span className="sr-only">{title}</span>
                    </Link>
                    <h1 className="mt-3.5 text-[22px] font-semibold text-rz-ink">
                        {title}
                    </h1>
                    <p className="mt-[5px] text-[13.5px] leading-normal text-rz-secondary">
                        {description}
                    </p>
                </div>
                <div className={`mt-6 ${FORM_SKIN}`}>{children}</div>
            </div>
        </div>
    );
}
