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

    'business.auth.wordmark': 'rozine',
    'business.auth.for_business': 'For business',
    'business.auth.rdb_verified': 'RDB verified',
    'business.auth.secure': 'Secure',
    'business.auth.please_wait': 'Please wait…',
    'business.auth.login.head_title': 'Business log in',
    'business.auth.login.title': 'Welcome back',
    'business.auth.login.subtitle': 'Log in to your issuer dashboard.',
    'business.auth.login.email_label': 'Work email',
    'business.auth.login.email_placeholder': 'you@company.rw',
    'business.auth.login.password_label': 'Password',
    'business.auth.login.password_placeholder': '••••••••',
    'business.auth.login.submit': 'Log in',
    'business.auth.login.switch_prompt': 'New to Rozine?',
    'business.auth.login.switch_action': 'Register a business',
    'business.auth.register.head_title': 'Register your business',
    'business.auth.register.title': 'Register your business',
    'business.auth.register.subtitle':
        'Verify your company with the Rwanda Development Board.',
    'business.auth.register.code_label': 'RDB company code',
    'business.auth.register.code_placeholder': '102938475',
    'business.auth.register.rdb_note':
        'We verify your company directly with the Rwanda Development Board — no personal login required.',
    'business.auth.register.matched': '✓ Company matched at RDB',
    'business.auth.register.name_label': 'Registered business name',
    'business.auth.register.name_placeholder': 'e.g. Karongi Freight Ltd',
    'business.auth.register.code_sent':
        'Enter a 6-digit code sent to the phone and email registered for this business at RDB.',
    'business.auth.register.otp_label': 'One-time code',
    'business.auth.register.otp_placeholder': '6-digit code',
    'business.auth.register.resend': 'Resend code',
    'business.auth.register.verify_company': 'Verify company code',
    'business.auth.register.verifying_company': 'Verifying with RDB…',
    'business.auth.register.verify_code': 'Verify & continue',
    'business.auth.register.verifying_code': 'Verifying code…',
    'business.auth.register.switch_prompt': 'Already registered?',
    'business.auth.register.switch_action': 'Log in',
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
