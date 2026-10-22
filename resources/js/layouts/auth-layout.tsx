import NonLiveNotice from '@/components/non-live-notice';
import { useTranslation } from '@/hooks/use-translation';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';
import type { MessageCode } from '@/lib/i18n/types';
import type { AuthAudience } from '@/types';

/**
 * The shared auth pages' layout. Pages name their title and line by message code, so the frame
 * reads them in the visitor's language. Log in and register also name the app they are branded for.
 */
export default function AuthLayout({
    title = '',
    description = '',
    eyebrow = '',
    audience = null,
    children,
}: {
    title?: string;
    description?: string;
    eyebrow?: string;
    audience?: AuthAudience;
    children: React.ReactNode;
}) {
    const { t } = useTranslation();

    return (
        <>
            <NonLiveNotice />
            <AuthLayoutTemplate
                title={title === '' ? '' : t(title as MessageCode)}
                description={
                    description === '' ? '' : t(description as MessageCode)
                }
                eyebrow={eyebrow === '' ? '' : t(eyebrow as MessageCode)}
                audience={audience}
            >
                {children}
            </AuthLayoutTemplate>
        </>
    );
}
