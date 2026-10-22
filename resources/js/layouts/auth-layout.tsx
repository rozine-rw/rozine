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

/** The copy a shared log in or register page shows, by message code. */
export type AuthBranding = {
    eyebrow: MessageCode | null;
    title: MessageCode;
    description: MessageCode;
    emailLabel: MessageCode;
    emailPlaceholder: MessageCode;
    switchCta: MessageCode;
};

const GENERIC: Record<'login' | 'register', AuthBranding> = {
    login: {
        eyebrow: null,
        title: 'auth.login.layout_title',
        description: 'auth.login.layout_description',
        emailLabel: 'auth.login.email_label',
        emailPlaceholder: 'auth.login.email_placeholder',
        switchCta: 'auth.login.create_account',
    },
    register: {
        eyebrow: null,
        title: 'auth.register.layout_title',
        description: 'auth.register.layout_description',
        emailLabel: 'auth.register.email_label',
        emailPlaceholder: 'auth.register.email_placeholder',
        switchCta: 'auth.register.log_in',
    },
};

const BRANDED: Record<
    Exclude<AuthAudience, null>,
    Record<'login' | 'register', AuthBranding>
> = {
    business: {
        login: {
            eyebrow: 'auth.brand.business.eyebrow',
            title: 'auth.login.layout_title',
            description: 'auth.brand.business.login_description',
            emailLabel: 'auth.brand.business.email_label',
            emailPlaceholder: 'auth.brand.business.email_placeholder',
            switchCta: 'auth.brand.business.register_cta',
        },
        register: {
            eyebrow: 'auth.brand.business.eyebrow',
            title: 'auth.brand.business.register_title',
            description: 'auth.brand.business.register_description',
            emailLabel: 'auth.brand.business.email_label',
            emailPlaceholder: 'auth.brand.business.email_placeholder',
            switchCta: 'auth.register.log_in',
        },
    },
    investor: {
        login: {
            eyebrow: null,
            title: 'auth.login.layout_title',
            description: 'auth.brand.investor.login_description',
            emailLabel: 'auth.login.email_label',
            emailPlaceholder: 'auth.register.email_placeholder',
            switchCta: 'auth.brand.investor.register_cta',
        },
        register: {
            eyebrow: null,
            title: 'auth.register.layout_title',
            description: 'auth.brand.investor.register_description',
            emailLabel: 'auth.register.email_label',
            emailPlaceholder: 'auth.register.email_placeholder',
            switchCta: 'auth.register.log_in',
        },
    },
    auditor: {
        login: {
            eyebrow: 'auth.brand.auditor.eyebrow',
            title: 'auth.login.layout_title',
            description: 'auth.brand.auditor.login_description',
            emailLabel: 'auth.brand.business.email_label',
            emailPlaceholder: 'auth.brand.business.email_placeholder',
            switchCta: 'auth.login.create_account',
        },
        register: {
            eyebrow: 'auth.brand.auditor.eyebrow',
            title: 'auth.register.layout_title',
            description: 'auth.brand.auditor.register_description',
            emailLabel: 'auth.brand.business.email_label',
            emailPlaceholder: 'auth.brand.business.email_placeholder',
            switchCta: 'auth.register.log_in',
        },
    },
};

/**
 * The shared log in and register pages keep one form for every app; the entry link only picks the
 * frame's copy and accent (Business L1521–1562, L4060–4067; Investor L3651–3656, L10181–10191).
 */
export function authBranding(
    audience: AuthAudience,
    page: 'login' | 'register',
): AuthBranding {
    return audience === null ? GENERIC[page] : BRANDED[audience][page];
}

/** The `for` query that keeps the app's branding across the log in and register switch. */
export function audienceQuery(audience: AuthAudience): {
    query?: { for: Exclude<AuthAudience, null> };
} {
    return audience === null ? {} : { query: { for: audience } };
}
