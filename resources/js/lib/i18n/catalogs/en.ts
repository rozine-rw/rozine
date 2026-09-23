/**
 * The canonical catalog.
 *
 * English defines the message-code key set; every other locale is checked against it by
 * `scripts/quality/verify-i18n.mjs`. Codes are stable identifiers and must not be renamed when copy
 * changes — a reworded English string keeps its code so translations, tests, and server message
 * codes stay aligned.
 */
const en = {
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

    'suite.head_title': 'Your apps',
    'suite.tagline': 'The Retail Capital Markets Layer for Emerging Economies',
    'suite.motto': 'One live core · every surface',
    'suite.section.apps': 'Your apps',
    'suite.app.open': 'Open app →',
    'suite.app.investor.title': 'Investor',
    'suite.app.investor.description':
        'Discover verified businesses, invest, track returns.',
    'suite.app.business.title': 'Business',
    'suite.app.business.description':
        'Raise capital, report to investors monthly.',
    'suite.app.auditor.title': 'Auditor',
    'suite.app.auditor.description':
        'Field-verify on site, audit reports, earn yield.',
    'suite.blocker.action.verify_email': 'Verify your email →',
    'suite.blocker.action.contact': 'Contact Rozine support →',
    'suite.blocker.EMAIL_VERIFICATION_REQUIRED.title':
        'Verify your email first',
    'suite.blocker.EMAIL_VERIFICATION_REQUIRED.body':
        'Confirm the link we emailed you, then your apps appear here.',
    'suite.blocker.IDENTITY_NOT_LINKED.title':
        'Your identity is not set up yet',
    'suite.blocker.IDENTITY_NOT_LINKED.body':
        'Your sign-in is not linked to a verified identity, so no app can open yet.',
    'suite.blocker.IDENTITY_VERIFICATION_REQUIRED.title':
        'Your identity is being verified',
    'suite.blocker.IDENTITY_VERIFICATION_REQUIRED.body':
        'Your apps open once your identity verification is complete.',
    'suite.blocker.PARTY_AUTHORITY_REQUIRED.title':
        'Signing authority is needed',
    'suite.blocker.PARTY_AUTHORITY_REQUIRED.body':
        'This organisation account needs a verified signatory before any app can open.',
    'suite.blocker.ROLE_MEMBERSHIP_INVALID.title':
        'Your account needs attention',
    'suite.blocker.ROLE_MEMBERSHIP_INVALID.body':
        'One of your app memberships could not be read. Support can fix it for you.',
    'suite.blocker.ROLE_MEMBERSHIP_CONFLICT.title':
        'These apps cannot be combined',
    'suite.blocker.ROLE_MEMBERSHIP_CONFLICT.body':
        'An Audit Partner cannot also invest or raise on Rozine. Support will help you choose.',
    'suite.blocker.ROLE_MEMBERSHIP_REQUIRED.title': 'No apps yet',
    'suite.blocker.ROLE_MEMBERSHIP_REQUIRED.body':
        'You have not joined an app yet. Support can set up the one you need.',

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
