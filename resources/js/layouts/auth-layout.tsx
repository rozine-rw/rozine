import NonLiveNotice from '@/components/non-live-notice';
import { useTranslation } from '@/hooks/use-translation';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';
import type { MessageCode } from '@/lib/i18n/types';

/**
 * The shared auth pages' layout. Pages name their title and line by message code, so the frame
 * reads them in the visitor's language.
 */
export default function AuthLayout({
    title = '',
    description = '',
    children,
}: {
    title?: string;
    description?: string;
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
            >
                {children}
            </AuthLayoutTemplate>
        </>
    );
}
