/**
 * The canonical catalog.
 *
 * English defines the message-code key set; every other locale is checked against it by
 * `scripts/quality/verify-i18n.mjs`. Codes are stable identifiers and must not be renamed when copy
 * changes — a reworded English string keeps its code so translations, tests, and server message
 * codes stay aligned.
 */
const en = {
    'identity.home.investor': 'Investor workspace',
    'identity.home.business': 'Business workspace',
    'identity.home.auditor': 'Auditor workspace',
    'identity.home.admin': 'Staff workspace',
    'identity.home.overview': 'Overview',
    'identity.home.access': 'Account access',
    'identity.home.ready': 'Your account has access to this workspace.',
    'identity.home.verified': 'Your identity is verified.',
    'identity.home.staff_ready':
        'Your staff account has permission to open this workspace.',
    'identity.home.back': 'Choose an app',
    'identity.home.settings': 'Account settings',
    'identity.home.saving': 'Saving your position…',
    'identity.home.failed':
        'We could not update your position. Return to the launcher to check your access.',
    'identity.denied.title': 'Access needs to be checked',
    'identity.denied.body':
        'Your account can no longer open this page in the selected role. Choose an app to refresh your access.',
    'environment.demo': 'Demo — not live',
    'environment.uat': 'UAT — not live',
    'environment.synthetic_only':
        'Use synthetic data only. No real-money transactions.',
    'common.brand.name': 'Rozine',
    'common.action.log_in': 'Log in',
    'common.action.sign_up': 'Sign up',

    'auth.login.head_title': 'Log in',
    'auth.login.layout_title': 'Log in to your account',
    'auth.login.layout_description':
        'Enter your email and password below to log in',
    'auth.login.email_label': 'Email address',
    'auth.login.email_placeholder': 'email@example.com',
    'auth.login.password_label': 'Password',
    'auth.login.password_placeholder': 'Password',
    'auth.login.forgot_password': 'Forgot your password?',
    'auth.login.remember_me': 'Remember me',
    'auth.login.no_account': "Don't have an account?",

    'auth.forgot_password.head_title': 'Forgot password',
    'auth.forgot_password.layout_title': 'Forgot password',
    'auth.forgot_password.layout_description':
        'Enter your email to receive a password reset link',
    'auth.forgot_password.email_label': 'Email address',
    'auth.forgot_password.email_placeholder': 'email@example.com',
    'auth.forgot_password.submit': 'Email password reset link',
    'auth.forgot_password.return_prefix': 'Or, return to',
    'auth.forgot_password.return_link': 'log in',

    /**
     * Plural example. Selection uses Intl.PluralRules for the active locale, so a locale needing
     * more categories than English simply declares them.
     */
    'auth.two_factor.recovery_codes_remaining': {
        one: '{count} recovery code remaining',
        other: '{count} recovery codes remaining',
    },
} as const;

export default en;
