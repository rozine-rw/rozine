/**
 * The canonical catalog.
 *
 * English defines the message-code key set; every other locale is checked against it by
 * `scripts/quality/verify-i18n.mjs`. Codes are stable identifiers and must not be renamed when copy
 * changes — a reworded English string keeps its code so translations, tests, and server message
 * codes stay aligned.
 */
const en = {
    'suite.sign_out': 'Sign out',
    'site.app_entry.label': 'Rozine apps',
    'site.app_entry.sign_in': 'Sign in',
    'site.app_entry.create_account': 'Create account',
    'site.app_entry.open_app': 'Open the app',
    'suite.preview': 'Preview with sample data',
    'suite.mfa_required':
        'Set up two-factor authentication to open the Auditor app.',
    'suite.mfa_setup': 'Set up two-factor authentication',
    'suite.network_error':
        'We could not confirm the switch. Check your connection and retry.',
    'suite.command_conflict':
        'This request could not be reused. Refresh your access before choosing an app again.',
    'suite.access_changed':
        'Your access or active app has changed. Refresh your access, then choose an app again.',
    'suite.retry': 'Retry switch',
    'suite.refresh_access': 'Refresh access',
    'suite.admin': 'Open staff workspace',
    'suite.opening': 'Opening your app…',
    'identity.home.back': 'Choose an app',
    'identity.denied.title': 'Access needs to be checked',
    'identity.denied.body':
        'Your account can no longer open this page in the selected role. Choose an app to refresh your access.',
    'identity.denied.role_not_available.title':
        "This app isn't on your account",
    'identity.denied.role_not_available.body':
        "Your account doesn't have access to this app yet. Choose an app, or request access from your apps.",
    'identity.denied.staff_only.title': 'This page is for Rozine staff',
    'identity.denied.staff_only.body':
        'Only Rozine staff can open this page. Choose an app to go back to your own work.',
    'identity.denied.verification_pending.title':
        'Your identity check is under review',
    'identity.denied.verification_pending.body':
        'Our Compliance team is checking the details you sent. This page opens once your identity is confirmed.',
    'identity.denied.verification_required.title':
        'Identity verification required',
    'identity.denied.verification_required.body':
        'Your identity needs to be verified before you can open this page. Choose an app to continue.',
    'identity.denied.verification_required.action': 'Verify your identity',
    'identity.denied.expired_offer.title': 'This offer has closed',
    'identity.denied.expired_offer.body':
        'The time to accept this job ran out, so it is no longer open to you. Choose an app to see your current work.',
    'identity.denied.withdrawn_audit.title':
        'This audit was withdrawn by Rozine',
    'identity.denied.withdrawn_audit.body':
        'Audit Operations withdrew this audit, so it is no longer yours to work on and nothing more is needed from you. Your other jobs are in Jobs.',
    'identity.denied.withdrawn_audit.action': 'Go to Jobs',
    'identity.denied.completed_review.title':
        'You have verified these statements',
    'identity.denied.completed_review.body':
        'This statement review is complete. Your verification is on record and nothing more is needed from you. Your other jobs are in Jobs.',
    'errors.not_found.head_title': 'Not found',
    'errors.not_found.title': "We couldn't find that page",
    'errors.not_found.body':
        'The link may be mistyped, or what it pointed to is no longer available. Check the link, or start again from Rozine.',
    'errors.not_found.home': 'Go to the Rozine home page',
    'errors.unavailable.head_title': 'Temporarily unavailable',
    'errors.unavailable.title': "Rozine couldn't load this page",
    'errors.unavailable.body':
        'Something went wrong on our side. Anything you already sent is unaffected. Wait a moment, then try again.',
    'errors.unavailable.retry': 'Try again',
    'environment.demo': 'Demo — not live',
    'environment.uat': 'UAT — not live',
    'environment.synthetic_only':
        'Use synthetic data only. No real-money transactions.',
    'common.brand.name': 'Rozine',
    'common.action.log_in': 'Log in',
    'common.action.sign_up': 'Sign up',

    'auth.login.head_title': 'Log in',
    'auth.login.layout_title': 'Welcome back',
    'auth.login.layout_description': 'Log in to your Rozine account.',
    'auth.login.email_label': 'Email address',
    'auth.login.email_placeholder': 'email@example.com',
    'auth.login.password_label': 'Password',
    'auth.login.password_placeholder': 'Password',
    'auth.login.forgot_password': 'Forgot your password?',
    'auth.login.remember_me': 'Remember me',
    'auth.login.no_account': 'New to Rozine?',

    'auth.forgot_password.head_title': 'Forgot password',
    'auth.forgot_password.layout_title': 'Forgot password',
    'auth.forgot_password.layout_description':
        'Enter your email to receive a password reset link',
    'auth.forgot_password.email_label': 'Email address',
    'auth.forgot_password.email_placeholder': 'email@example.com',
    'auth.forgot_password.submit': 'Email password reset link',
    'auth.forgot_password.return_prefix': 'Or, return to',
    'auth.forgot_password.return_link': 'log in',
    'auth.login.create_account': 'Create account',
    'auth.register.head_title': 'Register',
    'auth.register.layout_title': 'Create your account',
    'auth.register.layout_description':
        'Enter your details below to create your account.',
    'auth.register.name_label': 'Name',
    'auth.register.name_placeholder': 'Full name',
    'auth.register.email_label': 'Email address',
    'auth.register.email_placeholder': 'you@example.rw',
    'auth.register.password_label': 'Password',
    'auth.register.password_placeholder': 'Password',
    'auth.register.confirm_label': 'Confirm password',
    'auth.register.confirm_placeholder': 'Confirm password',
    'auth.register.submit': 'Create account',
    'auth.register.sandbox.title': 'Before you sign up',
    'auth.register.sandbox.acknowledge': 'I understand, continue',
    'auth.register.have_account': 'Already have an account?',
    'auth.register.log_in': 'Log in',
    'auth.brand.wordmark': 'rozine',
    'auth.brand.secure': 'Secure',
    'auth.brand.business.have_account': 'Already registered?',
    'auth.brand.business.eyebrow': 'For business',
    'auth.brand.business.login_description': 'Log in to your issuer dashboard.',
    'auth.brand.business.email_label': 'Work email',
    'auth.brand.business.email_placeholder': 'you@company.rw',
    'auth.brand.business.register_cta': 'Register a business',
    'auth.brand.business.register_title': 'Register your business',
    'auth.brand.business.register_description':
        'Create the account your business will apply and raise with.',
    'auth.brand.investor.login_description': 'Log in to manage your portfolio.',
    'auth.brand.investor.register_description':
        'Start investing in verified businesses across Rwanda.',
    'auth.brand.auditor.eyebrow': 'For audit partners',
    'auth.brand.auditor.login_description': 'Log in to your audit desk.',
    'auth.brand.auditor.register_description':
        'Create the account you will take audit jobs with.',
    'auth.reset_password.head_title': 'Reset password',
    'auth.reset_password.layout_title': 'Reset password',
    'auth.reset_password.layout_description':
        'Choose a new password for your account.',
    'auth.reset_password.email_label': 'Email',
    'auth.reset_password.password_label': 'Password',
    'auth.reset_password.password_placeholder': 'Password',
    'auth.reset_password.confirm_label': 'Confirm password',
    'auth.reset_password.confirm_placeholder': 'Confirm password',
    'auth.reset_password.submit': 'Reset password',
    'auth.verify_email.head_title': 'Email verification',
    'auth.verify_email.layout_title': 'Verify your email',
    'auth.verify_email.layout_description':
        'Enter the 6-digit code we emailed you. It expires in 10 minutes.',
    'auth.verify_email.resent':
        'A new code has been sent to the email address you signed up with. Earlier codes no longer work.',
    'auth.verify_email.submit': 'Verify email',
    'auth.verify_email.resend': 'Send a new code',
    'auth.verify_email.log_out': 'Log out',
    'auth.confirm_password.head_title': 'Confirm password',
    'auth.confirm_password.layout_title': 'Confirm password',
    'auth.confirm_password.layout_description':
        'This is a secure area. Please confirm your password before continuing.',
    'auth.confirm_password.passkey': 'Confirm with passkey',
    'auth.confirm_password.passkey_loading': 'Confirming…',
    'auth.confirm_password.separator': 'Or confirm with password',
    'auth.confirm_password.password_label': 'Password',
    'auth.confirm_password.password_placeholder': 'Password',
    'auth.confirm_password.submit': 'Confirm password',
    'auth.two_factor_challenge.head_title': 'Two-factor authentication',
    'auth.two_factor_challenge.code_title': 'Authentication code',
    'auth.two_factor_challenge.code_description':
        'Enter the authentication code provided by your authenticator application.',
    'auth.two_factor_challenge.recovery_title': 'Recovery code',
    'auth.two_factor_challenge.recovery_description':
        'Please confirm access to your account by entering one of your emergency recovery codes.',
    'auth.two_factor_challenge.recovery_placeholder': 'Enter recovery code',
    'auth.two_factor_challenge.submit': 'Continue',
    'auth.two_factor_challenge.toggle_prefix': 'or you can',
    'auth.two_factor_challenge.use_recovery': 'login using a recovery code',
    'auth.two_factor_challenge.use_code': 'login using an authentication code',

    'suite.head_title': 'Your apps',
    'suite.tagline': 'The Retail Capital Markets Layer for Emerging Economies',
    'suite.motto': 'One live core · every surface',
    'suite.section.apps': 'Your apps',
    'suite.app.open': 'Open app →',
    'suite.app.browse': 'Browse deals →',
    'suite.app.request_access': 'Request access →',
    'suite.app.add_access': 'Start investing →',
    'suite.app.investor.add':
        'Add Investor access to your verified identity. No second identity check.',
    'suite.add_investor.title': 'Add Investor access',
    'suite.add_investor.body':
        'Your identity is already verified for your Business account, so you will not be asked to verify it again. Read and accept the Investor terms to start investing.',
    'suite.add_investor.accept':
        'Continuing means you agree to these Investor terms.',
    'suite.add_investor.confirm': 'Add Investor access',
    'suite.add_investor.cancel': 'Cancel',
    'suite.add_investor.network_error':
        'We could not add Investor access. Check your connection and try again.',
    'suite.add_investor.terms_changed':
        'The Investor terms have changed. Refresh your access and read them again.',
    'suite.add_investor.unavailable':
        "Investor access can't be added to this account here. Refresh your access, or contact Rozine.",
    'suite.app.investor.preview':
        'Browse open deals now. Investing opens once your identity is verified.',
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
    'suite.blocker.action.verify_identity': 'Verify your identity →',
    'suite.blocker.action.contact': 'Contact Rozine support →',
    'suite.blocker.EMAIL_VERIFICATION_REQUIRED.title':
        'Verify your email first',
    'suite.blocker.EMAIL_VERIFICATION_REQUIRED.body':
        'Confirm the link we emailed you, then your apps appear here.',
    'suite.blocker.IDENTITY_NOT_LINKED.title':
        'Your identity is not set up yet',
    'suite.blocker.IDENTITY_NOT_LINKED.body':
        'Your sign-in is not linked to a verified identity, so no app can open yet.',
    'suite.blocker.IDENTITY_VERIFICATION_PENDING.body':
        "Your identity check is with our Compliance team. You can invest once it's confirmed.",
    'suite.blocker.IDENTITY_VERIFICATION_REQUIRED.title': 'Ready to invest?',
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
    'common.currency.rwf': 'RWF',
    'app.nav.label': 'App navigation',
    'app.nav.launcher': 'Launcher',
    'app.read_failure.server':
        "Rozine couldn't load this just now. Anything you already sent is unaffected.",
    'app.read_failure.network':
        "Couldn't reach Rozine. Check your connection, then try again.",
    'app.read_failure.retry': 'Try again',
    'app.connectivity.offline':
        "You're offline. What you see may be out of date, and nothing can be sent until you're back online.",
    'business.nav.home': 'Home',
    'business.nav.reports': 'Reports',
    'business.nav.readiness': 'Readiness',
    'business.nav.market': 'Market',
    'business.nav.profile': 'Profile',
    'business.readiness.title': 'Readiness',
    'business.readiness.subtitle':
        'How your business grows toward the capital market, in step with its size. Only you see this page.',
    'business.readiness.stage.0': 'Below Foundation',
    'business.readiness.stage.1': 'Stage 1 · Foundation',
    'business.readiness.stage.2': 'Stage 2 · Growth',
    'business.readiness.stage.3': 'Stage 3 · Scale',
    'business.readiness.stage.4': 'Stage 4 · Market',
    'business.readiness.stage_pending': 'Stage not set yet',
    'business.readiness.status.building': 'Building',
    'business.readiness.status.ready': 'Ready',
    'business.readiness.vmr': 'Verified monthly revenue',
    'business.readiness.vmr_basis':
        'The average of your last 3 published monthly reports.',
    'business.readiness.reports_needed': {
        one: 'Your stage is set once 3 monthly reports are published. {count} more to go.',
        other: 'Your stage is set once 3 monthly reports are published. {count} more to go.',
    },
    'business.readiness.pending.up': {
        one: '{count} reading so far points to {stage}. The stage moves up after {needed} readings in a row, with 2 of the last 3 months there too.',
        other: '{count} readings in a row point to {stage}. The stage moves up after {needed} readings in a row, with 2 of the last 3 months there too.',
    },
    'business.readiness.pending.down': {
        one: '{count} reading so far points to {stage}. The stage moves down only after {needed} readings in a row, with 2 of the last 3 months there too.',
        other: '{count} readings in a row point to {stage}. The stage moves down only after {needed} readings in a row, with 2 of the last 3 months there too.',
    },
    'business.readiness.next':
        '{stage} starts at {threshold} a month: {gap} to go.',
    'business.readiness.next_reached':
        '{stage} starts at {threshold} a month. Your average is there: hold it for {needed} readings in a row.',
    'business.readiness.top': 'You are in the top stage.',
    'business.readiness.ready_rule':
        'Ready comes when every requirement due is met, your three signals stay on target for 3 reports in a row, and you pass your stage’s core Academy modules.',
    'business.readiness.guidance.title': 'Monthly guidance',
    'business.readiness.guidance.rule':
        'After each published report, each signal is measured against the target for your stage, with one action.',
    'business.readiness.guidance.empty':
        'Your guidance starts once a month you sent figures for is verified in a published report.',
    'business.readiness.guidance.record_gap': 'Record Gap',
    'business.readiness.guidance.forecast_error': 'Forecast Error',
    'business.readiness.guidance.on_target': 'On target',
    'business.readiness.guidance.off_target': 'Off target',
    'business.readiness.guidance.value': {
        one: '{value}% over the last {count} month. Target: {target}% or less.',
        other: '{value}% over the last {count} months. Target: {target}% or less.',
    },
    'business.readiness.guidance.bias.over':
        'Your forecasts were above the money that came in.',
    'business.readiness.guidance.bias.under':
        'Your forecasts were below the money that came in.',
    'business.readiness.guidance.record_gap.keep':
        'Keep recording every sale and every payment in your books on the day it happens.',
    'business.readiness.guidance.record_gap.fix':
        'Record every cash sale and every payment in your books on the same day, and keep each receipt.',
    'business.readiness.guidance.forecast_error.keep':
        'Keep starting each forecast from your verified average and the orders you hold in writing.',
    'business.readiness.guidance.forecast_error.fix':
        "Start next month's forecast from your verified average, and add only orders you hold in writing.",
    'business.academy.title': 'Academy',
    'business.academy.intro':
        'Short lessons for your stage. Each module is a set of flashcards, then a test of {questions} questions. {pass} correct answers pass.',
    'business.academy.core_title': 'Core modules for {stage}',
    'business.academy.preview_title': 'Preview of {stage}',
    'business.academy.preview_body':
        'Cards only. The test opens when you reach this stage.',
    'business.academy.cards': {
        one: '{count} card',
        other: '{count} cards',
    },
    'business.academy.state.open': 'Test open',
    'business.academy.state.passed': 'Passed',
    'business.academy.state.waiting': 'Retake from {date}',
    'business.academy.state.preview': 'Preview',
    'business.academy.best': 'Best score: {score} of {total}',
    'business.academy.syllabus':
        'Academy: {passed} of {total} core modules passed for your stage.',
    'business.academy.open': 'Open the Academy',
    'business.academy.back': 'Back',
    'business.academy.flashcards': 'Flashcards',
    'business.academy.card_progress': 'Card {current} of {total}',
    'business.academy.show_back': 'Show answer',
    'business.academy.show_front': 'Show card',
    'business.academy.previous': 'Previous',
    'business.academy.next': 'Next',
    'business.academy.start_test': 'Start the test',
    'business.academy.test_title': 'Test',
    'business.academy.test_intro':
        'Answer all {count} questions. {pass} correct answers pass.',
    'business.academy.question': 'Question {current} of {total}',
    'business.academy.left': {
        one: '{count} question left to answer.',
        other: '{count} questions left to answer.',
    },
    'business.academy.send': 'Send answers',
    'business.academy.sending': 'Sending…',
    'business.academy.result.passed': 'Passed with {score} of {total}.',
    'business.academy.result.waiting':
        'Not passed: {score} of {total}. The test reopens on {date} with new questions.',
    'business.academy.result.open':
        'Not passed: {score} of {total}. Your next test is ready below.',
    'business.academy.note.passed':
        'You have passed this module. Its cards stay here to revise.',
    'business.academy.note.preview':
        'This module belongs to the next stage. Its test opens when you reach it.',
    'business.readiness.milestones.title':
        'Milestones toward the capital market',
    'business.readiness.milestones.rule':
        'Each milestone is reached automatically once every condition is met. Conditions Rozine cannot measure yet are shown as pending.',
    'business.readiness.milestones.bond.title': 'Bond track',
    'business.readiness.milestones.bond.summary':
        'Meet every condition and Rozine prepares your bond-readiness file.',
    'business.readiness.milestones.bond.reached':
        'Every condition is met. Rozine prepares your bond-readiness file.',
    'business.readiness.milestones.clinic.title':
        'RSE Investment Clinic referral',
    'business.readiness.milestones.clinic.summary':
        'Meet every condition and Rozine can refer you to the Rwanda Stock Exchange Investment Clinic, with your consent. Referrals start once Rozine’s agreement with the RSE is signed.',
    'business.readiness.milestones.clinic.reached':
        'Every condition is met. Rozine refers you, with your consent, once its agreement with the RSE is signed.',
    'business.readiness.milestones.condition.stage': 'Stage 3 or 4',
    'business.readiness.milestones.condition.ready_6_months':
        'Ready for 6 months in a row',
    'business.readiness.milestones.condition.ready_12_months':
        'Ready for 12 months in a row',
    'business.readiness.milestones.condition.notes_on_time':
        '3 or more Rozine notes, every instalment collected on its due day',
    'business.readiness.milestones.condition.audited_statements':
        'Audited annual statements uploaded',
    'business.readiness.milestones.condition.limited_company':
        'A limited company',
    'business.readiness.milestones.condition.trading_3_years':
        '3 or more years trading',
    'business.readiness.milestones.condition.no_proceedings':
        'No major legal proceedings',
    'business.readiness.milestones.state.met': 'Met',
    'business.readiness.milestones.state.not_met': 'Not met',
    'business.readiness.milestones.state.pending': 'Pending',
    'business.readiness.stages': 'The four stages',
    'business.readiness.stage_from': 'From {amount} a month',
    'business.readiness.current': 'Current',
    'business.readiness.months': 'Months counted',
    'business.readiness.months_empty': 'No monthly report is published yet.',
    'business.readiness.requirements': 'Requirements',
    'business.readiness.requirements_rule':
        'Your size sets these requirements. Your CPA confirms each one at the monthly visit.',
    'business.readiness.requirement.status.not_triggered': 'Not yet',
    'business.readiness.requirement.status.due': 'Due',
    'business.readiness.requirement.status.overdue': 'Overdue',
    'business.readiness.requirement.status.met': 'Met',
    'business.readiness.requirement.status.lapsed': 'Lapsed',
    'business.readiness.requirement.due_by': {
        one: 'Due by {date} · {count} day left',
        other: 'Due by {date} · {count} days left',
    },
    'business.readiness.requirement.lapsed_by': {
        one: 'Meet it again by {date} · {count} day left',
        other: 'Meet it again by {date} · {count} days left',
    },
    'business.readiness.requirement.overdue_line':
        'Overdue since {date}. Your next raise waits until it is met.',
    'business.readiness.requirement.met_on': 'Confirmed on {date}',
    'business.readiness.requirement.met_entry':
        'Checked when you joined Rozine',
    'business.readiness.requirement.applies_entry':
        'Applies once your entry audit is published',
    'business.readiness.requirement.applies_stage': 'Applies from {stage}',
    'business.readiness.requirement.applies_headcount':
        'Applies from {count} employees',
    'business.readiness.requirement.C1.title': 'Tax and RSSB up to date',
    'business.readiness.requirement.C1.detail':
        'Tax clearance and RSSB contributions current.',
    'business.readiness.requirement.F1.title':
        'Business money in business accounts',
    'business.readiness.requirement.F1.detail':
        'All business money goes through business-named accounts, with at least 90% of verified revenue traceable to them.',
    'business.readiness.requirement.F2.title': 'Qualified accountant',
    'business.readiness.requirement.F2.detail':
        'A qualified accountant, employed or contracted, and accounting software in use.',
    'business.readiness.requirement.F3.title': 'Finance manager',
    'business.readiness.requirement.F3.detail':
        'A full-time qualified finance manager (CPA, ACCA or equivalent, at least 3 years) and monthly management accounts.',
    'business.readiness.requirement.F4.title': 'External audit',
    'business.readiness.requirement.F4.detail':
        'An annual audit under IFRS for SMEs by an ICPAR auditor other than your Rozine CPA, and payments prepared and approved by different people.',
    'business.readiness.requirement.P1.title': 'Contracts for every employee',
    'business.readiness.requirement.P1.detail':
        'Written contracts and RSSB registration for every employee.',
    'business.readiness.requirement.P2.title': 'HR lead and policy',
    'business.readiness.requirement.P2.detail':
        'A named HR lead or contracted HR provider, and a written HR policy.',
    'business.readiness.requirement.G1.title': 'Board of directors',
    'business.readiness.requirement.G1.detail':
        'A board of at least 2 directors meeting quarterly with minutes, and a written payment delegation.',
    'business.readiness.requirement.G2.title': 'Independent director',
    'business.readiness.requirement.G2.detail':
        'At least one independent director, a governance code and a related-party register.',
    'business.market.title': 'Market Overview',
    'business.market.subtitle':
        'How your notes are performing on the secondary market.',
    'business.market.tile.demand': 'DEMAND',
    'business.market.tile.price': 'AVG SECONDARY PRICE',
    'business.market.tile.volume': 'VOLUME (7D)',
    'business.market.tile.liquidity': 'LIQUIDITY SCORE',
    'business.market.chart': 'Secondary price',
    'business.market.range': 'Price range',
    'business.market.range_7d': '7D',
    'business.market.range_14d': '14D',
    'business.market.range_30d': '30D',
    'business.market.chart_empty': 'No secondary trades yet.',
    'business.market.high': 'PERIOD HIGH',
    'business.market.low': 'PERIOD LOW',
    'business.market.notes': 'Your notes on the market',
    'business.market.notes_empty_title': 'None of your notes trade yet',
    'business.market.notes_empty_body':
        'Notes investors resell before maturity will show here with their price.',
    'business.home.head_title': 'Home',
    'business.home.wallet_balance': 'Wallet balance',
    'business.home.deposit': 'Deposit',
    'business.home.withdraw': 'Withdraw',
    'business.home.notifications': 'Notifications',
    'business.home.notifications_unread': 'Notifications, {count} unread',
    'business.home.company_code': 'RDB {code}',
    'business.home.rozine_rating': 'Rozine rating',
    'business.rating.pending': 'Pending audit',
    'business.rating.pending_hint': 'Rated after your first audited month',
    'business.rating.pending_next':
        "Your rating appears once your first monthly report is audited. We'll tell you when your audit opens, so you can get the month's records ready.",
    'business.rating.band.strong': 'Strong',
    'business.rating.band.stable': 'Stable',
    'business.rating.band.weak': 'Weak',
    'business.rating.band.distressed': 'Distressed',
    'business.home.raising_now': 'Raising now',
    'business.home.funded_progress': '{title} funding progress',
    'business.home.raised_of': '{raised} of {target}',
    'business.home.investors_link': '{count} investors ›',
    'business.today.title': 'Today',
    'business.today.subtitle': 'What needs you right now.',
    'business.today.approved.kicker': 'Application approved',
    'business.today.approved.sub':
        'Pay the {fee} application fee to publish it to investors.',
    'business.today.approved.cta': 'Pay fee & publish',
    'business.today.approved.sub_free':
        "Publish it to investors. There's no listing fee.",
    'business.today.approved.cta_free': 'Publish',
    'business.today.cosign.kicker': 'Audit report ready',
    'business.today.cosign.sub':
        'Your CPA sealed it. Read the findings. There is nothing to sign.',
    'business.today.cosign.cta': 'Review report',
    'business.today.cosign.due': 'Dispute by {date} · {hours} h left',
    'business.today.cosign.sign_sub':
        'Your CPA sealed it. Read the findings before you co-sign.',
    'business.today.cosign.sign_cta': 'Review & co-sign',
    'business.today.signature.kicker': 'Signature needed',
    'business.today.signature.sub':
        'Your signature is needed before this application can be submitted.',
    'business.today.signature.cta': 'Review & sign',
    'business.today.awaiting.kicker': 'Awaiting signatures',
    'business.today.awaiting.sub': 'Waiting for {names} to sign.',
    'business.today.awaiting.cta': 'Open application',
    'business.today.declined.kicker': 'Application declined',
    'business.today.declined.sub':
        'Outside your approved capacity — talk to us before resubmitting.',
    'business.today.declined.cta': 'See why',
    'business.today.disbursement.kicker': 'Raise complete',
    'business.today.disbursement.cta': 'Confirm disbursement',
    'business.today.audit.kicker': 'Audit window',
    'business.today.audit.title': '{month} audit',
    'business.today.audit.sub_open':
        'Your CPA visits after month end · sealed by {sealed}',
    'business.today.audit.sub_closing':
        'Month closes in {days} days · sealed by {sealed}',
    'business.today.audit.cta': 'Get ready',
    'business.today.repayment.kicker': 'Upcoming repayment',
    'business.today.repayment.sub': '{note} · Due {date}',
    'business.today.repayment.cta': 'Open Repayments',
    'business.today.visit_ready.kicker': 'Get ready for this month’s visit',
    'business.today.visit_ready.sub':
        'Your CPA visits between {from} and {by}.',
    'business.today.visit_ready.cta': 'Open the checklist',
    'business.today.blocked.kicker': 'Application on hold',
    'business.today.blocked.untitled': 'Your application',
    'business.today.blocked.sub.UNDERWRITING_EVIDENCE_REQUIRED':
        'Your statements must be verified by an Audit Partner before you can continue.',
    'business.today.blocked.sub.VERIFIED_HISTORY_INSUFFICIENT':
        'More verified months of statements are needed before you can continue.',
    'business.today.blocked.sub.POLICY_INPUT_REQUIRED':
        'Rozine needs to check your credit history and existing loans first. Nothing is needed from you.',
    'business.today.blocked.sub.RESTRICTION_ACTIVE':
        'A restriction on your account stops this application for now.',
    'business.today.blocked.sub.other':
        'Something needs attention before you can continue.',
    'business.today.blocked.cta': "See what's needed",
    'business.today.caught_up.title': "You're all caught up",
    'business.today.caught_up.body':
        "No repayments or actions due. We'll let you know the moment something needs you.",
    'business.capital.title': 'Your capital',
    'business.capital.subtitle':
        'Your all-time track record, across every note.',
    'business.capital.active_notes': '{count} active',
    'business.capital.active_and_repaying':
        '{active} active · {repaying} repaying',
    'business.capital.repaying_notes': '{count} repaying',
    'business.capital.about': 'About {label}',
    'business.capital.close': 'Close',
    'business.capital.raised.label': 'Raised',
    'business.capital.raised.about':
        'Total capital you’ve raised from investors across every note you’ve ever issued on Rozine.',
    'business.capital.investors.label': 'Investors',
    'business.capital.investors.about':
        'Unique investors backing your notes. Anyone who invested in more than one of your notes is counted once.',
    'business.capital.repaid.label': 'Repaid',
    'business.capital.repaid.about':
        'Total returns (yield) you’ve paid out to investors across all your notes to date.',
    'business.capital.on_time.label': 'On-time',
    'business.capital.on_time.about':
        'Share of your scheduled repayments made on or before their due date, all-time.',
    'business.note.title': 'Your notes',
    'business.note.filter_label': 'Filter notes by status',
    'business.note.filter.draft': 'Draft',
    'business.note.filter.active': 'Active',
    'business.note.filter.repaying': 'Repaying',
    'business.note.filter.failed': 'Failed',
    'business.note.filter.archived': 'Archived',
    'business.note.status.draft': 'Draft',
    'business.note.status.active': 'Active',
    'business.note.status.funded': 'Funded',
    'business.note.status.repaying': 'Repaying',
    'business.note.status.completed': 'Completed',
    'business.note.status.failed': 'Failed',
    'business.note.funded': 'funded',
    'business.note.investors': '{count} investors',
    'business.note.continue_application': 'Continue application',
    'business.note.empty.title': 'No notes with this status',
    'business.note.empty.body': 'Start a raise to fund your business.',
    'business.grow.title': 'Grow',
    'business.grow.subtitle': "Raise more when you're ready.",
    'business.grow.headroom': 'Headroom available',
    'business.grow.headroom_body': 'You can raise this much more now.',
    'business.grow.apply': 'Apply for a raise',
    'business.apply.head_title': 'Apply for a raise',
    'business.apply.label': 'Raise application',
    'business.apply.close': 'Close',
    'business.apply.back': 'Back',
    'business.apply.progress': 'Application progress',
    'business.apply.step_of': 'Step {step} of {total}',
    'business.apply.continue': 'Continue',
    'business.apply.saving': 'Saving…',
    'business.apply.submit': 'Sign application',
    'business.apply.submitting': 'Signing…',
    'business.apply.incomplete': 'Complete this step to continue',
    'business.apply.business.title': 'Business & finances',
    'business.apply.business.subtitle':
        'From your RDB certificate and your verified bank & mobile-money statements. Review it — if it looks right, continue.',
    'business.apply.business.rdb_verified': '✓ RDB verified',
    'business.apply.business.statements_verified': '✓ Statements verified',
    'business.apply.business.policy_check_next':
        'Next, Rozine needs to check your current credit history and existing loans. You don’t need to do anything, and this page updates once it is done.',
    'business.apply.business.active': '● Active',
    'business.apply.business.established': 'Est. {year}',
    'business.apply.business.standing': 'Financial standing',
    'business.apply.business.statements_badge': 'Statements verified',
    'business.apply.business.period': {
        one: '{from} – {through} · {count} month',
        other: '{from} – {through} · {count} months',
    },
    'business.apply.business.partial_year': {
        one: '{year} · {count} month',
        other: '{year} · {count} months',
    },
    'business.apply.business.revenue': 'Revenue',
    'business.apply.business.costs': 'Costs',
    'business.apply.business.net_profit': 'Net operating cash',
    'business.apply.business.existing_debt': 'Existing debt',
    'business.apply.business.crb_verified': '✓ CRB verified',
    'business.apply.business.year_by_year': 'Year by year',
    'business.apply.business.capacity': 'Approved capacity',
    'business.apply.business.capacity_basis': '· cash × stock cover',
    'business.apply.business.capacity_pending': 'Pending audit',
    'business.apply.business.capacity_body':
        'The largest raise the cash your business generates can carry at your verified stock cover. Every offer is sized within it.',
    'business.apply.raise.title': 'Your raise',
    'business.apply.raise.subtitle': 'Set your terms and pitch to investors.',
    'business.apply.raise.fundraise': '1 · Fundraise',
    'business.apply.raise.note_title': 'Note title · what this raise is for',
    'business.apply.raise.note_title_placeholder':
        'e.g. Cold-Chain Hub, Fleet Expansion…',
    'business.apply.raise.note_title_help':
        'Name the specific project investors are funding.',
    'business.apply.raise.target': 'Fundraising target (RWF)',
    'business.apply.raise.term': 'Term',
    'business.apply.raise.term_months': '{months} Months',
    'business.apply.raise.rate': 'Rate',
    'business.apply.raise.rate_info': 'How this yield is calculated',
    'business.apply.raise.rate_how': 'How your rate is set',
    'business.apply.raise.rate_one_charge':
        'One flat charge on what you borrow. The only deduction is the 3% platform fee, taken from your raise when it is paid out — there is no origination fee or levy on top.',
    'business.apply.raise.rate_yours': 'Your rating',
    'business.apply.raise.rate_rated': 'Rated {band}',
    'business.apply.raise.rate_best': 'Strongest rating, 3 months',
    'business.apply.raise.rate_best_detail': 'Best rate on Rozine',
    'business.apply.raise.rate_term_detail': '+{ratio} term premium',
    'business.apply.raise.rate_cap': 'Never above {cap}%',
    'business.apply.raise.quote_pending':
        'Enter a target and a term to see your quote.',
    'business.apply.raise.notes': 'Notes',
    'business.apply.raise.notes_info': 'About Rozine notes',
    'business.apply.raise.note_unit': 'A Rozine note is {price}.',
    'business.apply.raise.term_label': 'Term · months to repay',
    'business.apply.raise.term_any': '· any 3–6',
    'business.apply.raise.amount_raised': 'Amount raised',
    'business.apply.raise.fee_at_payout':
        'A 3% platform fee is deducted when this is paid out to you. You repay the full amount raised.',
    'business.apply.raise.interest': '+ Interest',
    'business.apply.raise.interest_basis': '({rate}% flat · {months} months)',
    'business.apply.raise.you_repay': 'You repay',
    'business.apply.raise.per_month': '{amount} / month',
    'business.apply.raise.first_payment': 'Starting 1 month after funding',
    'business.apply.raise.reserve': 'Investor protection reserve',
    'business.apply.raise.reserve_detail':
        'Taken out of the charge · nothing extra to pay',
    'business.apply.raise.use_of_funds': 'Use of funds',
    'business.apply.raise.use.inventory': 'Inventory',
    'business.apply.raise.use.expansion': 'Expansion',
    'business.apply.raise.use.equipment': 'Equipment',
    'business.apply.raise.use.hiring': 'Hiring',
    'business.apply.raise.use.working_capital': 'Working Capital',
    'business.apply.raise.use.other': 'Other',
    'business.apply.raise.show_investors': '2 · Show investors who you are',
    'business.apply.raise.logo': 'Logo',
    'business.apply.raise.photos_help':
        'Add your logo and up to 7 photos — products, facilities, team and operations.',
    'business.apply.raise.slot.products': 'Products',
    'business.apply.raise.slot.facilities': 'Facilities',
    'business.apply.raise.slot.team': 'Team',
    'business.apply.raise.slot.operations': 'Operations',
    'business.apply.raise.slot.customers': 'Customers',
    'business.apply.raise.slot.impact': 'Impact',
    'business.apply.raise.slot.brand': 'Brand',
    'business.apply.raise.story': '3 · Your story',
    'business.apply.raise.story_prompt':
        'Why should investors trust your business?',
    'business.apply.raise.story_placeholder':
        'Sebeya Logistics has moved goods across East Africa for 8 years...',
    'business.apply.raise.story_count': '{count} / 100 words',
    'business.apply.review.title': 'Review & sign',
    'business.apply.review.subtitle':
        'Acknowledge the risks, agree to the terms, and sign to submit.',
    'business.apply.review.risk_disclosures': 'Risk disclosures',
    'business.apply.review.agreements': 'Agreements',
    'business.apply.review.agree_terms_prefix': 'I agree to the',
    'business.apply.review.agree_privacy_prefix': 'I have read the',
    'business.apply.review.document.terms': 'Terms & Conditions',
    'business.apply.review.document.privacy': 'Privacy Note',
    'business.apply.review.read': 'Read',
    'business.apply.review.document_intro':
        'Rozine · Republic of Rwanda · version {version}. A summary of the key clauses, then the full text you accept.',
    'business.apply.review.got_it': 'Got it',
    'business.apply.review.sign_submit': 'Sign & submit',
    'business.apply.review.full_name': 'Your full name',
    'business.apply.review.full_name_placeholder': 'Robert Mugisha',
    'business.apply.review.signature': 'Digital signature',
    'business.apply.review.sign_here': 'Sign here',
    'business.apply.review.due_on_approval': 'Due on approval',
    'business.apply.review.application_fee': 'Application fee',
    'business.apply.review.application_fee_when':
        'One-time, charged when approved.',
    'business.apply.review.fee_note':
        'Charged once your note is approved, before it goes live. When your raise is paid out, a 3% platform fee is deducted from it; you repay the full amount raised.',
    'business.apply.review.binding':
        'Your signature legally binds the business to the disclosed obligations. Your application ID is issued on submission.',
    'business.apply.approved.title': 'Your application is approved',
    'business.apply.approved.body':
        'Publish it to the investor feed to start raising. The listing fee is waived.',
    'business.apply.approved.publish': 'Publish to the investor feed',
    'business.apply.published.title': 'Your raise is published',
    'business.apply.published.body':
        'Investors can see it in the feed and commit now.',
    'business.apply.submitted.title': 'Your application has been submitted',
    'business.apply.submitted.body':
        "Your application is being reviewed. You'll be notified at each stage.",
    'business.apply.submitted.note_id': 'Note ID · {id}',
    'business.apply.submitted.funded_title': 'Once fully funded',
    'business.apply.submitted.funded_body':
        'Funds reach your linked business bank account within 24 hours of closing. That day becomes your monthly repayment due date, repeating every month until the total is fully repaid.',
    'business.apply.submitted.timeline': 'Application progress',
    'business.apply.submitted.stage.submitted': 'Submitted',
    'business.apply.submitted.stage.under_review': 'Under review',
    'business.apply.submitted.stage.approved': 'Approved',
    'business.apply.submitted.stage.published': 'Published',
    'business.apply.submitted.back_home': 'Back to Home',
    'app.skip_to_content': 'Skip to content',
    'app.sheet.close': 'Close',
    'business.publish.title': 'Publish to the Investor feed',
    'business.publish.body_fee':
        '{title} passed vetting. Pay the one-time application fee to make it live for investors.',
    'business.publish.body_free':
        '{title} passed vetting. There is no application fee to pay — publish it to make it live for investors.',
    'business.publish.target': 'Raise target',
    'business.publish.fee': 'Application fee',
    'business.publish.pay_with': 'Pay with',
    'business.publish.source.wallet': 'Wallet',
    'business.publish.source.mtn': 'MTN MoMo',
    'business.publish.source.airtel': 'Airtel',
    'business.publish.source.card': 'Card',
    'business.publish.pay_and_publish': 'Pay & publish',
    'business.publish.publish': 'Publish',
    'business.publish.publishing': 'Publishing…',
    'business.publish.not_yet': 'Not yet',
    'business.onboarding.back': 'Back',
    'business.onboarding.progress': 'Setup progress',
    'business.onboarding.step_label.confirm': 'STEP 1 OF 4 · RDB',
    'business.onboarding.step_label.documents': 'STEP 2 OF 4',
    'business.onboarding.step_label.bank': 'STEP 3 OF 4',
    'business.onboarding.step_label.finish': 'STEP 4 OF 4',
    'business.onboarding.cta.confirm': 'Confirm & continue',
    'business.onboarding.cta.documents': 'Continue',
    'business.onboarding.cta.bank': 'Continue',
    'business.onboarding.cta.finish': 'Finish setup',
    'business.onboarding.error.certificate':
        'Upload and verify your RDB certificate to continue — no business lists without it',
    'business.onboarding.error.bank':
        'Link a verified business bank account to continue',
    'business.onboarding.confirm.title': 'Confirm your business',
    'business.onboarding.confirm.subtitle':
        "Pulled from the Rwanda Development Board. Review and confirm it's correct.",
    'business.onboarding.confirm.name': 'Registered name',
    'business.onboarding.confirm.company_code': 'Company code',
    'business.onboarding.confirm.legal_form': 'Legal form',
    'business.onboarding.confirm.registered': 'Registered',
    'business.onboarding.confirm.status': 'Status',
    'business.onboarding.confirm.status_active': 'Active',
    'business.onboarding.confirm.status_dormant': 'Dormant',
    'business.onboarding.confirm.status_deregistered': 'Deregistered',
    'business.onboarding.confirm.staff': 'Staff',
    'business.onboarding.confirm.staff_count': '{count} employees',
    'business.onboarding.confirm.address': 'Registered address',
    'business.onboarding.confirm.industry': 'Industry',
    'business.onboarding.confirm.auto_detected': '✓ Auto-detected from RDB',
    'business.onboarding.confirm.industry_help':
        "We detected this from your RDB registration ({category}). Change it if it doesn't fit.",
    'business.onboarding.confirm.management': 'Management',
    'business.onboarding.confirm.shareholders': 'Shareholders',
    'business.onboarding.documents.title': 'RDB certificate, logo & photos',
    'business.onboarding.documents.subtitle':
        'Your certificate of incorporation is mandatory — nothing lists without it. Then show investors who you are.',
    'business.onboarding.documents.certificate':
        'RDB certificate of incorporation',
    'business.onboarding.documents.certificate_required': 'Required',
    'business.onboarding.documents.certificate_uploaded': 'Uploaded',
    'business.onboarding.documents.certificate_verified': 'Verified',
    'business.onboarding.documents.certificate_drop':
        'Drop your RDB certificate (PDF scan or photo)',
    'business.onboarding.documents.certificate_number': 'Certificate number',
    'business.onboarding.documents.certificate_placeholder': 'RDB/2019/123456',
    'business.onboarding.documents.verify_certificate': 'Verify certificate',
    'business.onboarding.documents.verifying': 'Verifying…',
    'business.onboarding.documents.certificate_on_file': 'Certificate on file',
    'business.onboarding.documents.logo': 'Logo',
    'business.onboarding.documents.logo_set': 'Logo set',
    'business.onboarding.documents.logo_label': 'Upload your logo',
    'business.onboarding.documents.logo_help':
        'A clean logo builds instant credibility with investors.',
    'business.onboarding.documents.photos': 'Proof photos',
    'business.onboarding.documents.uploaded': 'Uploaded',
    'business.onboarding.bank.title': 'Business bank account',
    'business.onboarding.bank.subtitle':
        "Where you'll receive funds you raise. It must be a Rwandan business account in your company's name with at least {count} signatories.",
    'business.onboarding.bank.bank': 'Bank',
    'business.onboarding.bank.select': 'Select your bank…',
    'business.onboarding.bank.business_account_prefix': 'This is a registered',
    'business.onboarding.bank.business_account': 'business account',
    'business.onboarding.bank.business_account_suffix':
        ', not a personal account.',
    'business.onboarding.bank.account_name': 'Account name',
    'business.onboarding.bank.use_company_name': 'Use company name',
    'business.onboarding.bank.account_number': 'Account number',
    'business.onboarding.bank.account_number_placeholder': '00012345678',
    'business.onboarding.bank.signatories': 'Signatories',
    'business.onboarding.bank.signatories_count':
        '{selected} selected · need {required}+',
    'business.onboarding.bank.link': 'Link business account',
    'business.onboarding.bank.linking': 'Linking…',
    'business.onboarding.bank.linked': '✓ Business bank account linked',
    'business.onboarding.bank.locked':
        'You can link only one payout account. To change it later, contact Rozine support — this protects your funds from unauthorised redirection.',
    'business.onboarding.finish.title': "You're all set",
    'business.onboarding.finish.subtitle':
        'Your workspace is ready. Review before we finish.',
    'business.onboarding.finish.company': 'Company',
    'business.onboarding.finish.rdb': '✓ RDB',
    'business.onboarding.finish.contact': 'Contact',
    'business.onboarding.finish.payout': 'Payout account',
    'business.onboarding.finish.setting_up': 'Setting up your workspace…',
    'business.note.close': 'Back to Home',
    'business.note.back': 'Back',
    'business.note.tile.outstanding': 'Outstanding',
    'business.note.tile.payments_made': 'Payments made',
    'business.note.tile.investors': 'Investors',
    'business.note.tile.health': 'Repayment health',
    'business.note.tile.raised': 'Raised',
    'business.note.tile.funded': 'Funded',
    'business.note.tile.closes_in': 'Closes in',
    'business.note.payments_made': '{made} / {total}',
    'business.note.health.on_time': 'On time',
    'business.note.health.late': 'Late',
    'business.note.pct': '{pct}%',
    'business.note.day': '{count} day',
    'business.note.days': '{count} days',
    'business.note.tracker.repayment': 'Repayment tracker',
    'business.note.tracker.funding': 'Funding tracker',
    'business.note.tracker.repaid_pct': '{pct}% repaid',
    'business.note.tracker.funded_pct': '{pct}% funded',
    'business.note.tracker.repaid': 'Repaid',
    'business.note.tracker.left_to_pay': 'Left to pay',
    'business.note.tracker.over_month': 'over {count} month',
    'business.note.tracker.over_months': 'over {count} months',
    'business.note.tracker.fully_repaid': 'Fully repaid',
    'business.note.tracker.raised': 'Raised',
    'business.note.tracker.left_to_raise': 'Left to raise',
    'business.note.tracker.closes': 'closes {date}',
    'business.note.upcoming': 'Upcoming payment',
    'business.note.due_in_day': 'Due {date} · in {count} day',
    'business.note.due_in_days': 'Due {date} · in {count} days',
    'business.note.pay': 'Pay',
    'business.note.funded_notice':
        'Fully funded on {date}. Rozine is preparing your disbursement — monthly repayments begin 30 days after the funds reach your bank.',
    'business.note.expired_notice':
        'This raise closed on {date} before reaching {target}. Every investor was refunded in full, without fee.',
    'business.note.photos': 'Photos',
    'business.note.reported_by_business': 'Reported by business',
    'business.note.close_photo': 'Close photo',
    'business.note.previous_photo': 'Previous photo',
    'business.note.next_photo': 'Next photo',
    'business.note.trend.title': 'Performance trend',
    'business.note.trend.caption': 'Monthly revenue · repayment status',
    'business.note.trend.range': 'Period',
    'business.note.trend.six_months': '6M',
    'business.note.trend.twelve_months': '12M',
    'business.note.trend.hint': 'Tap a bar to inspect that month',
    'business.note.trend.month_on_time':
        '{month} · {revenue} revenue · payment on time',
    'business.note.trend.month_late':
        '{month} · {revenue} revenue · payment late',
    'business.note.trend.high': 'Period high',
    'business.note.trend.low': 'Period low',
    'business.note.investors.title': 'Recent investors',
    'business.note.investors.view_all': 'View all {count} ›',
    'business.note.investors.kind.individual': 'Individual',
    'business.note.investors.kind.institution': 'Institution',
    'business.note.investors.kind.sacco': 'SACCO',
    'common.ordinal.zero': '{n}th',
    'common.ordinal.one': '{n}st',
    'common.ordinal.two': '{n}nd',
    'common.ordinal.few': '{n}rd',
    'common.ordinal.many': '{n}th',
    'common.ordinal.other': '{n}th',
    'business.reports.title': 'Reports',
    'business.reports.subtitle':
        'Every Flash and monthly audit your Audit Partner seals, verified on site.',
    'business.reports.guide.title': 'How audits work',
    'business.reports.guide.flash.title': 'A Flash audit, when Rozine asks',
    'business.reports.guide.flash.body':
        'Before you raise, or whenever Rozine needs a fresh check, an Audit Partner visits unannounced and seals a Flash report within 24 hours. You review it just like a monthly report.',
    'business.reports.guide.opens.title': 'Every month, gather your records',
    'business.reports.guide.opens.body':
        'Between the 20th and month-end, gather all physical and digital financial records for your scheduled CPA in-person audit.',
    'business.reports.guide.visit.title': 'Be ready for the site visit',
    'business.reports.guide.visit.body_before':
        'Your assigned CPA visits your premises to review records and reconcile cash flows, then seals the report before the',
    'business.reports.guide.visit.body_after': '.',
    'business.reports.guide.cosign.title': 'Review, or dispute',
    'business.reports.guide.cosign.body':
        'When the audit is sealed you have 24 hours to review it and, if something is wrong, raise a dispute with counter-proof. You do not sign it: with no dispute, the report stands. Because you never touch the figures, the report is independent — which is exactly what investors pay for.',
    'business.reports.guide.visit.body_undated':
        'Your assigned CPA visits your premises to review records and reconcile cash flows, then seals the report.',
    'business.reports.tabs': 'Report status',
    'business.reports.tab.verified': 'Published',
    'business.reports.tab.in_audit': 'In audit',
    'business.reports.tab.in_audit_cosign': {
        one: 'In audit · {count} to review',
        other: 'In audit · {count} to review',
    },
    'business.reports.tab.archived': 'Archived',
    'business.reports.status.verified': 'Verified',
    'business.reports.status.in_audit': 'In audit',
    'business.reports.status.awaiting': 'Review now',
    'business.reports.status.archived': 'Archived',
    'business.reports.health.healthy': 'Healthy',
    'business.reports.health.watch': 'Watch',
    'business.reports.health.at_risk': 'At risk',
    'business.reports.row.filed':
        'Inflow {inflow} · {health} · audited by {auditor}',
    'business.reports.row.in_audit': 'With {auditor} · sealed by {date}',
    'business.reports.row.awaiting': 'Ready for your review',
    'business.reports.row.hours_left': {
        one: '{date} · {count} hour left',
        other: '{date} · {count} hours left',
    },
    'business.reports.row.awaiting_by': 'Dispute by {date}',
    'business.reports.row.annual': 'Full year summary',
    'business.reports.annual': '{year} Annual',
    'business.reports.empty': 'No reports here yet.',
    'business.reports.empty_body':
        'Each Flash or monthly report appears here once your Audit Partner seals it.',
    'business.reports.percent': '{value}%',
    'business.reports.count_of': '{count} of {of}',
    'business.reports.sheet.live': 'Live to your investors',
    'business.reports.sheet.archived': 'Archived filing',
    'business.reports.sheet.published':
        'Published {date} · seen by {count} investors',
    'business.reports.sheet.filed': 'Filed {date} · archived',
    'business.reports.sheet.inflow': 'Inflow',
    'business.reports.sheet.outflow': 'Outflow',
    'business.reports.sheet.health': 'Health',
    'business.reports.sheet.recap': 'What investors read',
    'business.reports.sheet.figures': 'Figures filed',
    'business.reports.sheet.audited_by': 'Audited by {name}',
    'business.reports.sheet.disclosure_live':
        'This is the whole of what your investors can see for this month. Nothing else about your business is published.',
    'business.reports.sheet.disclosure_archived':
        'Archived filings stay on your record but are no longer shown on your investor page.',
    'business.reports.figure.cash_inflow': 'Cash inflow',
    'business.reports.figure.cash_outflow': 'Cash outflow',
    'business.reports.figure.net_position': 'Net position',
    'business.reports.figure.net_margin': 'Net margin',
    'business.reports.figure.days_cash_on_hand': 'Days cash on hand',
    'business.reports.figure.quarters_above_floor':
        'Quarters above the {floor}% floor',
    'business.profile.title': 'Profile',
    'business.profile.verified': '✓ Verified',
    'business.profile.score': 'Score {score}',
    'business.profile.menu': 'Profile menu',
    'business.profile.section.company': 'Company information',
    'business.profile.section.linked': 'Linked accounts',
    'business.profile.section.terms': 'Terms & Conditions',
    'business.profile.section.privacy': 'Privacy Note',
    'business.profile.section.security': 'Security center',
    'business.profile.section.permissions': 'Permissions & roles',
    'business.profile.section.support': 'Support center',
    'business.profile.security.two_factor': 'Two-factor authentication',
    'business.profile.security.two_factor_on':
        'On · authenticator app code at sign-in',
    'business.profile.security.login_alerts': 'Sign-in alerts',
    'business.profile.security.login_alerts_hint': 'Email on new device',
    'business.profile.security.two_factor_off':
        'Off · add an authenticator app code at sign-in',
    'business.profile.security.password': 'Change password',
    'business.profile.permissions.none': 'No permissions on this business',
    'business.profile.permissions.business.view': 'View the business',
    'business.profile.permissions.application.create': 'Start raises',
    'business.profile.permissions.application.save': 'Edit applications',
    'business.profile.permissions.application.evaluate': 'Check eligibility',
    'business.profile.permissions.application.sign': 'Sign applications',
    'business.profile.permissions.report.cosign': 'Co-sign reports',
    'business.profile.permissions.business.wallet.deposit': 'Top up the wallet',
    'business.profile.permissions.business.wallet.withdraw':
        'Withdraw from the wallet',
    'business.profile.permissions.repayment.pay': 'Pay repayments',
    'business.profile.permissions.business.profile.update':
        'Edit company information',
    'business.profile.pending.linked_title': 'No payout account yet',
    'business.profile.pending.linked_body':
        'Payout accounts linked to this business will show here.',
    'business.help.intro':
        'Answers to the questions businesses ask most. Tap any card to read the full answer.',
    'business.help.group.start': 'Getting started',
    'business.help.group.capacity': 'Capacity & pricing',
    'business.help.group.raise': 'Raising a note',
    'business.help.group.repay': 'Repayments',
    'business.help.group.reports': 'Reporting & your rating',
    'business.help.group.fees': 'Fees & funds',
    'business.help.group.investors': 'Investors & secondary market',
    'business.help.group.account': 'Account & support',
    'business.help.start.what.q':
        'What is Rozine and how does it help my business?',
    'business.help.start.what.a':
        'Rozine lets verified Rwandan businesses borrow from many investors through a note. You raise RWF 3,000,000 to 100,000,000 for 3 to 6 months and repay it in equal monthly instalments of principal and a fixed return.\n\nYou give up no ownership in your company. Each investor is owed in proportion to the units they hold.',
    'business.help.start.eligible.q': 'Which businesses can raise?',
    'business.help.start.eligible.a':
        'A Rwandan-registered business with verified annual revenue of at least RWF 30,000,000 and 36 months of bank and mobile money statements. A repeat issuer that repaid its last Rozine note on time needs 12 months.\n\nYour rating must be at least 2.0, and an Audit Partner must visit your premises and seal a baseline report before your raise can list.',
    'business.help.start.documents.q': 'What do I need to apply?',
    'business.help.start.documents.a':
        'Your registration details and your bank and mobile money statements, uploaded in the app. The app tells you if anything else is needed.\n\nThere is no application fee during the sandbox.',
    'business.help.capacity.how.q': 'How is the size of my note decided?',
    'business.help.capacity.how.a':
        'Rozine works out the cash your business has left each month after operating costs, owner drawings and other debt payments, and sizes the note so that this cash covers the monthly instalment 1.5 times. A business that would cover it less than 1.25 times is not offered a note.\n\nA note is never more than 35% of your verified annual revenue, and is between RWF 3,000,000 and 100,000,000.',
    'business.help.capacity.rate.q': 'What return will I pay?',
    'business.help.capacity.rate.a':
        'A total return of 10.0% to 15.0% of principal for the whole term, set by your rating, with a small premium for longer terms. It is fixed when the raise opens.\n\nA later change in your rating never changes the terms of a note already issued.',
    'business.help.capacity.more.q': 'Can I raise more than I am offered?',
    'business.help.capacity.more.a':
        'No. A raise cannot be larger than the size Rozine calculates from your verified figures.\n\nRepaying on time and keeping your monthly verification on track are what improve your next offer.',
    'business.help.raise.time.q': 'How long does a raise stay open?',
    'business.help.raise.time.a':
        'A raise is open to investors for 30 days, or until it is fully funded. Investors buy units of RWF 5,000, and no investor can hold more than half of your raise.\n\nYou can follow how much is committed in the app at any time.',
    'business.help.raise.unfunded.q':
        'What happens if my raise is not fully funded?',
    'business.help.raise.unfunded.a':
        'If the raise does not reach 100% within 30 days, it expires and every investor is refunded in full. Nothing is paid out and nothing is owed.\n\nYou can apply again later.',
    'business.help.raise.schedule.q': 'When do I know my repayment schedule?',
    'business.help.raise.schedule.a':
        'Before anyone commits. The instalment is the principal plus the total return, divided by the number of months. The first instalment is due one month after the money is paid out, and each later one on the same day of the following month.\n\nFor example, RWF 12,000,000 at 11.1% for 6 months means 13,332,000 in total, or 2,222,000 a month.',
    'business.help.repay.how.q': 'How do I make a repayment?',
    'business.help.repay.how.a':
        "Keep the instalment in your Rozine business wallet. At 23:59 on the due date it is collected and shared among the note's holders automatically.\n\nAdd money to the wallet from a bank or mobile money account in your business's name.",
    'business.help.repay.early.q': 'Can I repay early?',
    'business.help.repay.early.a':
        'Yes. You can repay the whole remaining balance at any time, and the note then ends.\n\nThe return you agreed is not reduced: investors receive exactly the scheduled amounts still owed.',
    'business.help.repay.late.q': 'What happens if I pay late?',
    'business.help.repay.late.a':
        'Whatever is in your wallet at the due date is collected, and anything that arrives later is collected straight away. A late charge of 5% of the unpaid amount is added at the due date, day 7 and day 30, capped at 15% of the instalment; all of it goes to your investors.\n\nIf the arrears are not cleared by day 31, the note is in default and recovery begins. Talk to us early if you expect a problem.',
    'business.help.reports.monthly.q':
        'How does the monthly verification work?',
    'business.help.reports.monthly.a':
        "Every month while your note is outstanding, you provide the month's statements and records, your Audit Partner checks them in person at your premises, and seals a report that is published to your investors before payment day.\n\nYou are told when a sealed report is waiting for you.",
    'business.help.reports.rating.q': 'How is my Rozine rating set?',
    'business.help.reports.rating.a':
        'Your rating is one score out of 5.0, shown as Strong (4.0 to 5.0), Stable (3.0 to 3.9), Weak (2.0 to 2.9) or Distressed (below 2.0). It is recomputed with every sealed monthly report and published to your holders.\n\nPaying on time and keeping your records ready for each visit help it most.',
    'business.help.reports.dispute.q': 'What if I disagree with a report?',
    'business.help.reports.dispute.a':
        'You have 24 hours from delivery to dispute a sealed report, with supporting text or up to five proof files. While a dispute is open, your note pauses on the market.\n\nIf you do not dispute it in time, the report is published.',
    'business.help.fees.fees.q': 'What fees does Rozine charge?',
    'business.help.fees.fees.a':
        "Every fee is shown in RWF before you confirm, with the amount you will receive and the amount you will repay.\n\nThe return you pay is calculated on the full principal. Withdrawals carry no Rozine fee; the provider's own charge is shown first.",
    'business.help.fees.funds.q': 'When do I receive the money raised?',
    'business.help.fees.funds.a':
        "Once your raise reaches 100%, the money is paid out to your business and your repayment schedule starts.\n\nYou are notified when the money is sent, and can withdraw it to a verified account in your business's name.",
    'business.help.investors.who.q': 'Who funds my raise?',
    'business.help.investors.who.a':
        'Verified investors on Rozine, each holding units of RWF 5,000 in your note.\n\nYou do not deal with them one by one: Rozine shares each payment among them and publishes your sealed reports to them.',
    'business.help.investors.secondary.q':
        'Does the secondary market affect me?',
    'business.help.investors.secondary.a':
        'When the secondary market opens, investors will be able to sell units of your note to each other. That never changes what you owe or when.\n\nWhile your note is late, in default or disputed, its units cannot be traded.',
    'business.help.account.team.q': 'Who can act for my business?',
    'business.help.account.team.a':
        'Profile > Permissions & roles shows each person on your account and what they may do.\n\nAdd an authenticator app under Security center so every sign-in needs a code.',
    'business.help.account.complain.q': 'How do I make a complaint?',
    'business.help.account.complain.a':
        'Message us from this support center. Your complaint gets a reference number; we acknowledge it within 4 hours and aim to resolve it within 48 hours.\n\nYou can take a complaint to the Capital Market Authority at any stage.',
    'business.profile.pending.terms_title': 'Coming to the app soon',
    'business.profile.pending.terms_body':
        'The Terms & Conditions will be readable here once they are published.',
    'business.profile.pending.privacy_title': 'Coming to the app soon',
    'business.profile.pending.privacy_body':
        'The Privacy Note will be readable here once it is published.',
    'business.profile.sign_out': 'Sign out',
    'business.profile.back': 'Back to Profile',
    'business.profile.company.name': 'Company name',
    'business.profile.company.email': 'Business email',
    'business.profile.company.phone': 'Business phone',
    'business.profile.company.phone_placeholder': '07XX XXX XXX',
    'business.profile.company.email_hint':
        "Where Rozine sends the business's receipts, notices and statements.",
    'business.profile.company.address': 'Registered address',
    'business.profile.company.province': 'Province',
    'business.profile.company.district': 'District',
    'business.profile.company.sector': 'Sector',
    'business.profile.company.cell': 'Cell',
    'business.profile.company.street': 'Street',
    'business.profile.company.choose': 'Choose…',
    'business.profile.company.sector_placeholder': 'Sector name',
    'business.profile.company.cell_placeholder': 'Cell name',
    'business.profile.company.save': 'Save changes',
    'business.profile.company.saving': 'Saving…',
    'business.profile.company.saved': 'Company information saved',
    'business.profile.records.certificate': 'RDB certificate',
    'business.profile.records.expired_notice':
        'Your RDB certificate has expired. Upload a current one to keep raising.',
    'business.profile.records.number': 'Certificate number',
    'business.profile.records.status': 'Status',
    'business.profile.records.status_verified': 'Verified',
    'business.profile.records.status_expired': 'Expired',
    'business.profile.records.expires': 'Expires',
    'business.profile.records.no_expiry': 'No expiry',
    'business.profile.records.signatories': 'Signatories',
    'business.profile.records.mandate':
        '{count} on the mandate · need {required}+',
    'business.profile.registration.title': 'On record',
    'business.profile.registration.established': 'Established',
    'business.profile.registration.note':
        'From your verified registration and mandate. They change only when Rozine verifies new records.',
    'business.profile.registration.people': 'People on the mandate',
    'business.profile.registration.required': 'Required signatory',
    'business.profile.registration.role.owner': 'Owner',
    'business.profile.registration.role.beneficial_owner': 'Beneficial owner',
    'business.profile.registration.role.controller': 'Controller',
    'business.profile.registration.role.director': 'Director',
    'business.profile.registration.role.signatory': 'Signatory',
    'business.profile.registration.role.representative': 'Representative',
    'business.profile.linked.unlink': 'Unlink',
    'business.profile.linked.unlink_named': 'Unlink {name}',
    'business.profile.linked.add': '+ Link a payout account',
    'business.profile.linked.none':
        'No account linked yet. Link one to deposit and withdraw.',
    'business.profile.linked.closed':
        "No account linked yet. Rozine hasn't opened payout accounts for businesses yet, so there is nothing for you to do now. Your balance stays safe in your wallet, and you'll be able to link an account in the business's name here once payouts open.",
    'business.profile.linked.not_permitted':
        'No account linked yet. Only a signatory who can withdraw for this business can link a payout account. Ask one of them to link it here.',
    'business.profile.linked.ask_support': 'Questions? Ask Rozine support.',
    'business.profile.linked.footnote':
        "Only an account held in the business's registered name can be linked. Withdrawals go only to a linked account.",
    'business.profile.legal.updated': 'Last updated {date} · Version {version}',
    'business.profile.legal.terms_intro':
        'Please read these Terms carefully. By registering your business, starting a raise or otherwise using Rozine, you agree to be bound by these Terms in full. If you do not agree, you must not use the platform.',
    'business.profile.legal.privacy_intro':
        'This Privacy Note explains what information Rozine collects about your business, how we use it, and the choices you have. By using Rozine you consent to the practices described here.',
    'business.profile.legal.terms_footer':
        'These Terms are governed by the laws of the Republic of Rwanda. For any questions, contact',
    'business.profile.legal.terms_contact': 'legal@rozine.rw',
    'business.profile.legal.privacy_footer':
        'To exercise a data right or reach our Data Protection Officer, contact',
    'business.profile.legal.privacy_contact': 'privacy@rozine.rw',
    'business.profile.legal.footer_end': '.',
    'admin.auth.head_title': 'Sign in',
    'admin.auth.title': 'Sign in',
    'admin.auth.subtitle':
        'Internal access only. Every action is logged to the audit trail.',
    'admin.auth.email_label': 'Work email',
    'admin.auth.email_placeholder': 'you@rozine.com',
    'admin.auth.password_label': 'Password',
    'admin.auth.password_placeholder': '••••••••',
    'admin.auth.submit': 'Sign in to console',
    'admin.auth.submitting': 'Signing in…',
    'admin.auth.back': '← Back to all apps',
    'admin.nav.label': 'Console navigation',
    'admin.nav.open_menu': 'Open menu',
    'admin.nav.close_menu': 'Close menu',
    'admin.nav.all_apps': '← All apps',
    'admin.nav.group.accounts': 'Accounts',
    'admin.nav.group.capital': 'Capital',
    'admin.nav.group.treasury': 'Treasury',
    'admin.nav.group.console': 'Console',
    'admin.nav.notes': 'Notes',
    'admin.section.notes.title': 'Notes',
    'admin.section.notes.subtitle': 'Every note issued on Rozine',
    'admin.section.notes.search': 'Search notes by business or note ID…',
    'admin.nav.primary_market': 'Primary Market',
    'admin.section.primary_market.title': 'Primary Market Monitor',
    'admin.section.primary_market.subtitle': 'Live fundraising oversight',
    'admin.section.primary_market.search': 'Search raises by business or note…',
    'admin.nav.secondary_market': 'Secondary Market',
    'admin.section.secondary_market.title': 'Secondary Market Monitor',
    'admin.section.secondary_market.subtitle': 'Trading surveillance',
    'admin.section.secondary_market.search':
        'Search orders by note or investor…',
    'admin.nav.risk': 'Risk Center',
    'admin.section.risk.title': 'Risk Center',
    'admin.section.risk.subtitle': 'Platform-wide risk management',
    'admin.section.risk.search': 'Search distressed notes by business…',
    'admin.nav.compliance': 'Compliance',
    'admin.section.compliance.title': 'Compliance Dashboard',
    'admin.section.compliance.subtitle': 'KYC, AML and regulatory oversight',
    'admin.section.compliance.search': 'Search cases by name or reference…',
    'admin.nav.payments': 'Payments',
    'admin.section.payments.title': 'Payment Center',
    'admin.section.payments.subtitle': 'Money movement across the platform',
    'admin.section.payments.search': 'Search payments…',
    'admin.nav.ratings': 'Ratings',
    'admin.section.ratings.title': 'Ratings',
    'admin.section.ratings.subtitle':
        'Live bands, what moved them, and the weights behind them',
    'admin.section.ratings.search': 'Search businesses…',
    'admin.nav.deferrals': 'Deferrals',
    'admin.section.deferrals.title': 'Deferrals',
    'admin.section.deferrals.subtitle': 'Requests to move a repayment date',
    'admin.section.deferrals.search': 'Search deferrals by business or note…',
    'admin.nav.plus': 'Rozine Plus',
    'admin.section.plus.title': 'Rozine Plus',
    'admin.section.plus.subtitle':
        'Subscriptions and the standing orders they run',
    'admin.section.plus.search': 'Search members by name…',
    'admin.nav.finance': 'Finance',
    'admin.section.finance.title': 'Finance Dashboard',
    'admin.section.finance.subtitle': 'Every revenue stream and the ledger',
    'admin.section.finance.search':
        'Search the ledger by type, party, reference…',
    'admin.nav.messaging': 'Messaging',
    'admin.section.messaging.title': 'Messaging & Automations',
    'admin.section.messaging.subtitle':
        'Rules that decide when, why and how every notification goes out',
    'admin.section.messaging.search': 'Search messages…',
    'admin.nav.academies': 'Academies',
    'admin.section.academies.title': 'Academies',
    'admin.section.academies.subtitle':
        'Investor, Business & Auditor Academy lessons and points',
    'admin.section.academies.search': 'Search lessons…',
    'admin.nav.app_control': 'App Control',
    'admin.section.app_control.title': 'App Control Center',
    'admin.section.app_control.subtitle':
        'Everything across the Investor, Business & Auditor apps',
    'admin.section.app_control.search': 'Search entities by name or type…',
    'admin.nav.policies': 'Policies',
    'admin.section.policies.title': 'Policies & Rules',
    'admin.section.policies.subtitle': 'Every rule that governs the ecosystem',
    'admin.section.policies.search': 'Search rules by name or description…',
    'admin.nav.system_health': 'System Health',
    'admin.section.system_health.title': 'System Health',
    'admin.section.system_health.subtitle':
        'Signal monitoring across the ecosystem, with recommendations',
    'admin.section.system_health.search': 'Search signals by name or status…',
    'admin.nav.engines': 'Engines',
    'admin.section.engines.title': 'Engines',
    'admin.section.engines.subtitle':
        'How the platform decides — sizing, refinancing, the queue, partner floors, the CPA split and the loss curves behind them',
    'admin.section.engines.search': 'Search inputs…',
    'admin.design.tabs': '{section} views',
    'admin.design.col.note': 'Note',
    'admin.design.col.issuer': 'Issuer',
    'admin.design.col.state': 'State',
    'admin.design.col.yield': 'Yield',
    'admin.design.col.term': 'Term',
    'admin.design.col.funded': 'Funded',
    'admin.design.col.raised': 'Raised',
    'admin.design.col.raise': 'Raise',
    'admin.design.col.progress': 'Progress',
    'admin.design.col.closes': 'Closes',
    'admin.design.col.orders': 'Orders',
    'admin.design.col.avg_ask': 'Avg ask',
    'admin.design.col.spread': 'Spread',
    'admin.design.col.volume_30d': '30d vol',
    'admin.design.col.trades': 'Trades',
    'admin.design.col.business': 'Business',
    'admin.design.col.outstanding': 'Outstanding',
    'admin.design.col.days_late': 'Days late',
    'admin.design.col.risk': 'Risk',
    'admin.design.col.default_probability': 'Prob. default',
    'admin.design.col.applicant': 'Applicant',
    'admin.design.col.document': 'Document',
    'admin.design.col.checks': 'Automated checks',
    'admin.design.col.decision': 'Decision',
    'admin.design.col.reference': 'Ref',
    'admin.design.col.user': 'User',
    'admin.design.col.type': 'Type',
    'admin.design.col.amount': 'Amount',
    'admin.design.col.compliance': 'Compliance',
    'admin.design.col.sector': 'Sector',
    'admin.design.col.band': 'Band',
    'admin.design.col.change': 'Change',
    'admin.design.col.reason': 'Reason',
    'admin.design.col.why': 'Why',
    'admin.design.col.ask': 'They ask',
    'admin.design.col.deferred': 'Deferred',
    'admin.design.col.opened': 'Opened',
    'admin.design.col.plan': 'Plan',
    'admin.design.col.due': 'Due',
    'admin.design.col.decided': 'Decided',
    'admin.design.col.investor': 'Investor',
    'admin.design.col.tier': 'Tier',
    'admin.design.col.monthly': 'Monthly',
    'admin.design.col.min_interest': 'Min interest',
    'admin.design.col.bands': 'Bands',
    'admin.design.col.filled_mtd': 'Filled MTD',
    'admin.design.col.stream': 'Stream',
    'admin.design.col.limit': 'Limit',
    'admin.design.col.used': 'Used',
    'admin.design.col.cap': 'Cap',
    'admin.design.col.share': 'Share used',
    'admin.design.col.rate': 'Rate',
    'admin.design.col.paid_by': 'Paid by',
    'admin.design.col.collected': 'Collected',
    'admin.design.col.position': 'Position',
    'admin.design.col.updated': 'Updated',
    'admin.design.col.entry': 'Entry',
    'admin.design.col.account': 'Account',
    'admin.design.col.debit': 'Debit',
    'admin.design.col.credit': 'Credit',
    'admin.design.col.posted': 'Posted',
    'admin.design.col.rule': 'Rule',
    'admin.design.col.fires_when': 'Fires when',
    'admin.design.col.timing': 'Timing & channels',
    'admin.design.col.template': 'Template',
    'admin.design.col.channel': 'Channel',
    'admin.design.col.campaign': 'Campaign',
    'admin.design.col.audience': 'Audience',
    'admin.design.col.sent': 'Sent',
    'admin.design.col.message': 'Message',
    'admin.design.col.recipient': 'Recipient',
    'admin.design.col.lesson': 'Lesson',
    'admin.design.col.app': 'App',
    'admin.design.col.status': 'Status',
    'admin.design.col.assigned_cpa': 'Assigned CPA',
    'admin.design.col.variance': 'V_total / D',
    'admin.design.col.signed': 'Auditature',
    'admin.design.col.period': 'Period',
    'admin.design.col.audit_partner': 'Audit partner',
    'admin.design.col.features': 'Features',
    'admin.design.col.entity': 'Item',
    'admin.design.col.input': 'Input',
    'admin.design.col.value': 'Value',
    'admin.design.col.source': 'Source',
    'admin.design.col.signal': 'Signal',
    'admin.design.col.reading': 'Reading',
    'admin.design.col.recommendation': 'Recommendation',
    'admin.design.notes.kpi.total': 'Total Notes',
    'admin.design.notes.kpi.live': 'Live Raises',
    'admin.design.notes.kpi.repaying': 'Repaying',
    'admin.design.notes.kpi.raised': 'Capital Issued',
    'admin.design.notes.tab.all': 'All',
    'admin.design.notes.tab.live': 'Live',
    'admin.design.notes.tab.repaying': 'Repaying',
    'admin.design.notes.tab.matured': 'Matured',
    'admin.design.notes.tab.failed': 'Failed',
    'admin.design.notes.notes.title': 'Notes',
    'admin.design.notes.notes.empty': 'No notes to show yet.',
    'admin.design.primary_market.kpi.active': 'Active Raises',
    'admin.design.primary_market.kpi.active.sub': 'Live now',
    'admin.design.primary_market.kpi.in_flight': 'Raised in Live Raises',
    'admin.design.primary_market.kpi.in_flight.sub':
        'Committed to raises still filling',
    'admin.design.primary_market.kpi.fill': 'Avg Fill',
    'admin.design.primary_market.kpi.fill.sub': 'Of target',
    'admin.design.primary_market.kpi.completion': 'Completion Rate',
    'admin.design.primary_market.kpi.completion.sub': 'Reach target',
    'admin.design.primary_market.trend.title': 'Funding trend',
    'admin.design.primary_market.trend.empty': 'No funding to chart yet.',
    'admin.design.primary_market.raises.title': 'Live raises',
    'admin.design.primary_market.raises.empty': 'No live raises to show yet.',
    'admin.design.secondary_market.kpi.volume': 'Volume Today',
    'admin.design.secondary_market.kpi.orders': 'Open Orders',
    'admin.design.secondary_market.kpi.orders.sub': 'On the book',
    'admin.design.secondary_market.kpi.trades': 'Total Trades',
    'admin.design.secondary_market.kpi.trades.sub': 'All time',
    'admin.design.secondary_market.kpi.tradable': 'Tradable Notes',
    'admin.design.secondary_market.kpi.tradable.sub': 'Repaying · funded',
    'admin.design.secondary_market.activity.title': 'Trade activity',
    'admin.design.secondary_market.activity.empty': 'No trades to chart yet.',
    'admin.design.secondary_market.market.title': 'Market by note',
    'admin.design.secondary_market.market.sub':
        'Open sell orders, average ask against par, and 30-day volume.',
    'admin.design.secondary_market.market.empty': 'No open orders to show yet.',
    'admin.design.risk.kpi.exposure': 'Risk Exposure',
    'admin.design.risk.kpi.late': 'Late Payments',
    'admin.design.risk.kpi.distressed': 'Distressed Notes',
    'admin.design.risk.kpi.default_probability': 'Avg Default Prob.',
    'admin.design.risk.distressed.title':
        'Distressed notes · early warning engine',
    'admin.design.risk.distressed.empty': 'No distressed notes to show yet.',
    'admin.design.reports.kpi.published': 'Audited & published',
    'admin.design.reports.kpi.awaiting': 'Awaiting audit',
    'admin.design.reports.kpi.breached': 'SLA breached',
    'admin.design.reports.kpi.on_time': 'On-time audit rate',
    'admin.design.reports.field.title': 'Field Flash Audit operations',
    'admin.design.reports.field.sub':
        'Every field visit with its CPA, variance, sign-off time and signed findings.',
    'admin.design.reports.field.empty':
        'No ISRS 4400 engagements recorded yet.',
    'admin.design.reports.monthly.title': 'Monthly audit compliance tracker',
    'admin.design.reports.monthly.sub':
        'Statements monthly, verified on site by the 7th.',
    'admin.design.reports.monthly.empty': 'Nothing in this view.',
    'admin.design.compliance.kpi.kyc_completion': 'KYC Completion',
    'admin.design.compliance.kpi.aml_alerts': 'AML Alerts',
    'admin.design.compliance.kpi.sanctions': 'Sanctions Hits',
    'admin.design.compliance.kpi.pending': 'Pending Reviews',
    'admin.design.compliance.queue.title': 'KYC review queue',
    'admin.design.compliance.queue.empty': 'No KYC reviews to show yet.',
    'admin.design.payments.kpi.deposits': 'Deposits',
    'admin.design.payments.kpi.withdrawals': 'Withdrawals',
    'admin.design.payments.kpi.disbursed': 'Disbursed',
    'admin.design.payments.kpi.disbursed.sub': 'To businesses',
    'admin.design.payments.kpi.awaiting': 'Awaiting approval',
    'admin.design.payments.kpi.awaiting.sub': 'In the queue',
    'admin.design.payments.movement.title': 'Money movement',
    'admin.design.payments.movement.empty': 'No money movement to chart yet.',
    'admin.design.payments.approvals.title': 'Approval queue',
    'admin.design.payments.approvals.empty':
        'Nothing awaiting approval to show yet.',
    'admin.design.ratings.kpi.rated': 'Published ratings',
    'admin.design.ratings.kpi.changed': 'Band changed',
    'admin.design.ratings.kpi.trending_down': 'Trending down',
    'admin.design.ratings.kpi.pinned': 'Pinned by staff',
    'admin.design.ratings.tab.changed': 'Band changed',
    'admin.design.ratings.tab.trending_down': 'Trending down',
    'admin.design.ratings.tab.all': 'All businesses',
    'admin.design.ratings.book.title': 'The rating book',
    'admin.design.ratings.book.sub':
        'Businesses whose rating was published with a raise. Ratings on applications still under review are provisional and show in the Applications queue.',
    'admin.design.ratings.book.empty': 'No ratings to show yet.',
    'admin.design.deferrals.kpi.this_month': 'Deferrals this month',
    'admin.design.deferrals.kpi.live': 'Live',
    'admin.design.deferrals.kpi.deferred': 'Deferred',
    'admin.design.deferrals.kpi.extra_paid': 'Extra paid to investors',
    'admin.design.deferrals.awaiting.title': 'Awaiting your decision',
    'admin.design.deferrals.awaiting.empty':
        'No plans awaiting a decision to show yet.',
    'admin.design.deferrals.live_plans.title': 'Live plans',
    'admin.design.deferrals.live_plans.sub': 'Approved and running.',
    'admin.design.deferrals.live_plans.empty': 'No live plans to show yet.',
    'admin.design.deferrals.decided.title': 'Decided',
    'admin.design.deferrals.decided.empty': 'No decisions to show yet.',
    'admin.design.plus.tab.tiers': 'Plus tiers',
    'admin.design.plus.tab.institutions': 'Institutions',
    'admin.design.plus.tab.all': 'All investors',
    'admin.design.plus.subscribers.title': 'Rozine Plus subscribers',
    'admin.design.plus.subscribers.empty': 'No subscribers to show yet.',
    'admin.design.plus.orders.title': 'Standing orders',
    'admin.design.plus.orders.sub': 'Unattended buying by Plus accounts.',
    'admin.design.plus.orders.empty': 'No standing orders to show yet.',
    'admin.design.finance.kpi.revenue': 'All revenue',
    'admin.design.finance.kpi.primary': 'Primary market',
    'admin.design.finance.kpi.investor_fees': 'Investor fees',
    'admin.design.finance.kpi.secondary': 'Secondary market',
    'admin.design.finance.kpi.revenue.sub': 'Every stream, all time',
    'admin.design.finance.kpi.revenue.sub_period': 'Every stream, this period',
    'admin.finance.period.showing': 'Showing',
    'admin.finance.period.hint': 'Filters every figure on this page',
    'admin.finance.period.days': { one: '{count} day', other: '{count} days' },
    'admin.finance.period.year': '1 year',
    'admin.finance.period.to': 'to',
    'admin.finance.period.since': 'Since {from}',
    'admin.finance.period.until': 'Until {to}',
    'admin.design.finance.kpi.primary.sub': 'Platform fee, Rozine share',
    'admin.design.finance.kpi.investor_fees.sub': 'Earnings fee bands',
    'admin.design.finance.kpi.secondary.sub': 'Resale fee',
    'admin.design.finance.tab.streams': 'Revenue streams',
    'admin.design.finance.tab.ledger': 'Ledger & payouts',
    'admin.design.finance.raise.title': 'Primary market',
    'admin.design.finance.raise.sub': 'What businesses pay to raise money',
    'admin.design.finance.raise.empty': 'No raise fees to show yet.',
    'admin.design.finance.earnings.title': 'Investor fees',
    'admin.design.finance.earnings.sub': 'What investors pay on what they make',
    'admin.design.finance.earnings.empty': 'No earnings fees to show yet.',
    'admin.design.finance.trades.title': 'Secondary market',
    'admin.design.finance.trades.sub': 'What early exits and trades pay',
    'admin.design.finance.trades.empty': 'No trade fees to show yet.',
    'admin.design.finance.residual.title': 'Cost recovery and residuals',
    'admin.design.finance.residual.sub':
        'Small lines. Forecast nothing from them.',
    'admin.design.finance.residual.empty': 'No residual lines to show yet.',
    'admin.design.finance.ledger.title': 'Ledger & payouts',
    'admin.design.finance.ledger.empty': 'No ledger entries to show yet.',
    'admin.design.messaging.tab.automations': 'Automations',
    'admin.design.messaging.tab.templates': 'Templates',
    'admin.design.messaging.tab.campaigns': 'Campaigns',
    'admin.design.messaging.tab.history': 'History',
    'admin.design.messaging.automations.title': 'Automations',
    'admin.design.messaging.automations.empty': 'No automations to show yet.',
    'admin.design.messaging.templates.title': 'Templates',
    'admin.design.messaging.templates.empty': 'No templates to show yet.',
    'admin.design.messaging.campaigns.title': 'Campaigns',
    'admin.design.messaging.campaigns.empty': 'No campaigns to show yet.',
    'admin.design.messaging.history.title': 'History',
    'admin.design.messaging.history.empty': 'No messages to show yet.',
    'admin.messaging.col.recipient': 'Recipient',
    'admin.messaging.col.message': 'Message',
    'admin.messaging.col.category': 'Category',
    'admin.messaging.col.why': 'Why it went out',
    'admin.messaging.col.via': 'Via',
    'admin.messaging.col.when': 'When',
    'admin.messaging.source.automation': 'Automation',
    'admin.messaging.status.delivered': 'In the app inbox',
    'admin.messaging.status.sent': 'Email sent',
    'admin.messaging.status.failed': 'Email failed',
    'admin.messaging.channel.in_app': 'In-app',
    'admin.messaging.channel.mail': 'Email',
    'admin.messaging.category.onboarding': 'Onboarding',
    'admin.messaging.category.wallet': 'Wallet',
    'admin.messaging.category.fundraising': 'Fundraising',
    'admin.messaging.category.portfolio': 'Portfolio',
    'admin.messaging.category.reporting': 'Reporting',
    'admin.messaging.category.security': 'Security',
    'admin.messaging.category.underwriting': 'Underwriting',
    'admin.messaging.category.payments': 'Payments',
    'admin.messaging.category.audits': 'Audits',
    'admin.messaging.category.support': 'Support',
    'admin.messaging.category.general': 'General',
    'admin.messaging.audience.investor': 'Investors',
    'admin.messaging.audience.business': 'Businesses',
    'admin.messaging.audience.auditor': 'Audit Partners',
    'admin.messaging.audience.everyone': 'Everyone with an account',
    'admin.messaging.audience.staff': 'Rozine staff',
    'admin.messaging.trigger.verification.approved':
        'Compliance approves an identity check',
    'admin.messaging.trigger.verification.rejected':
        'Compliance sends an identity check back',
    'admin.messaging.trigger.wallet.deposit_received':
        'The provider confirms a deposit',
    'admin.messaging.trigger.wallet.withdrawal_sent':
        'A withdrawal is paid out',
    'admin.messaging.trigger.wallet.withdrawal_returned':
        'A withdrawal comes back to the wallet',
    'admin.messaging.trigger.primary.refunded':
        'A raise closes without reaching its target',
    'admin.messaging.trigger.primary.report_published':
        "A held business's monthly report is sealed",
    'admin.messaging.trigger.primary.raise_funded':
        'A raise the investor committed to is fully funded',
    'admin.messaging.trigger.primary.commitment_confirmed':
        'The investor confirms a purchase',
    'admin.messaging.trigger.primary.holding_issued':
        'Notes are issued to the investor',
    'admin.messaging.trigger.security.password_changed':
        'The password is changed',
    'admin.messaging.trigger.security.payout_account_linked':
        'A payout account is linked',
    'admin.messaging.trigger.security.payout_account_unlinked':
        'A payout account is removed',
    'admin.messaging.trigger.security.new_device': 'A new device signs in',
    'admin.messaging.trigger.business.deposit_received':
        'The provider confirms a business deposit',
    'admin.messaging.trigger.business.application_approved':
        'A note application is approved',
    'admin.messaging.trigger.business.raise_expired':
        'The raise closes without reaching its target',
    'admin.messaging.trigger.business.raise_funded':
        'A raise reaches its target',
    'admin.messaging.trigger.business.disbursement_sent':
        'Funds are released to the business wallet',
    'admin.messaging.trigger.business.withdrawal_sent':
        'A business withdrawal is paid out',
    'admin.messaging.trigger.business.withdrawal_returned':
        'A business withdrawal comes back',
    'admin.messaging.trigger.business.report_sealed':
        'The Audit Partner seals the monthly report',
    'admin.messaging.trigger.business.report_review_closing':
        'The window to dispute a report is closing',
    'admin.messaging.trigger.business.reporting_window_open':
        'A monthly audit opens for a business with a live note',
    'admin.messaging.trigger.business.readiness_stage_changed':
        'A business moves to another readiness stage',
    'admin.messaging.trigger.business.readiness_requirement_due':
        'A readiness requirement becomes due',
    'admin.messaging.trigger.business.readiness_requirement_closing':
        'A readiness requirement has 14 days left',
    'admin.messaging.trigger.business.readiness_requirement_overdue':
        'A readiness requirement turns overdue',
    'admin.messaging.trigger.business.visit_ready_open':
        'The visit-ready checklist opens 14 days before an instalment',
    'admin.messaging.trigger.business.visit_ready_reminder':
        'Checklist items are still not ticked 11 days before an instalment',
    'admin.messaging.trigger.auditor.accreditation_approved':
        'Accreditation is approved',
    'admin.messaging.trigger.auditor.accreditation_rejected':
        'Accreditation is declined',
    'admin.messaging.trigger.auditor.job_offered': 'An audit job is offered',
    'admin.messaging.trigger.auditor.report_reminder':
        'Staff reminds the Audit Partner about a report',
    'admin.messaging.trigger.auditor.accreditation_renewal_due':
        'The licence renewal falls due',
    'admin.messaging.trigger.support.complaint_answered':
        'Support answers a complaint',
    'admin.messaging.trigger.support.complaint_resolved':
        'A complaint is resolved',
    'admin.messaging.automations.live': '{count} automations live',
    'admin.messaging.automations.fire':
        'Each fires on its own when its trigger happens',
    'admin.messaging.automations.group': '{audience} automations',
    'admin.design.col.on': 'On',
    'admin.messaging.timing': 'As it happens · {channels}',
    'admin.messaging.state.live': 'Live',
    'admin.messaging.automations.sub':
        'Each notice goes out by itself when its event is recorded. They cannot be edited from the console yet.',
    'admin.messaging.templates.sub':
        'The transactional emails Rozine sends. They cannot be edited from the console yet.',
    'admin.messaging.rule.funds_disbursed': 'Funds disbursed',
    'admin.messaging.rule.verification_approved': 'Verification approved',
    'admin.messaging.rule.raise_fully_funded': 'Raise fully funded',
    'admin.messaging.template.account.new_device_sign_in': 'New device sign-in',
    'admin.messaging.template.account.one_time_code': 'One-time code',
    'admin.messaging.template.account.reset_password': 'Reset password',
    'admin.messaging.template.account.verify_email': 'Verify email address',
    'admin.messaging.template.auditor.accreditation_decided':
        'Accreditation decision',
    'admin.messaging.template.auditor.job_offered': 'Audit job offered',
    'admin.messaging.template.business.application_decided':
        'Application decision',
    'admin.messaging.template.business.disbursement_sent': 'Funds disbursed',
    'admin.messaging.template.business.raise_expired': 'Raise expired',
    'admin.messaging.template.business.repayment_reminder':
        'Repayment reminder',
    'admin.messaging.template.business.reporting_window_open':
        'Monthly report reminder',
    'admin.messaging.template.investor.investment_confirmed':
        'Investment confirmed',
    'admin.messaging.template.investor.payment_delayed': 'Payment delayed',
    'admin.messaging.template.investor.payout_received': 'Payout received',
    'admin.messaging.template.investor.purchase_confirmed':
        'Purchase confirmed',
    'admin.messaging.template.investor.raise_expired': 'Raise expired',
    'admin.messaging.template.investor.report_published': 'Report published',
    'admin.messaging.template.investor.verification_decided':
        'Verification decision',
    'admin.messaging.template.investor.wallet_transfer_completed':
        'Wallet transfer completed',
    'admin.messaging.no_match': 'No messages match your search.',
    'admin.messaging.older': 'Older messages',
    'admin.messaging.newest': 'Newest messages',
    'admin.design.academies.kpi.completed': 'Lessons completed',
    'admin.design.academies.kpi.live': 'Lessons live',
    'admin.design.col.track': 'Track',
    'admin.design.col.topics': 'Topics',
    'admin.design.academies.kpi.tracks': 'Tracks',
    'admin.design.academies.kpi.checks': 'Quick checks',
    'admin.design.academies.kpi.completed.sub': 'Kept on each learner’s device',
    'admin.design.academies.lessons.sub':
        'Each topic ends in one quick check. Progress stays on the learner’s device, so completions and scores are not reported here.',
    'admin.design.academies.tab.investor': 'Investor Academy',
    'admin.design.academies.tab.business': 'Business Academy',
    'admin.design.academies.tab.auditor': 'Auditor Academy',
    'admin.design.academies.tab.all': 'All',
    'admin.design.academies.lessons.live': '{count} live',
    'admin.design.academies.lessons.live_in': '{count} live in {app}',
    'admin.design.academies.app.investor': 'Investor app',
    'admin.design.academies.app.business': 'Business app',
    'admin.design.academies.app.auditor': 'Auditor app',
    'admin.design.academies.state.live': 'Live',
    'admin.design.academies.lessons.title': 'Lessons',
    'admin.design.academies.lessons.empty': 'No lessons to show yet.',
    'admin.design.app_control.apps.title': 'Apps',
    'admin.design.app_control.apps.sub':
        'The Investor, Business and Auditor apps and their features.',
    'admin.design.app_control.apps.empty': 'No app settings to show yet.',
    'admin.design.app_control.app.investor': 'Investor App',
    'admin.design.app_control.app.business': 'Business App',
    'admin.design.app_control.app.auditor': 'Auditor App',
    'admin.design.app_control.online': 'Online',
    'admin.design.app_control.feature.sign_ups': 'New sign-ups',
    'admin.design.app_control.feature.secondary_market': 'Secondary Market',
    'admin.design.app_control.feature.academy': 'Investor Academy',
    'admin.design.app_control.feature.note_creation': 'RNP creation',
    'admin.design.app_control.feature.monthly_reports': 'Monthly reports',
    'admin.design.app_control.feature.auditor_sign_ups': 'Auditor sign-ups',
    'admin.design.app_control.feature.auto_dispatch': '30km auto-dispatch',
    'admin.design.app_control.feature.flash_audits': 'Flash Audits (24h)',
    'admin.design.app_control.state.live': 'Live',
    'admin.design.app_control.state.not_built': 'Not built yet',
    'admin.design.app_control.sandbox.title': 'Sandbox limits',
    'admin.design.app_control.sandbox.sub':
        'How much of each CMA sandbox hard limit is used now. Nothing beyond a limit is accepted.',
    'admin.design.app_control.sandbox.empty': 'No sandbox usage to show yet.',
    'admin.design.app_control.sandbox.businesses':
        'Businesses with an active mandate',
    'admin.design.app_control.sandbox.investors': 'Verified investors',
    'admin.design.app_control.sandbox.principal': 'Principal outstanding',
    'admin.design.app_control.content.title': 'Content',
    'admin.design.app_control.content.sub':
        'Items across the Investor, Business and Auditor apps.',
    'admin.design.app_control.content.empty': 'No content to show yet.',
    'admin.design.app_control.type.business': 'Business',
    'admin.design.app_control.unrated': 'Not rated yet',
    'admin.design.app_control.content_state.active': 'Active',
    'admin.design.app_control.content_state.pending': 'Pending verification',
    'admin.design.engines.tab.sizing': 'Sizing',
    'admin.design.engines.tab.refinance': 'Refinance',
    'admin.design.engines.tab.queue': 'The queue',
    'admin.design.engines.tab.floors': 'Partner floors',
    'admin.design.engines.tab.cpa': 'CPA split',
    'admin.design.engines.tab.loss': 'Loss curves',
    'admin.design.engines.sizing.title': 'Sizing',
    'admin.design.engines.sizing.empty': 'No sizing inputs to show yet.',
    'admin.design.engines.refinance.title': 'Refinance',
    'admin.design.engines.refinance.empty': 'No refinance inputs to show yet.',
    'admin.design.engines.queue.title': 'The queue',
    'admin.design.engines.queue.empty': 'No queue inputs to show yet.',
    'admin.design.engines.floors.title': 'Partner floors',
    'admin.design.engines.floors.empty': 'No partner floors to show yet.',
    'admin.design.engines.cpa.title': 'CPA split',
    'admin.design.engines.cpa.empty': 'No CPA split inputs to show yet.',
    'admin.design.engines.loss.title': 'Loss curves',
    'admin.design.engines.loss.empty': 'No loss curves to show yet.',
    'admin.design.policies.tab.fees': 'Fees',
    'admin.design.policies.tab.underwriting': 'Underwriting',
    'admin.design.policies.tab.business': 'Business eligibility',
    'admin.design.policies.tab.investor': 'Investor eligibility',
    'admin.design.policies.tab.kyc': 'KYC requirements',
    'admin.design.policies.tab.penalties': 'Compliance & penalties',
    'admin.design.policies.tab.limits': 'Limits',
    'admin.design.policies.fees.sub':
        'What each side pays Rozine, and the share that goes to the auditor of record.',
    'admin.design.policies.underwriting.sub':
        'The rules that decide whether a raise is approved and on what terms.',
    'admin.design.policies.business.sub':
        'Who is allowed to raise capital on Rozine.',
    'admin.design.policies.investor.sub': 'Who is allowed to invest on Rozine.',
    'admin.design.policies.kyc.sub':
        'What each side must provide. Required blocks transacting until met; not collected is never asked for.',
    'admin.design.policies.penalties.sub':
        'How Rozine handles distress, defaults and violations.',
    'admin.design.policies.limits.sub':
        'Guardrails on activity across both apps.',
    'admin.design.policies.rules.empty': 'No rules to show yet.',
    'admin.design.system_health.signals.title': 'Signals',
    'admin.design.system_health.signals.empty': 'No signals to show yet.',
    'admin.nav.group.oversight': 'Oversight',
    'admin.nav.group.engagement': 'Engagement',
    'admin.pending.title': 'Nothing here yet',
    'admin.pending.body':
        "This part of the console isn't connected yet. It will show live records as soon as the platform records them.",
    'admin.nav.today': 'Dashboard',
    'admin.nav.businesses': 'Businesses',
    'admin.nav.investors': 'Investors',
    'admin.nav.auditors': 'Auditors',
    'admin.nav.applications': 'Applications',
    'admin.nav.disbursements': 'Disbursements',
    'admin.nav.ledger': 'Ledger',
    'admin.nav.staff': 'Staff & Roles',
    'admin.nav.events': 'Activity & Audit',
    'admin.section.about': 'About {title}',
    'admin.section.today.title': 'Rozine Operations Center',
    'admin.section.today.subtitle': 'Platform health at a glance',
    'admin.section.today.search': 'Search businesses, notes, investors…',
    'admin.section.applications.title': 'Applications Queue',
    'admin.section.applications.subtitle':
        'Review and underwrite new RNP submissions',
    'admin.section.applications.search': 'Search business or ID…',
    'admin.section.disbursements.title': 'Disbursements',
    'admin.section.disbursements.subtitle':
        'Approved raises awaiting release. Above the threshold, two different people must approve every release.',
    'admin.section.disbursements.search':
        'Search payouts by business, note or reference…',
    'admin.section.businesses.title': 'Business Directory',
    'admin.section.businesses.subtitle':
        'All verified businesses on the platform',
    'admin.section.businesses.search': 'Search businesses…',
    'admin.section.investors.title': 'Investor Directory',
    'admin.section.investors.subtitle': 'Manage investor accounts and KYC',
    'admin.section.investors.search': 'Search investors by name or KYC…',
    'admin.section.auditors.title': 'Auditor Network',
    'admin.section.auditors.subtitle':
        'ICPAR partners, their licences and standing',
    'admin.section.auditors.search':
        'Search partners by name, firm or district…',
    'admin.section.staff.title': 'Staff & Roles',
    'admin.section.staff.subtitle':
        'Operator accounts, their roles and account state',
    'admin.section.staff.search': 'Search operators by name or email…',
    'admin.section.ledger.title': 'Ledger',
    'admin.section.ledger.subtitle':
        'Every money movement, newest first. Open one to see its postings.',
    'admin.section.ledger.search':
        'Search the ledger by type, party, reference…',
    'admin.section.events.title': 'Activity & Audit',
    'admin.section.events.subtitle':
        'Immutable record of every action — filter by actor, action or object',
    'admin.section.events.search': 'Search the trail…',
    'admin.search.label': 'Search this section',
    'admin.search.empty_title': 'No matches',
    'admin.search.empty_body':
        'Nothing in {section} matches “{term}”. Try a shorter or different term.',
    'admin.account.open': 'Account, {name}',
    'admin.account.close': 'Close account menu',
    'admin.account.sign_out': 'Sign out',
    'admin.role.analyst': 'Analyst',
    'admin.role.approver': 'Approver',
    'admin.role.superadmin': 'Super-admin',
    'admin.role.access.analyst': 'Triage access',
    'admin.role.access.approver': 'Approver access',
    'admin.role.access.superadmin': 'Full access',
    'admin.role.compliance': 'Compliance',
    'admin.role.access.compliance': 'Compliance access',
    'admin.role.treasury': 'Treasury',
    'admin.role.access.treasury': 'Treasury access',
    'admin.kyc.intro':
        'Identity submissions from people who want to invest. Open one to check its documents, then approve or reject it with a reason.',
    'admin.kyc.tabs': 'Verification states',
    'admin.kyc.tab.submitted': 'Waiting for review',
    'admin.kyc.tab.decided': 'Decided',
    'admin.kyc.table': 'Identity submissions',
    'admin.kyc.queue': 'KYC review queue',
    'admin.kyc.col.document': 'Document',
    'admin.kyc.col.applicant': 'Applicant',
    'admin.kyc.col.checks': 'Automated checks',
    'admin.kyc.submitted_ago': 'Submitted {ago}',
    'admin.kyc.checks.passed': 'All automated KYC checks passed',
    'admin.kyc.checks.screening':
        'Age, ID number, documents, selfie · no screening yet',
    'admin.kyc.check.age': 'Age 18 or over',
    'admin.kyc.check.id_number': 'ID number format',
    'admin.kyc.check.documents': 'Document photos uploaded',
    'admin.kyc.check.selfie': 'Selfie taken',
    'admin.kyc.stats.aml': 'AML alerts',
    'admin.kyc.stats.sanctions': 'Sanctions hits',
    'admin.kyc.stats.not_connected': 'Not connected',
    'admin.kyc.stats.screening_sub': 'Screening (KYC-R04) not built yet',
    'admin.kyc.col.status': 'Status',
    'admin.kyc.col.decision': 'Decision',
    'admin.kyc.review': 'Review',
    'admin.kyc.review_named': 'Review {name}',
    'admin.kyc.stats.completion': 'KYC cases approved',
    'admin.kyc.stats.completion_sub':
        '{verified} of {total} in-app submissions',
    'admin.kyc.stats.percent': '{value}%',
    'admin.kyc.stats.pending': 'Pending reviews',
    'admin.kyc.stats.turnaround': 'Avg {hours}h turnaround',
    'admin.kyc.stats.turnaround_none': 'No decisions in 30 days',
    'admin.kyc.id_type.national_id': 'National ID',
    'admin.kyc.id_type.passport': 'Passport',
    'admin.kyc.id_type.drivers_license': 'Driving licence',
    'admin.kyc.status.draft': 'Reopened',
    'admin.kyc.status.submitted': 'Waiting',
    'admin.kyc.status.approved': 'Approved',
    'admin.kyc.status.rejected': 'Rejected',
    'admin.kyc.empty.submitted': 'No submissions are waiting for review.',
    'admin.kyc.empty.decided': 'No decisions yet.',
    'admin.kyc.more': 'Show more',
    'admin.kyc.drawer': 'Identity submission',
    'admin.kyc.facts': 'Submitted details',
    'admin.kyc.fact.date_of_birth': 'Date of birth',
    'admin.kyc.fact.id_type': 'Document',
    'admin.kyc.fact.id_number': 'Document number',
    'admin.kyc.fact.submitted_at': 'Submitted',
    'admin.kyc.documents': 'Documents',
    'admin.kyc.slot.front': 'ID front',
    'admin.kyc.slot.back': 'ID back',
    'admin.kyc.slot.selfie': 'Selfie',
    'admin.kyc.slot.passport': 'Passport photo page',
    'admin.kyc.document.replaced': 'Replaced upload',
    'admin.kyc.document.meta': '{name} · {size} KB',
    'admin.kyc.document.open': 'Download {name}',
    'admin.kyc.document.download': 'Download',
    'admin.kyc.document.current': 'Current documents',
    'admin.kyc.document.earlier': 'Earlier uploads',
    'admin.kyc.document.earlier_hint':
        'Files the person replaced with a newer upload. They are kept for the record and are not named for a document type, because what each one shows is not checked.',
    'admin.kyc.document.view': 'View {slot}',
    'admin.kyc.document.show': 'View',
    'admin.kyc.document.view_file': 'View {name}',
    'admin.kyc.document.pdf': 'PDF',
    'admin.kyc.viewer.position': '{index} of {total}',
    'admin.kyc.viewer.new_tab': 'Open in new tab',
    'admin.kyc.viewer.close': 'Close viewer',
    'admin.kyc.viewer.previous': 'Previous document',
    'admin.kyc.viewer.next': 'Next document',
    'admin.kyc.decision': 'Decision',
    'admin.kyc.history': 'History',
    'admin.kyc.command.verification.save': 'Saved a step',
    'admin.kyc.command.verification.upload': 'Uploaded a document',
    'admin.kyc.command.verification.submit': 'Submitted for review',
    'admin.kyc.command.verification.approve': 'Approved by Compliance',
    'admin.kyc.command.verification.reject': 'Rejected by Compliance',
    'admin.kyc.approve': 'Approve',
    'admin.kyc.reject': 'Reject',
    'admin.kyc.stage.approve.title': 'Approve this identity',
    'admin.kyc.stage.approve.body':
        'This verifies the person and opens their Investor access. Say what you checked.',
    'admin.kyc.stage.approve.cta': 'Approve identity',
    'admin.kyc.stage.approve.placeholder':
        'e.g. The ID photo, number and selfie match the account holder',
    'admin.kyc.stage.reject.title': 'Reject this submission',
    'admin.kyc.stage.reject.body':
        'The person sees your reason and can correct their details.',
    'admin.kyc.stage.reject.cta': 'Reject submission',
    'admin.kyc.stage.reject.placeholder':
        'e.g. The ID photo is blurred. Upload a sharper photo.',
    'admin.nav.mail_testers': 'Staging mail testers',
    'admin.section.mail_testers.title': 'Staging mail testers',
    'admin.section.mail_testers.subtitle':
        'Who staging may send real email to. Staging only.',
    'admin.section.mail_testers.search': 'Search testers by email…',
    'admin.mail_testers.intro':
        "Staging sends real email only to approved testers; mail to anyone else is dropped. Add a tester's exact address here, with a reason. Every change is recorded with your name.",
    'admin.mail_testers.server.title': 'Always approved on this server',
    'admin.mail_testers.server.body':
        'Set on the staging server. Change them there, not here.',
    'admin.mail_testers.add.label': 'Add a tester',
    'admin.mail_testers.add.email': 'Tester email address',
    'admin.mail_testers.add.placeholder': 'name@example.com',
    'admin.mail_testers.add.cta': 'Add tester',
    'admin.mail_testers.table': 'Named testers',
    'admin.mail_testers.col.email': 'Email',
    'admin.mail_testers.col.added_by': 'Added by',
    'admin.mail_testers.col.added_at': 'Added',
    'admin.mail_testers.col.actions': 'Actions',
    'admin.mail_testers.remove': 'Remove',
    'admin.mail_testers.remove_label': 'Remove {email}',
    'admin.mail_testers.empty': 'No named testers yet.',
    'admin.mail_testers.stage.add.title': 'Add {email} as a tester',
    'admin.mail_testers.stage.add.body':
        'Staging will be able to send this address real email, including sign-up and password-reset messages.',
    'admin.mail_testers.stage.add.cta': 'Add tester',
    'admin.mail_testers.stage.add.placeholder':
        'Why does this person need staging email?',
    'admin.mail_testers.stage.remove.title': 'Remove {email}',
    'admin.mail_testers.stage.remove.body':
        'Staging will stop sending this address email straight away.',
    'admin.mail_testers.stage.remove.cta': 'Remove tester',
    'admin.mail_testers.stage.remove.placeholder':
        'Why is this tester being removed?',
    'admin.drawer.close': 'Close',
    'admin.stage.reason_label': 'Reason for the record',
    'admin.stage.logged_as': 'Logged to the audit trail as {name} · {role}',
    'admin.stage.cancel': 'Cancel',
    'admin.rating.pending': 'Pending audit',
    'admin.rating.of_five': '{band} {score} / 5',
    'admin.rating.band.strong': 'Strong',
    'admin.rating.band.stable': 'Stable',
    'admin.rating.band.weak': 'Weak',
    'admin.rating.band.distressed': 'Distressed',
    'admin.design.ratings.weights.title': 'How a rating is computed',
    'admin.design.ratings.weights.sub':
        'Each input is scaled from 0 to 100 across its range, weighted, and summed; the score divided by 20 is the rating out of 5. Conduct caps apply after.',
    'admin.design.ratings.weights.empty':
        'Only underwriters can see the scorecard.',
    'admin.design.ratings.bands.title': 'Bands',
    'admin.design.ratings.bands.empty': 'Only underwriters can see the bands.',
    'admin.engine.target_dscr': 'Target DSCR',
    'admin.engine.minimum_dscr': 'Minimum DSCR',
    'admin.engine.repeat_monthly_dscr': 'Monthly DSCR · repeat issue',
    'admin.engine.min_principal': 'Smallest raise',
    'admin.engine.max_principal': 'Largest raise',
    'admin.engine.issuance_unit': 'Raise rounded down to',
    'admin.engine.first_issue_months': 'Audited months · first issue',
    'admin.engine.repeat_issue_months': 'Audited months · repeat issue',
    'admin.engine.tenors': 'Term · months',
    'admin.engine.rate_band': 'Rate band · flat, all-in',
    'admin.engine.platform_fee': 'Platform fee · at disbursement',
    'admin.engine.cpa_share': 'Audit Partner share of the fee',
    'admin.engine.coverage': 'Debt coverage',
    'admin.engine.cfads_margin': 'Cash available for debt / revenue',
    'admin.engine.nocf_stability': 'Operating cash stability',
    'admin.engine.positive_months': 'Months with positive cash',
    'admin.engine.history_depth': 'Months of history',
    'admin.engine.conduct': 'Repayment and reporting conduct',
    'admin.engine.adverse_conduct_cap': 'Rating cap · late or missed reports',
    'admin.engine.default_cap': 'Rating cap · default',
    'admin.engine.strong': 'Strong',
    'admin.engine.stable': 'Stable',
    'admin.engine.weak': 'Weak',
    'admin.engine.distressed': 'Distressed',
    'admin.design.engines.sizing.sub':
        'All four limits apply and the lowest binds: capacity at the target DSCR, the amount asked, the largest raise and the exposure room left. A raise below the smallest is refused. The result is then stepped down until DSCR holds at the target, and rounded down to the issuance unit.',
    'admin.design.ratings.movers.title': 'What moves a rating',
    'admin.design.ratings.movers.sub':
        'Each verified statement recomputes the financial inputs, so revenue and cash moving up or down move the rating. Repayments and monthly reports move conduct, and the caps hold a rating down while arrears, a reporting breach or a default stand. No staff override exists.',
    'admin.design.ratings.movers.empty':
        'Only underwriters can see what moves a rating.',
    'admin.engine.new_issuer_conduct':
        'Conduct before any repayment · out of 100',
    'admin.engine.instalment_conduct':
        'Instalments paid on time · share of conduct',
    'admin.engine.report_conduct': 'Monthly reports on time · share of conduct',
    'admin.policy.eyebrow': 'Set in Policies',
    'admin.policy.open': 'Policies',
    'admin.policy.strip.audit_routing': 'Audit routing band',
    'admin.policy.strip.min_statement_years': 'Min statement years',
    'admin.policy.strip.kyc_reverify': 'KYC re-verify',
    'admin.policy.target_dscr': 'Target DSCR',
    'admin.policy.listing_audit': 'Listing audit',
    'admin.policy.audit_routing': 'Audit routing floor',
    'admin.policy.rate_band': 'Rate band · flat, all-in',
    'admin.policy.min_revenue': 'Min annual revenue',
    'admin.policy.min_statement_years': 'Min years of statements',
    'admin.policy.rdb_certificate': 'RDB certificate',
    'admin.policy.max_notes': 'Max active notes / business',
    'admin.policy.platform_fee': 'Platform fee · at disbursement',
    'admin.policy.cpa_share': 'CPA share — Kigali',
    'admin.policy.listing_fee': 'Listing fee',
    'admin.policy.repayment_fee': 'Repayment fee',
    'admin.payments.policy_title': 'Fee schedule in effect',
    'admin.policy.minimum_age': 'Minimum age',
    'admin.policy.identity_evidence': 'Identity files per case',
    'admin.policy.complaint_acknowledge': 'Complaint acknowledged within',
    'admin.policy.complaint_resolve': 'Complaint resolved within',
    'admin.kyc.policy_title': 'Compliance rules in effect',
    'admin.policy.min_principal': 'Minimum raise target',
    'admin.policy.max_principal': 'Maximum raise target',
    'admin.policy.first_issue_months': 'Audited months · first issue',
    'admin.policy.repeat_issue_months': 'Audited months · repeat issue',
    'admin.policy.withdrawal_min': 'Smallest withdrawal',
    'admin.policy.withdrawal_max': 'Largest withdrawal',
    'admin.policy.cpa_share_upcountry': 'CPA share — upcountry',
    'admin.policy.resale_fee': 'Resale fee · seller',
    'admin.policy.earnings_fee': 'Earnings fee bands',
    'admin.policy.cpa_earnings_share': 'CPA share of the earnings fee',
    'admin.policy.application_fee': 'Application fee · sandbox',
    'admin.policy.withdrawal_fee': 'Withdrawal fee',
    'admin.policy.loss_reserve': 'Loss reserve',
    'admin.policy.min_rating': 'Minimum rating to raise',
    'admin.policy.revenue_cap': 'Largest raise · share of revenue',
    'admin.policy.kyc_reverify': 'KYC re-verification',
    'admin.policy.late_charge': 'Late charges',
    'admin.policy.miss_grace': 'Miss grace period',
    'admin.policy.default_at': 'Default',
    'admin.policy.auto_freeze': 'Auto-freeze on default',
    'admin.policy.min_investment': 'Minimum investment · one unit',
    'admin.policy.concentration': 'Max investment / note · share of the raise',
    'admin.policy.raise_days': 'Raise window · days',
    'admin.policy.resale_listing_days': 'Resale listing life · days',
    'admin.policy.resale_reservation_minutes': 'Resale reservation · minutes',
    'admin.policy.cpa_radius': 'CPA dispatch radius',
    'admin.policy.sandbox_businesses': 'Sandbox limit · businesses',
    'admin.policy.sandbox_investors': 'Sandbox limit · investors',
    'admin.policy.sandbox_principal': 'Sandbox limit · principal outstanding',
    'admin.policy.residency': 'Allowed countries of residence',
    'admin.policy.risk_statements': 'Risk statements before a first investment',
    'admin.policy.bank_signatories': 'Min bank signatories',
    'admin.policy.recovery_rate': 'Default recovery rate',
    'admin.policy.withdrawal_daily_cap': 'Daily withdrawal cap',
    'admin.policy.hint.bank_signatories':
        'One official representative acts for the business. A second signatory is never required.',
    'admin.policy.hint.recovery_rate':
        'Share of outstanding paid to holders on default.',
    'admin.policy.hint.withdrawal_daily_cap':
        'Maximum an account can withdraw per day.',
    'admin.policy.term.pro_rata': 'All of it, pro rata to holders',
    'admin.policy.hint.platform_fee':
        'Taken from the raise when it is disbursed to the business.',
    'admin.policy.hint.cpa_share':
        'Share of the platform fee paid to the auditor of record on Kigali deals.',
    'admin.policy.hint.cpa_share_upcountry':
        'The same share on deals outside Kigali.',
    'admin.policy.hint.earnings_fee':
        "Taken from what an investor earns. The band follows the investor's tier.",
    'admin.policy.hint.cpa_earnings_share':
        'Share of the earnings fee paid to the auditor of record.',
    'admin.policy.hint.resale_fee':
        'Charged to the seller when a note is resold before maturity.',
    'admin.policy.hint.listing_fee': 'Charged to a business to list a raise.',
    'admin.policy.hint.repayment_fee': 'Charged on each monthly repayment.',
    'admin.policy.hint.application_fee':
        'Charged to apply while Rozine runs in the sandbox.',
    'admin.policy.hint.withdrawal_fee': 'Applied to wallet withdrawals.',
    'admin.policy.hint.loss_reserve':
        'Share of fees set aside against defaults.',
    'admin.policy.hint.target_dscr':
        'Max raise is capped so cash flow covers repayments at this ratio.',
    'admin.policy.hint.listing_audit':
        'A CPA audits every raise before it lists.',
    'admin.policy.hint.audit_routing':
        'Lowest DSCR a deal may have. Below it, the application is refused.',
    'admin.policy.hint.min_rate': 'Lowest flat rate a note can carry, all-in.',
    'admin.policy.hint.max_rate': 'Highest flat rate a note can carry, all-in.',
    'admin.policy.hint.note_unit_price': 'Price of a single Rozine note.',
    'admin.policy.hint.min_term': 'Shortest allowed note term.',
    'admin.policy.hint.max_term': 'Longest allowed note term.',
    'admin.policy.hint.min_principal': 'Smallest raise a business may request.',
    'admin.policy.hint.max_principal': 'Largest raise a business may request.',
    'admin.policy.hint.min_rating': 'Lowest Rozine rating that may raise.',
    'admin.policy.hint.revenue_cap':
        'Largest raise as a share of verified annual revenue.',
    'admin.policy.hint.registration_countries':
        'Where a business must be registered.',
    'admin.policy.hint.min_statement_years':
        'Consecutive years of bank/MoMo statements required.',
    'admin.policy.hint.min_revenue': 'Minimum trailing revenue to qualify.',
    'admin.policy.hint.first_issue_months':
        'Months of statements audited before a first raise.',
    'admin.policy.hint.repeat_issue_months':
        'Months audited before each later raise.',
    'admin.policy.hint.rdb_certificate':
        'Business must upload a valid RDB certificate.',
    'admin.policy.hint.max_notes':
        'Live and repaying notes a business may hold at once.',
    'admin.policy.hint.residency': 'Where an investor must live.',
    'admin.policy.hint.minimum_age': 'Youngest an investor may be.',
    'admin.policy.hint.min_investment': 'Smallest single investment allowed.',
    'admin.policy.hint.concentration':
        'Cap one investor can place in a single note.',
    'admin.policy.hint.risk_statements':
        'Statements an investor accepts before a first investment.',
    'admin.policy.hint.kyc_investor_id': 'Front and back of the document.',
    'admin.policy.hint.kyc_investor_selfie':
        'A live photo matched to the document.',
    'admin.policy.hint.kyc_investor_dob':
        'Shows the investor is old enough to invest.',
    'admin.policy.hint.kyc_investor_address': 'Onboarding does not ask for it.',
    'admin.policy.hint.kyc_business_rdb':
        'Checked before a business may apply.',
    'admin.policy.hint.identity_evidence':
        'Photos in each identity case: ID front, ID back and selfie.',
    'admin.policy.hint.kyc_reverify':
        'Months before a user must re-verify their KYC.',
    'admin.policy.hint.late_charge': 'Added to an overdue instalment.',
    'admin.policy.hint.miss_grace': 'Days before a missed payment is flagged.',
    'admin.policy.hint.default_at':
        'When an unpaid instalment becomes a default.',
    'admin.policy.hint.auto_freeze':
        'Freeze a business automatically when it defaults.',
    'admin.policy.hint.complaint_acknowledge':
        'Time to acknowledge a new complaint.',
    'admin.policy.hint.complaint_resolve': 'Time to resolve a complaint.',
    'admin.policy.hint.raise_days': 'How long a raise stays open.',
    'admin.policy.hint.withdrawal_min': 'Smallest wallet withdrawal.',
    'admin.policy.hint.withdrawal_max': 'Largest wallet withdrawal allowed.',
    'admin.policy.hint.resale_listing_days':
        'How long a resale listing stays open.',
    'admin.policy.hint.resale_reservation_minutes':
        'How long a buyer holds a resale unit before paying.',
    'admin.policy.hint.cpa_radius': 'Furthest a CPA is sent for an audit.',
    'admin.policy.hint.sandbox_businesses':
        'Cap on active businesses while in the CMA sandbox.',
    'admin.policy.hint.sandbox_investors':
        'Cap on verified investors while in the sandbox.',
    'admin.policy.hint.sandbox_principal':
        'Cap on total principal raised while in the sandbox.',
    'admin.policy.hint.rate_band': 'Flat rate range a note can carry, all-in.',
    'admin.policy.hint.dual_control_threshold':
        'From this amount, a second approver is needed.',
    'admin.policy.min_rate': 'Minimum rate · flat',
    'admin.policy.max_rate': 'Maximum rate · flat',
    'admin.policy.note_unit_price': 'Note unit price',
    'admin.policy.min_term': 'Minimum term · months',
    'admin.policy.max_term': 'Maximum term · months',
    'admin.policy.registration_countries': 'Allowed countries of registration',
    'admin.policy.kyc_investor_id': 'Investor · National ID / passport',
    'admin.policy.kyc_investor_selfie': 'Investor · Liveness selfie',
    'admin.policy.kyc_investor_dob': 'Investor · Date of birth',
    'admin.policy.kyc_investor_address': 'Investor · Proof of address',
    'admin.policy.kyc_business_rdb': 'Business · RDB certificate',
    'admin.policy.term.rwanda': 'Rwanda',
    'admin.policy.term.not_collected': 'Not collected',
    'admin.policy.term.resident_rwanda': 'Resident in Rwanda',
    'admin.policy.term.every_deal': 'Required on every deal',
    'admin.policy.term.required': 'Required',
    'admin.policy.term.not_set': 'Not set',
    'admin.policy.term.none': 'None',
    'admin.policy.term.late_from_due': 'None · late from the due date',
    'admin.policy.term.day_31': 'Day 31 unpaid',
    'admin.policy.term.late_schedule': '5% at D, day 7 and day 30 · cap 15%',
    'admin.finance.stream.platform_fee': 'Platform fee · Rozine share',
    'admin.finance.stream.audit_partner_share':
        'Platform fee · Audit Partner share (payable)',
    'admin.finance.stream.deposit_fees': 'Deposit fees',
    'admin.finance.collected_untracked': 'Not tracked yet',
    'admin.finance.paid_by.business': 'Businesses',
    'admin.finance.paid_by.investor': 'Investors',
    'admin.finance.paid_by.seller': 'Seller',
    'admin.finance.stream.earnings_fee': 'Earnings fee',
    'admin.finance.stream.resale_fee': 'Resale fee',
    'admin.finance.stream.withdrawal_fee': 'Withdrawal fee',
    'admin.finance.stream.loss_reserve': 'Loss reserve',
    'admin.policy.dual_control_threshold': 'Two approvers from',
    'admin.time.now': 'just now',
    'admin.time.minutes': '{count}m ago',
    'admin.time.hours': '{count}h ago',
    'admin.time.days': '{count}d ago',
    'admin.ledger.kind.investment': 'Investment',
    'admin.ledger.kind.disbursement': 'Disbursement',
    'admin.ledger.kind.repayment': 'Repayment',
    'admin.ledger.kind.platform_fee': 'Platform fee',
    'admin.ledger.kind.repayment_fee': 'Repayment fee',
    'admin.ledger.kind.auditor_share': 'Audit Partner share',
    'admin.ledger.kind.distribution': 'Investor payout',
    'admin.ledger.kind.recovery': 'Recovery payout',
    'admin.ledger.kind.deposit': 'Wallet deposit',
    'admin.ledger.kind.withdrawal': 'Withdrawal',
    'admin.ledger.kind.secondary': 'Secondary trade',
    'admin.ledger.kind.secondary_fee': 'Secondary fee',
    'admin.ledger.kind.contra': 'Contra entry',
    'admin.today.commands': 'Quick commands',
    'admin.today.command.review_queue': 'Review queue',
    'admin.today.command.audit_desk': 'Audit desk',
    'admin.today.command.policies': 'Policies',
    'admin.today.kpi.capital_raised': 'Capital Issued',
    'admin.today.kpi.capital_raised_trend': 'Principal of issued notes',
    'admin.today.kpi.active_businesses': 'Active Businesses',
    'admin.today.kpi.active_businesses_trend': 'On the platform',
    'admin.today.kpi.verified_investors': 'Verified Investors',
    'admin.today.kpi.verified_investors_trend': 'KYC cleared',
    'admin.today.kpi.outstanding_notes': 'Open Notes',
    'admin.today.kpi.outstanding_notes_trend': 'Live · funding · repaying',
    'admin.today.kpi.treasury_position': 'Treasury Position',
    'admin.today.kpi.treasury_positive': 'Fees in, minus payouts',
    'admin.today.kpi.treasury_negative': 'Paid out more than collected',
    'admin.today.kpi.default_rate': 'Default Rate',
    'admin.today.kpi.default_rate_trend': '{failed} of {total} notes',
    'admin.today.kpi.secondary_volume': 'Secondary Volume Today',
    'admin.today.kpi.secondary_volume_trend': 'Investor-to-investor',
    'admin.today.kpi.not_tracked': 'Not tracked yet',
    'admin.today.attention.applications_pending': 'Applications pending',
    'admin.today.attention.kyc_awaiting': 'KYC awaiting review',
    'admin.today.attention.notes_late': 'Notes late on payment',
    'admin.today.attention.notes_default_risk': 'Notes at default risk',
    'admin.today.attention.frozen_accounts': 'Frozen accounts',
    'admin.today.breaks.title': 'Reconciliation breaks',
    'admin.today.breaks.caption':
        'Every break stays here, aged, until it is explained. Give it an owner or escalate it.',
    'admin.today.breaks.ledger': 'Open the ledger',
    'admin.today.breaks.empty_title': 'Ledger fully reconciled',
    'admin.today.breaks.empty_body': 'No open breaks.',
    'admin.today.breaks.untracked_title': 'Reconciliation not tracked yet',
    'admin.today.breaks.untracked_body':
        'Breaks appear here once statement reconciliation is connected.',
    'admin.today.breaks.age': 'Open {days} days',
    'admin.today.breaks.unassigned': 'Unassigned',
    'admin.today.breaks.owner': 'Owner: {name}',
    'admin.today.breaks.assign': 'Assign to me',
    'admin.today.breaks.escalate': 'Escalate',
    'admin.today.breaks.assign_title': 'Take ownership of {reference}',
    'admin.today.breaks.assign_body':
        "You become this break's owner until it is explained. Say how you will work it.",
    'admin.today.breaks.assign_cta': 'Take ownership',
    'admin.today.breaks.assign_placeholder':
        'e.g. Matching against the MoMo settlement file for 22 Sep.',
    'admin.today.breaks.escalate_title': 'Escalate {reference}',
    'admin.today.breaks.escalate_body':
        'The break moves to Finance leadership. Say what you found and why it needs them.',
    'admin.today.breaks.escalate_cta': 'Escalate',
    'admin.today.breaks.escalate_placeholder':
        'e.g. Provider statement disagrees with our dispatch record; needs the bank.',
    'admin.today.capital.title': 'Capital raised',
    'admin.today.capital.from': 'From',
    'admin.today.capital.to': 'To',
    'admin.today.capital.from_date': 'From date',
    'admin.today.capital.from_time': 'From time',
    'admin.today.capital.to_date': 'To date',
    'admin.today.capital.to_time': 'To time',
    'admin.today.capital.clear': 'Clear',
    'admin.trend.refused': 'Choose a To date on or after the From date.',
    'admin.today.capital.all_time': 'All time',
    'admin.today.capital.range': '{from} → {to}',
    'admin.today.capital.caption': '{range} · grouped by {grain}',
    'admin.today.capital.grain.year': 'year',
    'admin.today.capital.grain.month': 'month',
    'admin.today.capital.grain.day': 'day',
    'admin.today.capital.grain.hour': 'hour',
    'admin.today.health.title': 'Portfolio health',
    'admin.today.health.caption':
        '{count} issued notes, by their newest monthly report',
    'admin.today.health.donut': '{pct}% of notes healthy',
    'admin.today.health.healthy_caption': 'healthy',
    'admin.today.health.healthy': 'Healthy',
    'admin.today.health.watch': 'Watch',
    'admin.today.health.distressed': 'Distressed',
    'admin.today.health.untracked': 'Not tracked yet',
    'admin.today.activity.title': 'Live activity',
    'admin.today.activity.caption': 'Across the ecosystem',
    'admin.today.activity.empty': 'No money has moved yet.',
    'admin.today.activity.loading': 'Loading activity',
    'admin.today.activity.failed': 'Activity could not be loaded.',
    'admin.today.activity.retry': 'Try again',
    'admin.today.untracked': 'Not tracked in the console yet.',
    'admin.today.capital.empty': 'No capital raised in this window.',
    'admin.today.activity.see_all': 'See all activity in the ledger',
    'admin.today.lifecycle.title': 'Note lifecycle',
    'admin.today.lifecycle.caption': 'Where every note sits right now',
    'admin.today.lifecycle.submitted': 'Submitted',
    'admin.today.lifecycle.live': 'Live',
    'admin.today.lifecycle.funded': 'Funded',
    'admin.today.lifecycle.repaying': 'Repaying',
    'admin.today.lifecycle.matured': 'Matured',
    'admin.today.lifecycle.failed': 'Failed',
    'admin.today.pending.title': 'Pending applications',
    'admin.today.pending.empty': 'No applications waiting.',
    'admin.today.pending.restricted':
        'You do not have access to pending applications.',
    'admin.today.pending.review': 'Review',
    'admin.today.pending.review_named': 'Review {name}',
    'admin.today.sectors.title': 'Sector exposure',
    'admin.today.sectors.about': 'About sector exposure',
    'admin.today.sectors.tip':
        'Outstanding capital by industry. Red means at least one note on watch or in distress.',
    'admin.today.sectors.notes': '{count} notes',
    'admin.today.sectors.empty': 'No notes have issued yet.',
    'admin.today.treasury.title': 'Treasury snapshot',
    'admin.today.treasury.invested': 'Capital invested (primary)',
    'admin.today.treasury.disbursed': 'Disbursed to businesses',
    'admin.today.treasury.platform_net': 'Platform net position',
    'admin.today.treasury.paid_to_investors': 'Paid out to investors',
    'admin.today.collections.title': 'Collections',
    'admin.today.collections.caption': '{count} notes in repayment',
    'admin.today.collections.outstanding': 'Outstanding',
    'admin.today.collections.next_due': 'Next installments due',
    'admin.today.collections.on_track': 'On track',
    'admin.today.collections.late': 'Late',
    'admin.today.collections.default_risk': 'At default risk',
    'admin.trail.by': 'by {actor}',
    'admin.trail.reason': 'Reason: {reason}',
    'admin.applications.policy_title': 'Underwriting rules in effect',
    'admin.applications.tabs': 'Application states',
    'admin.applications.tab.pending': 'Pending',
    'admin.applications.tab.under_review': 'Under Review',
    'admin.applications.tab.escalated': 'Escalated',
    'admin.applications.tab.approved': 'Approved',
    'admin.applications.tab.rejected': 'Rejected',
    'admin.applications.table': 'Applications',
    'admin.applications.col.business': 'Business · Note',
    'admin.applications.col.sector': 'Sector',
    'admin.applications.col.requested': 'Requested',
    'admin.applications.col.capacity': 'Capacity used',
    'admin.applications.col.rating': 'Engine rating',
    'admin.applications.col.submitted': 'Submitted',
    'admin.applications.col.decision': 'Decision · Actions',
    'admin.applications.terms': '{term}mo · {rate}% flat',
    'admin.applications.capacity_used': 'Capacity used',
    'admin.applications.capacity_unavailable': 'Not available',
    'admin.applications.older': 'Older applications',
    'admin.decision.reason_code.CURRENT_RELEASE_REVIEW_REQUIRED':
        'Current release review required: a retained quote is not a current approval. Check the release gates below.',
    'admin.decision.reason_code.DSCR_BELOW_CUTOFF':
        'Coverage below 1.25: cash flow cannot carry the instalment.',
    'admin.decision.reason_code.RESTRICTION_ACTIVE':
        'An active credit restriction blocks new borrowing.',
    'admin.applications.term_only': '{term}mo',
    'admin.applications.decision.approve': 'Auto-approve',
    'admin.applications.decision.reject': 'Flag: reject',
    'admin.applications.decision.audit': 'Route to audit',
    'admin.applications.decision.review': 'Needs review',
    'admin.applications.review': 'Review',
    'admin.applications.review_named': 'Review {name}',
    'admin.applications.approve': 'Approve',
    'admin.applications.approve_named': 'Approve {name}',
    'admin.applications.empty': 'No applications in this queue.',
    'admin.applications.state.submitted': 'Pending',
    'admin.applications.state.under_review': 'Under review',
    'admin.applications.state.info_requested': 'Info requested',
    'admin.applications.state.escalated': 'Escalated',
    'admin.applications.state.approved': 'Approved',
    'admin.applications.state.rejected': 'Rejected',
    'admin.review.label': 'Underwriting review, {business}',
    'admin.review.eyebrow': 'Underwriting review',
    'admin.review.recommend.approve': 'Recommend: Approve',
    'admin.review.recommend.reject': 'Recommend: Reject',
    'admin.review.recommend.audit': 'Recommend: Route to audit',
    'admin.review.recommend.review': 'Recommend: Manual review',
    'admin.review.audit.missing_title': 'Listing audit missing',
    'admin.review.audit.missing_body':
        "Approval is blocked until an Audit Partner seals this business's listing audit. Request it from the business, or escalate.",
    'admin.review.audit.pending_title': 'Listing audit in progress',
    'admin.review.audit.pending_body':
        'Approval unlocks when the Audit Partner seals the audit. Nothing can be listed before then.',
    'admin.review.requested': 'Requested',
    'admin.review.rating': 'Rozine rating',
    'admin.review.rating_score': '· {score} / 5',
    'admin.review.term': 'Term',
    'admin.review.term_months': '{count} months',
    'admin.review.yield': 'Investor yield',
    'admin.review.yield_value': '{rate}% flat',
    'admin.review.capacity_title': 'Capacity used by this raise',
    'admin.review.capacity_approved': 'Approved capacity {amount}',
    'admin.review.capacity_existing': '{count} active notes · {amount} out',
    'admin.review.factors_title': 'Rating breakdown',
    'admin.review.factors_caption': 'From the underwriting engine · read-only',
    'admin.review.factor.repayment_history': 'Repayment history',
    'admin.review.factor.revenue_consistency': 'Revenue consistency',
    'admin.review.factor.statement_record': 'Statement record',
    'admin.review.factor.capacity_headroom': 'Capacity headroom',
    'admin.review.factor.sector_risk': 'Sector risk',
    'admin.review.use_of_funds': 'Use of funds',
    'admin.review.submitted': 'Submitted {date}',
    'admin.review.reviewer': 'Reviewer: {name}',
    'admin.review.unassigned': 'Unassigned',
    'admin.review.business_profile': 'View full business profile →',
    'admin.review.trail': 'Decision trail',
    'admin.review.trail_empty': 'No decisions recorded yet.',
    'admin.review.approve': 'Approve & create note',
    'admin.review.reject': 'Reject',
    'admin.review.take': 'Take for review',
    'admin.review.info': 'Request info',
    'admin.review.escalate': 'Escalate',
    'admin.review.stage.approve.title': 'Approve & create note',
    'admin.review.stage.approve.body':
        'This creates a live {amount} note at {rate}% flat over {term} months and publishes it to investors. The business will be notified.',
    'admin.review.stage.approve.cta': 'Confirm approval',
    'admin.review.stage.approve.placeholder':
        'e.g. Evidence complete; DSCR and capacity within policy.',
    'admin.review.stage.reject.title': 'Reject application',
    'admin.review.stage.reject.body':
        'The business will be notified. Add a clear reason so they understand and can re-apply.',
    'admin.review.stage.reject.cta': 'Confirm rejection',
    'admin.review.stage.reject.placeholder':
        'e.g. Requested amount exceeds approved capacity; reduce and re-submit.',
    'admin.review.stage.info.title': 'Request more information',
    'admin.review.stage.info.body':
        'Tell the business exactly what to provide. They’ll get a notification with your request.',
    'admin.review.stage.info.cta': 'Send request',
    'admin.review.stage.info.placeholder':
        'e.g. Upload the last 6 months of bank and mobile-money statements.',
    'admin.review.stage.escalate.title': 'Escalate to Credit Committee',
    'admin.review.stage.escalate.body':
        'This moves the application to senior review. Add context for the committee.',
    'admin.review.stage.escalate.cta': 'Escalate',
    'admin.review.stage.escalate.placeholder':
        'e.g. Borderline evidence but strong repayment history — needs a committee view.',
    'admin.review.stage.take.title': 'Take for review',
    'admin.review.stage.take.body':
        'The application moves to Under review with you as its reviewer.',
    'admin.review.stage.take.cta': 'Take for review',
    'admin.review.stage.take.placeholder':
        "e.g. Picking this up for today's credit session.",
    'admin.maker_checker.by': '{actor} · {at}',
    'admin.disbursements.policy_title': 'Release rules in effect',
    'admin.disbursements.title': 'Pending disbursements',
    'admin.disbursements.table': 'Pending disbursements',
    'admin.disbursements.col.reference': 'Payout ref',
    'admin.disbursements.col.note': 'Note',
    'admin.disbursements.col.recipient': 'Recipient',
    'admin.disbursements.col.amount': 'Amount',
    'admin.disbursements.col.due': 'Due',
    'admin.disbursements.col.actions': 'State · Actions',
    'admin.disbursements.state.ready': 'Waiting for automatic payment',
    'admin.disbursements.state.awaiting_second_approver':
        'Awaiting second approver',
    'admin.disbursements.state.on_hold': 'On hold',
    'admin.disbursements.state.dispatched': 'Sent to provider',
    'admin.disbursements.state.paid': 'Paid',
    'admin.disbursements.state.failed': 'Payout failed',
    'admin.disbursements.action.release': 'Release',
    'admin.disbursements.action.check': 'Check',
    'admin.disbursements.action.open': 'Open',
    'admin.disbursements.action.inspect': 'Inspect',
    'admin.disbursements.open_named': 'Open {reference}',
    'admin.disbursements.today': 'Today',
    'admin.disbursements.tomorrow': 'Tomorrow',
    'admin.disbursements.in_days': 'In {count} days',
    'admin.disbursements.overdue': 'Overdue {count}d',
    'admin.disbursements.empty_title': 'All disbursements released',
    'admin.disbursements.empty_body': 'No payouts owed right now.',
    'admin.disbursements.drawer_label': 'Disbursement {reference}',
    'admin.disbursements.amount': 'Amount',
    'admin.disbursements.platform_fee':
        '3% platform fee {fee}, of which {share} to the Audit Partner. The Business receives {payout}.',
    'admin.disbursements.destination': 'Destination',
    'admin.disbursements.due': 'Due',
    'admin.disbursements.rule_dual':
        'At or above {threshold}, a release needs two different people: one authorizes, another approves. Nobody approves their own release.',
    'admin.disbursements.rule_single':
        'Below {threshold}, one authorized approver releases this payout.',
    'admin.disbursements.failure_title': 'Payout failed',
    'admin.disbursements.failure_code': 'Code',
    'admin.disbursements.failure_provider': 'Provider reference',
    'admin.disbursements.failure_at': 'Failed at',
    'admin.disbursements.failure_attempts': 'Attempts',
    'admin.disbursements.failure_inspect':
        'Inspect before retrying: confirm with the provider that no money left, so the same payout is never sent twice.',
    'admin.disbursements.trail': 'Release trail',
    'admin.disbursements.trail_empty': 'Nothing recorded yet.',
    'admin.disbursements.command.hold': 'Hold',
    'admin.disbursements.command.retry': 'Retry payout',
    'admin.disbursements.stage.hold.title': 'Put this release on hold',
    'admin.disbursements.stage.hold.body':
        'Nothing is paid while it is held. Say why, so the next person knows what to clear.',
    'admin.disbursements.stage.hold.cta': 'Hold release',
    'admin.disbursements.stage.hold.placeholder':
        'e.g. Waiting for the business to confirm its new MoMo number.',
    'admin.disbursements.stage.retry.title': 'Retry this payout',
    'admin.disbursements.stage.retry.body':
        'Only retry once the provider confirms the failed attempt moved no money. {amount} will be sent to {business} again.',
    'admin.disbursements.stage.retry.cta': 'Retry payout',
    'admin.disbursements.stage.retry.placeholder':
        'e.g. MTN confirmed reference MM-88213 reversed in full; destination re-verified.',
    'admin.parties.policy_title': 'Business eligibility rules',
    'admin.parties.filter': 'Filter by status',
    'admin.audit_reports.title': 'Audit report tracker',
    'admin.audit_reports.caption':
        'Every audit with the state of its report. Monthly audits are verified on site by the 7th; the business then has 24 hours to dispute.',
    'admin.audit_reports.field.title': 'Field Flash Audit operations',
    'admin.audit_reports.field.caption':
        'Each field audit shows its CPA and licence, report state and weighted variance, with the retained seal digest and report PDF when available.',
    'admin.audit_reports.field.table': 'Field Flash Audits',
    'admin.audit_reports.field.empty': 'No ISRS 4400 engagements recorded yet.',
    'admin.audit_reports.feed.title': 'Recently audited & published',
    'admin.audit_reports.feed.audited':
        '✓ Audited on site by {auditor} · {licence}',
    'admin.audit_reports.feed.empty': 'No monthly reports audited yet.',
    'admin.audit_reports.filter': 'Audit report views',
    'admin.audit_reports.table': 'Audit reports',
    'admin.audit_reports.empty_title': 'Nothing in this view.',
    'admin.audit_reports.empty_body':
        'Audits appear here once an Audit Partner accepts them.',
    'admin.audit_reports.monthly': 'Monthly audit',
    'admin.audit_reports.flash': 'Flash Audit',
    'admin.audit_reports.due': 'Due {date}',
    'admin.audit_reports.sealed_on': 'Sealed {date}',
    'admin.audit_reports.period_flash': 'On demand',
    'admin.audit_reports.cosign_by': 'Dispute window ends {date}',
    'admin.audit_reports.remind': 'Remind',
    'admin.audit_reports.remind_auditor':
        'Remind the Audit Partner about {business}',
    'admin.audit_reports.remind_business':
        'Remind {business} to review the report',
    'admin.audit_reports.reminded': 'Reminded {date}',
    'admin.audit_reports.pdf': 'Generate PDF',
    'admin.audit_reports.pdf_named':
        'Download the sealed report for {business} (PDF)',
    'admin.audit_reports.stats.published': 'Audited & published',
    'admin.audit_reports.stats.awaiting_cosign': 'In dispute window',
    'admin.audit_reports.stats.overdue': 'Overdue reports',
    'admin.audit_reports.stats.on_time': 'On-time audit rate',
    'admin.audit_reports.chip.all': 'All',
    'admin.audit_reports.chip.owed': 'Owed by a partner',
    'admin.audit_reports.chip.awaiting_cosign': 'In dispute window',
    'admin.audit_reports.chip.published': 'Published',
    'admin.audit_reports.chip.disputed': 'In dispute',
    'admin.audit_reports.col.business': 'Business',
    'admin.audit_reports.col.period': 'Period',
    'admin.audit_reports.col.partner': 'Audit Partner',
    'admin.audit_reports.col.status': 'Status',
    'admin.audit_reports.col.variance': 'Variance',
    'admin.audit_reports.col.action': 'Action',
    'admin.audit_reports.status.not_started': 'Audit not opened',
    'admin.audit_reports.status.in_progress': 'In progress',
    'admin.audit_reports.status.overdue': 'Overdue',
    'admin.audit_reports.status.returned': 'Returned',
    'admin.audit_reports.status.sealed': 'Sealed',
    'admin.audit_reports.status.awaiting_cosign': 'In dispute window',
    'admin.audit_reports.status.published': 'Published',
    'admin.audit_reports.status.disputed': 'Disputed',
    'admin.audit_reports.status.escalated': 'Escalated',
    'admin.audit_reports.status.amended': 'Amended',
    'admin.audit_reports.status.withdrawn': 'Withdrawn',
    'admin.parties.coverage_link': 'Partner coverage',
    'admin.parties.registry.title': 'CPA registry',
    'admin.parties.registry.caption':
        'Every licensed CPA, with their licence, standing and active audits. Open a row for the full profile.',
    'admin.parties.queue.requests.title': 'Partner change requests',
    'admin.parties.queue.requests.caption':
        'Licence renewals waiting for review. The partner sees theirs as pending until you decide.',
    'admin.parties.queue.requests.empty':
        'No partner requests waiting. Licence renewals from the Auditor app land here.',
    'admin.parties.queue.requests.chip': 'RENEWAL',
    'admin.parties.queue.desk.title': 'ICPAR verification desk',
    'admin.parties.queue.desk.caption':
        'Confirm the licence on the ICPAR register, then approve so the partner can take audits.',
    'admin.parties.queue.desk.empty':
        'No auditor applications awaiting verification.',
    'admin.parties.queue.desk.chip': 'PENDING VERIFICATION',
    'admin.parties.queue.approve': 'Approve',
    'admin.parties.queue.reject': 'Reject',
    'admin.parties.queue.review': 'Review',
    'admin.parties.queue.field.licence': 'Licence',
    'admin.parties.queue.field.expires_on': 'Licence expires',
    'admin.parties.queue.field.submitted_at': 'Submitted',
    'admin.parties.chip.all': 'All',
    'admin.parties.chip.healthy': 'Healthy',
    'admin.parties.chip.watch': 'Watch',
    'admin.parties.chip.distressed': 'Distressed',
    'admin.parties.chip.frozen': 'Frozen',
    'admin.parties.chip.verified': 'Verified',
    'admin.parties.chip.pending': 'Pending',
    'admin.parties.chip.kyc_overdue': 'KYC overdue',
    'admin.parties.chip.restricted': 'Restricted',
    'admin.parties.chip.active': 'Active',
    'admin.parties.chip.licence_expired': 'Licence expired',
    'admin.parties.chip.suspended': 'Suspended',
    'admin.parties.select.sector': 'Sector',
    'admin.parties.select.country': 'Country',
    'admin.parties.select.sort': 'Sort',
    'admin.parties.stats.total_businesses': 'Total Businesses',
    'admin.parties.stats.avg_rating': 'Avg rating',
    'admin.parties.stats.active_notes': 'Active Notes',
    'admin.parties.stats.capital_raised': 'Capital Funded',
    'admin.parties.stats.total_investors': 'Total Investors',
    'admin.parties.stats.kyc_verified': 'KYC Verified',
    'admin.parties.stats.awaiting_kyc': 'Awaiting KYC',
    'admin.parties.stats.total_aum': 'Total AUM',
    'admin.parties.stats.active_audits': 'Active audits',
    'admin.parties.stats.active_partners': 'Active partners',
    'admin.parties.stats.pending_partners': 'Pending ICPAR',
    'admin.parties.stats.licences_expiring': 'Licences expiring ≤30d',
    'admin.parties.stats.operators': 'Operators',
    'admin.parties.stats.approvers': 'Approvers',
    'admin.parties.stats.frozen_accounts': 'Frozen',
    'admin.parties.count.business': '{count} businesses',
    'admin.parties.count.investor': '{count} investors',
    'admin.parties.count.auditor': '{count} Audit Partners',
    'admin.parties.count.staff': '{count} operators',
    'admin.parties.showing.business':
        'Showing first {shown} of {total} businesses',
    'admin.parties.showing.investor':
        'Showing first {shown} of {total} investors',
    'admin.parties.showing.auditor':
        'Showing first {shown} of {total} Audit Partners',
    'admin.parties.showing.staff': 'Showing first {shown} of {total} operators',
    'admin.parties.table.business': 'Businesses',
    'admin.parties.table.investor': 'Investors',
    'admin.parties.table.auditor': 'Audit Partners',
    'admin.parties.table.staff': 'Operators',
    'admin.parties.empty.business': 'No businesses match these filters.',
    'admin.parties.empty.investor': 'No investors match these filters.',
    'admin.parties.empty.auditor': 'No Audit Partners match these filters.',
    'admin.parties.empty.staff': 'No operators match these filters.',
    'admin.parties.col.business': 'Business',
    'admin.parties.col.rating': 'Rating',
    'admin.parties.name_pending': 'Name pending',
    'admin.parties.col.active_notes': 'Active notes',
    'admin.parties.col.investors': 'Funders',
    'admin.parties.col.raised': 'Funded',
    'admin.parties.col.capacity': 'Capacity used',
    'admin.parties.col.status': 'Status',
    'admin.parties.col.investor': 'Investor',
    'admin.parties.col.kyc': 'KYC',
    'admin.parties.col.portfolio': 'Invested + committed',
    'admin.parties.col.wallet': 'Wallet',
    'admin.parties.col.holdings': 'Holdings',
    'admin.parties.col.businesses': 'Businesses',
    'admin.parties.col.partner': 'Partner',
    'admin.parties.col.district': 'District',
    'admin.parties.col.engagements': 'Engagements',
    'admin.parties.col.on_time': 'On-time',
    'admin.parties.col.share_mtd': 'Share MTD',
    'admin.parties.col.operator': 'Operator',
    'admin.parties.col.role': 'Role',
    'admin.parties.badge.frozen': 'Frozen',
    'admin.parties.badge.kyc_overdue': 'KYC overdue',
    'admin.parties.badge.restricted': 'Restricted',
    'admin.parties.health.healthy': 'Healthy',
    'admin.parties.health.watch': 'Watch',
    'admin.parties.health.distressed': 'Distressed',
    'admin.parties.health.active': 'Active',
    'admin.parties.health.kyc_pending': 'KYC pending',
    'admin.parties.health.licence_pending': 'Licence pending',
    'admin.parties.health.frozen': 'Frozen',
    'admin.parties.health.not_tracked': 'Not tracked',
    'admin.parties.kyc.verified': 'Verified',
    'admin.parties.kyc.pending': 'Pending',
    'admin.parties.kyc.overdue': 'Overdue',
    'admin.parties.kyc.rejected': 'Rejected',
    'admin.parties.status.frozen': 'Frozen',
    'admin.parties.status.suspended': 'Suspended',
    'admin.parties.status.restricted': 'Restricted',
    'admin.parties.status.verified': 'Verified',
    'admin.parties.status.pending': 'Pending',
    'admin.parties.status.active': 'Active',
    'admin.parties.standing.active': 'Active',
    'admin.parties.standing.pending': 'Pending verification',
    'admin.parties.standing.licence_expired': 'Licence expired',
    'admin.parties.standing.suspended': 'Suspended',
    'admin.matrix.title': 'Role permissions',
    'admin.matrix.info':
        'What each tier may do. Lower tiers are blocked from destructive actions, not just hidden from them.',
    'admin.matrix.col.capability': 'Capability',
    'admin.matrix.col.detail': 'What it allows',
    'admin.matrix.granted': 'Allowed',
    'admin.matrix.withheld': 'Not allowed',
    'admin.permission.admin.open': 'Open the console',
    'admin.permission_detail.admin.open':
        'Sign in to the staff console, its dashboard and Staff & Roles.',
    'admin.permission.businesses.view': 'View businesses',
    'admin.permission_detail.businesses.view':
        'Read the business directory and each business 360.',
    'admin.permission.businesses.verify': 'Verify businesses',
    'admin.permission_detail.businesses.verify':
        'Decide business verification, with a recorded reason.',
    'admin.permission.investors.verify': 'KYC decisions',
    'admin.permission_detail.investors.verify':
        'Verify or reject investor identity documents, and read Activity & Audit.',
    'admin.permission.applications.review': 'Approvals',
    'admin.permission_detail.applications.review':
        'Approve applications: release them for listing once every check passes.',
    'admin.permission.audit.partners.verify': 'Verify Audit Partners',
    'admin.permission_detail.audit.partners.verify':
        'Approve CPA firms and read partner coverage.',
    'admin.permission.audit.assignments.manage': 'Assign audits',
    'admin.permission_detail.audit.assignments.manage':
        'Dispatch and reassign audits to Audit Partners.',
    'admin.permission.audit.reports.view': 'Read audit reports',
    'admin.permission_detail.audit.reports.view':
        'Open sealed audit reports and their evidence.',
    'admin.permission.consent.documents.record': 'Record legal documents',
    'admin.permission_detail.consent.documents.record':
        'Record new versions of the Terms and the Privacy Note.',
    'admin.permission.ledger.view': 'View the ledger',
    'admin.permission_detail.ledger.view': 'Read ledger entries and balances.',
    'admin.permission.disbursements.view': 'View disbursements',
    'admin.permission_detail.disbursements.view':
        'See the disbursement queue and each release.',
    'admin.permission.disbursements.hold': 'Hold disbursements',
    'admin.permission_detail.disbursements.hold':
        'Hold a disbursement before it is released.',
    'admin.permission.disbursements.requery': 'Requery disbursements',
    'admin.permission_detail.disbursements.requery':
        "Ask the payment provider for a disbursement's current state.",
    'admin.permission.staging.mail.testers.manage': 'Manage mail testers',
    'admin.permission_detail.staging.mail.testers.manage':
        'Choose who receives email on staging.',
    'admin.permission.staff.manage': 'Manage operators',
    'admin.permission_detail.staff.manage':
        "Add an operator, or suspend or reactivate another operator's access, with a reason.",
    'admin.permission.accounts.freeze': 'Freeze / suspend',
    'admin.permission_detail.accounts.freeze':
        'Pause an account’s access to either app.',
    'admin.permission.capacity.adjust': 'Adjust capacity',
    'admin.permission_detail.capacity.adjust':
        'Change how much a business may raise.',
    'admin.permission.records.edit': 'Edit entities',
    'admin.permission_detail.records.edit':
        'Change stored details on any record.',
    'admin.permission.ledger.correct': 'Reversals',
    'admin.permission_detail.ledger.correct':
        'Correct a settled entry with a new linked entry and a reason.',
    'admin.permission.policies.change': 'Policies',
    'admin.permission_detail.policies.change':
        'Change pricing, eligibility and automation rules.',
    'admin.permission.records.delete': 'Delete',
    'admin.permission_detail.records.delete':
        'Remove a record from the platform permanently.',
    'admin.matrix.withheld.not_built': 'No role yet: not built',
    'admin.matrix.withheld.by_rule':
        'No role, by rule: ledger and audit entries are never deleted',
    'admin.action_log.title': 'Admin action log',
    'admin.staff.approvals.title': 'Awaiting super-admin approval',
    'admin.staff.approvals.body':
        'Super-admin access stays unchanged until a different, currently authorized super-admin approves. The requester and recipient cannot approve.',
    'admin.staff.approvals.grant': '{requester} requested super-admin access.',
    'admin.staff.approvals.reactivate':
        '{requester} requested super-admin reactivation.',
    'admin.staff.approvals.form': 'Approve access for {recipient}',
    'admin.staff.approvals.submit': 'Approve super-admin access',
    'admin.staff.approvals.waiting':
        'Waiting for a different super-admin to approve.',
    'admin.staff.invite.open': '+ Invite operator',
    'admin.staff.invite.title': 'Invite an operator',
    'admin.staff.invite.body':
        'Give an existing account console access with one role. The operator signs up with their own dedicated account and turns on two-factor sign-in first. The change is logged with your name and reason.',
    'admin.staff.invite.email': 'Account email',
    'admin.staff.invite.role': 'Role',
    'admin.staff.invite.cancel': 'Cancel',
    'admin.staff.invite.submit': 'Give access',
    'admin.businesses.onboard.open': '+ Onboard a business',
    'admin.businesses.onboard.title': 'Onboard a business',
    'admin.businesses.onboard.body':
        'Record a company from its RDB code, or a sole trader, with its official representative. The representative must already have a Rozine account with a verified identity. They get every role and permission on the first mandate. The change is logged with your name and reason.',
    'admin.businesses.onboard.field.kind': 'Type of business',
    'admin.businesses.onboard.kind.organization': 'Company',
    'admin.businesses.onboard.kind.person': 'Sole trader',
    'admin.businesses.onboard.field.rdb_code': 'RDB company code',
    'admin.businesses.onboard.field.name': 'Business name',
    'admin.businesses.onboard.field.industry': 'Sector',
    'admin.businesses.onboard.field.district': 'District',
    'admin.businesses.onboard.field.established_year':
        'Year established (optional)',
    'admin.businesses.onboard.field.representative_email':
        "Representative's account email",
    'admin.businesses.onboard.field.evidence_reference': 'Evidence checked',
    'admin.businesses.onboard.placeholder.rdb_code': '9 digits',
    'admin.businesses.onboard.placeholder.name': 'As on the RDB certificate',
    'admin.businesses.onboard.placeholder.industry': 'Choose a sector',
    'admin.businesses.onboard.placeholder.district': 'Choose a district',
    'admin.businesses.onboard.placeholder.established_year': 'e.g. 2019',
    'admin.businesses.onboard.placeholder.representative_email':
        'name@example.com',
    'admin.businesses.onboard.placeholder.evidence_reference':
        'e.g. RDB certificate and ID checked',
    'admin.businesses.onboard.attest':
        'The representative named here is everyone with authority over this business today.',
    'admin.businesses.onboard.hint.rdb_code':
        'Enter the 9-digit RDB company code.',
    'admin.businesses.onboard.hint.established_year':
        'Enter a year from 1900 to {year}.',
    'admin.businesses.onboard.hint.attest':
        'Tick this box to confirm before you onboard the business.',
    'admin.businesses.onboard.submit': 'Onboard business',
    'admin.businesses.onboarded.title': 'Business onboarded',
    'admin.businesses.onboarded.ready':
        '{name} can open it in the Business app now.',
    'admin.businesses.onboarded.access_needed':
        '{name} needs Business access from an identity operator before they can open it in the Business app.',
    'admin.staff.signed_in_as': 'Signed in as',
    'admin.staff.access_full': 'Full access',
    'admin.staff.access_count': '{granted} of {total} capabilities',
    'admin.action_log.info':
        'The latest changes to staff access, newest first: who made each one, whose access it changed and why.',
    'admin.action_log.empty': 'No admin actions logged yet.',
    'admin.action_log.col.action': 'Action',
    'admin.action_log.col.by': 'By',
    'admin.action_log.col.target': 'Target',
    'admin.action_log.col.reason': 'Reason',
    'admin.action_log.col.when': 'When',
    'admin.action_log.action.staff_suspended': 'Suspended access',
    'admin.action_log.action.staff_enabled': 'Enabled access',
    'admin.action_log.action.staff_granted': 'Added as {role}',
    'admin.permission.reconciliation.view': 'View reconciliation',
    'admin.permission_detail.reconciliation.view':
        'Compare the ledger with provider statements.',
    'admin.permission.exceptions.view': 'View exceptions',
    'admin.permission_detail.exceptions.view':
        "See the Risk Center's open exceptions.",
    'admin.permission.messaging.view': 'View messaging',
    'admin.permission_detail.messaging.view': 'Read the messaging history.',
    'admin.permission.primary.view': 'View the primary market',
    'admin.permission_detail.primary.view':
        'Follow live raises and the Notes register.',
    'admin.permission.payments.view': 'View payments',
    'admin.permission_detail.payments.view': 'Open the Payment Center.',
    'admin.permission.complaints.answer': 'Answer complaints',
    'admin.permission_detail.complaints.answer':
        'Reply to and close participant complaints.',
    'admin.permission.health.view': 'View system health',
    'admin.permission_detail.health.view':
        'See queues, jobs and service status.',
    'admin.permission.cart.releases.resolve': 'Retry stuck cart releases',
    'admin.permission_detail.cart.releases.resolve':
        'Release a whole-cart hold whose expiry failed, every line together.',
    'admin.permission.secondary.halt': 'Halt resale trading',
    'admin.permission_detail.secondary.halt':
        'Halt or reopen the resale market or one note, with a recorded reason.',
    'admin.permission.secondary.listings.cancel': 'Cancel resale listings',
    'admin.permission_detail.secondary.listings.cancel':
        'Cancel one resale listing, with a reason shown to the seller.',
    'admin.parties.you': 'You',
    'admin.parties.drawer_label': '{name}, party record',
    'admin.parties.type.business': 'Business',
    'admin.parties.type.investor': 'Investor',
    'admin.parties.type.auditor': 'Audit Partner',
    'admin.parties.type.staff': 'Staff',
    'admin.parties.app.business': 'Business',
    'admin.parties.app.investor': 'Investor',
    'admin.parties.app.auditor': 'Auditor',
    'admin.parties.app.staff': 'Admin',
    'admin.parties.tabs': 'Party record',
    'admin.parties.tab.overview': 'Overview',
    'admin.parties.tab.activity': 'Activity',
    'admin.parties.tab.controls': 'Controls',
    'admin.parties.frozen_note':
        'Frozen by {actor} at {at}. See Controls for the reason and release.',
    'admin.parties.kyc_overdue_note':
        'KYC re-verification overdue since {date}.',
    'admin.parties.stat.active_notes': 'Active notes',
    'admin.parties.stat.investors': 'Funders',
    'admin.parties.stat.raised': 'Funded',
    'admin.parties.stat.rating': 'Rozine rating',
    'admin.parties.stat.capacity': 'Capacity',
    'admin.parties.stat.capacity_used': 'Capacity used',
    'admin.parties.stat.portfolio': 'Invested + committed',
    'admin.parties.stat.wallet': 'Wallet',
    'admin.parties.stat.holdings': 'Holdings',
    'admin.parties.stat.businesses': 'Businesses',
    'admin.parties.stat.kyc_due': 'KYC due',
    'admin.parties.stat.engagements': 'Engagements',
    'admin.parties.stat.on_time': 'On-time',
    'admin.parties.stat.share_mtd': 'Share MTD',
    'admin.parties.stat.role': 'Role',
    'admin.parties.stat.last_sign_in': 'Last sign-in',
    'admin.parties.list.active_notes': 'Active notes',
    'admin.parties.list.holdings': 'Holdings',
    'admin.parties.list.engagements': 'Engagements',
    'admin.parties.committed_note':
        '{amount} is committed to raises whose notes are not issued yet. The portfolio counts it; it joins Holdings once the notes issue.',
    'admin.parties.list_empty': 'Nothing here yet.',
    'admin.parties.holding_detail': {
        one: '{amount} · {count} note',
        other: '{amount} · {count} notes',
    },
    'admin.parties.note_status.draft': 'Draft',
    'admin.parties.note_status.active': 'Live',
    'admin.parties.note_status.funded': 'Funded',
    'admin.parties.note_status.repaying': 'Repaying',
    'admin.parties.note_status.completed': 'Completed',
    'admin.parties.note_status.failed': 'Failed',
    'admin.parties.history': 'Activity',
    'admin.parties.history_empty': 'No activity recorded yet.',
    'admin.parties.kyc_title': 'KYC & verification',
    'admin.parties.kyc_current': 'Current status',
    'admin.parties.kyc_due': 'Re-verification due {date}',
    'admin.parties.verify_kyc': '✓ Verify KYC',
    'admin.parties.verify_licence': '✓ Verify licence',
    'admin.parties.reject': 'Reject',
    'admin.parties.licence_title': 'ICPAR licence & verification',
    'admin.parties.office.title': 'Registered office',
    'admin.parties.office.status': 'Dispatch location',
    'admin.parties.office.state.verified': 'Verified',
    'admin.parties.office.state.expired': 'Verification lapsed',
    'admin.parties.office.state.missing': 'Not verified',
    'admin.parties.office.note.missing':
        'No office location is on record, so this partner is not offered jobs. Jobs only go to partners whose verified office is within 30 km of the business.',
    'admin.parties.office.note.expired':
        'This office was verified more than a year ago, so this partner is not offered jobs until it is verified again.',
    'admin.parties.office.coordinates': 'Coordinates',
    'admin.parties.office.point': '{latitude}, {longitude} (±{metres} m)',
    'admin.parties.office.verified_on': 'Verified on',
    'admin.parties.office.evidence': 'Evidence checked',
    'admin.parties.office.record': 'Record verified office',
    'admin.parties.office.reverify': 'Verify office again',
    'admin.parties.office.form': 'Verify the registered office',
    'admin.parties.office.form_body':
        'Enter the office coordinates you checked against the evidence. Dispatch adds the accuracy to the distance, so a wide margin can put businesses out of range.',
    'admin.parties.office.field.latitude': 'Latitude',
    'admin.parties.office.field.longitude': 'Longitude',
    'admin.parties.office.field.uncertainty_m': 'Accuracy (metres)',
    'admin.parties.office.field.reference': 'Evidence checked',
    'admin.parties.office.field.reason': 'Reason',
    'admin.parties.office.placeholder.latitude': '-1.9441',
    'admin.parties.office.placeholder.longitude': '30.0619',
    'admin.parties.office.placeholder.uncertainty_m': '50',
    'admin.parties.office.placeholder.reference':
        'e.g. ICPAR register entry, site visit',
    'admin.parties.office.placeholder.reason':
        'e.g. Office address confirmed on the ICPAR register and by phone.',
    'admin.parties.office.cta': 'Record as verified',
    'admin.parties.premises.title': 'Business premises',
    'admin.parties.premises.status': 'Dispatch location',
    'admin.parties.premises.state.verified': 'Verified',
    'admin.parties.premises.state.expired': 'Verification lapsed',
    'admin.parties.premises.state.missing': 'Not verified',
    'admin.parties.premises.note.missing':
        'No premises location is on record, so no Audit Partner can be dispatched here. Jobs only go to partners whose verified office is within 30 km of these premises.',
    'admin.parties.premises.note.expired':
        'These premises were verified more than a year ago, so no Audit Partner is dispatched here until they are verified again.',
    'admin.parties.premises.coordinates': 'Coordinates',
    'admin.parties.premises.point': '{latitude}, {longitude} (±{metres} m)',
    'admin.parties.premises.verified_on': 'Verified on',
    'admin.parties.premises.evidence': 'Evidence checked',
    'admin.parties.premises.record': 'Record verified premises',
    'admin.parties.premises.reverify': 'Verify premises again',
    'admin.parties.premises.form': 'Verify the business premises',
    'admin.parties.premises.form_body':
        'Enter the premises coordinates you checked against the evidence. Dispatch adds the accuracy to the distance, so a wide margin can put partners out of range.',
    'admin.parties.premises.field.latitude': 'Latitude',
    'admin.parties.premises.field.longitude': 'Longitude',
    'admin.parties.premises.field.uncertainty_m': 'Accuracy (metres)',
    'admin.parties.premises.field.reference': 'Evidence checked',
    'admin.parties.premises.field.reason': 'Reason',
    'admin.parties.premises.placeholder.latitude': '-1.9441',
    'admin.parties.premises.placeholder.longitude': '30.0619',
    'admin.parties.premises.placeholder.uncertainty_m': '50',
    'admin.parties.premises.placeholder.reference':
        'e.g. site visit, RDB registered address',
    'admin.parties.premises.placeholder.reason':
        'e.g. Premises confirmed on a site visit and against the RDB address.',
    'admin.parties.premises.cta': 'Record as verified',
    'admin.parties.audit.title': 'Audit',
    'admin.parties.audit.none': 'No audit is open on this business.',
    'admin.parties.audit.closed_last':
        'Audit Operations closed this business’s last audit, so it can never be audited again: no Flash audit or statement review can be requested here.',
    'admin.parties.audit.kind.flash': 'Flash audit · 24-hour deadline',
    'admin.parties.audit.kind.routine': 'Monthly audit',
    'admin.parties.audit.kind.statement_review': 'Statement review',
    'admin.parties.audit.status.operations': 'With Audit Operations',
    'admin.parties.audit.status.offered': 'Offered to a partner',
    'admin.parties.audit.status.accepted': 'Accepted by a partner',
    'admin.parties.audit.status.overdue': 'Overdue',
    'admin.parties.audit.overdue':
        'The 24-hour deadline passed on {date} without a sealed report. A Flash audit must be completed within 24 hours, so this one is in breach. It is listed in Open exceptions for Audit Operations to follow up.',
    'admin.parties.audit.reason.AUDIT_NO_ELIGIBLE_PARTNER':
        'Audit Partners have verified offices within 30 km, but none can take this audit now (availability, capacity or independence). Dispatch attempts used: {attempts} of 3. Redispatch it once one can.',
    'admin.parties.audit.reason.AUDIT_DISPATCH_EXHAUSTED':
        'Every dispatch attempt is used, or the deadline has passed, so this audit can no longer be redispatched.',
    'admin.parties.audit.reason.AUDIT_PREMISES_UNVERIFIED':
        'This business’s premises are not verified, so dispatch cannot measure the 30 km. Staff who verify businesses record them in the Premises section. Dispatch attempts used: {attempts} of 3.',
    'admin.parties.audit.reason.AUDIT_NO_PARTNER_IN_RANGE':
        'No Audit Partner has a verified office within 30 km of these premises. Approve a partner nearby or verify their office location. Dispatch attempts used: {attempts} of 3.',
    'admin.parties.audit.remedy': 'Open Audit Partners',
    'admin.parties.audit.field.reason': 'Reason',
    'admin.parties.audit.flash.open': 'Request Flash audit',
    'admin.parties.audit.flash.form': 'Request a Flash audit',
    'admin.parties.audit.flash.form_body':
        'The audit is offered to the next eligible Audit Partner within 30 km and must be completed within 24 hours. The business is not told in advance.',
    'admin.parties.audit.flash.placeholder':
        'e.g. Unusual movement on the revenue account this week.',
    'admin.parties.audit.flash.cta': 'Request Flash audit',
    'admin.parties.audit.flash.done': 'Flash audit requested.',
    'admin.parties.audit.redispatch.open': 'Redispatch',
    'admin.parties.audit.redispatch.form': 'Redispatch this audit',
    'admin.parties.audit.redispatch.form_body':
        'The audit is offered to the next eligible Audit Partner it has not been offered to. Its original deadline does not move.',
    'admin.parties.audit.redispatch.placeholder':
        'e.g. A new Audit Partner near this business was approved.',
    'admin.parties.audit.redispatch.cta': 'Redispatch',
    'admin.parties.audit.redispatch.done': 'Audit sent back to dispatch.',
    'admin.parties.audit.close.open': 'Close permanently',
    'admin.parties.audit.close.hint':
        'Closing is permanent: it ends this audit and this business can never be audited again. To end only this audit, withdraw it.',
    'admin.parties.audit.close.done':
        'Audit closed. This business can no longer be audited.',
    'admin.parties.audit.close.form': 'Close this audit',
    'admin.parties.audit.close.form_body':
        'Closing ends this audit without a report and can’t be undone. This business can never be audited again: no Flash audit or statement review can be requested for it afterwards.',
    'admin.parties.audit.close.placeholder':
        'e.g. The business stopped trading before a partner could visit.',
    'admin.parties.audit.close.cta': 'Close audit',
    'admin.parties.audit.withdraw.open': 'Withdraw',
    'admin.parties.audit.withdraw.form': 'Withdraw this audit',
    'admin.parties.audit.withdraw.form_body':
        'Withdrawing ends this audit without a report and takes it from its partner, along with any unsealed draft. Only this audit ends: a new Flash audit or statement review can be requested for the business afterwards.',
    'admin.parties.audit.withdraw.placeholder':
        'e.g. The partner did not seal a report by the deadline.',
    'admin.parties.audit.withdraw.cta': 'Withdraw audit',
    'admin.parties.audit.withdraw.done':
        'Audit withdrawn. You can request a new one.',
    'admin.parties.audit.reason.AUDIT_PARTNER_AVAILABLE':
        'An Audit Partner within 30 km can take this audit now. Redispatch it to offer it to them. Dispatch attempts used: {attempts} of 3.',
    'admin.parties.audit.reason.AUDIT_INDEPENDENCE_REVIEW_REQUIRED':
        'Audit Partners have verified offices within 30 km, but none has been reviewed as independent of this business for its current mandate, so dispatch can’t offer it to them. Record an independence review for a partner below. Dispatch attempts used: {attempts} of 3.',
    'admin.parties.audit.partners.title': 'Audit Partners within 30 km',
    'admin.parties.audit.partners.reviewed': 'Independence reviewed',
    'admin.parties.audit.partners.unreviewed': 'Not reviewed',
    'admin.parties.audit.independence.open':
        'Record independence review for {name}',
    'admin.parties.audit.independence.form': 'Independence review of {name}',
    'admin.parties.audit.independence.form_body':
        'Record what you checked about this partner’s ties to the business. Dispatch offers them this business’s audits only if none of these apply and any past role here ended over 24 months ago.',
    'admin.parties.audit.independence.fact.financial_interest':
        'Has a financial interest in the business',
    'admin.parties.audit.independence.fact.current_role_tie':
        'Holds a role at the business now',
    'admin.parties.audit.independence.fact.family_or_business_conflict':
        'Has a family or business tie to its owners',
    'admin.parties.audit.independence.fact.unresolved_conflict':
        'Has another conflict not yet resolved',
    'admin.parties.audit.independence.field.ended':
        'If they held a role here, the date it ended (optional)',
    'admin.parties.audit.independence.field.reference': 'Evidence checked',
    'admin.parties.audit.independence.placeholder.reference':
        'e.g. Partner declaration of 30 Oct and RDB shareholder record.',
    'admin.parties.audit.independence.cta': 'Record review',
    'admin.parties.audit.review.title': 'Statement review',
    'admin.parties.audit.review.months': {
        one: '{count} month transcribed, {first} to {last}.',
        other: '{count} months transcribed, {first} to {last}.',
    },
    'admin.parties.audit.review.no_months':
        'No statement months are transcribed yet.',
    'admin.parties.audit.review.verified': 'Verified by an Audit Partner',
    'admin.parties.audit.review.unverified': 'Not verified',
    'admin.parties.audit.review.ready':
        'The uploaded statements are ready for an Audit Partner to verify. Until they are verified, the business can’t continue its application.',
    'admin.parties.audit.review.blocked.BUSINESS_VERIFICATION_REQUIRED':
        'The business’s registration or representative isn’t currently verified.',
    'admin.parties.audit.review.blocked.STATEMENT_ORIGINAL_REQUIRED':
        'The business hasn’t uploaded any statements yet.',
    'admin.parties.audit.review.blocked.STATEMENT_RECONCILIATION_REQUIRED':
        'The uploaded statements haven’t been transcribed into monthly figures yet.',
    'admin.parties.audit.review.blocked.STATEMENT_TRANSCRIPTION_STALE':
        'New statements were uploaded after the last transcription, so it needs redoing first.',
    'admin.parties.audit.review.blocked.STATEMENT_ALREADY_VERIFIED':
        'An Audit Partner has already verified these statements.',
    'admin.parties.audit.review.blocked.AUDIT_STATEMENT_REVIEW_OPEN':
        'A statement review is already in progress.',
    'admin.parties.audit.review.blocked.AUDIT_ENGAGEMENT_OPEN':
        'Another audit is open on this business. Request the review once it ends.',
    'admin.parties.audit.review.blocked.AUDIT_ENGAGEMENT_CLOSED':
        'Audit Operations closed this business’s last audit, so a review can’t be requested here.',
    'admin.parties.audit.review.open': 'Request statement review',
    'admin.parties.audit.review.form': 'Request a statement review',
    'admin.parties.audit.review.form_body':
        'An Audit Partner within 30 km checks the uploaded statements against their originals. The request is for the statements shown here; if they change first, you’ll be asked to review them again.',
    'admin.parties.audit.review.placeholder':
        'e.g. First review of the statements uploaded with the application.',
    'admin.parties.audit.review.cta': 'Request review',
    'admin.parties.audit.review.done': 'Statement review requested.',
    'admin.parties.licence_status': 'Verification status',
    'admin.parties.licence.verified': 'Verified',
    'admin.parties.licence.pending': 'Pending',
    'admin.parties.licence.expired': 'Expired',
    'admin.parties.licence_field.member_id': 'ICPAR ID',
    'admin.parties.licence_field.licence': 'PPC licence',
    'admin.parties.licence_field.expires_on': 'Expires',
    'admin.parties.licence_field.district': 'District',
    'admin.parties.account_state': 'Account state',
    'admin.parties.frozen_by': 'Frozen by {actor} · {at}',
    'admin.parties.legal_hold':
        'A legal hold keeps this account frozen. Only Compliance or Legal can release it.',
    'admin.parties.freeze': 'Freeze account',
    'admin.parties.release': 'Reactivate account',
    'admin.parties.freeze_caption':
        "Freezing instantly revokes the account's access in the {app} app. Reversible at any time, and both the freeze and the release carry a reason and a name.",
    'admin.parties.restrictions': 'Freeze history',
    'admin.parties.restrictions_empty': 'This account has never been frozen.',
    'admin.parties.stage.freeze.title': 'Freeze {name}',
    'admin.parties.stage.freeze.body':
        'Access to the {app} app stops at once. Say why, so the release can be judged against it.',
    'admin.parties.stage.freeze.cta': 'Freeze account',
    'admin.parties.stage.freeze.placeholder':
        'e.g. Provider fraud alert FA-2291 on the payout destination.',
    'admin.parties.stage.release.title': 'Reactivate {name}',
    'admin.parties.stage.release.body':
        'Access to the {app} app comes back at once. Say what cleared the reason for the freeze.',
    'admin.parties.stage.release.cta': 'Reactivate account',
    'admin.parties.stage.release.placeholder':
        'e.g. Provider closed FA-2291 as a false positive; destination re-verified.',
    'admin.parties.stage.verify_kyc.title': 'Verify KYC for {name}',
    'admin.parties.stage.verify_kyc.body':
        'The account can transact in the {app} app within its policy limits once verified.',
    'admin.parties.stage.verify_kyc.cta': 'Verify KYC',
    'admin.parties.stage.verify_kyc.placeholder':
        'e.g. NID and selfie matched; address confirmed against the utility bill.',
    'admin.parties.stage.reject_kyc.title': 'Reject KYC for {name}',
    'admin.parties.stage.reject_kyc.body':
        'The account stays unable to transact in the {app} app. Say what failed so they can fix it.',
    'admin.parties.stage.reject_kyc.cta': 'Reject KYC',
    'admin.parties.stage.reject_kyc.placeholder':
        'e.g. ID photo unreadable; ask for a new upload.',
    'admin.parties.stage.verify_licence.title': 'Verify licence for {name}',
    'admin.parties.stage.verify_licence.body':
        "The partner joins their district's dispatch pool in the {app} app.",
    'admin.parties.stage.verify_licence.cta': 'Verify licence',
    'admin.parties.stage.verify_licence.placeholder':
        'e.g. ICPAR register checked today; practising certificate valid to 2027.',
    'admin.parties.stage.reject_licence.title': 'Reject licence for {name}',
    'admin.parties.stage.reject_licence.body':
        'The partner gets no work in the {app} app until a valid licence is verified.',
    'admin.parties.stage.reject_licence.cta': 'Reject licence',
    'admin.parties.stage.reject_licence.placeholder':
        'e.g. Member ID not on the ICPAR register.',
    'admin.ledger.title': 'Ledger',
    'admin.ledger.caption': 'Every money movement, newest first.',
    'admin.ledger.count': '{count} entries',
    'admin.ledger.table': 'Ledger',
    'admin.ledger.search_label': 'Search the ledger',
    'admin.ledger.search_placeholder': 'Search type, party or reference…',
    'admin.ledger.col.time': 'Time',
    'admin.ledger.col.type': 'Type',
    'admin.ledger.col.from_to': 'From → To',
    'admin.ledger.col.reference': 'Reference',
    'admin.ledger.col.amount': 'Amount',
    'admin.ledger.col.account': 'Account',
    'admin.ledger.col.debit': 'Debit',
    'admin.ledger.col.credit': 'Credit',
    'admin.ledger.open_named': 'Open entry {id}',
    'admin.ledger.see_all': 'See all {count} entries',
    'admin.ledger.older': 'Older entries',
    'admin.ledger.newest': 'Back to the newest',
    'admin.ledger.empty': 'No ledger entries in this range.',
    'admin.ledger.drawer_label': 'Ledger entry {id}',
    'admin.ledger.fact.from': 'From',
    'admin.ledger.fact.to': 'To',
    'admin.ledger.fact.reference': 'Reference',
    'admin.ledger.fact.operation': 'Operation',
    'admin.ledger.posted_by': 'Posted by {actor} · {at}',
    'admin.ledger.origin':
        'Flow started by {actor} · {at}. Later movements in the same flow keep this origin.',
    'admin.ledger.origin_none':
        'No starting operation is recorded for this entry.',
    'admin.ledger.postings': 'Postings',
    'admin.ledger.total': 'Total',
    'admin.ledger.balanced': 'Balanced',
    'admin.ledger.unbalanced': 'Out of balance',
    'admin.ledger.contra_of': 'This contra entry reverses',
    'admin.ledger.contra_by': 'Reversed by contra entry',
    'admin.ledger.immutable':
        'Ledger entries are never edited. A correction is its own contra entry, linked here.',
    'admin.ledger.open_events': 'See this entry in the event log →',
    'admin.events.immutable':
        'Audit logs are immutable. Nothing on this screen can be edited or deleted.',
    'admin.events.title': 'Audit trail',
    'admin.events.table': 'Audit trail',
    'admin.events.count': '{count} entries',
    'admin.events.search_label': 'Search the audit trail',
    'admin.events.search_placeholder': 'Search user, action or object…',
    'admin.events.preset.today': 'Today',
    'admin.events.preset.7d': '7d',
    'admin.events.preset.30d': '30d',
    'admin.events.from': 'From date',
    'admin.events.to': 'To date',
    'admin.events.to_word': 'to',
    'admin.events.clear': 'Clear',
    'admin.events.clear_filters': 'Clear filters',
    'admin.events.filtered_empty':
        'Nothing in the trail matches these filters.',
    'admin.events.load_more': 'Load more',
    'admin.events.loading_more': 'Loading more…',
    'admin.events.load_failed':
        'Unable to load more activity. Please try again.',
    'admin.events.retry_more': 'Retry',
    'admin.events.access_revoked':
        'Your access to the activity trail has changed.',
    'admin.events.empty': 'No actions recorded yet.',
    'admin.events.col.when': 'When',
    'admin.events.col.who': 'Who',
    'admin.events.col.action': 'Action',
    'admin.events.col.object': 'Object',
    'admin.events.col.source': 'Source',
    'admin.events.no_reason': 'No reason recorded with this action.',
    'admin.events.field': 'Field',
    'admin.events.before': 'Before',
    'admin.events.after': 'After',
    'admin.events.no_changes': 'No field values changed.',
    'admin.events.see_all': 'See all {count} entries',
    'admin.events.export': 'Export',
    'admin.events.export_running': 'Export running · {actor} · {at}',
    'admin.events.export_download': 'Download export',
    'admin.events.export_title': 'Export this trail',
    'admin.events.export_body':
        'The export uses the filters shown and is itself recorded in the trail. Say who it is for.',
    'admin.events.export_cta': 'Start export',
    'admin.events.export_placeholder':
        'e.g. RCMA request 2026-114, September activity.',
    'business.wallet.title': 'Wallet',
    'business.wallet.back': 'Back to Home',
    'business.wallet.available': 'Available balance',
    'business.wallet.brand': 'Rozine Wallet',
    'business.wallet.range.label': 'Date range',
    'business.wallet.range.all': 'All time',
    'business.wallet.range.start': 'Start',
    'business.wallet.range.now': 'Now',
    'business.wallet.range.from': 'From',
    'business.wallet.range.to': 'To',
    'business.wallet.range.apply': 'Apply',
    'business.wallet.range.clear': 'All time',
    'business.wallet.export.label': 'Download statement',
    'business.wallet.export.pdf': 'Download PDF',
    'business.wallet.export.csv': 'Download CSV',
    'business.wallet.caption': 'Fees, payouts & disbursements settle here',
    'business.wallet.deposit.button': 'Deposit',
    'business.wallet.withdraw.button': 'Withdraw',
    'business.wallet.processing': 'Processing…',
    'business.wallet.history': 'Transaction history',
    'business.wallet.detail.when': 'Date & time',
    'business.wallet.detail.reference': 'Reference',
    'business.wallet.detail.done': 'Done',
    'business.servicing.wallet.status.active': 'Active',
    'business.servicing.wallet.status.not_open': 'Not open yet',
    'business.servicing.wallet.status.restricted': 'Restricted',
    'business.servicing.wallet.restricted':
        'Restricted since {date}. Deposits and repayments still go through.',
    'business.servicing.wallet.pending': '{amount} waiting to be confirmed',
    'business.servicing.wallet.to_repayments': 'Go to repayments',
    'business.servicing.wallet.movement': 'Movement type',
    'business.servicing.wallet.filter.external': 'Deposits',
    'business.servicing.wallet.filter.internal': 'Repayments',
    'business.servicing.wallet.entry.deposit': 'Deposit · {counterparty}',
    'business.servicing.wallet.entry.repayment': 'Repayment · {note}',
    'business.servicing.wallet.instalments': 'Instalments {list}',
    'business.servicing.wallet.empty': 'Nothing here yet.',
    'business.servicing.wallet.older': 'Show more',
    'business.servicing.wallet.receipt.label': 'Transaction receipt',
    'business.servicing.wallet.receipt.psp_fee': 'Provider fee',
    'business.servicing.wallet.receipt.policy': 'Policy version',
    'auditor.nav.home': 'Home',
    'auditor.nav.jobs': 'Jobs',
    'auditor.nav.portfolio': 'Portfolio',
    'auditor.nav.profile': 'Profile',
    'auditor.nav.conflicts': 'Conflicts',
    'auditor.nav.jobs_badge': '{count} open offers',
    'auditor.clock.label': 'Time left on this job',
    'auditor.clock.time_left': 'Time left',
    'auditor.clock.overdue': 'Overdue',
    'auditor.clock.overdue_label': 'This job’s deadline has passed',
    'auditor.time.minutes_ago': '{count}m ago',
    'auditor.time.hours_ago': '{count}h ago',
    'auditor.time.days_ago': '{count}d ago',
    'auditor.home.head_title': 'Home',
    'auditor.home.wallet_owed': 'Owed to you',
    'auditor.home.withdraw': 'Withdraw',
    'auditor.home.statement': 'Statement',
    'auditor.home.notifications': 'Notifications',
    'auditor.home.notifications_unread': 'Notifications, {count} unread',
    'auditor.home.greeting.morning': 'Good morning,',
    'auditor.home.greeting.afternoon': 'Good afternoon,',
    'auditor.home.greeting.evening': 'Good evening,',
    'auditor.home.tile.yield': 'Fees · {month}',
    'auditor.home.tile.deals': 'Jobs',
    'auditor.home.tile.licence': 'Licence',
    'auditor.rating.word': 'Rating',
    'auditor.rating.label': 'Partner rating {score} out of 100',
    'auditor.availability.accepting': 'Accepting audits',
    'auditor.availability.paused': 'Paused',
    'auditor.availability.home_sub':
        '{radius} km radius · max {max} jobs at a time',
    'auditor.availability.home_paused':
        'Tap to start accepting flash audits again',
    'auditor.availability.toggle': 'Accepting audits',
    'auditor.home.nearby_one': '1 Flash Audit nearby',
    'auditor.home.nearby_other': '{count} Flash Audits nearby',
    'auditor.home.nearby_sub':
        'Closest {distance}km · first to accept locks the file',
    'auditor.home.in_progress': 'In progress',
    'auditor.job.step_of': 'Step {step} of {steps}',
    'auditor.job.status.overdue': 'Overdue',
    'auditor.job.status.awaiting_cosign': 'In dispute window',
    'auditor.job.reassigned': 'Reassigned to you',
    'auditor.standing.title': 'Your standing',
    'auditor.standing.on_time': 'On-time close',
    'auditor.standing.avg_variance': 'Avg variance',
    'auditor.standing.jobs_done': 'Jobs done',
    'auditor.standing.clock_expiries': 'Clock expiries',
    'auditor.activity.title': 'Recent activity',
    'auditor.activity.empty': 'Nothing has happened on your files yet.',
    'auditor.activity.filed': 'Filed · {business}',
    'auditor.activity.filed_sub': '{when} · {variance}% variance',
    'auditor.activity.published': '{month} report approved',
    'auditor.activity.repayment': 'Repayment received',
    'auditor.activity.missed': 'Repayment missed · {business}',
    'auditor.activity.missed_sub': '{when} · your share pauses',
    'auditor.activity.deferred': 'Payment deferred · {business}',
    'auditor.activity.payout': 'Yield-share payout',
    'auditor.sector.agriculture': 'Agriculture',
    'auditor.sector.logistics': 'Logistics',
    'auditor.sector.manufacturing': 'Manufacturing',
    'auditor.sector.retail': 'Retail',
    'auditor.sector.energy': 'Energy',
    'auditor.sector.technology': 'Technology',
    'auditor.sector.services': 'Services',
    'auditor.jobs.head_title': 'Jobs',
    'auditor.jobs.title': 'Audit jobs',
    'auditor.jobs.lead':
        "Open field checks within {radius}km. First to accept locks the file. Each flash audit is due {hours} hours after it's sent out.",
    'auditor.jobs.map_label':
        'Map of your {radius} km dispatch radius with {count} open jobs at approximate positions',
    'auditor.jobs.map_badge': '{radius}km radius · {count} open',
    'auditor.jobs.map_label_page':
        'Map of your {radius} km dispatch radius with {count} jobs on this page at approximate positions',
    'auditor.jobs.map_badge_page': '{radius}km radius · {count} on this page',
    'auditor.jobs.assigned': 'In progress',
    'auditor.jobs.distance': 'Distance',
    'auditor.jobs.km': '{distance}km',
    'auditor.jobs.sector_unavailable': 'Sector unavailable',
    'auditor.jobs.kind_monthly': 'Monthly visit',
    'auditor.jobs.kind_flash': 'Flash audit',
    'auditor.jobs.show_more': 'Show more',
    'auditor.jobs.conflicts_link': 'Your declared conflicts →',
    'auditor.jobs.page_empty':
        'Nothing to show on this page. Earlier jobs may follow.',
    'auditor.jobs.assigned_empty':
        'No accepted work is on the clock right now.',
    'auditor.jobs.assigned_page_empty': 'Nothing assigned to you on this page.',
    'auditor.jobs.requested': 'Requested',
    'auditor.jobs.dscr': 'DSCR',
    'auditor.jobs.term': 'Term',
    'auditor.jobs.term_months': '{months}mo',
    'auditor.jobs.view_file': 'View full application →',
    'auditor.jobs.decline': 'Decline',
    'auditor.jobs.declare_conflict': 'Declare a conflict',
    'auditor.jobs.office.missing':
        "Rozine hasn't verified your office location yet, so no jobs can be sent to you. Jobs go to partners whose office is within {radius} km of the business. Contact Audit Operations to have it verified.",
    'auditor.jobs.office.expired':
        'Your office location was verified more than a year ago, so no new jobs can be sent to you until Rozine verifies it again. Contact Audit Operations.',
    'auditor.jobs.empty':
        "No open jobs in your area. We'll ping you the moment a Tier 2 deal needs a field check within {radius}km.",
    'auditor.monthly.title': 'Monthly reports',
    'auditor.monthly.lead':
        "You open each month's audit file for the businesses you manage. Audit on site and seal before the 7th — the business then has 24 hours to dispute.",
    'auditor.monthly.to_open': 'Audits to open',
    'auditor.monthly.waiting': '{count} waiting',
    'auditor.monthly.not_opened': '{month} · audit file not opened yet',
    'auditor.monthly.not_opened_undated': 'Audit file not opened yet',
    'auditor.desk_review.eyebrow': 'Desk review · {month}',
    'auditor.desk_review.eyebrow_undated': 'Desk review',
    'auditor.desk_review.status.disputed': 'Under desk review',
    'auditor.desk_review.status.escalated': 'Escalated',
    'auditor.desk_review.note':
        'Read-only until Rozine Admin resolves the ticket. Direct contact with this merchant is disabled.',
    'auditor.desk_review.proofs': 'Counter-proof filed',
    'auditor.desk_review.proofs_value': {
        one: '{count} item',
        other: '{count} items',
    },
    'auditor.desk_review.variance': 'Your weighted variance',
    'auditor.desk_review.view': 'View the report →',
    'auditor.monthly.open': 'Open the audit',
    'auditor.monthly.show_all': 'Show all {count} open windows',
    'auditor.monthly.show_fewer': 'Show fewer · {shown} of {count}',
    'auditor.monthly.due_line': '{note} · Due {date}',
    'auditor.monthly.status.in_progress': 'In progress',
    'auditor.monthly.status.overdue': 'Overdue',
    'auditor.monthly.status.changes_requested': 'Sent back',
    'auditor.monthly.status.awaiting_cosign': 'In dispute window',
    'auditor.monthly.inflow': 'Inflow',
    'auditor.monthly.outflow': 'Outflow',
    'auditor.monthly.cover': 'Cover',
    'auditor.monthly.review': 'Review & sign off on site →',
    'auditor.monthly.empty':
        'No monthly reports awaiting your audit right now.',
    'auditor.sheet.close': 'Close',
    'auditor.sheet.cancel': 'Cancel',
    'auditor.decline.title': 'Decline {business}',
    'auditor.decline.lead':
        'The job goes back to dispatch. Choose a reason; it is recorded with your decline.',
    'auditor.decline.placeholder': 'What keeps you from taking this job',
    'auditor.decline.submit': 'Decline job',
    'auditor.conflict.sheet_title': 'Declare an interest in {business}',
    'auditor.conflict.body':
        'If you have any interest in a business you are assigned to verify, say so. The declaration goes on the record, and a blocking conflict stops your work on the file straight away while Audit Operations arranges reassignment. You are never asked to grade a file you sourced — Rozine blocks that outright.',
    'auditor.conflict.kind_label': 'What kind of interest',
    'auditor.conflict.kind.financial_interest': 'Financial interest',
    'auditor.conflict.kind.role_tie':
        'Owner, director, employee or adviser tie',
    'auditor.conflict.kind.family_or_business': 'Close family or business tie',
    'auditor.conflict.kind.other': 'Other',
    'auditor.conflict.note_label': 'Factual explanation (required)',
    'auditor.conflict.note_placeholder': 'What the interest is and since when',
    'auditor.conflict.submit': 'Declare interest',
    'auditor.outcome.done': 'Done',
    'auditor.outcome.conflict.title': 'Interest declared',
    'auditor.outcome.conflict.recorded':
        'Your declaration about {business} is on the record. It does not stop your work on this assignment.',
    'auditor.outcome.declined.title': 'Job declined',
    'auditor.outcome.declined.body':
        '{business} is back with dispatch and your reason is on the record.',
    'auditor.outcome.sealed.title': 'Audit sealed and filed',
    'auditor.outcome.sealed.flash':
        'The field report for {business} is sealed. {business} can dispute it until {date}; the engine rates the business from your findings.',
    'auditor.outcome.sealed.monthly':
        'The {month} report for {business} — its uploaded statements and your factual findings — is sealed. {business} can dispute it until {date}.',
    'auditor.outcome.suggested.title': 'Changes requested',
    'auditor.outcome.suggested.body':
        'The business has your reason and explanation, and can resubmit the filing for your audit.',
    'auditor.outcome.rejected.title': 'Filing rejected',
    'auditor.outcome.rejected.body':
        "This filing version can't be verified and is closed with your reason on the record. It is not a credit judgement, and its evidence and report history are kept.",
    'auditor.sheet.back': 'Back',
    'auditor.file.head_title': '{business} · file',
    'auditor.file.label': '{business} business file',
    'auditor.file.eyebrow_preview': 'Application preview',
    'auditor.file.eyebrow_file': 'Business file',
    'auditor.file.place': '{district} · {distance}km',
    'auditor.file.continue': 'Continue the audit',
    'auditor.file.title': 'Review the application',
    'auditor.file.lead':
        "Everything {business} submitted, screened against Rozine's thresholds. Your field check resolves what the engine can't confirm remotely.",
    'auditor.file.lead_provisional':
        "{business}'s application as it currently stands.",
    'auditor.file.lead_no_prescreen': 'No automated pre-screen is on record.',
    'auditor.file.lead_field_check':
        "Your field check confirms what can't be verified remotely.",
    'auditor.file.reassigned_title': 'Reassigned to you',
    'auditor.file.reassigned_body':
        'The original deadline still applies — reassignment does not reset the clock. Earlier evidence on the file stays on the record.',
    'auditor.file.raise': 'The raise',
    'auditor.file.term_months': '{months} months',
    'auditor.file.return': 'Total return',
    'auditor.file.return_value': '{pct}% total',
    'auditor.file.use_of_funds': 'Use of funds',
    'auditor.file.documents': 'Submitted documents',
    'auditor.file.no_documents':
        'No submitted documents are on record for this file yet.',
    'auditor.file.doc_status.parsed': '✓ OCR',
    'auditor.file.doc_status.present': '✓ Present',
    'auditor.file.prescreen': 'Automated pre-screen',
    'auditor.file.no_prescreen':
        'No automated pre-screen result has been published for this file yet.',
    'auditor.file.check.met': 'Met',
    'auditor.file.check.flag': 'Flag',
    'auditor.file.document.pdf': 'Statement (PDF)',
    'auditor.file.document.csv': 'Statement (CSV)',
    'auditor.file.document.received': 'Received {date}',
    'auditor.file.document.received_records': {
        one: 'Received {date} · {count} record',
        other: 'Received {date} · {count} records',
    },
    'auditor.file.brief.flash.reason':
        'No note can list until an Audit Partner has visited the business and sealed a baseline report. Rozine checked the submitted statements remotely; your visit confirms on site that the business, its stock and its ledgers are real before any principal is issued.',
    'auditor.file.brief.flash.step_1':
        'Check in at the business premises; your live location is logged with the audit.',
    'auditor.file.brief.flash.step_2':
        'Take the required photos with the in-app camera inside the premises geofence. Gallery uploads are blocked.',
    'auditor.file.brief.flash.step_3':
        'Count the stock on site and record its value. Any difference from the declared figure must be explained before you can seal.',
    'auditor.file.brief.flash.step_4':
        'Reconcile the paper ledgers and receipts with the verified statements, following the ISRS 4400 agreed-upon procedures.',
    'auditor.file.brief.monthly.reason':
        'Every month while a note is outstanding, an Audit Partner checks the business’s records in person. In the current app, seal the audited month’s report by the end of the 7th of the following month (Kigali time). The business then has 24 hours to approve or dispute.',
    'auditor.file.brief.monthly.step_1':
        "Review the month's statements the business uploaded.",
    'auditor.file.brief.monthly.step_2':
        'Count cash and stock units on site. Any difference must be explained before you can seal.',
    'auditor.file.brief.monthly.step_3':
        'Take the required photos with the in-app camera inside the premises geofence.',
    'auditor.file.brief.monthly.step_4':
        'Seal by the report’s due date: the end of the 7th of the following month (Kigali time) in the current app.',
    'auditor.file.why_review': 'Why this statement review is required',
    'auditor.file.mandate_review': 'What you must check',
    'auditor.file.brief.statement_review.reason':
        'Before this business can raise, an Audit Partner checks its bank and Mobile Money statements against the monthly figures it saved. This is a factual review of the documents, done from the statements on file. There is no site visit, stock count or photos.',
    'auditor.file.brief.statement_review.step_1':
        'Open each statement and note where you checked it.',
    'auditor.file.brief.statement_review.step_2':
        'Compare the monthly figures and each account’s opening and closing balances with the statements.',
    'auditor.file.brief.statement_review.step_3':
        'Record every debt you find, with what was paid each month and its schedule ahead.',
    'auditor.file.brief.statement_review.step_4':
        'Record the usual owner draw and your findings, then verify the statements.',
    'auditor.file.why': 'Why a field audit is required',
    'auditor.file.mandate': 'What you must clear on site',
    'auditor.file.history': 'File history',
    'auditor.file.first_visit': 'First visit',
    'auditor.file.first_visit_body':
        'No earlier audit is on file for this business.',
    'auditor.file.last_audit': 'Last audit {date} · {kind} · {by}',
    'auditor.file.kind.flash': 'Flash Audit',
    'auditor.file.kind.monthly': 'Monthly report',
    'auditor.file.flags': 'Flags on file',
    'auditor.file.no_flags': 'No flags on file.',
    'auditor.audit.head_title': '{business} · audit',
    'auditor.audit.label': '{business} audit',
    'auditor.audit.eyebrow_flash': 'Field Flash Audit',
    'auditor.audit.eyebrow_monthly': 'Monthly report · audit',
    'auditor.audit.steps': 'Audit steps',
    'auditor.audit.step.review': 'Review',
    'auditor.audit.step.check_in': 'Check-in',
    'auditor.audit.step.photos': 'Photos',
    'auditor.audit.step.ledger': 'Ledger',
    'auditor.audit.step.seal': 'Seal',
    'auditor.audit.step.statements': 'Statements',
    'auditor.audit.step.count': 'Count & cash',
    'auditor.audit.step.cash_sales': 'Cash sales',
    'auditor.audit.step.costs': 'Costs',
    'auditor.audit.step.history': 'History',
    'auditor.audit.step.premises': 'Premises',
    'auditor.audit.step.people': 'People',
    'auditor.audit.step.registration': 'Registration',
    'auditor.audit.step.trading': 'Trading start',
    'auditor.audit.step.available': 'Money now',
    'auditor.audit.step.reason': 'Reason',
    'auditor.audit.step.drawings': 'Drawings',
    'auditor.audit.step.loans': 'Other loans',
    'auditor.audit.step.differences': 'Differences',
    'auditor.audit.step.staff': 'Staff',
    'auditor.audit.step.expiries': 'Expiry dates',
    'auditor.audit.step.loan_use': 'Loan money',
    'auditor.audit.step.changes': 'Changes',
    'auditor.audit.continue': 'Continue',
    'auditor.audit.to_seal': 'Review & seal',
    'auditor.audit.start_count': 'Start the count',
    'auditor.audit.to_photos': 'Continue to photos',
    'auditor.audit.explain_differences': 'Explain the difference',
    'auditor.audit.back': 'Back',
    'auditor.audit.back_to_jobs': 'Back to jobs',
    'auditor.audit.offline':
        "You're offline. Nothing is lost: what you typed stays on this page, and the capture app keeps your evidence encrypted and syncs it when you reconnect.",
    'auditor.audit.variance': 'Variance',
    'auditor.audit.within_tolerance': 'Within tolerance',
    'auditor.audit.outside_tolerance': 'Variance exceeds tolerance',
    'auditor.capture.title': 'Captured in the Rozine capture app',
    'auditor.capture.body':
        "Photos and your on-site check-in are taken in the capture app, never on the web. Each item's capture time, position and device check are shown exactly as the server records them.",
    'auditor.capture.open': 'Open the capture app',
    'auditor.capture.status.not_started':
        'Capture app not opened on this job yet',
    'auditor.capture.status.capturing':
        'Capturing on your phone · {received} of {expected} received',
    'auditor.capture.status.syncing':
        'Syncing · {received} of {expected} received',
    'auditor.capture.status.complete': 'All {expected} items received',
    'auditor.capture.attention.no_signal':
        'No signal — the capture app keeps everything encrypted and syncs when you reconnect',
    'auditor.capture.attention.storage_full':
        "Your phone's storage is full — free space so the capture app can keep capturing",
    'auditor.capture.attention.upload_failed':
        'Upload failed — open the capture app to retry',
    'auditor.capture.last_sync': 'Last sync {when}',
    'auditor.checkin.title': 'Check in on site',
    'auditor.checkin.lead':
        "Confirm you're physically at the business. Your location is logged with the audit.",
    'auditor.checkin.waiting': 'Waiting for your check-in',
    'auditor.checkin.position': '{position} · ±{accuracy} m',
    'auditor.checkin.done': 'Checked in on site · {time}',
    'auditor.checkin.open': 'Check in with the capture app',
    'auditor.checkin.review_title': 'This check-in goes to review',
    'auditor.checkin.review_body':
        "The location's accuracy or its distance from the registered premises is outside policy. Carry on; Audit Operations reviews it.",
    'auditor.photos.title': 'Geo-tagged site photos',
    'auditor.photos.lead':
        'Captured live in the capture app — gallery uploads are disabled to prevent spoofing. {captured} of {required} required captured.',
    'auditor.photos.extras': {
        one: 'Plus {count} extra photo.',
        other: 'Plus {count} extra photos.',
    },
    'auditor.photos.grid': 'Site photos',
    'auditor.photos.pending': 'Not captured yet',
    'auditor.photos.captured': 'Captured',
    'auditor.photos.untitled': 'Untitled',
    'auditor.photos.add': 'Add photo',
    'auditor.photos.titles': 'Title your extra photos',
    'auditor.photos.extra': 'Extra {n}',
    'auditor.photos.left': '{count} left',
    'auditor.photos.placeholder':
        'What this shows — e.g. cold room #2 at capacity',
    'auditor.ledger.title': 'Inventory & ledger sign-off',
    'auditor.ledger.lead':
        'Enter the inventory value you counted. We compare it to the reported figure — a variance over {tolerance} is flagged.',
    'auditor.ledger.reported': 'Reported inventory',
    'auditor.ledger.observed': 'Observed on site · stock value (RWF)',
    'auditor.ledger.observed_placeholder': 'e.g. 42,000,000',
    'auditor.ledger.attachments': 'Ledger book attachments',
    'auditor.ledger.none': 'None attached',
    'auditor.ledger.accepted': '{parsed} of {count} accepted',
    'auditor.ledger.rules':
        'Upload the original ledger as a PDF or CSV (up to 10 MB). A scanned ledger can be a PDF; a scan without text is marked for manual source review.',
    'auditor.ledger.doc.scanning': 'Checking … {detail}',
    'auditor.ledger.doc.scanning_hint':
        'Reading a ledger usually takes about a minute. This page updates on its own.',
    'auditor.ledger.doc.parsed': 'Accepted · {detail}',
    'auditor.ledger.doc.failed': 'Rejected · {detail}',
    'auditor.ledger.reading': 'Reading the document',
    'auditor.ledger.rescan': 'Re-scan this document',
    'auditor.ledger.file_input': 'Ledger document file',
    'auditor.ledger.attach': 'Attach the ledger (PDF or CSV)',
    'auditor.ledger.attach_another': 'Add another ledger document',
    'auditor.ledger.reconciles':
        'Secondary paper ledgers and receipts reconcile with the digital statements.',
    'auditor.ledger.reconciles_blocked':
        'Attach and pass at least one ledger document first.',
    'auditor.statements.title': 'Read the month',
    'auditor.statements.lead':
        'What the bank, MoMo and POS statements on file report for the period. Nothing to fill in here.',
    'auditor.statements.unavailable':
        "Statements for this period aren't on file yet",
    'auditor.statements.parsed': 'Parsed from bank & MoMo statements',
    'auditor.statements.inflow': 'Gross inflow',
    'auditor.statements.outflow': 'Gross outflow',
    'auditor.statements.net': 'Net cash',
    'auditor.statements.cover': 'Liquidity cover',
    'auditor.statements.view': 'View',
    'auditor.count.title': 'Count & cash',
    'auditor.count.lead':
        'Tick the proof you actually saw, then enter what you observed. We compare each figure to the statements on file — no figure here comes from the business.',
    'auditor.count.financial': 'Financial proof',
    'auditor.count.vault': 'Evidence vault',
    'auditor.count.cash': 'Observed on site · cash (RWF)',
    'auditor.count.cash_hint':
        'Statements show {amount} · tolerance {tolerance}',
    'auditor.count.cash_no_statement':
        'No statement balance on file for this period.',
    'auditor.count.period': 'Statement period',
    'auditor.count.account': 'Account / MoMo ref',
    'auditor.count.inventory': 'Inventory proof',
    'auditor.count.stock': 'Observed on site · stock (units)',
    'auditor.count.units': 'units',
    'auditor.count.stock_hint': 'Reported stock: {units} units',
    'auditor.count.stock_no_baseline':
        'No reported stock baseline for this period.',
    'auditor.count.stock_tolerance': 'Stock tolerance: {units} units',
    'auditor.count.operational': 'Operational status',
    'auditor.count.status.active': 'Active',
    'auditor.count.status.suspended': 'Suspended',
    'auditor.count.status.restricted': 'Restricted',
    'auditor.seal.title': 'Sign off & seal',
    'auditor.seal.lead':
        'Your findings are attributed to your ICPAR licence and published to holders once the business has had 24 hours to dispute them.',
    'auditor.seal.note': 'Assessment note',
    'auditor.seal.note_optional': 'Optional',
    'auditor.seal.note_required': 'Required',
    'auditor.seal.note_done': 'Required · done',
    'auditor.seal.note_placeholder': 'Say what you saw and why',
    'auditor.seal.note_count': '{count} / {max}',
    'auditor.seal.preview': 'Preview findings',
    'auditor.seal.suggest': 'Request changes',
    'auditor.seal.suggest_lead':
        "{business}'s filing goes back to the business to correct and resubmit. Choose the reason and state the facts.",
    'auditor.seal.suggest_placeholder':
        'e.g. the September cash count sheet is unsigned',
    'auditor.seal.suggest_submit': 'Request changes',
    'auditor.seal.reject': 'Reject filing',
    'auditor.seal.reject_lead':
        "This version of {business}'s filing can't be verified. Choose the reason and state the facts — this concerns the filing, not the business's credit, and its evidence and history are kept.",
    'auditor.seal.reject_placeholder':
        "What you checked and what couldn't be verified",
    'auditor.seal.reject_submit': 'Reject filing',
    'auditor.seal.findings_eyebrow': 'Factual findings · {version}',
    'auditor.seal.digest': 'Pending digest',
    'auditor.seal.digest_note':
        'Finalised when your ICPAR seal is applied. The signed PDF is compiled by Rozine and released to holders after the business approves.',
    'auditor.seal.apply': 'Re-authenticate to seal',
    'auditor.seal.submit': 'Apply ICPAR licence seal',
    'auditor.sealed.title': 'Sealed and filed',
    'auditor.sealed.body':
        'The report is sealed and can no longer be edited. {party} can dispute it until {date}; it publishes to holders after that.',
    'auditor.sealed.timeline': 'Filing progress',
    'auditor.sealed.stage.sealed': 'Sealed by you',
    'auditor.sealed.stage.cosigned': 'Business co-signature',
    'auditor.sealed.stage.published': 'Published to holders',
    'auditor.sealed.cosign.pending': 'Waiting',
    'auditor.sealed.cosign.signed': 'Signed',
    'auditor.sealed.cosign.declined': 'Disputed',
    'auditor.sealed.cosign.overdue': 'Overdue',
    'auditor.sealed.seal': 'Seal digest',
    'auditor.sealed.licence':
        'Sealed under licence {licence}. The original stays on the record; any correction is a linked amendment.',
    'auditor.sealed.amend': 'Start a linked amendment',
    'auditor.portfolio.head_title': 'Portfolio',
    'auditor.portfolio.title': 'Portfolio',
    'auditor.portfolio.lead':
        'You earn two ways: verification and monitoring on the files you grade, and origination commission on the deals you bring in.',
    'auditor.portfolio.cpa_card': 'Your CPA card',
    'auditor.portfolio.cpa_card_sub': 'Proof you are ours',
    'auditor.portfolio.sourced.title': 'Deals you sourced',
    'auditor.portfolio.sourced.tag': 'Origination',
    'auditor.portfolio.sourced.empty':
        'Deals you bring in earn origination commission from repayments. A different CPA verifies them, so your commission never depends on the grade you would have given.',
    'auditor.portfolio.earnings.title': 'Verification earned · this month',
    'auditor.portfolio.earnings.managed': 'Managed deals',
    'auditor.portfolio.earnings.next_payout': 'Next payout',
    'auditor.portfolio.yield.title': 'Yield-share',
    'auditor.portfolio.yield.empty':
        'Yield-share payouts appear here once they are paid.',
    'auditor.portfolio.managed.title': 'Managed deals',
    'auditor.portfolio.managed.groups': 'Managed deal groups',
    'auditor.portfolio.managed.group.active': 'Active portfolio',
    'auditor.portfolio.managed.group.raising': 'Raising',
    'auditor.portfolio.managed.filter': 'Raise status',
    'auditor.portfolio.managed.chip.all': 'All',
    'auditor.portfolio.managed.chip.live': 'Live',
    'auditor.portfolio.managed.chip.failed': 'Failed',
    'auditor.portfolio.managed.raising_empty':
        'No raises from the businesses you audited in this view.',
    'auditor.portfolio.managed.raised': 'Raised',
    'auditor.portfolio.managed.target': 'Target',
    'auditor.portfolio.managed.investors': 'Investors',
    'auditor.portfolio.managed.count': '{count} total',
    'auditor.portfolio.managed.sub': '{district} · credited {date}',
    'auditor.portfolio.managed.funded': 'Funded',
    'auditor.portfolio.managed.raise': 'Raise',
    'auditor.portfolio.managed.fee': 'Platform fee',
    'auditor.portfolio.managed.share': 'Your 40%',
    'auditor.portfolio.managed.empty':
        "No managed deals yet. Pass a Flash Audit to become a business's account manager.",
    'auditor.calendar.title': 'Audit calendar',
    'auditor.calendar.lead': 'Every monthly verification you owe, and when.',
    'auditor.calendar.previous': 'Previous month',
    'auditor.calendar.next': 'Next month',
    'auditor.calendar.legend.soon': 'Due within 5 days',
    'auditor.calendar.legend.scheduled': 'Scheduled',
    'auditor.calendar.legend.passed': 'Passed',
    'auditor.calendar.day_due_one': '1 verification due',
    'auditor.calendar.day_due_other': '{count} verifications due',
    'auditor.calendar.close': 'Close',
    'auditor.calendar.soon_one': '1 verification due within 5 days',
    'auditor.calendar.soon_other': '{count} verifications due within 5 days',
    'auditor.calendar.soon_body_one':
        'File by the due date: {business}, due {date}.',
    'auditor.calendar.soon_body_other':
        'File each by its due date. The first is {business}, due {date}.',
    'auditor.calendar.clear': 'Nothing due in the next five days',
    'auditor.calendar.clear_next':
        'You are clear. The next filing is {business} on {date}.',
    'auditor.calendar.clear_none':
        'You are clear. The next filing is not scheduled yet.',
    'auditor.calendar.due_in': 'Due in {month}',
    'auditor.calendar.count_one': '1 deal',
    'auditor.calendar.count_other': '{count} deals',
    'auditor.calendar.empty': 'Nothing due in this month.',
    'auditor.calendar.partial': 'Some assigned work is not shown here',
    'auditor.calendar.partial_body':
        'This calendar shows your latest assignments only. Open Jobs to check every verification you owe.',
    'auditor.calendar.partial_empty':
        'Nothing due this month among your latest assignments.',
    'auditor.calendar.reports_partial':
        'Only your latest filed reports are shown here, so a month may be missing some.',
    'auditor.calendar.open_jobs': 'Open Jobs',
    'auditor.calendar.share': 'Your share',
    'auditor.calendar.on_time_by': 'On-time by',
    'auditor.calendar.due_today': 'Due today',
    'auditor.calendar.in_days_one': 'In 1 day',
    'auditor.calendar.in_days_other': 'In {count} days',
    'auditor.calendar.overdue_one': 'Overdue 1 day',
    'auditor.calendar.overdue_other': 'Overdue {count} days',
    'auditor.calendar.monthly': 'Monthly verification',
    'auditor.calendar.filed_on': '{title} · filed {date}',
    'auditor.conflict.title': 'Declare an interest',
    'auditor.conflict.none_assigned':
        'You have no files assigned to verify right now.',
    'auditor.conflict.options_one': 'Declare on your 1 assigned file',
    'auditor.conflict.options_other':
        'Declare on any of your {count} assigned files',
    'auditor.conflict.on_record': 'On the record',
    'auditor.conflict.business_on_record': 'Business on record',
    'auditor.conflict.assignment_ref': 'Ref. {reference}',
    'auditor.conflicts.head_title': 'Your conflicts',
    'auditor.conflicts.title': 'Your declared conflicts',
    'auditor.conflicts.lead':
        'Every conflict you have declared, as it was recorded. Each one stopped your work on that assignment, and only your own receipt remains.',
    'auditor.conflicts.empty': 'You have not declared any conflicts.',
    'auditor.conflicts.page_empty':
        'Nothing to show on this page. Earlier declarations may follow.',
    'auditor.reports.status.awaiting_cosign': 'In dispute window',
    'auditor.reports.status.published': 'Published',
    'auditor.reports.status.late': 'Late',
    'auditor.reports.status.rejected': 'Rejected',
    'auditor.reports.flash': 'Flash Audit',
    'auditor.reports.monthly': '{month} report',
    'auditor.reports.late_by': '{title} · filed {days} days late',
    'auditor.reports.district': 'District',
    'auditor.reports.amend': 'Start a linked amendment →',
    'auditor.profile.head_title': 'Profile',
    'auditor.profile.title': 'Profile',
    'auditor.profile.menu': 'Profile sections',
    'auditor.profile.section.accreditation': 'Accreditation',
    'auditor.profile.section.availability': 'Availability & coverage',
    'auditor.profile.section.earnings': 'Earnings',
    'auditor.profile.section.contact': 'Personal & contact',
    'auditor.profile.section.payout': 'Payout bank account',
    'auditor.profile.section.telemetry': 'Audit-the-Auditor telemetry',
    'auditor.profile.section.security': 'Security & devices',
    'auditor.profile.section.learn': 'Auditor Academy',
    'auditor.profile.section.legal': 'Terms & legal',
    'auditor.profile.contact.title': 'Personal & contact',
    'auditor.profile.contact.phone': 'Phone',
    'auditor.profile.contact.email': 'Email',
    'auditor.profile.contact.address': 'Address',
    'auditor.profile.contact.district': 'District of operation',
    'auditor.profile.contact.not_provided': 'Not provided',
    'auditor.profile.telemetry.title': 'Audit-the-Auditor telemetry',
    'auditor.profile.telemetry.clock_expiries': 'Clock expiries',
    'auditor.profile.telemetry.clock_expiries_sub':
        'Accepted audits that hit 0:00',
    'auditor.profile.telemetry.variance': 'Variance accuracy',
    'auditor.profile.telemetry.variance_sub':
        'Average gap against the backend analysis',
    'auditor.profile.telemetry.on_time': 'On-time closing',
    'auditor.profile.telemetry.jobs_done': {
        one: '{count} job completed',
        other: '{count} jobs completed',
    },
    'auditor.profile.telemetry.strikes': 'Quality strikes',
    'auditor.profile.telemetry.strikes_sub': 'Disputes upheld against you',
    'auditor.profile.telemetry.defended': 'Disputes defended',
    'auditor.profile.telemetry.defended_sub': 'Desk review confirmed your work',
    'auditor.profile.telemetry.first_note': 'First-note defaults',
    'auditor.profile.telemetry.first_note_sub': 'Deals defaulting on note 1',
    'auditor.profile.telemetry.note':
        'Rozine monitors partner performance to protect investor capital. A metric shows a dash until it is measured.',
    'auditor.profile.security.title': 'Security & devices',
    'auditor.profile.security.two_factor': 'Two-factor authentication',
    'auditor.profile.security.two_factor_on':
        'On · authenticator app code at sign-in',
    'auditor.profile.security.two_factor_off':
        'Off · add an authenticator app code at sign-in',
    'auditor.profile.security.password': 'Change password',
    'auditor.profile.legal.title': 'Terms & legal',
    'auditor.profile.legal.engagement': 'Engagement terms',
    'auditor.profile.legal.current':
        'Accepted · the platform MSA and agreed procedures',
    'auditor.profile.legal.required': 'Needs your acceptance',
    'auditor.profile.legal.unavailable': "Can't be read right now",
    'auditor.profile.earnings.title': 'Earnings',
    'auditor.profile.earnings.lead':
        'Everything you have earned this month, every line, and exactly how it was worked out.',
    'auditor.profile.earnings.this_month': 'Earned in {month}',
    'auditor.profile.earnings.total': 'Earned in total',
    'auditor.profile.earnings.sources': 'Where it came from',
    'auditor.profile.earnings.platform_fee': 'Platform fee share',
    'auditor.profile.earnings.platform_fee_rule':
        '40% of the 3% platform fee on each raise you audited, credited when it is paid out.',
    'auditor.profile.earnings.earnings_fee': 'Earnings fee share',
    'auditor.profile.earnings.earnings_fee_rule':
        '25% of the earnings fee on each monthly payout to investors. Starts with the first payout.',
    'auditor.profile.earnings.lines': 'Every line this month',
    'auditor.profile.earnings.line_count': {
        one: '{count} line',
        other: '{count} lines',
    },
    'auditor.profile.earnings.empty':
        'Nothing earned yet this month. A line appears the moment a raise you audited is paid out.',
    'auditor.profile.earnings.basis':
        'Raise {raise} · 3% platform fee {fee} · your 40% {amount}',
    'auditor.profile.pending.payout_title': 'No payout account yet',
    'auditor.profile.pending.payout_body':
        'The bank account your earnings are paid to will show here.',
    'auditor.profile.pending.legal_title': 'Coming to the app soon',
    'auditor.profile.pending.legal_body':
        'Your engagement terms will be readable here.',
    'auditor.profile.on_time': 'On-time',
    'auditor.profile.jobs': 'Jobs',
    'auditor.profile.since': 'Since',
    'auditor.accreditation.title': 'Accreditation',
    'auditor.accreditation.licence_line': 'Licence {licence} · expires {date}',
    'auditor.accreditation.badge.active': '✓ Active',
    'auditor.accreditation.badge.expired': 'Expired',
    'auditor.accreditation.badge.suspended': 'Suspended',
    'auditor.accreditation.badge.pending': 'Renewal pending',
    'auditor.accreditation.standing': 'Days of standing left',
    'auditor.accreditation.days_left': '{count} days until renewal is due',
    'auditor.accreditation.expired_ago': 'Expired {count} days ago',
    'auditor.accreditation.pending_title': 'Renewal under review',
    'auditor.accreditation.pending_line':
        '{id} · licence {licence} to {date}, submitted {submitted}. Rozine staff clear it against the ICPAR register.',
    'auditor.accreditation.withdraw': 'Withdraw',
    'auditor.accreditation.rejected_title': 'Renewal declined',
    'auditor.accreditation.rejected_line':
        '{id} was declined — {reason}. Correct the details and submit again.',
    'auditor.accreditation.renew': 'Renew accreditation',
    'auditor.accreditation.licence_label': 'Licence number',
    'auditor.accreditation.licence_placeholder': 'ICPAR/CPA/0000',
    'auditor.accreditation.expiry_label': 'New expiry date',
    'auditor.accreditation.certificate_label': 'Licence certificate',
    'auditor.accreditation.certificate_drop': 'Drop the ICPAR certificate',
    'auditor.accreditation.submit': 'Submit for review',
    'auditor.availability.title': 'Availability & coverage',
    'auditor.availability.accepting_sub':
        'You appear in the dispatch pool and can be offered flash audits.',
    'auditor.availability.paused_sub':
        'No new jobs are offered. Accepted jobs still run their clock.',
    'auditor.availability.max_title': 'Jobs at a time',
    'auditor.availability.max_sub':
        'Dispatch stops offering you work past this.',
    'auditor.availability.radius_title': 'Coverage',
    'auditor.availability.radius_sub':
        'Measured from your registered office to the business premises.',
    'business.rating.page_title': 'Financial health',
    'business.rating.page_subtitle':
        'Your score, capacity and verified financials.',
    'business.rating.title': 'Rozine rating',
    'business.rating.out_of': 'out of 5',
    'business.rating.explainer':
        'Each audited monthly report updates this. A higher rating unlocks more capacity and lower rates on your next raise.',
    'business.rating.drift': 'Rated {audited} at audit — now {now}',
    'business.rating.refused':
        'The rating engine could not rate this business yet',
    'business.rating.factors': 'Score breakdown',
    'business.rating.factor.financial_health': 'Financial health',
    'business.rating.factor.repayment_history': 'Repayment history',
    'business.rating.factor.statement_consistency': 'Statement consistency',
    'business.rating.factor.track_record': 'Track record',
    'business.rating.sizing.title': 'How your capacity is sized',
    'business.rating.sizing.cash': 'Cash you keep a month',
    'business.rating.sizing.cash_note':
        'After owner drawings and the debts you already pay',
    'business.rating.sizing.cover': 'Cover kept',
    'business.rating.sizing.cover_note':
        'Every monthly payment must be covered {cover} times by that cash',
    'business.rating.sizing.cover_value': '{cover}×',
    'business.rating.sizing.term': 'Term and rate sized on',
    'business.rating.sizing.term_value': '{months} months · {rate}% flat',
    'business.rating.sizing.explainer':
        'Your capacity is what the cash your business keeps each month can repay with that margin of safety, capped by the lowest of the three limits above. Nobody at Rozine sets this number by hand. Headroom is that capacity less the raises you have signed since it was sized; every new application is sized again on fresh statements.',
    'business.rating.limits': 'Three limits · the lowest one applies',
    'business.rating.limit.cash_flow': 'What your cash carries',
    'business.rating.limit.cash_flow_detail':
        'Repaid at {cover}× cover over {months} months',
    'business.rating.limit.cash_flow_lift':
        'Keep more cash each month, or choose a longer term',
    'business.rating.limit.exposure': 'Exposure limit',
    'business.rating.limit.exposure_detail':
        '{limit} (35% of a year’s revenue) less {committed} you already owe',
    'business.rating.limit.exposure_lift':
        'Grow your revenue, or repay what you owe',
    'business.rating.limit.policy_max': 'Policy maximum',
    'business.rating.limit.policy_max_detail':
        'Hard ceiling on any single raise',
    'business.rating.limit.policy_max_lift': 'None — this is absolute',
    'business.rating.limit.yours': 'This is your limit',
    'business.rating.approved': 'Approved capacity',
    'business.rating.bound': 'What is limiting you: {limit}',
    'business.rating.lift': 'To raise it — {how}',
    'business.rating.headroom': 'Headroom available',
    'business.rating.headroom_note': 'You can raise this much more now.',
    'business.rating.raise': 'Raise',
    'business.rating.financials': 'Verified financials',
    'business.rating.sources': 'Bank · MoMo',
    'business.rating.financial.revenue': 'Avg monthly rev.',
    'business.rating.financial.ebitda': 'Net cash / month',
    'business.rating.financial.margin': 'Net margin',
    'business.rating.financial.outstanding': 'Outstanding',
    'business.repayments.title': 'Repayments',
    'business.repayments.progress': 'Repayment progress',
    'business.repayments.payments': '{made} / {total} payments',
    'business.repayments.remaining': 'Remaining',
    'business.repayments.total': 'Total',
    'business.repayments.schedule': 'Repayment schedule',
    'business.repayments.late.title': 'If a payment is late',
    'business.repayments.issued.lede': '{title} · as issued',
    'business.repayments.issued.principal': 'Principal',
    'business.repayments.issued.return': 'Return',
    'business.repayments.issued.total': 'Total to repay',
    'business.visit_ready.title': 'Get ready for this month’s visit',
    'business.visit_ready.lede':
        'Your next repayment is due on {due}. Your CPA visits between {from} and {by}, and will ask for the items below. Tick each one when it is ready.',
    'business.visit_ready.progress': '{done} of {total} ready',
    'business.visit_ready.shows': 'Must show: {text}',
    'business.visit_ready.where': 'Where to get it: {text}',
    'business.visit_ready.failed':
        'We couldn’t save that tick. Check your connection and try again.',
    'business.visit_ready.footer':
        'The CPA sees these on the visit; investors only ever see the sealed report. The 10 transactions the CPA will check are shown here 3 days before the visit. Uploading early is optional.',
    'business.visit_ready.item.bank_statements.title':
        'Bank and mobile money statements',
    'business.visit_ready.item.bank_statements.shows':
        'every account’s money in and out for last month',
    'business.visit_ready.item.bank_statements.where':
        'your bank or mobile money app (download the monthly statement)',
    'business.visit_ready.item.cash_and_stock.title':
        'Cash and stock ready to count',
    'business.visit_ready.item.cash_and_stock.shows':
        'the cash on hand and the stock on the premises on the day',
    'business.visit_ready.item.cash_and_stock.where':
        'your till, safe and store room',
    'business.visit_ready.item.sales_records.title':
        'Record of cash sales not banked',
    'business.visit_ready.item.sales_records.shows':
        'cash sales kept or spent before reaching the bank',
    'business.visit_ready.item.sales_records.where':
        'your sales book or receipt book',
    'business.visit_ready.item.cost_receipts.title':
        'Receipts and invoices for costs',
    'business.visit_ready.item.cost_receipts.shows':
        'what you spent on stock, staff, premises, transport, taxes and other costs',
    'business.visit_ready.item.cost_receipts.where':
        'your suppliers, landlord and RRA receipts',
    'business.visit_ready.item.owner_payments.title':
        'Record of money paid to owners',
    'business.visit_ready.item.owner_payments.shows':
        'what the owners took out of the business last month',
    'business.visit_ready.item.owner_payments.where':
        'your cash book or bank statement',
    'business.visit_ready.item.loan_papers.title': 'Papers for other loans',
    'business.visit_ready.item.loan_papers.shows':
        'each other lender, what is still owed and the monthly payment',
    'business.visit_ready.item.loan_papers.where':
        'your loan agreements or lender statements',
    'business.visit_ready.item.rssb_total.title': 'RSSB total page',
    'business.visit_ready.item.rssb_total.shows':
        'the number of staff declared, with names covered',
    'business.visit_ready.item.rssb_total.where':
        'your RSSB declaration (the total page only)',
    'business.visit_ready.item.tax_clearance.title':
        'Tax clearance certificate',
    'business.visit_ready.item.tax_clearance.shows': 'the date it expires',
    'business.visit_ready.item.tax_clearance.where':
        'RRA, or your copy of the certificate',
    'business.visit_ready.item.rssb_clearance.title':
        'RSSB clearance certificate',
    'business.visit_ready.item.rssb_clearance.shows': 'the date it expires',
    'business.visit_ready.item.rssb_clearance.where':
        'RSSB, or your copy of the certificate',
    'business.visit_ready.item.loan_use_proof.title':
        'Proof of how the loan money was used',
    'business.visit_ready.item.loan_use_proof.shows':
        'what the Rozine money paid for',
    'business.visit_ready.item.loan_use_proof.where':
        'the invoices or receipts for what you bought',
    'business.visit_ready.item.change_papers.title': 'Papers for any changes',
    'business.visit_ready.item.change_papers.shows':
        'any new debt, change of director or owner, or legal claim since the last visit',
    'business.visit_ready.item.change_papers.where':
        'contracts, board minutes or court papers',
    'business.visit_ready.item.premises_access.title': 'Access for photos',
    'business.visit_ready.item.premises_access.shows':
        'the premises and stock, so the CPA can take photos in the app',
    'business.visit_ready.item.premises_access.where':
        'someone on site to open up on the day',
    'business.repayments.issued.split':
        'Principal {principal} · return {return}',
    'business.repayments.issued.note':
        'Each instalment is principal plus return, due on the date shown. Payments, late charges and receipts will show here once repayments open.',
    'business.repayments.none.title': 'Nothing to repay yet',
    'business.repayments.none.body':
        'Repayments start once a raise is funded and paid out to you. Its schedule, what is due and every payment will show here.',
    'business.servicing.repay.state.current': 'Current',
    'business.servicing.repay.state.due_today': 'Due today',
    'business.servicing.repay.state.overdue': 'Overdue',
    'business.servicing.repay.state.repaid': 'Repaid',
    'business.servicing.repay.state.defaulted': 'In default',
    'business.servicing.repay.instalments_left': {
        one: '{count} instalment left',
        other: '{count} instalments left',
    },
    'business.servicing.repay.repaid': 'This note is fully repaid. Thank you.',
    'business.servicing.repay.due_title': 'Due now',
    'business.servicing.repay.due_today': 'Due today',
    'business.servicing.repay.dpd': {
        one: '{count} day past due',
        other: '{count} days past due',
    },
    'business.servicing.repay.next_title': 'Next instalment · #{index}',
    'business.servicing.repay.due_on': 'Due {date}',
    'business.servicing.repay.next_late_fee':
        'If it is still unpaid on {date}, a late fee of about {amount} is added. This is a projection.',
    'business.servicing.repay.autocollect':
        "We'll collect it from your Rozine wallet on {date}.",
    'business.servicing.repay.autocollect_off':
        'Automatic collection from your wallet is off.',
    'business.servicing.repay.restriction.ARREARS':
        "In arrears since {date}. Your notes can't be traded until it is paid.",
    'business.servicing.repay.restriction.RESTRICTION_ACTIVE':
        'Restricted since {date}. You can still repay.',
    'business.servicing.repay.part.principal': 'Principal',
    'business.servicing.repay.part.return': 'Return',
    'business.servicing.repay.part.late_fees': 'Late fees',
    'business.servicing.repay.part.total': 'Total',
    'business.servicing.repay.pay_title': 'Pay from your wallet',
    'business.servicing.repay.option.due_now': "What's due now",
    'business.servicing.repay.option.next_instalment': 'Next instalment early',
    'business.servicing.repay.option_note.due_now':
        'Pays instalment {list}, with any late fees.',
    'business.servicing.repay.option_note.next_instalment':
        "Pays instalment {list} early, at its scheduled amount. There's no discount for paying early.",
    'business.servicing.repay.wallet_has':
        'Your wallet has {amount} available.',
    'business.servicing.repay.pay_cta': 'Pay {amount}',
    'business.servicing.repay.short':
        "There isn't enough in your wallet for this payment.",
    'business.servicing.repay.top_up': 'Add money to your wallet',
    'business.servicing.repay.receipt_title': 'Payment recorded',
    'business.servicing.repay.allocating': 'Being shared with your investors',
    'business.servicing.repay.allocated': {
        one: 'Shared with {count} investor',
        other: 'Shared with {count} investors',
    },
    'business.servicing.repay.unapplied':
        "{amount} wasn't needed and stays in your wallet.",
    'business.servicing.repay.receipt_done': 'Done',
    'business.servicing.repay.recent': 'Recent repayments',
    'business.servicing.repay.row.due': '#{index} · due {date}',
    'business.servicing.repay.row.paid': '#{index} · paid {date}',
    'business.servicing.repay.status.paid': 'Paid',
    'business.servicing.repay.status.processing': 'Processing',
    'business.servicing.repay.status.due': 'Due',
    'business.servicing.repay.status.partially_paid': 'Part paid',
    'business.servicing.repay.status.overdue': 'Overdue',
    'business.servicing.repay.status.upcoming': 'Upcoming',
    'business.servicing.repay.late_lines': 'Late fees on this instalment',
    'business.servicing.repay.late_line': '{step} · {date} · {status}',
    'business.servicing.repay.step.due_date': 'Due date missed',
    'business.servicing.repay.step.day_7': 'Day 7 unpaid',
    'business.servicing.repay.step.day_30': 'Day 30 unpaid',
    'business.servicing.repay.fee_status.projected': 'projected',
    'business.servicing.repay.fee_status.assessed': 'added',
    'business.servicing.repay.fee_status.partially_collected': 'part paid',
    'business.servicing.repay.fee_status.collected': 'paid',
    'business.servicing.repay.fee_status.waived': 'waived',
    'business.servicing.repay.step_rate': '+{percent}%',
    'business.servicing.repay.ladder_unavailable':
        "The late-fee policy isn't available right now, so no late fee is shown.",
    'business.servicing.repay.ladder_version': 'Late-fee policy {version}.',
    'business.audit_prep.title': 'Get ready for your audit',
    'business.monthly_figures.title': 'Monthly figures',
    'business.monthly_figures.intro':
        'Send what came in and went out in {month}. Your Audit Partner checks these figures at the visit, before {date}.',
    'business.monthly_figures.step': 'Step {current} of {total}',
    'business.monthly_figures.money_in.title': 'Money in for {month}',
    'business.monthly_figures.money_in.body':
        'All the money the business received in the month, in every account and in cash, in Rwandan francs.',
    'business.monthly_figures.money_in.label': 'Money in',
    'business.monthly_figures.costs.title': 'Costs for {month}',
    'business.monthly_figures.costs.body':
        'What the business spent in the month, in each of the six categories. Enter 0 where there was none.',
    'business.monthly_figures.costs.stock': 'Stock and materials',
    'business.monthly_figures.costs.staff': 'Staff pay',
    'business.monthly_figures.costs.premises': 'Rent and utilities',
    'business.monthly_figures.costs.transport': 'Transport',
    'business.monthly_figures.costs.taxes': 'Taxes and fees',
    'business.monthly_figures.costs.other': 'Other costs',
    'business.monthly_figures.costs.total': 'Total costs',
    'business.monthly_figures.costs.total_line': 'Total costs: {amount}',
    'business.monthly_figures.paid.title': 'Paid to owners in {month}',
    'business.monthly_figures.paid.body':
        'Money the owners took out of the business for themselves. Enter 0 if there was none.',
    'business.monthly_figures.paid.label': 'Paid to owners',
    'business.monthly_figures.forecast.title': 'Forecast for {forecast}',
    'business.monthly_figures.forecast.basis':
        'Pre-filled with your verified average for the last three months: {amount}.',
    'business.monthly_figures.forecast.no_basis':
        'Enter the money you expect to come in. There is no verified average yet.',
    'business.monthly_figures.forecast.label': 'Money in expected in {month}',
    'business.monthly_figures.forecast.order': 'Signed order',
    'business.monthly_figures.forecast.order_hint':
        'This forecast is more than 25% away from your verified average. Name the signed order behind it, for example "Order 114 from Simba Supermarket". Your Audit Partner sees it at the visit.',
    'business.monthly_figures.changes.title': 'Changes in {month}',
    'business.monthly_figures.changes.owners': 'Owners or directors changed',
    'business.monthly_figures.changes.premises':
        'Moved premises, or opened or closed a location',
    'business.monthly_figures.changes.borrowing': 'Took a new loan or credit',
    'business.monthly_figures.changes.customers':
        'Lost a main customer or supplier',
    'business.monthly_figures.changes.yes': 'Yes',
    'business.monthly_figures.changes.no': 'No',
    'business.monthly_figures.summary.title': 'Check and send',
    'business.monthly_figures.summary.line':
        '{month}: money in {money_in}, costs {costs}, paid to owners {paid}. Forecast for {forecast_month}: {forecast}.',
    'business.monthly_figures.summary.final':
        'Once sent, these figures are final for the month.',
    'business.monthly_figures.sent.title': 'Figures for {month}',
    'business.monthly_figures.sent.body':
        'Sent {date}. Your Audit Partner checks them at the visit.',
    'business.monthly_figures.back': 'Back',
    'business.monthly_figures.next': 'Next',
    'business.monthly_figures.send': 'Send figures',
    'business.monthly_figures.sending': 'Sending…',
    'business.audit_prep.figures.title': 'Send your monthly figures',
    'business.audit_prep.figures.body':
        'Six short screens, about 3 minutes. Your Audit Partner checks them at the visit.',
    'business.audit_prep.window_open': 'Audit window open',
    'business.audit_prep.next': 'Next audit',
    'business.audit_prep.first': 'Your first audit',
    'business.audit_prep.month': '{month} audit',
    'business.audit_prep.day_left': 'Day left',
    'business.audit_prep.days_left': 'Days left',
    'business.audit_prep.intro':
        'Notification received: Please prepare all bank statements, Mobile Money logs, and physical receipt books for your upcoming CPA visit.',
    'business.audit_prep.reassigned':
        'Your file moved from {from} to {to}, and your history moved with it.',
    'business.audit_prep.ready': 'Have this ready',
    'business.audit_prep.item.statements.title':
        'Bank statements & Mobile Money logs',
    'business.audit_prep.item.statements.body':
        'The full month, to the last day, ready for your CPA to review on site.',
    'business.audit_prep.item.stock.title': 'Stock counted and ledgers current',
    'business.audit_prep.item.stock.body':
        'Your Audit Partner does a physical count. A stale ledger reads as a variance and cuts your borrowing capacity.',
    'business.audit_prep.item.access.title':
        'Access to every bay, store and till',
    'business.audit_prep.item.access.body':
        'Anything locked or unreachable on the day is counted as missing.',
    'business.audit_prep.item.papers.title':
        'Till receipts and transactions reconciled',
    'business.audit_prep.item.papers.body':
        "Ensure all physical till receipts and digital transactions are reconciled for the CPA's on-site review.",
    'business.audit_prep.item.person.title': 'Someone with authority on site',
    'business.audit_prep.item.person.body':
        'They need a person who can open doors and answer for the numbers — not just staff on shift.',
    'business.audit_prep.how': 'How the audit runs',
    'business.audit_prep.flow.closes.title': 'Month closes',
    'business.audit_prep.flow.closes.body':
        'Your CPA opens the audit file for the period. Nothing is required from you to start it.',
    'business.audit_prep.flow.visit.title': 'On-site visit',
    'business.audit_prep.flow.visit.body':
        'Your assigned CPA will visit your premises to review records, reconcile cash flows, and generate the monthly audit report.',
    'business.audit_prep.flow.sealed.title': 'Sealed',
    'business.audit_prep.flow.sealed.body':
        'The factual findings and variances are sealed under their ICPAR licence.',
    'business.audit_prep.flow.cosign.title': 'You review',
    'business.audit_prep.flow.cosign.body':
        'You read the findings and, if something is wrong, dispute with counter-proof by {date}.',
    'business.audit_prep.closing':
        'You never file the monthly report yourself. Your CPA visits, reviews your records on site and seals the report — your part is being ready, then reviewing what they found and disputing it if something is wrong.',
    'investor.nav.deals': 'Deals',
    'investor.nav.portfolio': 'Portfolio',
    'investor.nav.profile': 'Profile',
    'investor.market.order_detail.title': 'Order detail',
    'investor.market.order_detail.empty_title': 'No order selected',
    'investor.market.order_detail.empty_body':
        "Pick an order on the left to see why it has or hasn't filled.",
    'investor.nav.market': 'Market',
    'investor.nav.cart': 'Cart',
    'investor.market.title': 'Market',
    'investor.market.subtitle': 'Trade active Rozine Notes before maturity.',
    'investor.market.view.browse': 'Browse',
    'investor.market.view.orders': 'Orders',
    'investor.market.view.saved': 'Saved',
    'investor.market.filter.performance': 'Performance',
    'investor.market.filter.status': 'Status',
    'investor.market.filter.industry': 'Industry',
    'investor.market.performance.all': 'All',
    'investor.market.performance.most_active': 'Most Active',
    'investor.market.performance.highest_yield': 'Highest Yield',
    'investor.market.performance.lowest_risk': 'Lowest Risk',
    'investor.market.performance.most_liquid': 'Most Liquid',
    'investor.market.status.all': 'All',
    'investor.market.status.active': 'Active',
    'investor.market.status.sold': 'Sold',
    'investor.market.status.complete': 'Complete',
    'investor.market.industry.all': 'All',
    'investor.market.industry.agriculture': 'Agriculture',
    'investor.market.industry.manufacturing': 'Manufacturing',
    'investor.market.industry.energy': 'Energy',
    'investor.market.industry.technology': 'Technology',
    'investor.market.industry.logistics': 'Logistics',
    'investor.market.industry.finance': 'Finance',
    'investor.market.orders.label': 'Orders',
    'investor.market.orders.buy': 'Buy Orders',
    'investor.market.orders.sell': 'Sell Orders',
    'investor.market.orders.completed': 'Completed',
    'investor.market.orders.cancelled': 'Cancelled',
    'investor.market.orders.empty': 'No orders in this tab.',
    'investor.market.browse_none.empty_title': 'No notes are for sale yet',
    'investor.market.browse_none.empty_body':
        'Notes appear here when investors list them for resale.',
    'investor.market.browse.empty_title': 'No notes match these filters',
    'investor.market.browse.empty_body': 'Try adjusting the filters above.',
    'investor.market.saved.empty_title': 'No saved notes yet',
    'investor.market.saved.empty_body':
        'Swipe a note up to save it here for later.',
    'investor.cart.title': 'Cart',
    'investor.cart.empty_title': 'Your cart is empty',
    'investor.cart.empty_body':
        'Add notes from Deals, set how much you want in each, and check them all out at once.',
    'investor.cart.browse_deals': 'Browse deals',
    'investor.cart.add': 'Add to cart',
    'investor.cart.in_cart_remove': 'In cart · Remove',
    'investor.cart.update_failed':
        "We couldn't update your cart. Refresh and try again.",
    'investor.cart.count': { one: '{count} deal', other: '{count} deals' },
    'investor.cart.total': 'CART TOTAL',
    'investor.cart.remove': 'Remove {name} from cart',
    'investor.cart.filled_remove': 'Fully funded · Remove',
    'investor.cart.filled_note': {
        one: "{count} deal is fully funded and won't be checked out.",
        other: "{count} deals are fully funded and won't be checked out.",
    },
    'investor.cart.target_of': 'of {amount}',
    'investor.cart.units': { one: 'note', other: 'notes' },
    'investor.cart.quantity': 'Number of notes in {name}',
    'investor.cart.checkout': 'Check out',
    'investor.cart.checkout_note':
        'Check out the whole cart at once below, or open one deal to buy it on its own.',
    'investor.cart.before_charge':
        'Get back is before the charge on earnings. Each checkout shows the exact amount.',
    'investor.cart.whole.band_title': 'FEE ON EARNINGS',
    'investor.cart.whole.band_next': '{amount} more unlocks {rate}',
    'investor.cart.whole.band_top': "You're on the lowest fee band.",
    'investor.cart.whole.band_basis':
        'Your band counts the notes you hold plus this cart, never wallet cash.',
    'investor.cart.whole.quoting': 'Pricing your cart…',
    'investor.cart.whole.holding': 'Holding your notes…',
    'investor.cart.whole.confirming': 'Confirming…',
    'investor.cart.whole.releasing': 'Releasing…',
    'investor.cart.whole.settling_wait':
        'This usually takes a few minutes. This page updates by itself, then you can check out again.',
    'investor.cart.whole.checkout': 'Check out all · {amount}',
    'investor.cart.whole.fine_print':
        'Holds every note in your cart for 5 minutes while you confirm. Nothing is committed until you confirm.',
    'investor.cart.whole.remove_filled':
        'Remove the fully funded deals to check out the whole cart.',
    'investor.cart.whole.fix_units':
        'Some deals are above what you can take now. Fit them, or remove them, to check out the whole cart.',
    'investor.cart.whole.over_cap': 'This line is above what you can take now.',
    'investor.cart.whole.fit': {
        one: 'Fit to {count} note',
        other: 'Fit to {count} notes',
    },
    'investor.cart.whole.risks_first':
        'Confirm the risk statements first, then check out the whole cart.',
    'investor.cart.whole.open_checkout': 'Open checkout',
    'investor.cart.whole.held_title': 'Confirm your cart',
    'investor.cart.whole.held_lead':
        'Every note below is held for you at one fee band. Confirm all buys them together, or none.',
    'investor.cart.whole.line': {
        one: '{count} note · {rate}% return',
        other: '{count} notes · {rate}% return',
    },
    'investor.cart.whole.line_return': 'Return {return} · fee {fee}',
    'investor.cart.whole.net_return': 'Expected return after fee',
    'investor.cart.whole.acknowledge':
        'Paying confirms you have read the prospectus for each note.',
    'investor.cart.whole.confirm': 'Pay {amount}',
    'investor.cart.whole.release': 'Release all notes',
    'investor.cart.whole.requoted':
        'The terms changed before you confirmed. Check the new total and band, then confirm again.',
    'investor.cart.whole.settling':
        'Your hold has ended. These notes are being released back to your wallet.',
    'investor.cart.whole.done_title': 'Cart checked out',
    'investor.cart.whole.done_body': {
        one: 'You committed {amount} to {count} deal. Your notes are issued after the business is paid.',
        other: 'You committed {amount} across {count} deals. Your notes are issued after each business is paid.',
    },
    'investor.cart.whole.done_fee': 'Fee on earnings {rate}',
    'investor.cart.whole.go_portfolio': 'Go to Portfolio',
    'investor.cart.whole.go_deals': 'Go to Deals',
    'investor.common.back': 'Back',
    'investor.money.rwf': 'RWF',
    'investor.rating.strong': 'Strong',
    'investor.rating.stable': 'Stable',
    'investor.rating.weak': 'Weak',
    'investor.rating.distressed': 'Distressed',
    'investor.deals.head_title': 'Deals',
    'investor.deals.wallet_balance': 'Wallet balance',
    'investor.deals.deposit': 'Deposit',
    'investor.deals.withdraw': 'Withdraw',
    'investor.deals.notifications': 'Notifications',
    'investor.deals.notifications_unread': 'Notifications, {count} unread',
    'investor.deals.sort_label': 'Sort deals',
    'investor.deals.sort.all': 'All',
    'investor.deals.sort.top_interest': 'Top Interest',
    'investor.deals.sort.top_rated': 'Top Rated',
    'investor.deals.sort.top_picks': 'Top Picks',
    'investor.deals.sort.in_cart': 'In cart',
    'investor.deals.industry_label': 'Filter by industry',
    'investor.deals.industry_all': 'All',
    'investor.deals.audited': 'AUDITED',
    'investor.deals.just_listed': 'JUST LISTED',
    'investor.deals.photo_previous': 'Previous photo of {name}',
    'investor.deals.photo_next': 'Next photo of {name}',
    'investor.deals.open_deal': 'Open {name}',
    'investor.deals.raised': 'RAISED',
    'investor.deals.avg_revenue_short': 'AVG MO. REV.',
    'investor.deals.avg_revenue': 'AVG MONTHLY REV.',
    'investor.deals.funded_label': '{name} funding progress',
    'investor.deals.investors_one': 'investor',
    'investor.deals.investors': 'investors',
    'investor.deals.left_to_fill': 'LEFT TO FILL',
    'investor.deals.notes': 'NOTES',
    'investor.deals.taken': 'taken',
    'investor.deals.fully_funded': 'Fully funded',
    'investor.deals.days_left': '{count} days',
    'investor.deals.closing': 'Closing',
    'investor.deals.deal_previous': 'Previous deal',
    'investor.deals.deal_next': 'Next deal',
    'investor.deals.empty_title': 'No deals open right now',
    'investor.deals.empty_body':
        'New raises appear here once they pass audit and are listed.',
    'investor.deals.all_closed':
        'Every open raise is fully reserved or closed right now. New raises appear here as they go live.',
    'investor.deals.invest_bar': 'Invest in {name}',
    'investor.deals.notes_quantity': 'Number of notes',
    'investor.deals.notes_suffix': 'NOTES',
    'investor.deals.of_left': 'of {count} left',
    'investor.deals.term': 'TERM',
    'investor.deals.months_short': '{count} mo',
    'investor.deals.bar_invest': 'INVEST',
    'investor.deals.bar_interest': 'INTEREST',
    'investor.deals.bar_get_back': 'GET BACK',
    'investor.deals.invest': 'Invest',
    'investor.deals.verify_to_invest': 'Verify to invest',
    'investor.deals.gate.verification_required':
        'Verify your identity to invest. Browsing is open to everyone.',
    'investor.deals.gate.verification_pending':
        "We're verifying your identity. You can invest once it's confirmed.",
    'investor.deals.gate.restricted':
        'Investing is paused on this account. Contact support for help.',
    'investor.deal.status.open': 'LIVE',
    'investor.deal.status.sold_out': 'SOLD OUT',
    'investor.deal.status.frozen': 'PAUSED',
    'investor.deal.status.withdrawn': 'WITHDRAWN',
    'investor.deal.notice.sold_out.title': 'This raise is fully funded',
    'investor.deal.notice.sold_out.body':
        'Every note has been taken, so there is nothing left to buy here.',
    'investor.deal.notice.frozen.title': 'This raise is paused',
    'investor.deal.notice.frozen.body':
        'Rozine has paused new investment while a review is completed. Nothing you hold is affected.',
    'investor.deal.notice.withdrawn.title': 'This raise was withdrawn',
    'investor.deal.notice.withdrawn.body':
        'The business withdrew it before funding. Any committed money is returned in full, without a fee.',
    'investor.deal.funding_progress': 'Funding progress',
    'investor.deal.loading': 'Loading deal details',
    'investor.deal.raised': 'RAISED',
    'investor.deal.target': 'TARGET',
    'investor.deal.time_left': 'TIME LEFT',
    'investor.deal.notes_sold_of': '{sold} of {total} notes sold',
    'investor.deal.notes_of': '{sold} of {total} notes',
    'investor.deal.details': 'DETAILS',
    'investor.deal.photos': 'Photos',
    'investor.deal.prospectus.title': 'Prospectus · version {version}',
    'investor.deal.prospectus.signed': 'Signed',
    'investor.deal.prospectus.signed_by': 'Signed by {name} on {date}',
    'investor.deal.prospectus.fingerprint': 'Fingerprint {fingerprint}…',
    'investor.deal.prospectus.read': 'Read',
    'investor.prospectus.title': '{name} prospectus',
    'investor.prospectus.back': 'Back to the deal',
    'investor.prospectus.signed':
        'Version {version}, signed by {name} on {date}',
    'investor.prospectus.fingerprint': 'Fingerprint {fingerprint}',
    'investor.prospectus.purpose': 'Purpose',
    'investor.prospectus.terms': 'Terms and fees',
    'investor.prospectus.principal': 'Amount raised',
    'investor.prospectus.fee': 'Rozine platform fee (3%)',
    'investor.prospectus.net': 'Left for the business to spend',
    'investor.prospectus.term': 'Term',
    'investor.prospectus.instalment': 'Monthly instalment',
    'investor.prospectus.funds': 'Use of funds',
    'investor.prospectus.quoted': 'Quote on file',
    'investor.prospectus.financials': 'CPA-verified financials and forecast',
    'investor.prospectus.verified_revenue': 'Verified average monthly revenue',
    'investor.prospectus.sealed': 'Latest sealed CPA report',
    'investor.prospectus.forecast': 'The business’s forecast',
    'investor.prospectus.month': 'Month',
    'investor.prospectus.revenue': 'Revenue',
    'investor.prospectus.costs': 'Costs',
    'investor.prospectus.contracted': 'Signed contract on file',
    'investor.prospectus.assumptions': 'Assumptions',
    'investor.prospectus.downside': 'If sales fall short',
    'investor.prospectus.management': 'Management',
    'investor.prospectus.role.representative': 'Official representative',
    'investor.prospectus.role.director': 'Director',
    'investor.prospectus.role.owner': 'Owner',
    'investor.prospectus.risks': 'Risks',
    'investor.prospectus.largest_customer':
        'The largest customer brings {percent}% of revenue.',
    'investor.prospectus.concentration': 'Customer concentration',
    'investor.prospectus.months': {
        one: '{count} month',
        other: '{count} months',
    },
    'investor.deal.reported_by_business': 'Reported by business',
    'investor.deal.use_of_funds': 'Use of funds',
    'investor.deal.use.inventory': 'Inventory',
    'investor.deal.use.equipment': 'Equipment',
    'investor.deal.use.expansion': 'Expansion',
    'investor.deal.use.hiring': 'Hiring',
    'investor.deal.use.working_capital': 'Working capital',
    'investor.deal.use.other': 'Other',
    'investor.deal.financials': 'Financials',
    'investor.deal.verified_audited': 'Verified · audited',
    'investor.deal.avg_monthly_revenue': 'AVG MONTHLY REV.',
    'investor.deal.ebitda': 'EBITDA',
    'investor.deal.existing_debt': 'EXISTING DEBT',
    'investor.deal.capacity': 'APPR. CAPACITY',
    'investor.deal.rozine_tag': 'Rozine',
    'investor.deal.assessment': 'Rozine assessment',
    'investor.deal.rozine_analysis': 'Rozine analysis',
    'investor.deal.assessed_by_rozine': 'Assessed by Rozine',
    'investor.deal.rozine_rating': 'Rozine rating',
    'investor.deal.out_of_five': '{score} / 5',
    'investor.deal.total_return': 'Total return',
    'investor.deal.repayment': 'Repayment',
    'investor.deal.monthly': 'Monthly',
    'investor.deal.term': 'Term',
    'investor.deal.months_long': '{count} months',
    'investor.deal.track_record': 'Track record on Rozine',
    'investor.deal.history': 'HISTORY',
    'investor.deal.raises': 'RAISES',
    'investor.deal.raises_done': 'RAISES DONE',
    'investor.deal.on_time': 'ON-TIME',
    'investor.deal.repaid': 'REPAID',
    'investor.deal.verified': 'Verified',
    'investor.deal.about': 'About the business',
    'investor.deal.registration': 'RDB company code',
    'investor.deal.registered': 'Registered',
    'investor.deal.registered_value': '{year} · {years} yrs',
    'investor.deal.team_size': 'Team size',
    'investor.deal.industry': 'Industry',
    'investor.deal.address': 'Address',
    'investor.deal.monthly_updates': 'Monthly updates',
    'investor.deal.audited': 'Audited',
    'investor.deal.rating': 'RATING',
    'investor.deal.interest': 'INTEREST',
    'investor.deal.return': 'RETURN',
    'investor.deal.verified_pill': '✓ Verified',
    'investor.deal.overdue_title': '{month} report overdue · compliance watch',
    'investor.deal.overdue_body':
        "The verified audit report wasn't audited by the 7th. Rozine's compliance team is following up with the business's Audit Partner.",
    'investor.deal.your_investment': 'YOUR INVESTMENT',
    'investor.deal.repaid_monthly': 'Repaid monthly · {count} mo',
    'investor.deal.unit_times_one': '{price} × {count} note',
    'investor.deal.unit_times_other': '{price} × {count} notes',
    'investor.deal.expected_return': 'Expected return',
    'investor.deal.fee_at_checkout': 'Shown at checkout',
    'investor.deal.get_back_before_fee': 'Get back, before the fee',
    'investor.deal.total_at_maturity': 'Total repaid at maturity',
    'investor.deal.fewer_notes': 'One note fewer',
    'investor.deal.more_notes': 'One note more',
    'investor.checkout.title': 'Checkout',
    'investor.checkout.done': 'Done',
    'investor.checkout.deal_line': '{rate}% return · {count} months',
    'investor.checkout.closes_in': 'CLOSES IN',
    'investor.checkout.amount': 'INVESTMENT AMOUNT',
    'investor.checkout.units_each_one': '{count} note · {price} each',
    'investor.checkout.units_each_other': '{count} notes · {price} each',
    'investor.checkout.expected_return': 'Expected return ({rate}%)',
    'investor.plus.fee': 'Fee on earnings',
    'investor.plus.fee_label': 'Fee on earnings · {rate}',
    'investor.plus.fee_rate': '{rate}% ({tier})',
    'investor.plus.fee_note':
        'Charged on your return only, never on your principal. This rate is fixed for this note.',
    'investor.plus.tier.standard': 'Standard',
    'investor.plus.tier.bronze': 'Bronze',
    'investor.plus.tier.silver': 'Silver',
    'investor.plus.tier.gold': 'Gold',
    'investor.plus.tier.platinum': 'Platinum',
    'investor.plus.tier.diamond': 'Diamond',
    'investor.checkout.maturity_value': 'Maturity value · {date}',
    'investor.checkout.wallet': 'Wallet',
    'investor.checkout.wallet_available': 'Available: {amount}',
    'investor.checkout.pay_with': 'Pay with',
    'investor.checkout.wallet_line': '{wallet} · {amount} available',
    'investor.checkout.insufficient':
        'Amount exceeds your wallet balance. Deposit to your wallet, or lower the amount.',
    'investor.checkout.deposit': 'Deposit',
    'investor.checkout.disclosure': 'COST AND RISK',
    'investor.checkout.acknowledge':
        'Paying confirms you have read the prospectus for this note ({version}).',
    'investor.checkout.refusal.TRADE_BELOW_MINIMUM':
        'Invest at least one note.',
    'investor.checkout.refusal.INSUFFICIENT_AVAILABLE_FUNDS':
        'Amount exceeds your wallet balance. Deposit to your wallet, or lower the amount.',
    'investor.checkout.refusal.EXPOSURE_LIMIT':
        'That takes you past your investment limit. Lower the amount.',
    'investor.checkout.refusal.NOTE_INELIGIBLE':
        'This raise can no longer be bought.',
    'investor.checkout.refusal.RESERVATION_EXPIRED':
        'Your reserved notes were released. Check the amount and confirm again.',
    'investor.checkout.refusal.RESTRICTION_ACTIVE':
        'Investing is paused on this account. Contact support for help.',
    'investor.checkout.refusal.VERSION_CONFLICT':
        'The terms changed while you were reading. Review them and confirm again.',
    'investor.checkout.processing': 'Confirming…',
    'investor.checkout.confirm': 'Pay {amount}',
    'investor.checkout.fine_print':
        'Funds committed instantly · Returns are projected, not guaranteed',
    'investor.checkout.confirmed': 'Investment confirmed',
    'investor.checkout.confirmed_body_before': 'You invested',
    'investor.checkout.confirmed_body_after':
        "in {name}. It's now in your portfolio.",
    'investor.checkout.expected_return_plain': 'Expected return',
    'investor.checkout.maturity_value_plain': 'Maturity value',
    'investor.checkout.maturity_date': 'Maturity date',
    'investor.checkout.transaction_id': 'Transaction ID',
    'investor.checkout.reference': 'Reference',
    'investor.checkout.view_receipt': 'View receipt',
    'investor.checkout.view_portfolio': 'View in portfolio',
    'investor.checkout.explore': 'Explore more deals',
    'investor.updates.status.healthy': 'Healthy',
    'investor.updates.status.watch': 'On watch',
    'investor.updates.net': 'net',
    'investor.updates.none': 'No monthly reports published yet.',
    'investor.updates.parsed_audited': 'Statement parsed · audited',
    'investor.updates.show_more': 'Show more',
    'investor.updates.show_less': 'Show less',
    'investor.updates.sheet_label': '{month} monthly report',
    'investor.updates.verified_report': 'Verified monthly report',
    'investor.updates.audited_by': 'Audited on site by {name}',
    'investor.updates.licence_verified': '{licence} · verified {date}',
    'investor.updates.financials': 'FINANCIALS THIS MONTH',
    'investor.updates.inflow': 'INFLOW',
    'investor.updates.outflow': 'OUTFLOW',
    'investor.updates.verified': 'VERIFIED',
    'investor.updates.from_statements': 'from bank & MoMo statements',
    'investor.updates.net_month': 'Net for the month',
    'investor.updates.from_business': 'FROM THE BUSINESS',
    'investor.updates.auditor_note': "AUDITOR'S NOTE",
    'investor.updates.proof_photos': 'PROOF PHOTOS',
    'investor.updates.open_photo': 'Open photo: {caption}',
    'investor.updates.required_shot': 'Required shot',
    'investor.updates.added_by_auditor': 'Added by auditor',
    'investor.updates.gps': 'GPS COORDINATES',
    'investor.updates.captured': 'CAPTURED',
    'investor.updates.camera_only':
        'Captured live in the Rozine auditor camera · gallery upload disabled',
    'investor.updates.photo_previous': 'Previous photo',
    'investor.updates.photo_next': 'Next photo',
    'investor.audit.kicker': 'Independently audited · {standard}',
    'investor.audit.kicker_plain': 'Independently audited',
    'investor.audit.verified_line': '{licence} · verified {date}',
    'investor.audit.view': 'View',
    'investor.audit.hide': 'Hide',
    'investor.audit.standard': 'Standard',
    'investor.audit.partner': 'Audit Partner',
    'investor.audit.licence': 'ICPAR registration',
    'investor.audit.cash': 'Cash / MoMo observed',
    'investor.audit.inventory': 'Inventory sample count',
    'investor.audit.units': '{count} units',
    'investor.audit.variance': 'Variance vs telemetry',
    'investor.audit.digest': 'Report digest',
    'investor.audit.photos': 'GEO-TAGGED CPA INSPECTION PHOTOS',
    'investor.audit.download': 'Download signed audit report (PDF)',
    'investor.audit.disclaimer':
        'Findings are factual field observations under agreed-upon procedures — not an audit opinion. The digest binds the CPA licence, GPS and timestamp to this report.',
    'investor.auth.tagline': 'Growth capital for profitable businesses',
    'investor.auth.have_account': 'Already have an account?',
    'investor.auth.log_in': 'Log in',
    'investor.auth.new_to_rozine': 'New to Rozine?',
    'investor.auth.create_account': 'Create account',
    'investor.auth.new_here': 'New here?',
    'investor.auth.create_an_account': 'Create an account',
    'investor.auth.create_title': 'Create your account',
    'investor.auth.continue': 'Continue',
    'investor.auth.please_wait': 'Please wait…',
    'investor.auth.email': 'EMAIL',
    'investor.auth.email_placeholder': 'you@example.rw',
    'investor.auth.first_name': 'FIRST NAME',
    'investor.auth.last_name': 'LAST NAME',
    'investor.auth.first_placeholder': 'Robert',
    'investor.auth.last_placeholder': 'Mugisha',
    'investor.auth.entity_name': 'ENTITY NAME',
    'investor.auth.entity_placeholder': 'Horizon Capital Partners',
    'investor.auth.representative': 'REPRESENTATIVE',
    'investor.auth.representative_placeholder': 'Aline Uwase',
    'investor.auth.password_placeholder': '6+ characters',
    'investor.auth.brand_headline':
        'Earn up to {max}% backing profitable Rwandan businesses.',
    'investor.auth.brand_point_1':
        'Every business audited by an ICPAR-certified CPA',
    'investor.auth.brand_point_2':
        'A flat {min}–{max}% per term, repaid monthly',
    'investor.auth.brand_point_3':
        'Sell your units to other investors on the market',
    'investor.auth.type.individual': 'Individual',
    'investor.auth.type.institution': 'Institution',
    'investor.auth.role.individual': 'I am an individual',
    'investor.auth.role.institution': 'I am an institution',
    'investor.auth.role.individual_body':
        'Investing in your own name, from {price} a note.',
    'investor.auth.role.institution_body':
        'A fund, SACCO, insurer or company treasury investing its own capital.',
    'investor.auth.register_head_title': 'Get started',
    'investor.auth.login.head_title': 'Log in',
    'investor.auth.login.title': 'Welcome back',
    'investor.auth.login.subtitle': 'Log in to manage your portfolio.',
    'investor.auth.login.method': 'How to log in',
    'investor.auth.login.tab_password': 'Password',
    'investor.auth.login.tab_pin': 'PIN',
    'investor.auth.login.id': 'EMAIL OR PHONE',
    'investor.auth.login.password': 'PASSWORD',
    'investor.auth.login.pin': '4-DIGIT PIN',
    'investor.auth.login.use_pin': 'Use PIN instead',
    'investor.auth.login.use_password': 'Use password instead',
    'investor.auth.login.use_pin_short': 'Use a PIN',
    'investor.auth.login.use_password_short': 'Use a password',
    'investor.auth.login.pin_note': 'No OTP required with PIN login.',
    'investor.intro.head_title': 'Welcome',
    'investor.intro.skip': 'Skip',
    'investor.intro.back': 'Back',
    'investor.intro.next': 'Next',
    'investor.intro.start': 'Get started',
    'investor.intro.slide': 'slide',
    'investor.intro.slide_of': '{index} of {count}',
    'investor.intro.audit.title':
        'Every business is audited before you see it.',
    'investor.intro.audit.body':
        'Rozine only lists businesses that pass an independent audit — not a credit score, an actual inspection.',
    'investor.intro.audit.point_1':
        'An ICPAR-certified CPA verifies the books and visits the premises.',
    'investor.intro.audit.point_2':
        'Five years of profitable trading, with a 10% margin held throughout.',
    'investor.intro.audit.point_3':
        'Monthly reports keep coming while your money is out.',
    'investor.intro.rate.title': 'A flat {min}–{max}%, paid back monthly.',
    'investor.intro.rate.body':
        'You know the full return before you commit. No compounding, no moving rate, no fees taken off the top.',
    'investor.intro.rate.point_1':
        'Terms run {term_min} to {term_max} months, repaid every month.',
    'investor.intro.rate.point_2':
        'The rate is set by the business’s rating and term, and never changes.',
    'investor.intro.rate.point_3':
        'From {price} a note, across as many businesses as you like.',
    'investor.intro.exit.title': 'Need your money early? Sell it back.',
    'investor.intro.exit.body':
        'Sell your notes to other investors on the open market — matched in seconds at a fair price, or list them and name your own.',
    'investor.intro.exit.point_1':
        'Matched against standing bids and paid to your wallet.',
    'investor.intro.exit.point_2':
        'Set a floor so it never sells below your price.',
    'investor.intro.exit.point_3':
        'Sell part of a holding and keep the rest earning.',
    'investor.signup.progress': 'Sign-up progress',
    'investor.signup.step_label': 'Step {step} of {count} · {type}',
    'investor.signup.create': 'Create account',
    'investor.signup.identity.head_title': 'Personal information',
    'investor.signup.identity.title': 'Personal information',
    'investor.signup.identity.title_institution': 'Institution details',
    'investor.signup.identity.account_type': 'ACCOUNT TYPE',
    'investor.signup.identity.type_individual': 'Individual',
    'investor.signup.identity.type_institution': 'Institution · Plus',
    'investor.signup.identity.institution_name': 'INSTITUTION NAME',
    'investor.signup.identity.contact_person': 'CONTACT PERSON',
    'investor.signup.identity.address': 'ADDRESS / LOCATION',
    'investor.signup.identity.address_placeholder':
        'KN 4 Ave, Nyarugenge, Kigali',
    'investor.signup.identity.company_code': 'RDB COMPANY CODE',
    'investor.signup.identity.company_code_hint':
        'The 9-digit code on your RDB certificate',
    'investor.signup.identity.id_type': 'ID TYPE',
    'investor.signup.identity.id_number': 'ID / PASSPORT / LICENSE NUMBER',
    'investor.signup.identity.id_hint': 'As printed on your selected ID',
    'investor.signup.id.national_id': 'National ID',
    'investor.signup.id.passport': 'Passport',
    'investor.signup.id.drivers_license': 'Driver’s License',
    'investor.signup.id_placeholder.national_id': '1 1990 8 0012345 6 78',
    'investor.signup.id_placeholder.passport': 'PC1234567',
    'investor.signup.id_placeholder.drivers_license': 'RA-0123456',
    'investor.signup.address.head_title': 'Address information',
    'investor.signup.address.title': 'Address information',
    'investor.signup.address.country': 'COUNTRY',
    'investor.signup.address.province': 'PROVINCE',
    'investor.signup.address.district': 'DISTRICT',
    'investor.signup.address.sector': 'SECTOR',
    'investor.signup.address.cell': 'CELL',
    'investor.signup.address.province_placeholder': 'Kigali City',
    'investor.signup.address.district_placeholder': 'Gasabo',
    'investor.signup.address.sector_placeholder': 'Kimironko',
    'investor.signup.address.cell_placeholder': 'Bibare',
    'investor.signup.contact.head_title': 'Contact information',
    'investor.signup.contact.title': 'Contact information',
    'investor.signup.contact.email': 'EMAIL ADDRESS',
    'investor.signup.contact.phone': 'PHONE NUMBER',
    'investor.signup.contact.country_code': 'Country code',
    'investor.signup.security.head_title': 'Secure your account',
    'investor.signup.security.title': 'Secure your account',
    'investor.signup.security.subtitle':
        'Create a 4-digit PIN, or a password of 6+ characters.',
    'investor.signup.security.pin': 'PIN',
    'investor.signup.security.password': 'Password',
    'investor.signup.security.password_placeholder': 'At least 6 characters',
    'investor.signup.payment.head_title': 'Payment information',
    'investor.signup.payment.title': 'Payment information',
    'investor.signup.payment.subtitle':
        'Link a method to fund your wallet. We verify with an OTP.',
    'investor.signup.payment.mtn': 'MTN Mobile Money',
    'investor.signup.payment.airtel': 'Airtel Money',
    'investor.signup.payment.bank': 'Bank account',
    'investor.signup.payment.otp': 'One-time code',
    'investor.signup.payment.otp_placeholder': 'Enter OTP',
    'investor.signup.payment.send_otp': 'Send OTP',
    'investor.signup.payment.otp_sent':
        '✓ OTP sent · enter the 6-digit code to verify',
    'investor.signup.agree.head_title': 'Review & agree',
    'investor.signup.agree.title': 'Review & agree',
    'investor.signup.agree.terms_before': 'Continuing means you agree to the',
    'investor.signup.agree.terms': 'Terms & Conditions',
    'investor.signup.agree.privacy_before': 'and the',
    'investor.signup.agree.privacy': 'Privacy Note',
    'investor.signup.agree.privacy_after': '.',
    'investor.kyc.progress': 'Verification progress',
    'investor.kyc.kicker_identity': 'IDENTITY VERIFICATION',
    'investor.kyc.kicker_entity': 'ENTITY VERIFICATION',
    'investor.kyc.personal.title': 'Personal details',
    'investor.kyc.personal.subtitle':
        'We verify every investor to keep the platform safe and compliant.',
    'investor.kyc.document.title': 'Verify your ID',
    'investor.kyc.document.subtitle':
        'Choose a document type and upload clear photos.',
    'investor.kyc.review.title': 'Under review',
    'investor.kyc.review.subtitle':
        "Your details are with our Compliance team. We'll let you know once your identity is confirmed.",
    'investor.kyc.liveness.title': 'Liveness check',
    'investor.kyc.liveness.subtitle':
        "A quick selfie confirms it's really you.",
    'investor.kyc.entity.title': 'Entity verification',
    'investor.kyc.entity.subtitle':
        'We verify the legal entity before any capital can be committed.',
    'investor.kyc.representative.title': 'Authorised representative',
    'investor.kyc.representative.subtitle':
        'The person who will place commitments on behalf of the entity.',
    'investor.kyc.declarations.title': 'Source of funds & declarations',
    'investor.kyc.declarations.subtitle':
        'Required before a mandate can be activated.',
    'investor.kyc.country': 'COUNTRY OF RESIDENCE',
    'investor.kyc.dob': 'DATE OF BIRTH',
    'investor.kyc.dob_placeholder': 'DD / MM / YYYY',
    'investor.kyc.dob_hint':
        'Pick the day you were born. You must be at least 18.',
    'investor.kyc.id_number': 'ID NUMBER',
    'investor.kyc.upload_front': 'Upload front',
    'investor.kyc.upload_back': 'Upload back',
    'investor.kyc.uploaded_front': 'Front uploaded',
    'investor.kyc.uploaded_back': 'Back uploaded',
    'investor.kyc.selfie': 'Tap to capture selfie',
    'investor.kyc.selfie_done': 'Selfie captured',
    'investor.kyc.upload_passport': 'Upload passport photo page',
    'investor.kyc.uploaded_passport': 'Passport photo page uploaded',
    'investor.kyc.replace': 'Tap to replace',
    'investor.kyc.view_upload': 'View {slot}',
    'investor.kyc.selfie_slot': 'your selfie',
    'investor.kyc.slot.front': 'the front',
    'investor.kyc.slot.back': 'the back',
    'investor.kyc.slot.passport': 'the photo page',
    'investor.kyc.dob_invalid': 'Enter a real date of birth.',
    'investor.kyc.dob_minor': 'You must be at least 18 to invest.',
    'investor.kyc.id_hint.national_id': 'The 16 digits on your national ID.',
    'investor.kyc.id_hint.passport':
        '6 to 12 letters or digits, as printed on the photo page.',
    'investor.kyc.id_invalid.national_id':
        'Enter the 16-digit number on the card.',
    'investor.kyc.id_invalid.passport':
        'Enter the passport number, 6 to 12 letters or digits.',
    'investor.kyc.entity_type': 'ENTITY TYPE',
    'investor.kyc.entity.fund': 'Investment fund',
    'investor.kyc.entity.sacco': 'SACCO',
    'investor.kyc.entity.treasury': 'Corporate treasury',
    'investor.kyc.entity.insurer': 'Insurer',
    'investor.kyc.entity.pension': 'Pension fund',
    'investor.kyc.entity.other': 'Other',
    'investor.kyc.company_code': 'RDB COMPANY CODE',
    'investor.kyc.incorporated': 'INCORPORATED',
    'investor.kyc.incorporated_placeholder': 'MM / YYYY',
    'investor.kyc.certificate': 'Upload certificate',
    'investor.kyc.certificate_done': 'Certificate uploaded',
    'investor.kyc.certificate_hint':
        'Certificate of incorporation · PDF or photo',
    'investor.kyc.rep_name': 'FULL NAME',
    'investor.kyc.rep_role': 'ROLE',
    'investor.kyc.rep_role_placeholder': 'Head of Treasury',
    'investor.kyc.rep_id': 'NATIONAL ID OR PASSPORT NUMBER',
    'investor.kyc.resolution': 'Upload board resolution',
    'investor.kyc.resolution_done': 'Board resolution uploaded',
    'investor.kyc.resolution_hint':
        'Board resolution authorising this person to commit capital',
    'investor.kyc.rep_liveness': 'Liveness check on the representative',
    'investor.kyc.source': 'PRIMARY SOURCE OF FUNDS',
    'investor.kyc.funds.operations': 'Own balance sheet',
    'investor.kyc.funds.member_savings': 'Member deposits',
    'investor.kyc.funds.investment_returns': 'Managed client capital',
    'investor.kyc.funds.premiums': 'Endowment',
    'investor.kyc.funds.contributions': 'Pension contributions',
    'investor.kyc.funds.other': 'Other',
    'investor.kyc.commitment': 'INTENDED ANNUAL COMMITMENT',
    'investor.kyc.commitment_hint':
        'Indicative only — you set the actual commitment inside the app.',
    'investor.kyc.aml':
        "I confirm the entity's beneficial owners have been disclosed and that the funds are not the proceeds of crime.",
    'investor.kyc.target':
        "I understand the Rozine Plus return is a target, not a guarantee, that notes are held in the entity's own name, and that Rozine does not hold capital on its balance sheet.",
    'investor.kyc.submit': 'Submit for verification',
    'investor.kyc.previous': 'Previous step',
    'investor.kyc.submitted_notice':
        'Your details are with our Compliance team. We will tell you when they have been reviewed.',
    'investor.kyc.rejected_notice':
        'Compliance could not verify these details: {reason}. Correct them and submit again.',
    'investor.kyc.verifying': 'Verifying your identity…',
    'investor.kyc.verifying_entity': 'Verifying the entity…',
    'investor.verified.title': "You're verified",
    'investor.verified.body':
        'Welcome to Rozine. Your wallet is ready and {count} verified opportunities are waiting for you.',
    'investor.verified.kyc': 'KYC STATUS',
    'investor.verified.verified': 'Verified',
    'investor.verified.wallet': 'WALLET',
    'investor.verified.cta': 'Start exploring',
    'investor.health.healthy': 'Healthy',
    'investor.health.watch': 'Watch',
    'investor.health.arrears': 'In arrears',
    'investor.health.frozen': 'Frozen',
    'investor.health.defaulted': 'Defaulted',
    'investor.health.matured': 'Matured',
    'investor.portfolio.title': 'Portfolio',
    'investor.portfolio.tabs': 'Holdings',
    'investor.portfolio.tab.active': 'Active',
    'investor.portfolio.tab.matured': 'Matured',
    'investor.portfolio.tab.secondary': 'Secondary',
    'investor.portfolio.tab.saved': 'Saved',
    'investor.portfolio.invested_line': 'Invested RWF {amount}',
    'investor.portfolio.matures_line':
        'Matures {date} · {made}/{total} payments',
    'investor.portfolio.details': 'Details',
    'investor.portfolio.see_all': 'See all {count} more ›',
    'investor.portfolio.empty.active.title': 'Nothing held yet',
    'investor.portfolio.empty.active.body':
        'Notes you buy appear here with their payouts and reports.',
    'investor.portfolio.empty.active.awaiting':
        'Your notes move into your holdings here once they are issued, after disbursement.',
    'investor.portfolio.empty.matured.title': 'Nothing here yet',
    'investor.portfolio.empty.matured.body':
        'Holdings in this category will appear here.',
    'investor.portfolio.empty.secondary.title': 'Nothing here yet',
    'investor.portfolio.empty.secondary.body':
        'Holdings in this category will appear here.',
    'investor.portfolio.empty.saved.title': 'No saved deals yet',
    'investor.portfolio.browse_deals': 'Browse opportunities',
    'investor.portfolio.total_value': 'TOTAL VALUE',
    'investor.portfolio.businesses_one': '1 business',
    'investor.portfolio.businesses': '{count} businesses',
    'investor.portfolio.invested': 'INVESTED',
    'investor.portfolio.total_gain': 'TOTAL GAIN',
    'investor.portfolio.this_month': 'THIS MONTH',
    'investor.portfolio.projected': 'PROJECTED · NEXT 3 MONTHS',
    'investor.portfolio.next_payout': 'Next: {amount} · {month}',
    'investor.portfolio.avg_month': 'AVG / MO',
    'investor.portfolio.upcoming': 'UPCOMING PAYOUTS · RWF',
    'investor.portfolio.pays_one': '{count} business pays this month',
    'investor.portfolio.pays_other': '{count} businesses pay this month',
    'investor.portfolio.upcoming_note':
        'Tap a month to see which businesses pay you. The total steps down as notes mature and drop out of the schedule.',
    'investor.portfolio.diversification': 'DIVERSIFICATION BY INDUSTRY',
    'investor.portfolio.risk_balance': 'RISK BALANCE',
    'investor.portfolio.concentrated': 'Concentrated position',
    'investor.portfolio.concentrated_body':
        '{name} is {pct}% of your invested capital. Spreading across more businesses lowers your exposure if one underperforms.',
    'investor.portfolio.browse': 'Browse to diversify',
    'investor.portfolio.balanced': 'Well diversified',
    'investor.portfolio.balanced_body':
        'Your capital is spread across several businesses with no single position dominating. Keep it up.',
    'investor.portfolio.balanced_count':
        'Your capital is spread across {count} businesses with no single position dominating. Keep it up.',
    'investor.portfolio.idle': 'IDLE IN WALLET',
    'investor.portfolio.idle_body': 'Put it to work in a new note.',
    'investor.portfolio.reinvest': 'Reinvest ›',
    'investor.holding.frozen_title': 'This note is frozen',
    'investor.holding.frozen_body':
        'Rozine has frozen this note while a review is completed. Payouts resume when it is released; your claim is unchanged.',
    'investor.holding.latest_report': 'Latest report',
    'investor.holding.latest_report_month': '{month} report',
    'investor.holding.on_watch': 'Watchlist · under review',
    'investor.holding.on_plan': 'On plan',
    'investor.holding.invested': 'INVESTED',
    'investor.holding.expected_profit': 'EXPECTED PROFIT',
    'investor.holding.yield': '{rate}% yield',
    'investor.holding.total_at_maturity': 'TOTAL AT MATURITY',
    'investor.holding.principal_yield': 'Principal + yield',
    'investor.holding.received': 'RECEIVED SO FAR',
    'investor.holding.payments': '{made}/{total} payments',
    'investor.holding.due.today': 'Today',
    'investor.holding.due.tomorrow': 'Tomorrow',
    'investor.holding.due.in_days': 'in {count} days',
    'investor.holding.next_payment': 'NEXT PAYMENT',
    'investor.holding.no_next.matured': 'Fully repaid',
    'investor.holding.no_next.paused': 'Paused',
    'investor.holding.on_time': 'ON-TIME RECORD',
    'investor.holding.all_on_time': 'All on time',
    'investor.holding.late_one': '{count} payment late',
    'investor.holding.late_other': '{count} payments late',
    'investor.holding.rating': 'RATING',
    'investor.holding.out_of_five': 'out of 5',
    'investor.holding.repaid': 'REPAID',
    'investor.holding.month_left_one': '{count} month left',
    'investor.holding.month_left_other': '{count} months left',
    'investor.holding.rating_changed': 'Rating has changed',
    'investor.holding.rating_changed_body':
        'Rated {from} when you invested · now {to}',
    'investor.holding.rating_changed_note':
        'Ratings update from each audited monthly report.',
    'investor.holding.plan_title': 'One payment deferred',
    'investor.holding.plan_state.on_track': 'On track',
    'investor.holding.plan_state.off_track': 'Off track',
    'investor.holding.plan_body':
        '{name} told us before the due date. Checked against their audited figures, approved, and their rating dropped a band.',
    'investor.holding.plan_reason': 'Reason given',
    'investor.holding.plan_arrives': 'Money arrives',
    'investor.holding.plan_deferred': 'Deferred',
    'investor.holding.delayed': 'Payment delayed',
    'investor.holding.defaulted': 'Payments defaulted',
    'investor.holding.days_overdue': '{count} days overdue',
    'investor.holding.in_recovery': 'IN RECOVERY',
    'investor.holding.arrears_body':
        'The most recent monthly payment from {name} is late. Its Rozine rating has been adjusted and our recovery team is engaged.',
    'investor.holding.step.missed.title': 'Payment missed',
    'investor.holding.step.missed.body':
        'Automated systems flagged a late repayment.',
    'investor.holding.step.contacted.title': 'Business contacted',
    'investor.holding.step.contacted.body':
        'Rozine reached out and confirmed the cause.',
    'investor.holding.step.plan.title': 'Recovery plan agreed',
    'investor.holding.step.plan.body':
        'A catch-up schedule is being finalised.',
    'investor.holding.step.resume.title': 'Repayments resume',
    'investor.holding.step.resume.body': 'Normal monthly payments continue.',
    'investor.holding.claim_note':
        'Your principal remains a claim on the business. Rozine pursues recovery on your behalf.',
    'investor.holding.photos': 'PHOTOS',
    'investor.holding.progress': 'REPAYMENT PROGRESS',
    'investor.holding.payments_made': '{made} of {total} payments made',
    'investor.holding.investors': '{count} investors',
    'investor.holding.updates': 'MONTHLY UPDATES',
    'investor.wallet.title': 'Wallet',
    'investor.wallet.available': 'AVAILABLE BALANCE',
    'investor.wallet.status.active': 'Active',
    'investor.wallet.status.restricted': 'Restricted',
    'investor.wallet.pending_withdrawal': '{amount} withdrawal pending',
    'investor.wallet.instant': 'Instantly available to invest',
    'investor.wallet.rozine_wallet': 'Rozine Wallet',
    'investor.wallet.deposit': 'Deposit',
    'investor.wallet.withdraw': 'Withdraw',
    'investor.wallet.panel.deposit': 'Add money',
    'investor.wallet.panel.withdraw': 'Withdraw',
    'investor.wallet.amount': 'AMOUNT',
    'investor.wallet.method': 'PAYMENT METHOD',
    'investor.wallet.method_to': 'PAYMENT METHOD',
    'investor.wallet.fee': 'Processing fee',
    'investor.wallet.result.deposit': 'New available balance',
    'investor.wallet.result.withdraw': 'You receive',
    'investor.wallet.confirm.deposit': 'Confirm deposit',
    'investor.wallet.confirm.withdraw': 'Confirm withdrawal',
    'investor.wallet.sending.deposit': 'Sending deposit…',
    'investor.wallet.sending.withdraw': 'Sending withdrawal…',
    'investor.wallet.done.deposit.pending':
        "Deposit of {amount} requested. We'll credit it once {method} confirms.",
    'investor.wallet.done.deposit.succeeded':
        '{amount} from {method} is now in your wallet.',
    'investor.wallet.done.deposit.failed':
        "The {amount} deposit from {method} didn't go through. Nothing was credited.",
    'investor.wallet.done.withdraw.pending':
        "Withdrawal of {amount} to {method} requested. It's paid out once {method} confirms.",
    'investor.wallet.done.withdraw.succeeded':
        '{amount} was paid out to {method}.',
    'investor.wallet.done.withdraw.failed':
        "The {amount} withdrawal to {method} didn't go through. The money is back in your wallet.",
    'investor.wallet.c3.credited': 'Credited',
    'investor.wallet.processing': 'Sending…',
    'investor.wallet.refusal.TRADE_BELOW_MINIMUM':
        'Enter an amount of at least the minimum',
    'investor.wallet.refusal.INSUFFICIENT_AVAILABLE_FUNDS':
        'Amount exceeds your available balance',
    'investor.wallet.refusal.DAILY_LIMIT': 'Exceeds the daily withdrawal cap',
    'investor.wallet.refusal.RESTRICTION_ACTIVE':
        'Withdrawals are paused on this account. Contact support for help.',
    'investor.wallet.no_methods': 'No payout account linked yet.',
    'investor.wallet.link_account': 'Link an account',
    'investor.wallet.earn.title': 'Earnings history',
    'investor.wallet.earn.subtitle': 'Interest earned and principal returned.',
    'investor.wallet.earn.from': 'From date',
    'investor.wallet.earn.to': 'to',
    'investor.wallet.earn.to_date': 'To date',
    'investor.wallet.earn.ranges': 'Earnings period',
    'investor.wallet.earn.range.3m': '3 months',
    'investor.wallet.earn.range.6m': '6 months',
    'investor.wallet.earn.range.12m': '12 months',
    'investor.wallet.earn.range.ytd': 'This year',
    'investor.wallet.earn.interest': 'INTEREST',
    'investor.wallet.earn.received': 'RECEIVED',
    'investor.wallet.earn.payouts': 'PAYOUTS',
    'investor.wallet.earn.avg': 'AVG / MONTH',
    'investor.wallet.earn.month': 'MONTH',
    'investor.wallet.earn.paid': 'PAID',
    'investor.wallet.earn.total': 'TOTAL IN',
    'investor.wallet.earn.empty': 'No payouts landed between those dates.',
    'investor.wallet.tx.title': 'Transaction history',
    'investor.wallet.tx.title_short': 'Transactions',
    'investor.wallet.tx.export': 'Export',
    'investor.wallet.tx.pdf': 'Download PDF',
    'investor.wallet.tx.csv': 'Download CSV',
    'investor.wallet.tx.ranges': 'Transaction period',
    'investor.wallet.tx.range.today': 'Today',
    'investor.wallet.tx.range.7d': '7 days',
    'investor.wallet.tx.range.30d': '30 days',
    'investor.wallet.tx.range.all': 'All',
    'investor.wallet.tx.kind.deposit': 'Deposit · {counterparty}',
    'investor.wallet.tx.kind.withdrawal': 'Withdrawal · {counterparty}',
    'investor.wallet.tx.kind.investment': 'Investment · {counterparty}',
    'investor.wallet.tx.kind.payout': 'Payout · {counterparty}',
    'investor.wallet.tx.kind.refund': 'Refund · {counterparty}',
    'investor.wallet.tx.kind.fee': 'Fee · {counterparty}',
    'investor.wallet.tx.status.completed': 'Completed',
    'investor.wallet.tx.status.pending': 'Pending',
    'investor.wallet.tx.status.failed': 'Failed',
    'investor.wallet.tx.empty': 'No transactions in this range.',
    'investor.wallet.receipt.label': 'Transaction receipt',
    'investor.wallet.receipt.kind.deposit': 'Deposit',
    'investor.wallet.receipt.kind.withdrawal': 'Withdrawal',
    'investor.wallet.receipt.kind.investment': 'Investment',
    'investor.wallet.receipt.kind.payout': 'Monthly repayment',
    'investor.wallet.receipt.kind.refund': 'Refund',
    'investor.wallet.receipt.kind.fee': 'Fee',
    'investor.wallet.receipt.amount': 'AMOUNT',
    'investor.wallet.receipt.status': 'Status',
    'investor.wallet.receipt.type': 'Type',
    'investor.wallet.receipt.credit': 'Credit',
    'investor.wallet.receipt.debit': 'Debit',
    'investor.wallet.receipt.when': 'Date & time',
    'investor.wallet.receipt.transaction_id': 'Transaction ID',
    'investor.wallet.receipt.reference': 'Reference',
    'investor.wallet.receipt.before': 'Balance before',
    'investor.wallet.receipt.after': 'Balance after',
    'investor.wallet.receipt.gross': 'Gross amount',
    'investor.wallet.receipt.fee': 'Processing fee',
    'investor.wallet.receipt.net': 'Net received',
    'investor.wallet.receipt.principal': 'Principal returned',
    'investor.wallet.receipt.return': 'Return paid',
    'investor.wallet.receipt.payout_fee': 'Investor fee (1%)',
    'investor.wallet.receipt.done': 'Done',
    'investor.profile.title': 'Profile',
    'investor.profile.menu': 'Profile menu',
    'investor.profile.kyc.verified': '✓ KYC Verified',
    'investor.profile.kyc.pending': 'Verification pending',
    'investor.profile.kyc.unverified': 'Not verified',
    'investor.profile.kyc.expired': 'Document expired',
    'investor.profile.type.individual': 'Individual',
    'investor.profile.type.institution': 'Institution',
    'investor.profile.support.label': 'Support',
    'investor.profile.support.title': 'Talk to Rozine support',
    'investor.profile.support.sub':
        'Ask a question or raise a complaint. The team replies in the Help center.',
    'investor.profile.support.message': 'Message',
    'investor.profile.member_since': 'Member since {date}',
    'investor.profile.item.linked': 'Linked accounts',
    'investor.profile.item.statements': 'Statements & tax',
    'investor.profile.item.automation': 'Auto-Deploy',
    'investor.profile.item.verification': 'Identity verification',
    'investor.profile.item.terms': 'Terms & Conditions',
    'investor.profile.item.privacy': 'Privacy Note',
    'investor.profile.item.personal': 'Personal information',
    'investor.profile.item.plan': 'Rozine Plus · how to unlock',
    'investor.profile.item.security': 'Security center',
    'investor.profile.item.help': 'Help center',
    'investor.profile.plan_title': 'Rozine Plus',
    'investor.profile.personal.name': 'FULL NAME',
    'investor.profile.personal.email': 'EMAIL',
    'investor.profile.personal.phone': 'PHONE',
    'investor.profile.personal.id_type': 'ID TYPE',
    'investor.profile.personal.id_number': 'ID NUMBER',
    'investor.profile.personal.passport_number': 'PASSPORT NUMBER',
    'investor.profile.personal.address': 'ADDRESS',
    'investor.profile.personal.country': 'COUNTRY',
    'investor.profile.personal.country_unknown': 'Unknown',
    'investor.profile.personal.province': 'PROVINCE',
    'investor.profile.personal.district': 'DISTRICT',
    'investor.profile.personal.sector': 'SECTOR',
    'investor.profile.personal.cell': 'CELL',
    'investor.profile.personal.under_review': 'Under review',
    'investor.profile.personal.not_provided': 'Not provided',
    'investor.profile.security.two_factor': 'Two-factor authentication',
    'investor.profile.security.two_factor_on':
        'On · authenticator app code at sign-in',
    'investor.profile.security.login_alerts': 'Login alerts',
    'investor.profile.security.login_alerts_hint':
        'Email me on new device sign-in',
    'investor.profile.security.two_factor_off':
        'Off · add an authenticator app code at sign-in',
    'investor.profile.security.password': 'Change password',
    'investor.profile.pending.plan_title': 'Rozine Plus is not open yet',
    'investor.profile.pending.plan_body':
        'Its bands and charges are still being set. Until then, you invest deal by deal on the terms each deal shows.',
    'investor.help.intro':
        'Answers to the questions investors ask most. Tap any card to read the full answer.',
    'support.help.updated': 'Updated {date}',
    'support.help.updated_long': 'Last updated {date}',
    'support.help.count': { one: '{count} answer', other: '{count} answers' },
    'support.help.still_title': 'Still need help?',
    'support.help.still_body':
        'Message us. We acknowledge every message within 4 hours.',
    'support.help.still_action': 'Message support',
    'investor.deals.nudge.tag': 'Keep learning',
    'investor.deals.nudge.title': 'Continue Investor Academy',
    'investor.deals.nudge.body':
        '{done} of {total} lessons done. Pick up where you left off.',
    'investor.deals.nudge.later': 'Later',
    'investor.deals.nudge.continue': 'Continue',
    'investor.academy.title': 'Investor Academy',
    'investor.academy.learning': 'Learning',
    'investor.academy.summary_start': 'Learn how Rozine notes work',
    'investor.academy.summary_resume': 'Pick up where you left off',
    'investor.academy.summary_done': 'All lessons complete',
    'investor.academy.done_count': '{done} of {total} lessons done',
    'investor.academy.tracks': '{count} tracks',
    'investor.academy.intro':
        'Short lessons on how notes, ratings and repayments work. Your progress is saved on this device only.',
    'investor.academy.group.basics': 'Rozine basics',
    'investor.academy.group.mission': 'Our mission',
    'investor.academy.group.investing': 'Investing 101',
    'investor.academy.group.rozine': 'Investing on Rozine',
    'investor.academy.group_progress': '{done}/{total} done',
    'investor.academy.status.start': 'Start',
    'investor.academy.status.progress': '{done}/{total}',
    'investor.academy.status.done': 'Done ✓',
    'investor.academy.close': 'Close lesson',
    'investor.academy.progress': 'Lesson progress',
    'investor.academy.step': '{step} of {total}',
    'investor.academy.got_it': 'Got it',
    'investor.academy.quick_check': 'Quick check',
    'investor.academy.correct': 'Correct',
    'investor.academy.correct_body': 'That’s right. On to the next one.',
    'investor.academy.wrong': 'Not quite',
    'investor.academy.wrong_body':
        'Take another look at this topic. It comes round once more before the lesson ends.',
    'investor.academy.next': 'Next',
    'investor.academy.finish': 'Finish',
    'investor.academy.done_title': 'Lesson complete',
    'investor.academy.done_body': 'You finished {lesson}.',
    'investor.academy.partial_title': 'Not quite yet',
    'investor.academy.partial_body':
        'You passed {passed} of {total} quick checks in {lesson}. Try the lesson again to finish it.',
    'investor.academy.partial_retry': 'Try the lesson again',
    'investor.academy.done_cta': 'Back to the Academy',
    'investor.academy.lesson.notes.title': 'Rozine notes',
    'investor.academy.lesson.notes.sub': 'What you buy and how it pays',
    'investor.academy.lesson.notes.topic1.title': 'What is a note?',
    'investor.academy.lesson.notes.topic1.body':
        'A note is a short loan to a verified Rwandan business, sold in units of RWF 5,000. The business repays your principal plus a fixed return over 3 to 6 months.',
    'investor.academy.lesson.notes.topic1.q': 'Buying a note means you…',
    'investor.academy.lesson.notes.topic1.option1':
        'Lend to a verified business for a fixed return',
    'investor.academy.lesson.notes.topic1.option2': 'Buy shares in Rozine',
    'investor.academy.lesson.notes.topic1.option3':
        'Get a bonus whatever happens',
    'investor.academy.lesson.notes.topic2.title': 'Returns and repayment',
    'investor.academy.lesson.notes.topic2.body':
        'Each deal shows its total return and term. The business repays in equal monthly instalments, and your share of each one is credited to your wallet, less Rozine’s fee on the return part.',
    'investor.academy.lesson.notes.topic2.q': 'Your return is paid…',
    'investor.academy.lesson.notes.topic2.option1':
        'In one lump sum after a year',
    'investor.academy.lesson.notes.topic2.option2':
        'Monthly, over the term of the note',
    'investor.academy.lesson.notes.topic2.option3': 'Only if you ask for it',
    'investor.academy.lesson.wallet.title': 'Your Rozine wallet',
    'investor.academy.lesson.wallet.sub': 'Deposits, payouts and withdrawals',
    'investor.academy.lesson.wallet.topic1.title':
        'Available, held and committed',
    'investor.academy.lesson.wallet.topic1.body':
        'Available money is ready to invest or withdraw. Money held for a checkout, or committed to a raise you joined, is set aside until the raise fills or is refunded.',
    'investor.academy.lesson.wallet.topic1.q': 'Money committed to a raise is…',
    'investor.academy.lesson.wallet.topic1.option1':
        'Free to withdraw at any time',
    'investor.academy.lesson.wallet.topic1.option2': 'Lost for good',
    'investor.academy.lesson.wallet.topic1.option3':
        'Set aside until the raise fills or is refunded',
    'investor.academy.lesson.wallet.topic2.title': 'Getting paid',
    'investor.academy.lesson.wallet.topic2.body':
        'Payouts land in your wallet on each payment day. You can put them into another open raise, or withdraw them to a verified account in your own name.',
    'investor.academy.lesson.wallet.topic2.q':
        'When a business repays, the money…',
    'investor.academy.lesson.wallet.topic2.option1':
        'Lands in your wallet automatically',
    'investor.academy.lesson.wallet.topic2.option2':
        'Must be claimed within a day',
    'investor.academy.lesson.wallet.topic2.option3':
        'Goes straight into a new note',
    'investor.academy.lesson.rating.title': 'Reading the Rozine rating',
    'investor.academy.lesson.rating.sub': 'One score, one word',
    'investor.academy.lesson.rating.topic1.title': 'What the rating means',
    'investor.academy.lesson.rating.topic1.body':
        'Every business gets one score out of 5.0 with a plain word: Strong (4.0 to 5.0), Stable (3.0 to 3.9), Weak (2.0 to 2.9) or Distressed (below 2.0). A business must score at least 2.0 to raise.',
    'investor.academy.lesson.rating.topic1.q': 'A business rated 4.4 is…',
    'investor.academy.lesson.rating.topic1.option1': 'Distressed',
    'investor.academy.lesson.rating.topic1.option2': 'Strong',
    'investor.academy.lesson.rating.topic1.option3': 'Not allowed to raise',
    'investor.academy.lesson.rating.topic2.title': 'Using it well',
    'investor.academy.lesson.rating.topic2.body':
        'The score is recomputed with every sealed monthly report. A higher score means lower risk, not no risk, so spread your money across several notes.',
    'investor.academy.lesson.rating.topic2.q': 'The rating is best used as…',
    'investor.academy.lesson.rating.topic2.option1': 'A promise of profit',
    'investor.academy.lesson.rating.topic2.option2':
        'A reason to put everything in one note',
    'investor.academy.lesson.rating.topic2.option3':
        'A guide, alongside spreading your money',
    'investor.academy.lesson.mission.title': 'Why Rozine exists',
    'investor.academy.lesson.mission.sub': 'Our mission and values',
    'investor.academy.lesson.mission.topic1.title': 'The mission',
    'investor.academy.lesson.mission.topic1.body':
        'Rozine connects everyday investors with verified Rwandan businesses that need working capital, so local firms can grow and investors can earn a fixed return.',
    'investor.academy.lesson.mission.topic1.q': 'Rozine exists to…',
    'investor.academy.lesson.mission.topic1.option1':
        'Connect investors with verified local businesses',
    'investor.academy.lesson.mission.topic1.option2': 'Trade foreign shares',
    'investor.academy.lesson.mission.topic1.option3': 'Sell insurance',
    'investor.academy.lesson.mission.topic2.title': 'Transparency first',
    'investor.academy.lesson.mission.topic2.body':
        'Every deal is backed by verified bank and mobile money statements, an independent on-site visit and sealed monthly reports, so you can see what you are investing in.',
    'investor.academy.lesson.mission.topic2.q': 'Rozine backs each deal with…',
    'investor.academy.lesson.mission.topic2.option1':
        'The business’s own promises',
    'investor.academy.lesson.mission.topic2.option2':
        'Verified records and sealed monthly reports',
    'investor.academy.lesson.mission.topic2.option3':
        'A pledge from Rozine to repay',
    'investor.academy.lesson.fundamentals.title': 'Investing fundamentals',
    'investor.academy.lesson.fundamentals.sub': 'Risk and return',
    'investor.academy.lesson.fundamentals.topic1.title': 'Risk and return',
    'investor.academy.lesson.fundamentals.topic1.body':
        'A higher possible return usually comes with higher risk. You can lose some or all of the money you invest if a business does not repay.',
    'investor.academy.lesson.fundamentals.topic1.q':
        'A higher possible return usually comes with…',
    'investor.academy.lesson.fundamentals.topic1.option1': 'No risk at all',
    'investor.academy.lesson.fundamentals.topic1.option2':
        'A promise of profit',
    'investor.academy.lesson.fundamentals.topic1.option3': 'Higher risk',
    'investor.academy.lesson.fundamentals.topic2.title': 'Invest with a plan',
    'investor.academy.lesson.fundamentals.topic2.body':
        'Notes cannot be cashed in before they end, so only invest money you will not need during the term, and decide in advance how much you want to put in.',
    'investor.academy.lesson.fundamentals.topic2.q':
        'Before you invest, you should…',
    'investor.academy.lesson.fundamentals.topic2.option1':
        'Only use money you will not need during the term',
    'investor.academy.lesson.fundamentals.topic2.option2':
        'Borrow to invest more',
    'investor.academy.lesson.fundamentals.topic2.option3':
        'Plan to cash in early',
    'investor.academy.lesson.diversify.title': 'Diversification',
    'investor.academy.lesson.diversify.sub': 'Spread your money out',
    'investor.academy.lesson.diversify.topic1.title': 'Spread it out',
    'investor.academy.lesson.diversify.topic1.body':
        'Spreading your money across several businesses means one that pays late has a smaller effect on your total.',
    'investor.academy.lesson.diversify.topic1.q':
        'Diversifying mainly helps to…',
    'investor.academy.lesson.diversify.topic1.option1':
        'Make every note pay more',
    'investor.academy.lesson.diversify.topic1.option2':
        'Reduce the effect of one business',
    'investor.academy.lesson.diversify.topic1.option3': 'Remove all risk',
    'investor.academy.lesson.diversify.topic2.title':
        'Mix industries and terms',
    'investor.academy.lesson.diversify.topic2.body':
        'Mixing industries, ratings and terms keeps you from depending on a single shock. You can hold at most half of any one raise.',
    'investor.academy.lesson.diversify.topic2.q':
        'Mixing industries and terms helps you…',
    'investor.academy.lesson.diversify.topic2.option1':
        'Avoid depending on a single shock',
    'investor.academy.lesson.diversify.topic2.option2': 'Pay lower fees',
    'investor.academy.lesson.diversify.topic2.option3':
        'Hold more than half of a raise',
    'investor.academy.lesson.deals.title': 'Choosing a deal',
    'investor.academy.lesson.deals.sub': 'Reading the deal page',
    'investor.academy.lesson.deals.topic1.title': 'What to check',
    'investor.academy.lesson.deals.topic1.body':
        'On each deal page, look at the rating, return, term, how much is left to fill, the verified figures and the sealed monthly reports before you commit.',
    'investor.academy.lesson.deals.topic1.q':
        'Before investing you should review…',
    'investor.academy.lesson.deals.topic1.option1': 'Only the business’s name',
    'investor.academy.lesson.deals.topic1.option2': 'How many people liked it',
    'investor.academy.lesson.deals.topic1.option3':
        'Rating, return, term and verified figures',
    'investor.academy.lesson.deals.topic2.title': 'How much you can put in',
    'investor.academy.lesson.deals.topic2.body':
        'Notes are sold in units of RWF 5,000, so you can start with one unit. An investment can never be more than what is left to fill.',
    'investor.academy.lesson.deals.topic2.q': 'The smallest investment is…',
    'investor.academy.lesson.deals.topic2.option1': 'RWF 1,000,000',
    'investor.academy.lesson.deals.topic2.option2': 'One unit of RWF 5,000',
    'investor.academy.lesson.deals.topic2.option3': 'Half of the raise',
    'investor.academy.lesson.repayments.title': 'Repayments and payouts',
    'investor.academy.lesson.repayments.sub': 'How you get paid back',
    'investor.academy.lesson.repayments.topic1.title': 'Paid over the term',
    'investor.academy.lesson.repayments.topic1.body':
        'Before you commit, you see the repayment amounts and term. The first instalment is due one full month after the actual payout to the business, then monthly on the same day (or the month’s last day when needed). Exact due dates are fixed and shown when the notes are issued.',
    'investor.academy.lesson.repayments.topic1.q': 'Rozine repayments are…',
    'investor.academy.lesson.repayments.topic1.option1':
        'Scheduled monthly over the term',
    'investor.academy.lesson.repayments.topic1.option2':
        'A single payment at the end',
    'investor.academy.lesson.repayments.topic1.option3':
        'Whenever the business chooses',
    'investor.academy.lesson.repayments.topic2.title': 'Reinvest or withdraw',
    'investor.academy.lesson.repayments.topic2.body':
        'Each payout is yours to put into another open raise or to withdraw. Nothing is reinvested automatically.',
    'investor.academy.lesson.repayments.topic2.q':
        'When you receive a payout you can…',
    'investor.academy.lesson.repayments.topic2.option1':
        'Only leave it in the note',
    'investor.academy.lesson.repayments.topic2.option2':
        'Only spend it on fees',
    'investor.academy.lesson.repayments.topic2.option3':
        'Reinvest it or withdraw it',

    'investor.help.group.start': 'Getting started',
    'investor.help.group.returns': 'Returns & payouts',
    'investor.help.group.risk': 'Risk & how businesses are vetted',
    'investor.help.group.portfolio': 'Building your portfolio',
    'investor.help.group.secondary': 'Secondary market',
    'investor.help.group.wallet': 'Wallet, deposits & fees',
    'investor.help.group.safety': "Your money's safety",
    'investor.help.group.account': 'Account & support',
    'investor.help.start.what.q': 'What is Rozine and how does investing work?',
    'investor.help.start.what.a':
        "Rozine lets you lend to verified Rwandan businesses through notes. You buy units in a business's raise; once the raise is fully funded, the business repays in equal monthly instalments, and your share of principal and return is credited to your Rozine wallet.\n\nRozine runs inside the Capital Market Authority's regulatory sandbox, so some limits apply while the sandbox lasts.",
    'investor.help.start.first.q': 'How do I make my first investment?',
    'investor.help.start.first.a':
        "Verify your identity, then add money to your wallet from a mobile money or bank account in your own name. Open a deal on Market to see its rating, return, term, schedule and the business's verified figures, choose how many units you want, and confirm.\n\nBefore you confirm, you see the amount, every fee in RWF and exactly what you get back.",
    'investor.help.start.minimum.q': 'Is there a minimum investment?',
    'investor.help.start.minimum.a':
        'Notes are sold in units of RWF 5,000, so you can start with a single unit.\n\nYou can hold at most half of any one raise, and an investment can never be larger than what is left to fill.',
    'investor.help.returns.when.q': 'How and when are my returns paid?',
    'investor.help.returns.when.a':
        "The first instalment is due one month after the money is disbursed, and each later one on the same day of the following month. On each due date, the business's payment is shared among holders in proportion to the units they hold, and credited to their wallets.\n\nThe full schedule is shown before you commit, and it never changes after the note is issued.",
    'investor.help.returns.yield.q': 'What return can I expect?',
    'investor.help.returns.yield.a':
        "Each note has a fixed total return of 10% to 15% of principal for the whole term of 3 to 6 months, set when the raise opens.\n\nRozine's earnings fee is taken from the return part of each payout, never from your principal. Its rate depends on your active capital and is locked for each investment.",
    'investor.help.returns.guaranteed.q': 'Are my returns guaranteed?',
    'investor.help.returns.guaranteed.a':
        'No. The return shown is what the business has agreed to pay, not a guarantee. You can lose some or all of the money you invest if a business does not repay.\n\nDiversifying across several notes limits the effect of any one business.',
    'investor.help.risk.protected.q': 'Is my capital protected?',
    'investor.help.risk.protected.a':
        'No. Your capital is at risk. Every business is checked from its statements and visited by an independent licensed accountant before it can raise, and every month while its note is outstanding. That lowers the risk, but it does not remove it.\n\nNotes cannot be cashed in before they end, so only invest money you will not need during the term.',
    'investor.help.risk.vetting.q':
        'How are businesses vetted before they raise?',
    'investor.help.risk.vetting.a':
        'A business needs verified annual revenue of at least RWF 30,000,000 and 36 months of bank and mobile money statements (12 for a repeat issuer that paid on time). Rozine sizes each note so that the business earns at least 1.5 times its monthly instalment.\n\nNo note lists until an Audit Partner has visited the premises and sealed a baseline report.',
    'investor.help.risk.rating.q': 'What is the Rozine rating?',
    'investor.help.risk.rating.a':
        'Each business gets one score out of 5.0, to one decimal, shown with a word: Strong (4.0 to 5.0), Stable (3.0 to 3.9), Weak (2.0 to 2.9) or Distressed (below 2.0). A business must score at least 2.0 to raise.\n\nThe score is recomputed with every sealed monthly report. A new score never changes the terms of a note already issued.',
    'investor.help.risk.late.q':
        'What happens if a business pays late or defaults?',
    'investor.help.risk.late.a':
        "On the due date, whatever is in the business's wallet is collected and paid out, and anything that arrives later is collected straight away. Late charges of 5% of the unpaid amount at the due date, day 7 and day 30 all go to holders. Rozine takes none of it.\n\nLate charges and penalty fees are accrued daily upon default and are payable to note holders only as and when collected from the borrower. Rozine does not guarantee or advance late fee recoveries.\n\nIf the arrears are not cleared by day 31, the note is in default. Recovery is pursued but may not return the full amount.",
    'investor.help.portfolio.diversify.q': 'How should I diversify?',
    'investor.help.portfolio.diversify.a':
        'Spread your money across several businesses, industries and terms rather than one note. If one business pays late, the others soften the effect.\n\nMany investors set a limit for any single note and add variety over time.',
    'investor.help.portfolio.track.q': 'How do I track my investments?',
    'investor.help.portfolio.track.a':
        "Portfolio shows every holding with its principal, what has been paid and what is still due. Each business's sealed monthly report appears on the holding.\n\nEvery payout is listed in your wallet history, and Profile > Statements & tax holds your statements.",
    'investor.help.portfolio.reinvest.q': 'Can I reinvest my payouts?',
    'investor.help.portfolio.reinvest.a':
        'Yes. Payouts land in your wallet, and you can put that balance into another open raise whenever you choose.\n\nNothing is reinvested automatically.',
    'investor.help.secondary.exit.q': 'Can I exit an investment early?',
    'investor.help.secondary.exit.a':
        'The secondary market is not open yet, so for now you hold each note until its last instalment.\n\nWhen it opens, you will be able to list units of a current note for another investor to buy. Late, defaulted or disputed notes cannot be traded.',
    'investor.help.secondary.price.q': 'How will a resale price be decided?',
    'investor.help.secondary.price.a':
        'You will choose the number of units and a whole-franc price per unit. The price can never be higher than the payouts the units still have to come, but it can be lower for a quicker sale.\n\nA listing lasts 7 days and can be relisted at no cost. The seller pays a 2% fee on the sale price; the buyer pays nothing.',
    'investor.help.secondary.guaranteed.q': 'Will a sale be guaranteed?',
    'investor.help.secondary.guaranteed.a':
        'No. A sale needs another investor to buy at your price, so a listing may sell only in part, or not at all.\n\nUnits that do not sell stay yours and keep receiving payouts.',
    'investor.help.wallet.move.q': 'How do I add or withdraw money?',
    'investor.help.wallet.move.a':
        'Deposits come by mobile money or bank from a verified account in your own name. Withdrawals go only to your own verified account, between RWF 1,000 and RWF 2,000,000 each.\n\nManage your accounts under Profile > Linked accounts.',
    'investor.help.wallet.fees.q': 'What fees does Rozine charge investors?',
    'investor.help.wallet.fees.a':
        "Rozine's earnings fee is taken from the return part of each payout: 15% under RWF 1M of active capital, 12% from 1M to 5M, 10% from 5M to 25M, 9% from 25M to 100M and 8% above. It is never charged on principal.\n\nWithdrawals carry no Rozine fee; the provider's own charge is shown before you confirm.",
    'investor.help.wallet.speed.q': 'How quickly are withdrawals processed?',
    'investor.help.wallet.speed.a':
        'Once you confirm, the withdrawal is sent to your account through the payment provider. An unusual request may be held for review for up to 24 hours.\n\nYou can follow each withdrawal in your wallet history. Money committed to an open raise cannot be withdrawn.',
    'investor.help.safety.held.q': 'Where is my money held?',
    'investor.help.safety.held.a':
        "All customer money is held in a segregated client account at a licensed Rwandan bank, separate from Rozine's own money. The total of all wallets is matched to that account every day.\n\nOnly fees that were earned and disclosed can move to Rozine's own account.",
    'investor.help.safety.stop.q': 'What happens if Rozine has to stop?',
    'investor.help.safety.stop.a':
        'New business stops the same day, every commitment in an unfunded raise is refunded in full, and every issued note keeps being serviced to maturity from a dedicated wind-down reserve.\n\nWithdrawals of wallet balances are free during a wind-down.',
    'investor.help.account.secure.q': 'How is my account kept secure?',
    'investor.help.account.secure.a':
        'Add an authenticator app under Profile > Security center for a code at every sign-in, and turn on login alerts to hear about any new device.\n\nRozine never asks for your password or codes by phone, SMS or e-mail.',
    'investor.help.account.complain.q': 'How do I make a complaint?',
    'investor.help.account.complain.a':
        'Message us from this help center. Your complaint gets a reference number; we acknowledge it within 4 hours and aim to resolve it within 48 hours.\n\nYou can take a complaint to the Capital Market Authority at any stage.',
    'investor.profile.pending.terms_title': 'Coming to the app soon',
    'investor.profile.pending.terms_body':
        'The Terms & Conditions will be readable here once they are published.',
    'investor.profile.pending.privacy_title': 'Coming to the app soon',
    'investor.profile.pending.privacy_body':
        'The Privacy Note will be readable here once it is published.',
    'investor.profile.sign_out': 'Sign out',
    'investor.profile.linked.unlink': 'Unlink',
    'investor.profile.linked.unverified': 'Verifying',
    'investor.profile.linked.none':
        'No payout account yet. Link one to deposit and receive payouts.',
    'investor.profile.linked.empty': 'No payout account linked yet.',
    'investor.profile.linked.new': 'Link a new account',
    'investor.profile.linked.add': '+ Link a new account',
    'investor.profile.linked.type': 'TYPE',
    'investor.profile.linked.type_mobile': 'Mobile money',
    'investor.profile.linked.type_bank': 'Bank account',
    'investor.profile.linked.network': 'NETWORK',
    'investor.profile.linked.mtn': 'MTN MoMo',
    'investor.profile.linked.airtel': 'Airtel Money',
    'investor.profile.linked.bank': 'BANK',
    'investor.profile.linked.mobile_number': 'MOBILE NUMBER',
    'investor.profile.linked.account_number': 'ACCOUNT NUMBER',
    'investor.profile.linked.mobile_placeholder': '07X XXX XXXX',
    'investor.profile.linked.account_placeholder': 'Account number',
    'investor.profile.linked.submit': 'Link account',
    'investor.profile.linked.footnote':
        'Rozine sends a one-off verification debit. Payouts only ever reach an account in your own name.',
    'investor.profile.automation.gated_title':
        "Auto-Deploy isn't available yet",
    'investor.profile.automation.gated_body':
        'It needs product, fee and legal approval before Rozine can invest on your behalf.',
    'investor.profile.automation.intro':
        'Auto-Deploy would let Rozine reserve notes in new raises for you, within rules you set. Until it is approved, every investment is one you choose and confirm yourself.',
    'investor.profile.automation.nothing_runs':
        'Nothing is set up, running or charged for Auto-Deploy on your account.',
    'investor.profile.automation.next': 'Choose a deal yourself',
    'investor.profile.statements.intro':
        'Download portfolio statements and tax summaries. Each is generated from your verified transaction history.',
    'investor.profile.statements.year': 'TAX YEAR {year}',
    'investor.profile.statements.annual': 'Annual returns summary',
    'investor.profile.statements.annual_meta':
        'Total interest earned · fees paid',
    'investor.profile.statements.incomplete':
        'Year still open · figures still moving',
    'investor.profile.statements.download_annual': 'Download annual summary',
    'investor.profile.statements.monthly': 'MONTHLY STATEMENTS',
    'investor.profile.statements.pdf': 'PDF statement',
    'investor.profile.statements.month_open':
        '{month} still open · figures may change',
    'investor.profile.statements.none':
        'No statements yet. Your first appears after your first full month.',
    'investor.profile.statements.disclaimer':
        'Statements are provided for your records. Rozine does not provide tax advice — consult a qualified adviser about your obligations.',
    'business.apply.business.unavailable': 'Unavailable',
    'business.apply.business.ineligible': 'Not eligible to raise yet',
    'business.apply.business.requirement':
        'Verified history: {verified} of {required} months',
    'business.apply.business.uploaded_statements': {
        one: '{count} statement uploaded. Months not yet verified count once an Audit Partner verifies them.',
        other: '{count} statements uploaded. Months not yet verified count once an Audit Partner verifies them.',
    },
    'business.apply.business.no_statements':
        'No statements uploaded yet. Upload them below to start verification.',
    'business.apply.business.obligations':
        'Existing loans and other obligations are checked in the same verification, from your statements and a credit bureau check. There is nothing separate to upload.',
    'business.apply.statements.title': 'Bank & Mobile Money statements',
    'business.apply.statements.body':
        'Upload the original statement of every bank and Mobile Money account the business uses (PDF, or a CSV export; up to 10 MB each). Rozine reads them, then an Audit Partner verifies them before your figures and capacity appear here.',
    'business.apply.statements.empty': 'No statements received yet.',
    'business.apply.statements.upload': 'Upload a statement',
    'business.apply.statements.failed':
        'We could not keep this statement. Try again.',
    'business.apply.statements.received': 'Received {date}',
    'business.apply.statements.reading.reading': 'Reading',
    'business.apply.statements.reading.read': 'Read · awaiting verification',
    'business.apply.statements.reading.needs_review': 'Our team will check it',
    'business.apply.statements.reading.verified': 'Verified',
    'business.apply.raise.target_required': 'Enter how much you want to raise.',
    'business.apply.raise.resized_reason':
        '{capacity} is the most you can raise right now: your borrowing capacity sets this limit.',
    'business.apply.raise.resized':
        'You asked for {requested} · this is the offer you accept when you sign',
    'business.apply.raise.schedule': 'Repayment schedule',
    'business.apply.raise.instalment': 'Instalment {n}',
    'business.apply.raise.instalment_final': 'Instalment {n} · final',
    'business.apply.review.your_offer': 'Your offer',
    'business.apply.review.flat_rate': '{rate}% flat',
    'business.apply.review.accept_offer':
        'I accept this offer: {principal} over {months} months, repaid on the schedule above.',
    'business.apply.review.no_offer':
        'There is no offer to accept yet. Go back to your raise to get a quote.',
    'business.apply.review.signatories': 'Signatories',
    'business.apply.review.signatures_required':
        "Signatures the company's mandate requires: {count}",
    'business.apply.review.signed_on': 'Signed · {date}',
    'business.apply.review.signer.signed': 'Signed',
    'business.apply.review.signer.pending': 'Pending',
    'business.apply.review.attestation':
        'Typing your name confirms you are signing yourself. Your verified account, not this name, is what signs.',
    'business.apply.review.waiting':
        'Waiting for {names} to sign. The application is submitted once every required signature is in.',
    'business.apply.review.cannot_sign':
        "Only a signatory on the company's verified mandate can sign this application.",
    'business.apply.review.agreement_unavailable':
        "The agreement isn't available yet.",
    'business.apply.review.agreement_unavailable_body':
        'Rozine has not published the approved terms and risk disclosures for this application, so there is nothing to sign yet. Your draft and your offer stay saved.',
    'business.apply.statements.transcribe':
        'Enter the figures from your statements',
    'business.transcription.title': 'Enter your statement figures',
    'business.transcription.intro':
        'Copy each month from the statements you uploaded: the balance at the start and end, and every transaction in between. An Audit Partner then checks these figures against your original statements. Months count only once they are verified.',
    'business.transcription.saved': 'Figures saved · not yet verified',
    'business.transcription.no_statements':
        'Upload at least one bank or Mobile Money statement on step 1 first. Then come back to enter its figures.',
    'business.transcription.view_only':
        'You can see your statements, but only someone who can save the application can enter their figures.',
    'business.transcription.months.title': 'Months your statements cover',
    'business.transcription.months.first': 'From',
    'business.transcription.months.last': 'To',
    'business.transcription.months.count': {
        one: '{count} month (at most {max})',
        other: '{count} months (at most {max})',
    },
    'business.transcription.accounts.title': 'Accounts',
    'business.transcription.accounts.hint':
        'Add each bank or Mobile Money account the statements are from. Use letters, numbers, - and _ in the name.',
    'business.transcription.account.unnamed': 'Account {number}',
    'business.transcription.account.name': 'Account name',
    'business.transcription.account.placeholder':
        'e.g. BK-current or MoMo-0788',
    'business.transcription.account.from': 'Opened in',
    'business.transcription.account.until': 'Closed in',
    'business.transcription.account.open': 'Still open',
    'business.transcription.account.remove': 'Remove {account}',
    'business.transcription.account.add': 'Add an account',
    'business.transcription.figures.title': 'Monthly figures',
    'business.transcription.figures.months': 'Choose a month',
    'business.transcription.figures.heading': '{account} · {month}',
    'business.transcription.figures.sources': 'From statement',
    'business.transcription.figures.opening': 'Opening balance (RWF)',
    'business.transcription.figures.opening_fixed':
        'Carried from the month before, or 0 in the month the account opened.',
    'business.transcription.figures.transactions': 'Transactions',
    'business.transcription.figures.none': 'No transactions this month.',
    'business.transcription.figures.closing':
        'Closing balance on the statement (RWF)',
    'business.transcription.figures.computed':
        'Opening balance plus transactions: RWF {amount}',
    'business.transcription.row.label': 'Transaction {number}',
    'business.transcription.row.date': 'Date',
    'business.transcription.row.direction': 'Money',
    'business.transcription.row.in': 'In',
    'business.transcription.row.out': 'Out',
    'business.transcription.row.amount': 'Amount (RWF)',
    'business.transcription.row.type': 'What it was',
    'business.transcription.row.choose': 'Choose…',
    'business.transcription.row.link.transfer':
        'The same transfer on your other account',
    'business.transcription.row.link.owner_return':
        'The owner withdrawal it pays back',
    'business.transcription.row.reference': 'Reference on the statement',
    'business.transcription.row.remove': 'Remove {row}',
    'business.transcription.row.add': 'Add a transaction',
    'business.transcription.type.in.operating_inflow':
        'Sales or other business income',
    'business.transcription.type.in.financing': 'Loan or investment received',
    'business.transcription.type.in.transfer':
        'Transfer from your other account',
    'business.transcription.type.in.owner_return': 'Owner paid money back',
    'business.transcription.type.out.operating_outflow': 'Business expense',
    'business.transcription.type.out.debt_service': 'Loan repayment',
    'business.transcription.type.out.owner_draw': 'Owner withdrawal',
    'business.transcription.type.out.transfer':
        'Transfer to your other account',
    'business.transcription.problem.months':
        'Choose the first and last month your statements cover.',
    'business.transcription.problem.too_many_months':
        'Choose {max} months at most.',
    'business.transcription.problem.no_accounts': 'Add at least one account.',
    'business.transcription.problem.account_name':
        'Give the account a name using letters, numbers, - and _.',
    'business.transcription.problem.account_duplicate':
        'Two accounts have the same name.',
    'business.transcription.problem.account_dates':
        'An account cannot close before it opens.',
    'business.transcription.problem.sources':
        'Tick the statement these figures are from.',
    'business.transcription.problem.balance':
        'Enter the opening and closing balances in whole francs.',
    'business.transcription.problem.row_amount':
        'Enter each amount in whole francs.',
    'business.transcription.problem.row_date':
        'Each transaction date must be in this month.',
    'business.transcription.problem.row_reference':
        'Each transaction needs its own reference.',
    'business.transcription.problem.row_type':
        'Choose what each transaction was.',
    'business.transcription.problem.row_link':
        'Link each transfer or owner pay-back to its other side.',
    'business.transcription.problem.difference':
        'This month does not add up: the opening balance plus transactions must equal the closing balance.',
    'business.transcription.problem.closed_balance':
        'An account must end at 0 in the month it closed.',
    'business.transcription.to_fix': {
        one: '{count} thing to fix before saving',
        other: '{count} things to fix before saving',
    },
    'business.transcription.reviewed':
        'I checked every figure against my original statements.',
    'business.transcription.save': 'Save figures',
    'business.transcription.saving': 'Saving…',
    'business.transcription.failed':
        'We could not save these figures. Try again.',
    'business.apply.view_only':
        'You can view this application, but not change it.',
    'business.apply.submitted.application_id': 'Application ID · {id}',
    'business.apply.outcome.checking.title': 'Checking what happened',
    'business.apply.outcome.checking.body':
        "The connection dropped before the server answered. We're asking whether your request went through.",
    'business.apply.outcome.unconfirmed.title': "We couldn't confirm this yet",
    'business.apply.outcome.unconfirmed.body':
        'Nothing will be sent again until the server confirms what happened to your request.',
    'business.apply.outcome.pending.title': 'Still being processed',
    'business.apply.outcome.pending.body':
        'The server has your request and is still working on it.',
    'business.apply.outcome.not_recorded.title': 'No result recorded',
    'business.apply.outcome.not_recorded.body':
        'The server has no result for your request yet. You can send exactly the same request again.',
    'business.apply.outcome.check_again': 'Check again',
    'business.apply.outcome.try_again': 'Try again',
    'business.apply.outcome.refused.VERSION_CONFLICT':
        "This application changed since you opened it. We've loaded the latest version — check it and try again.",
    'business.apply.outcome.refused.IDEMPOTENCY_CONFLICT':
        "This request was already used with different details, so it was not sent again. We've loaded the latest version.",
    'business.apply.outcome.refused.QUOTE_STALE':
        'Your quote changed before you signed. Check the new offer and accept it again.',
    'business.apply.outcome.refused.DOCUMENT_VERSION_STALE':
        'A document or disclosure changed before you signed. Read the new version and accept it again.',
    'business.apply.outcome.refused.ACTION_FORBIDDEN':
        "You can't do this for this business.",
    'business.apply.outcome.refused.MANDATE_REQUIRED':
        "Your verified mandate doesn't let you sign for this business.",
    'business.apply.outcome.refused.NOT_FOUND':
        'This application is no longer available to you.',
    'business.apply.outcome.refused.denied':
        'Your access has changed. Return to your apps and try again.',
    'business.apply.outcome.refused.failed':
        "The server couldn't complete this. We've loaded the latest version.",
    'business.publish.unavailable':
        'Publishing opens once your application is approved and fully signed, and the listing flow is ready.',
    'business.apply.outcome.refused.MANDATE_STALE':
        "The company's signing mandate changed before you signed. Check who must sign now, then sign again.",
    'business.apply.outcome.refused.APPLICATION_PENDING_REVIEW':
        "Your business already has an application under review. You can apply again once it's decided.",
    'business.apply.outcome.refused.APPLICATION_STEP_INVALID':
        'Go back to Review & sign to submit this application.',
    'business.apply.pending_review.link': 'View the application under review',
    'business.apply.review.document_summary': 'Summary',
    'business.apply.review.document_full_text': 'Full text',
    'business.apply.review.reduce.open': 'Take a smaller amount',
    'business.apply.review.reduce.label': 'Amount you want (RWF)',
    'business.apply.review.reduce.help':
        'Up to the offer, in whole notes of {unit}. We recalculate the offer for this amount and you accept it again.',
    'business.apply.review.reduce.submit': 'Recalculate',
    'business.apply.review.reduce.cancel': 'Keep the offer',
    'business.apply.review.reduced':
        'You chose {principal} of the {offered} offered.',
    'business.apply.review.use_full': 'Use the full offer',
    'business.apply.recalculating': 'Recalculating…',
    'auditor.capture.unavailable':
        "The capture app isn't available for this assignment yet, so photos and the check-in can't be taken. There's no way to capture them on the web.",
    'auditor.photos.add_unavailable': 'Capture unavailable',
    'auditor.checkin.position_unavailable': 'Position unavailable',
    'auditor.ledger.ingested': 'Received · not yet reviewed',
    'auditor.jobs.map_approximate': 'Approximate positions',
    'auditor.reason.label': 'Reason',
    'auditor.reason.explanation_required': 'Factual explanation (required)',
    'auditor.reason.explanation_optional': 'Explanation (optional)',
    'auditor.outcome.conflict.blocking.reassignment_pending':
        'Your conflict has been recorded. Work on this assignment is stopped while Audit Operations arranges reassignment.',
    'auditor.outcome.conflict.blocking.reassigned':
        'Your conflict has been recorded and {business} has been reassigned. Your work on this assignment has stopped.',
    'auditor.outcome.conflict.blocking.recorded':
        'Your conflict has been recorded. Work on this assignment is stopped.',
    'auditor.outcome.conflict.blocking.closed':
        'Your conflict has been recorded. Audit Operations has closed this assignment, and your work on it has stopped.',
    'auditor.seal.cites': 'Evidence: {ids}',
    'auditor.seal.evidence': 'Evidence sealed with this report',
    'auditor.seal.versions':
        'Procedure {procedure} · findings {findings} · evidence {evidence}',
    'auditor.seal.code_title': "Confirm it's you",
    'auditor.seal.code_lead':
        'Enter the six-digit code from your authenticator app to seal under licence {licence}.',
    'auditor.seal.code_label': 'Six-digit authenticator code',
    'auditor.seal.code_scope':
        'This code only confirms that you are the one sealing. It says nothing about the phones or devices used to capture the evidence.',
    'auditor.seal.code_wrong':
        "That code didn't match. Enter the current code from your authenticator.",
    'auditor.seal.code_expired':
        'Your confirmation expired before the seal went through. Enter a new code.',
    'auditor.seal.code_throttled':
        'Too many attempts. You can enter a new code in {wait}.',
    'auditor.seal.code_throttled_later':
        'Too many attempts. Wait a moment, then enter a new code.',
    'auditor.seal.code_unreachable':
        "Rozine couldn't be reached to check your code. Nothing was sealed — enter a new code to try again.",
    'auditor.seal.sealing': 'Sealing…',
    'auditor.seal.mfa_title': 'Turn on two-factor authentication to seal',
    'auditor.seal.mfa_body':
        "Sealing asks for a code from an authenticator app you've confirmed on your account. Set one up in your security settings, then come back to seal.",
    'auditor.seal.mfa_settings': 'Open security settings',
    'auditor.sealed.report_id': 'Report',
    'auditor.sealed.signature_ref': 'Signature',
    'auditor.sealed.key_id': 'Key',
    'auditor.sealed.amended_by':
        'Report {report} amends this one; this report stays as sealed.',
    'auditor.sealed.open_amendment': 'Open the amendment',
    'auditor.sealed.verify': 'Verify seal',
    'auditor.audit.amends':
        'This is a linked amendment of report {report}. That report remains unchanged.',
    'auditor.audit.open_original': 'Open the original',
    'auditor.audit.guide.label': 'How to do this step',
    'auditor.audit.guide.how_to': 'How to',
    'auditor.audit.guide.part.do': 'Do',
    'auditor.audit.guide.part.enter': 'Enter',
    'auditor.audit.guide.part.upload': 'Upload',
    'auditor.audit.guide.part.done': 'Done when',
    'auditor.audit.guide.uploads_rule':
        'Collect only what proves a figure: statements, invoices, receipts, contracts, loan agreements, certificates, minutes, audit reports, and premises and asset photos. Never collect staff names or personal details (the RSSB total page only, names covered), passwords or PINs, medical records, or anything about your other clients. Uploads are encrypted and seen only by you, the business and Rozine.',
    'auditor.audit.guide.review.do':
        'Read the business file before you go on site.',
    'auditor.audit.guide.review.done':
        'You have read the file. Continue to check in.',
    'auditor.audit.guide.review.how':
        'Open each document in the file and note what you need to see on site. Nothing is recorded in this step.',
    'auditor.audit.guide.check_in.do':
        'Check in at the business premises with the capture app.',
    'auditor.audit.guide.check_in.enter':
        'Nothing to type. Your position and the time come from your phone.',
    'auditor.audit.guide.check_in.done':
        'Your check-in has arrived and shows here.',
    'auditor.audit.guide.check_in.how':
        'Stand inside the premises, open the capture app, allow location and check in. A check-in far from the registered premises, or with a weak location, goes to review; carry on.',
    'auditor.audit.guide.photos.do':
        'Take every required site photo live in the capture app.',
    'auditor.audit.guide.photos.upload':
        'Live photos only. Gallery uploads are refused.',
    'auditor.audit.guide.photos.done':
        'Every required photo has arrived and has a title.',
    'auditor.audit.guide.photos.how':
        'Photograph the premises and the stock as the list asks. Each photo carries its own time and place. Add more photos only where they prove a figure.',
    'auditor.audit.guide.ledger.do':
        'Count the stock and enter the inventory value you counted.',
    'auditor.audit.guide.ledger.enter':
        'Inventory value in whole francs, for example 1250000.',
    'auditor.audit.guide.ledger.upload':
        'The original ledger as a PDF or CSV, up to 10 MB.',
    'auditor.audit.guide.ledger.done':
        'The ledger is attached and the difference from the reported figure is shown.',
    'auditor.audit.guide.ledger.how':
        'Count what is on the shelves, add up its value and type the total. Then attach the ledger the business keeps. A scan without text is marked for manual review.',
    'auditor.audit.guide.statements.do':
        'Read what the bank, MoMo and POS statements on file report for the month.',
    'auditor.audit.guide.statements.done':
        'You have read money in, money out and the net for the month.',
    'auditor.audit.guide.statements.how':
        'Open each statement to see where a figure comes from. Nothing is typed here; the figures come from the statements.',
    'auditor.audit.guide.count.do':
        'Tick the proof you actually saw, then enter what you observed.',
    'auditor.audit.guide.count.enter':
        'Cash on hand in whole francs and the stock units you counted. For example 450000 and 120.',
    'auditor.audit.guide.count.done':
        'Every figure is entered and every listed proof is ticked.',
    'auditor.audit.guide.count.how':
        'Count the cash and the stock yourself. Each figure is compared to the statements on file; nothing here comes from the business.',
    'auditor.audit.guide.cash_sales.do':
        'Record the cash sales this month that were never paid into a bank or mobile-money account.',
    'auditor.audit.guide.cash_sales.enter':
        'One amount in whole francs. For example 15000. Enter 0 if every sale was deposited.',
    'auditor.audit.guide.cash_sales.done':
        'An amount is entered, even if it is 0.',
    'auditor.audit.guide.cash_sales.how':
        'Compare the sales book and receipts with the deposits on the statements. Cash kept back for change or spent before banking counts as not deposited.',
    'auditor.audit.guide.costs.do':
        'Sort this month’s costs into the six categories.',
    'auditor.audit.guide.costs.enter':
        'An amount in whole francs for each category: stock, staff, premises, transport, taxes and other. For example stock 400000. Enter 0 where there was no cost.',
    'auditor.audit.guide.costs.done':
        'Every category has an amount, even if it is 0.',
    'auditor.audit.guide.costs.how':
        'Use the invoices, receipts and statement payments for the month. Put each cost in one category only; anything that fits none goes under Other.',
    'auditor.audit.guide.history.do':
        'Compare each month of the verified history with the business’s statements.',
    'auditor.audit.guide.history.done':
        'You ticked that every month matches the statements.',
    'auditor.audit.guide.history.how':
        'Open the statements for a month, check the money in and out against the row, and move on. Raise anything that does not match with the business before you tick.',
    'auditor.audit.guide.premises.do':
        'Say who holds the premises and list the business’s main assets.',
    'auditor.audit.guide.premises.enter':
        'Owned, rented or shared, then each main asset with an estimated value in whole francs. For example Delivery motorbike, 1800000.',
    'auditor.audit.guide.premises.done':
        'Who holds the premises is chosen. Assets are optional; list at most 10.',
    'auditor.audit.guide.premises.how':
        'Ask for the title deed or the lease. Walk the premises and list only what the business uses to trade, such as vehicles, machines and cold rooms.',
    'auditor.audit.guide.people.do':
        'Check the identity of every director and owner, and confirm the management profiles.',
    'auditor.audit.guide.people.done': 'Both checks are ticked.',
    'auditor.audit.guide.people.how':
        'Compare each person’s ID with the RDB register extract. Confirm that the management profiles the business gave match the people you met.',
    'auditor.audit.guide.registration.do':
        'Check the registration certificate and record the tax clearance expiry date.',
    'auditor.audit.guide.registration.enter':
        'The tax clearance expiry date as written on the certificate. For example 31 March 2027.',
    'auditor.audit.guide.registration.done':
        'The certificate is ticked as checked and a date is entered.',
    'auditor.audit.guide.registration.how':
        'The company name and code on the RDB certificate must match the business’s record. Read the expiry from the RRA tax clearance certificate.',
    'auditor.audit.guide.trading.do':
        'Record the month the business started trading.',
    'auditor.audit.guide.trading.enter':
        'A month and year. For example June 2021.',
    'auditor.audit.guide.trading.done': 'A month is entered.',
    'auditor.audit.guide.trading.how':
        'Use the earliest invoice, statement or trading licence you can see, not the registration date if trading began later.',
    'auditor.audit.guide.available.do':
        'Ask to see every cash box, bank and mobile-money balance the business could pay from today.',
    'auditor.audit.guide.available.enter':
        'The total available now, in whole francs.',
    'auditor.audit.guide.available.done':
        'Every proof you saw is ticked and the total is entered.',
    'auditor.audit.guide.available.how':
        'Count cash yourself and read balances on the live app or a same-day statement, not a printout from earlier.',
    'auditor.audit.guide.reason.do':
        'Ask why the repayment is late and pick the one reason that fits best.',
    'auditor.audit.guide.reason.enter':
        'One reason from the list, and a short note if it is Other.',
    'auditor.audit.guide.reason.done': 'A reason is chosen.',
    'auditor.audit.guide.reason.how':
        'Record what you were told and saw. Do not agree a plan, a new date or any discount: nothing is negotiated on this visit.',
    'auditor.audit.guide.drawings.do':
        'Record what the owners took out of the business this month.',
    'auditor.audit.guide.drawings.enter':
        'One amount in whole francs. For example 150000. Enter 0 if nothing was taken.',
    'auditor.audit.guide.drawings.done':
        'An amount is entered, even if it is 0.',
    'auditor.audit.guide.drawings.how':
        'Look for transfers and cash taken by the owners in the statements and the cash book. Count salaries paid to owners only if they are not on the payroll.',
    'auditor.audit.guide.loans.do':
        'List every loan the business owes anyone other than Rozine.',
    'auditor.audit.guide.loans.enter':
        'For each loan: who lent it, what is still owed and the monthly payment, in whole francs. For example Bank of Kigali, 2400000 and 200000.',
    'auditor.audit.guide.loans.done':
        'Each loan has a lender and the amount still owed. A business with no other loans continues with an empty list.',
    'auditor.audit.guide.loans.how':
        'Ask the owner and check the statements for regular repayments. Read the figures from the loan agreement or the lender’s latest statement.',
    'auditor.audit.guide.differences.do':
        'Give each cause of the cash difference an amount until nothing is left to explain.',
    'auditor.audit.guide.differences.enter':
        'Whole francs per cause. For example 20000 for personal spending and 6000 for an entry error.',
    'auditor.audit.guide.differences.done':
        'The amounts add up to the whole difference, to the franc. A month with no difference has nothing to explain.',
    'auditor.audit.guide.differences.how':
        'Ask the owner about each gap and check it against the ledger or receipts. Use only the causes listed; the amounts are sealed with the cash finding.',
    'auditor.differences.title': 'Explain the difference',
    'auditor.differences.lead':
        'Split the gap between the cash counted and what the statements report across its causes, to the franc.',
    'auditor.differences.lead_none':
        'The cash counted matches the statements, so there is nothing to explain.',
    'auditor.differences.reported': 'Statements',
    'auditor.differences.observed': 'Counted',
    'auditor.differences.difference': 'Difference',
    'auditor.differences.unavailable': 'Unavailable',
    'auditor.differences.causes': 'Causes',
    'auditor.differences.amount': 'Amount for {cause}',
    'auditor.differences.balanced': 'The whole difference is explained.',
    'auditor.differences.left': '{amount} still to explain.',
    'auditor.differences.over':
        '{amount} more than the difference. Lower an amount.',
    'auditor.differences.cause.unrecorded_sales': 'Sales not recorded',
    'auditor.differences.cause.unproven_expense': 'Expense without proof',
    'auditor.differences.cause.personal_spending':
        'Personal spending from the business',
    'auditor.differences.cause.wrong_month': 'Recorded in the wrong month',
    'auditor.differences.cause.entry_error': 'Entry error',
    'auditor.differences.cause.counted_twice': 'Counted twice',
    'auditor.cash_sales.title': 'Cash sales not deposited',
    'auditor.cash_sales.lead':
        'Cash taken this month that never reached a bank or mobile-money account, from the sales book and receipts.',
    'auditor.cash_sales.amount': 'Not deposited this month',
    'auditor.cash_sales.hint': 'Enter 0 if every cash sale was deposited.',
    'auditor.costs.title': 'Costs',
    'auditor.costs.lead':
        'This month’s costs in the six categories the business also uses for its monthly figures.',
    'auditor.costs.category.stock': 'Stock and supplies',
    'auditor.costs.category.staff': 'Staff',
    'auditor.costs.category.premises': 'Premises',
    'auditor.costs.category.transport': 'Transport',
    'auditor.costs.category.taxes': 'Taxes and fees',
    'auditor.costs.category.other': 'Other',
    'auditor.costs.total': 'Total costs',
    'auditor.costs.hint': 'Enter 0 for a category with no cost this month.',
    'auditor.history.title': 'Trading history',
    'auditor.history.lead':
        'The verified money in and out for the last {count} months, newest first.',
    'auditor.history.none': 'No verified history is on file yet.',
    'auditor.history.month': 'Month',
    'auditor.history.in': 'Money in (RWF)',
    'auditor.history.out': 'Money out (RWF)',
    'auditor.history.checked':
        'I compared every month with the statements and they match.',
    'auditor.premises.title': 'Premises and assets',
    'auditor.premises.lead':
        'Who holds the premises, and the main assets the business trades with.',
    'auditor.premises.tenure': 'The premises are',
    'auditor.premises.tenure.owned': 'Owned by the business',
    'auditor.premises.tenure.rented': 'Rented',
    'auditor.premises.tenure.shared': 'Shared with another business',
    'auditor.premises.assets': 'Main assets',
    'auditor.premises.none':
        'No assets listed. Continue if the business trades without any.',
    'auditor.premises.item': 'Asset {number}',
    'auditor.premises.asset': 'What it is',
    'auditor.premises.value': 'Estimated value (RWF)',
    'auditor.premises.remove': 'Remove asset {number}',
    'auditor.premises.add': 'Add an asset',
    'auditor.people.title': 'Directors, owners and managers',
    'auditor.people.lead':
        'Check who owns and runs the business before it lists for the first time.',
    'auditor.people.owners_confirmed':
        'I checked the ID of every director and owner against the RDB register.',
    'auditor.people.management_confirmed':
        'The management profiles match the people I met.',
    'auditor.registration.title': 'Registration and tax',
    'auditor.registration.lead':
        'The RDB certificate and the RRA tax clearance, as you saw them.',
    'auditor.registration.confirmed':
        'The RDB certificate matches the business’s name and code.',
    'auditor.registration.no_tax':
        'The business has no tax clearance certificate',
    'auditor.registration.tax': 'Tax clearance expires',
    'auditor.trading.title': 'Date trading started',
    'auditor.trading.lead':
        'The month the business began trading, from its earliest records.',
    'auditor.trading.since': 'Trading since',
    'auditor.available.title': 'Money available now',
    'auditor.available.lead':
        'The repayment is 7 days late. Record what the business could pay with today.',
    'auditor.available.proofs': 'Proof seen',
    'auditor.available.amount': 'Available now',
    'auditor.available.hint':
        'Cash on hand plus bank and mobile-money balances. Enter 0 if there is none.',
    'auditor.late_reason.title': 'Why it is late',
    'auditor.late_reason.lead':
        'Choose the reason that fits best. Nothing is negotiated on this visit.',
    'auditor.late_reason.option.sales_fell': 'Sales fell',
    'auditor.late_reason.option.customers_owe': 'Customers have not paid',
    'auditor.late_reason.option.unexpected_cost': 'An unexpected cost',
    'auditor.late_reason.option.stock_bought': 'Money went on stock',
    'auditor.late_reason.option.owner_away': 'Owner away or unwell',
    'auditor.late_reason.option.trading_stopped': 'Trading has stopped',
    'auditor.late_reason.option.other': 'Other',
    'auditor.late_reason.note': 'Note (optional)',
    'auditor.late_reason.note_required': 'Say what happened',
    'auditor.drawings.title': 'Owner drawings',
    'auditor.drawings.lead':
        'What the owners took out of the business this month, from the statements and the cash book.',
    'auditor.drawings.amount': 'Taken by the owners this month',
    'auditor.drawings.hint': 'Enter 0 if the owners took nothing.',
    'auditor.loans.title': 'Other loans',
    'auditor.loans.lead':
        'Every loan the business owes anyone other than Rozine, from the agreement or the lender’s statement.',
    'auditor.loans.none':
        'No other loans recorded. Continue if the business owes no one else.',
    'auditor.loans.item': 'Loan {number}',
    'auditor.loans.lender': 'Lender',
    'auditor.loans.outstanding': 'Still owed (RWF)',
    'auditor.loans.monthly_payment': 'Monthly payment (RWF)',
    'auditor.loans.remove': 'Remove loan {number}',
    'auditor.loans.add': 'Add a loan',
    'auditor.audit.guide.staff.do':
        'Record how many people the business employs, from the RSSB declaration.',
    'auditor.audit.guide.staff.enter':
        'One whole number. For example 6. Enter 0 if no one is employed.',
    'auditor.audit.guide.staff.done':
        'A number is entered and the RSSB total page is checked.',
    'auditor.audit.guide.staff.how':
        'Ask for the latest RSSB declaration and read the total from its summary page. Ask the owner to cover the names; Rozine never collects employee details.',
    'auditor.audit.guide.expiries.do':
        'Record when the tax and RSSB clearance certificates expire.',
    'auditor.audit.guide.expiries.enter':
        'Two dates, as printed on each certificate. For example 31/03/2027.',
    'auditor.audit.guide.expiries.done':
        'Both dates are entered, even if one has already passed.',
    'auditor.audit.guide.expiries.how':
        'Read each date from the certificate itself, not from what the owner says. An expired certificate is recorded as you saw it.',
    'auditor.audit.guide.loan_use.do':
        'Say how the Rozine loan money was used this month.',
    'auditor.audit.guide.loan_use.enter':
        'Choose as planned, partly or not as planned. Anything but as planned needs one line, for example: Part paid the October rent.',
    'auditor.audit.guide.loan_use.done':
        'An answer is chosen, with a line saying where the money went if it was not as planned.',
    'auditor.audit.guide.loan_use.how':
        'Compare what the money paid for with the use stated in the prospectus. Check receipts or statements for the larger payments.',
    'auditor.audit.guide.changes.do':
        'Ask about four changes since the last visit.',
    'auditor.audit.guide.changes.enter':
        'Yes or no for each: a new loan, a director change, an ownership change, a legal claim.',
    'auditor.audit.guide.changes.done': 'Every question has a yes or no.',
    'auditor.audit.guide.changes.how':
        'Ask the owner each question plainly and check the registry extract or loan papers where you can. A yes is sealed with the report.',
    'auditor.staff.title': 'Staff count',
    'auditor.staff.lead':
        'How many people the business employs, read from the RSSB total page.',
    'auditor.staff.count': 'People employed',
    'auditor.staff.rssb_checked':
        'I checked this against the RSSB total page, with the names covered.',
    'auditor.expiries.title': 'Tax and RSSB expiry dates',
    'auditor.expiries.lead':
        'The expiry date printed on each clearance certificate.',
    'auditor.expiries.tax': 'Tax clearance expires',
    'auditor.expiries.rssb': 'RSSB clearance expires',
    'auditor.loan_use.title': 'Use of the loan money',
    'auditor.loan_use.lead':
        'Whether the Rozine loan money went where the prospectus said.',
    'auditor.loan_use.option.as_planned': 'As planned',
    'auditor.loan_use.option.partly': 'Partly as planned',
    'auditor.loan_use.option.not_as_planned': 'Not as planned',
    'auditor.loan_use.note': 'Note (optional)',
    'auditor.loan_use.note_required': 'Where did the money go?',
    'auditor.changes.title': 'Changes',
    'auditor.changes.lead': 'Has any of these changed since the last visit?',
    'auditor.changes.kind.new_debt': 'A new loan or debt',
    'auditor.changes.kind.director_change': 'A change of director',
    'auditor.changes.kind.ownership_change':
        'A change of owners or shareholders',
    'auditor.changes.kind.legal_claim': 'A legal claim against the business',
    'auditor.changes.yes': 'Yes',
    'auditor.changes.no': 'No',
    'auditor.audit.guide.seal.do':
        'Check the result, then seal with your authenticator code.',
    'auditor.audit.guide.seal.enter':
        'The six-digit code from your authenticator app.',
    'auditor.audit.guide.seal.done':
        'Every earlier step is complete. Seal stays off until then.',
    'auditor.audit.guide.seal.how':
        'Read the findings once more. When you seal, the report is fixed under your licence and the business has 24 hours to dispute it.',
    'auditor.receipt.title': 'Conflict recorded',
    'auditor.receipt.body.reassignment_pending':
        'Your conflict has been recorded. Work on this assignment is stopped while Audit Operations arranges reassignment.',
    'auditor.receipt.body.reassigned':
        'Your conflict has been recorded and the assignment has been reassigned. You no longer have access to its file.',
    'auditor.receipt.body.recorded':
        'Your conflict has been recorded. Work on this assignment is stopped, and you no longer have access to its file.',
    'auditor.receipt.body.closed':
        'Your conflict has been recorded. Audit Operations has closed this assignment, and you no longer have access to its file.',
    'auditor.receipt.status': 'Assignment',
    'auditor.receipt.state.reassignment_pending': 'Reassignment pending',
    'auditor.receipt.state.reassigned': 'Reassigned',
    'auditor.receipt.state.recorded': 'Recorded',
    'auditor.receipt.state.closed': 'Closed by Audit Operations',
    'auditor.receipt.kind': 'Kind of interest',
    'auditor.receipt.declared': 'Declared',
    'auditor.receipt.note': 'Your explanation',
    'auditor.evidence.title': 'Evidence',
    'auditor.evidence.captured': 'Captured',
    'auditor.evidence.source': 'Source',
    'auditor.evidence.attestation': 'Device attestation',
    'auditor.evidence.position': 'Position',
    'auditor.evidence.accuracy': 'Accuracy',
    'auditor.evidence.metres': '±{metres} m',
    'auditor.evidence.unavailable': 'Unavailable',
    'auditor.evidence.digest': 'SHA-256 {digest}…',
    'auditor.evidence.source_companion_device': 'Capture app',
    'auditor.evidence.source_web_upload': 'Web upload',
    'auditor.evidence.attestation_verified': 'Attested',
    'auditor.evidence.attestation_unverified': 'Not attested',
    'auditor.evidence.attestation_unavailable': 'Unavailable',
    'auditor.evidence.kind.photo': 'Site photo',
    'auditor.evidence.kind.check_in': 'On-site check-in',
    'auditor.evidence.kind.ledger': 'Ledger document',
    'auditor.evidence.kind.statement': 'Statement',
    'auditor.evidence.kind.licence_certificate': 'Licence certificate',
    'auditor.command.checking.title': 'Checking what happened',
    'auditor.command.checking.body':
        'The answer to your last action was lost, so Rozine is looking up whether it was recorded. Nothing is sent again meanwhile.',
    'auditor.command.unconfirmed.title': "We couldn't confirm your last action",
    'auditor.command.unconfirmed.body':
        "Rozine couldn't be reached to check whether it was recorded. Check again before doing anything else — it won't be sent twice.",
    'auditor.command.pending.title': 'Recorded — still processing',
    'auditor.command.pending.body':
        'Rozine has your last action and is still working on it. Check again in a moment.',
    'auditor.command.not_recorded.title': 'Not recorded',
    'auditor.command.not_recorded.body':
        "Rozine has no record of your last action. You can send the identical request again; it can't be applied twice.",
    'auditor.command.check_again': 'Check again',
    'auditor.command.try_again': 'Try again',
    'auditor.command.refused.VERSION_CONFLICT':
        'This record changed since you opened it. The page has been refreshed — check it and try again.',
    'auditor.command.refused.IDEMPOTENCY_CONFLICT':
        'This request was already used for a different action. Start again from the refreshed page.',
    'auditor.command.refused.DIGEST_STALE':
        'The evidence changed after your preview. Review the new preview and confirm with a new code.',
    'auditor.command.refused.EVIDENCE_VERSION_STALE':
        'The evidence changed after your preview. Review the new preview and confirm again.',
    'auditor.command.refused.FINDINGS_VERSION_STALE':
        'The findings changed after your preview. Review the new preview and confirm again.',
    'auditor.command.refused.PROCEDURE_VERSION_STALE':
        'The procedure version changed. Review the new preview and confirm again.',
    'auditor.command.refused.MANDATE_STALE':
        "Your assignment's terms changed. Review the refreshed page and confirm again.",
    'auditor.command.refused.ACTION_FORBIDDEN':
        "You can't do this on this assignment any more. Your access changed.",
    'auditor.command.refused.NOT_FOUND': "This record isn't available to you.",
    'auditor.command.refused.STEP_UP_INVALID':
        "That confirmation didn't go through. Enter a new code from your authenticator.",
    'auditor.command.refused.STEP_UP_EXPIRED':
        'That confirmation expired before the seal. Enter a new code from your authenticator.',
    'auditor.command.refused.denied':
        "Your access changed, so this wasn't done.",
    'auditor.command.refused.failed':
        "This couldn't be done. Refresh the page and try again.",
    'auditor.accreditation.badge.none': 'Not accredited',
    'auditor.accreditation.badge.first_pending': 'Under review',
    'auditor.accreditation.none_line': 'No licence on record',
    'auditor.accreditation.none_body':
        'Submit your ICPAR licence for review. Submitting gives you no standing: you can take work only after an authorized Rozine staff member records the ICPAR check and its dates.',
    'auditor.accreditation.first_pending_title':
        'First accreditation under review',
    'auditor.accreditation.evidence_line':
        'Certificate {id} · SHA-256 {digest}…',
    'auditor.accreditation.expiry_label_first': 'Licence expiry date',
    'auditor.accreditation.first_submit': 'Submit your accreditation',
    'auditor.availability.locked':
        "Your availability can't be changed from here right now.",
    'auditor.home.unavailable': 'Unavailable',
    'auditor.availability.home_paused_locked':
        'Dispatch is not offering you flash audits',
    'business.grow.starting': 'Starting…',
    'auditor.conflict.not_allowed':
        "You can no longer declare on this file, so your declaration hasn't been sent.",
    'auditor.jobs.accept_due': 'Accept · due {time}',
    'auditor.jobs.accept_plain': 'Accept',
    'auditor.jobs.offer_open': 'Offer open until {time} · {left} left',
    'auditor.jobs.offer_label': 'Time left to accept this offer',
    'auditor.jobs.offer_closed': 'This offer has closed',
    'auditor.command.refused.ASSIGNMENT_ACCEPTANCE_EXPIRED':
        "This offer closed before your acceptance reached Rozine, so it wasn't accepted. The page has been refreshed.",
    'auditor.standing.reason.ACCREDITATION_REQUIRED':
        "You don't have an approved accreditation yet.",
    'auditor.command.refused.ACCREDITATION_REQUIRED':
        "You don't have an approved accreditation yet. Dispatch can't offer you work until then, so this wasn't changed.",
    'auditor.standing.reason.ACCREDITATION_EXPIRED':
        'Your licence has expired. Renew it to receive work again.',
    'auditor.command.refused.ACCREDITATION_EXPIRED':
        "Your licence has expired. Renew it to receive work again. Dispatch can't offer you work until then, so this wasn't changed.",
    'auditor.standing.reason.ACCREDITATION_SUSPENDED':
        'Your accreditation is suspended by Audit Operations.',
    'auditor.command.refused.ACCREDITATION_SUSPENDED':
        "Your accreditation is suspended by Audit Operations. Dispatch can't offer you work until then, so this wasn't changed.",
    'auditor.standing.reason.STANDING_CHECK_REQUIRED':
        'A standing check by Audit Operations is due.',
    'auditor.command.refused.STANDING_CHECK_REQUIRED':
        "A standing check by Audit Operations is due. Dispatch can't offer you work until then, so this wasn't changed.",
    'auditor.standing.paused_until_restored':
        'Dispatch is paused until your standing is restored. Your choice to accept audits is kept.',
    'auditor.standing.turning_on':
        "Turning this on won't bring offers until your standing is restored.",
    'auditor.accreditation.licence_title': 'Practising licence',
    'auditor.accreditation.view_certificate':
        'Download the certificate on record',
    'auditor.accreditation.view_submitted':
        'Download the submitted certificate',
    'auditor.seal.save_note': 'Save note',
    'auditor.seal.save_note_preview': 'Save note to preview',
    'auditor.seal.saving_note': 'Saving note…',
    'auditor.seal.note_unsaved':
        'Save your note before you preview. The preview, your code and the seal all cover the saved note.',
    'auditor.ledger.reported_undeclared': 'Not declared',
    'auditor.ledger.reported_undeclared_note':
        'The business has not declared a stock value, so there is no reported figure to compare your count with. Record what you counted.',
    'auditor.ledger.reconciles_undeclared':
        'Reconciliation stays blocked until the business declares its stock.',
    'auditor.statements.cover_unavailable': 'Unavailable',
    'auditor.statements.documents': 'Source documents',
    'auditor.statements.no_documents':
        'No source documents are on file for this month.',
    'auditor.count.period_unavailable': 'Unavailable',
    'auditor.file.start': 'Start the audit',
    'auditor.file.starting': 'Starting…',
    'auditor.file.application_unavailable':
        'No submitted application is available to audit yet.',
    'auditor.command.refused.APPLICATION_VERSION_CONFLICT':
        "The business's application changed since you opened this file. The page has been refreshed — check it and start again.",
    'auditor.command.refused.APPLICATION_NOT_SUBMITTED':
        "This application hasn't been submitted, so there is nothing to audit yet. The page has been refreshed.",
    'auditor.command.refused.APPLICATION_NOT_FOUND':
        'This application is no longer available to you, so nothing was started.',
    'auditor.command.refused.AUDIT_APPLICATION_BOUND':
        'A report is already bound to this application, so no new one was started. The page has been refreshed — continue from there.',
    'auditor.command.refused.AUDIT_REPORT_REASSIGNMENT_REQUIRED':
        'This report has to be reassigned before work on it can continue, so nothing was started. The page has been refreshed.',
    'auditor.evidence.source_isolated_synthetic':
        'Synthetic test evidence (isolated)',
    'auditor.ledger.file_type': 'Choose the ledger as a PDF or CSV file.',
    'auditor.ledger.file_size':
        'This file is larger than 10 MB. Upload a PDF or CSV of 10 MB or less.',
    'auditor.seal.note_unsaved_unsealable':
        "Your note isn't saved yet. Save it now; sealing opens once the report is ready for it.",
    'auditor.capture.synthetic':
        'Synthetic test evidence (isolated) — not a native capture.',
    'auditor.returned.title.changes_requested': 'Changes requested',
    'auditor.returned.title.rejected': 'Filing rejected',
    'auditor.returned.lead.changes_requested':
        'This filing went back to the business with the reason below. The report is kept exactly as it was returned.',
    'auditor.returned.lead.rejected':
        "This filing version could not be verified, for the reason below. This concerns the filing, not the business's credit. The report is kept exactly as it was rejected.",
    'auditor.returned.reason': 'Reason',
    'auditor.returned.recorded': 'Recorded',
    'auditor.returned.amend': 'Start a linked amendment',
    'auditor.returned.amended_by':
        'An amendment {report} was started from this report.',
    'auditor.returned.view_amendment': 'View the amendment',
    'auditor.reason.count': '{count} / {max}',
    'auditor.command.refused.AUDIT_REPORT_DECISION_NOT_ALLOWED':
        'This report can no longer be returned or rejected, so nothing was recorded. The page has been refreshed.',
    'auditor.command.refused.AUDIT_REPORT_NOT_AMENDABLE':
        "This report can't be amended now, so no amendment was started. The page has been refreshed.",
    'auditor.ledger.download': 'Download original',
    'auditor.sealed.body_undated':
        'The report is sealed and can no longer be edited. {party} still needs to co-sign; it publishes to holders after that.',
    'auditor.sealed.unavailable':
        "This seal can't be verified right now — its signing key is no longer current. The sealed record and its history are unchanged.",
    'auditor.sealed.body_published':
        'Sealed and co-signed; published to holders on {date}.',
    'auditor.sealed.body_signed':
        'The report is sealed and {party} has co-signed it. It publishes to holders next.',
    'auditor.sealed.body_declined':
        'The report is sealed. {party} disputed it rather than co-signing, so it is not published.',
    'auditor.sealed.body_overdue':
        "The report is sealed, but {party}'s co-signing window has passed. It can no longer be co-signed and is not published; nothing is approved automatically.",
    'auditor.command.refused.AUDIT_PROCEDURE_SOURCE_CHANGED':
        'A source changed after your preview. Go back to review it, then preview again before sealing.',
    'auditor.sealed.body_amended':
        "You amended this report, so it won't be co-signed or published. The amendment replaces it.",
    'settlement.notice.checking.title': 'Checking what happened',
    'settlement.notice.checking.body':
        "The answer didn't reach us, so we're looking up whether this was recorded. Nothing is sent again meanwhile.",
    'settlement.notice.unconfirmed.title': 'Not yet confirmed',
    'settlement.notice.unconfirmed.body':
        "We couldn't reach the server to confirm this. Nothing has been sent again. Check again once you're connected.",
    'settlement.notice.pending.title': 'Recorded, not yet confirmed',
    'settlement.notice.pending.body':
        "The server recorded this request and is still working on it. This page shows the result once it's confirmed.",
    'settlement.notice.not_recorded.title': 'Nothing was recorded',
    'settlement.notice.not_recorded.body':
        'The server has no record of this request. We refreshed the details, and you can send the same request again.',
    'settlement.notice.check_again': 'Check again',
    'settlement.notice.try_again': 'Send the same request again',
    'settlement.notice.no_longer_allowed':
        'Nothing was recorded, and this action is no longer available with the current details.',
    'settlement.poll.stopped':
        "Not yet confirmed. We've stopped checking automatically — refresh to see the latest.",
    'settlement.poll.refresh': 'Refresh',
    'settlement.refusal.other':
        'This request was refused. Refresh and try again.',
    'settlement.refusal.VALIDATION_FAILED':
        'Some details need correcting before this can go ahead.',
    'settlement.refusal.EXPOSURE_LIMIT':
        'This would take you over one of your investment limits.',
    'settlement.refusal.INSUFFICIENT_AVAILABLE_FUNDS':
        "Your available balance doesn't cover this amount.",
    'settlement.refusal.VERSION_CONFLICT':
        'Something changed since this page loaded. We refreshed the details — check them and try again.',
    'settlement.refusal.IDEMPOTENCY_CONFLICT':
        'This request was already used for something else. Start again from the current details.',
    'settlement.refusal.RESERVATION_EXPIRED':
        'Your 5-minute hold ended and its notes were released. Reserve again to continue.',
    'settlement.refusal.CAMPAIGN_CLOSED': 'This raise is no longer open.',
    'settlement.refusal.UNITS_UNAVAILABLE':
        'Those notes are no longer available. Choose fewer notes or try again later.',
    'settlement.refusal.COMMITMENT_LOCKED':
        'The raise is fully funded, so this can no longer be cancelled.',
    'settlement.refusal.CAMPAIGN_SETTLEMENT_REQUIRED':
        "Investors have already committed to this raise, so it can't be cancelled here. Their commitments have to be settled first.",
    'settlement.refusal.CAMPAIGN_FUNDED':
        "This raise is funded and awaiting disbursement, so it can't be cancelled.",
    'settlement.refusal.COMMITMENT_WITHDRAWN':
        "You've already withdrawn this commitment. Its amount is back in Available.",
    'settlement.refusal.NOTE_INELIGIBLE': "This note isn't eligible right now.",
    'settlement.refusal.DISCLOSURE_STALE':
        'The disclosure changed. Read the current version and acknowledge it again.',
    'settlement.refusal.POLICY_INPUT_REQUIRED':
        "This isn't available yet: a required policy hasn't been set.",
    'settlement.refusal.DEPOSIT_METHOD_UNVERIFIED':
        "That account isn't verified for deposits yet.",
    'settlement.refusal.WITHDRAWAL_METHOD_UNVERIFIED':
        'Choose a verified account to withdraw to.',
    'settlement.refusal.EXPOSURE_RESERVATION_REQUIRED':
        "No borrowing reservation is on record for this application, so it can't be listed.",
    'settlement.refusal.SIGNATURES_REQUIRED':
        'Not every required signatory has signed at Review.',
    'settlement.refusal.STAFF_RELEASE_REQUIRED':
        "Rozine staff haven't released this application yet.",
    'settlement.refusal.TERMS_CHANGED': 'The terms changed since you signed.',
    'settlement.refusal.FEE_DISCLOSURE_CHANGED':
        'The listing fee disclosure changed. Read the current one before you publish.',
    'settlement.refusal.QUOTE_STALE': 'Your quote is no longer current.',
    'settlement.refusal.APPLICATION_ALREADY_RELEASED':
        'This application has already been released.',
    'settlement.refusal.LISTING_ALREADY_PUBLISHED':
        'This listing is already published.',
    'settlement.refusal.NOTHING_DUE':
        'Nothing is due on this note right now. The page has been refreshed.',
    'settlement.refusal.NOTE_NOT_SERVICING':
        "This note isn't being repaid, so there's nothing to pay.",
    'settlement.refusal.APPLICATION_NOT_RELEASED':
        "This application hasn't been released for listing yet.",
    'settlement.refusal.DISBURSEMENT_IN_FLIGHT':
        'The payment intent is already recorded, so this can no longer change it.',
    'settlement.refusal.PROVIDER_OUTCOME_UNRESOLVED':
        "The provider's outcome isn't resolved yet, so this is blocked.",
    'settlement.refusal.ACTION_FORBIDDEN':
        "You can't do this with your current access.",
    'settlement.refusal.IDENTITY_VERIFICATION_REQUIRED':
        'Verify your identity to invest.',
    'settlement.refusal.RESTRICTION_ACTIVE':
        "A restriction is in place, so this isn't available right now.",
    'settlement.refusal.CONNECTED_PARTY':
        "You're connected to this business, so you can't take part.",
    'settlement.refusal.SELF_APPROVAL_FORBIDDEN':
        "A different staff member must do this: you can't check your own action.",
    'settlement.refusal.STEP_UP_REQUIRED':
        'A fresh step-up confirmation is required first.',
    'settlement.refusal.STEP_UP_INVALID':
        "That confirmation didn't go through. Enter a new code from your authenticator.",
    'settlement.refusal.STEP_UP_EXPIRED':
        'That confirmation expired. Enter a new code from your authenticator.',
    'settlement.refusal.MANDATE_REQUIRED':
        "This needs someone authorised under the business's mandate.",
    'settlement.refusal.STAFF_ACCESS_REQUIRED': 'Staff access is required.',
    'settlement.refusal.STAFF_PERMISSION_REQUIRED':
        "Your staff permissions don't include this action.",
    'settlement.refusal.STAFF_VERIFIED_EMAIL_AND_MFA_REQUIRED':
        'Verify your email and turn on two-factor authentication to continue.',
    'settlement.refusal.MFA_REQUIRED': 'Two-factor authentication is required.',
    'settlement.refusal.NOT_FOUND': 'You can no longer view this record.',
    'settlement.refusal.CART_REVISION_CONFLICT':
        'Your cart changed. We refreshed it — check it and try again.',
    'settlement.refusal.CART_LINES_REFUSED':
        "Some deals in your cart can't be bought right now, so nothing was held.",
    'settlement.refusal.CART_QUOTE_STALE':
        'The terms changed since we priced your cart. Check the new total and try again.',
    'settlement.refusal.CART_QUOTE_EXPIRED':
        'Your cart price expired. We priced it again — check it and try again.',
    'settlement.refusal.CART_QUOTE_NOT_FOUND':
        "We couldn't find that price for your cart. Try again.",
    'settlement.refusal.CART_CHECKOUT_ACTIVE':
        'Your cart is held for checkout. Confirm or release it first.',
    'settlement.refusal.CART_EXISTING_RESERVATION':
        'You already hold notes in one of these deals. Confirm or release that hold first.',
    'settlement.refusal.CART_RESERVATION_EXPIRED':
        'Your hold ended before you confirmed. Check out again.',
    'settlement.refusal.CART_RESERVATION_NOT_HELD':
        'This checkout is no longer held.',
    'settlement.refusal.CART_RESERVATION_NOT_FOUND':
        "We couldn't find this checkout.",
    'settlement.refusal.CART_LINE_UNAVAILABLE':
        'A deal in your cart is no longer open. Remove it to check out.',
    'settlement.refusal.CART_UNITS_ABOVE_LIMIT':
        'Some deals are above what you can take now. Adjust them and try again.',
    'settlement.refusal.CART_EMPTY': 'Your cart is empty.',
    'settlement.refusal.RISK_ACKNOWLEDGEMENT_REQUIRED':
        'Confirm the risk statements before your first investment.',
    'investor.wallet.c3.available_balance': 'Available balance',
    'investor.wallet.c3.total': 'Wallet total',
    'investor.wallet.c3.bucket.available': 'Available',
    'investor.wallet.c3.bucket.held': 'Held',
    'investor.wallet.c3.bucket.committed': 'Committed',
    'investor.wallet.c3.spendable':
        'Only Available can be spent. Held is in a live checkout; Committed awaits issue.',
    'investor.wallet.c3.restricted':
        'A restriction has applied since {date}. Deposits still work.',
    'investor.wallet.c3.restricted_unavailable':
        'A restriction has applied since {date}. Deposits are not currently available.',
    'investor.wallet.c3.no_pending': 'Instantly available to invest',
    'investor.wallet.c3.pending_deposits':
        '{amount} not yet confirmed — not in the total',
    'investor.wallet.c3.no_policy':
        "Deposits aren't available yet: no deposit policy has been set.",
    'investor.wallet.c3.credited_on_success': 'Credited once confirmed',
    'investor.wallet.c3.policy_minimum': 'Minimum {amount}',
    'investor.wallet.c3.policy_maximum': 'Maximum {amount}',
    'investor.wallet.c3.deposit_between':
        'Enter an amount between {minimum} and {maximum}.',
    'investor.wallet.c3.deposit_at_least': 'Enter at least {amount}.',
    'investor.wallet.c3.deposit_at_most': 'Enter at most {amount}.',
    'investor.wallet.c3.deposit_unavailable':
        "Deposits aren't open on this account yet. Rozine turns them on once the account is cleared for money movement.",
    'investor.wallet.c3.intent_only':
        'This records your deposit request. Nothing is credited until the payment is confirmed.',
    'investor.wallet.c3.holds': 'Held for checkout',
    'investor.wallet.c3.hold_line': {
        one: '{name} · {count} note',
        other: '{name} · {count} notes',
    },
    'investor.wallet.c3.hold_expires': 'Released in {time} unless you confirm',
    'investor.hold_float.label':
        'Held for checkout, {time} left. Open checkout',
    'investor.hold_float.continue': 'Continue to checkout',
    'investor.hold_float.more': {
        one: '+{count} more hold in your wallet',
        other: '+{count} more holds in your wallet',
    },
    'investor.wallet.c3.deposits': 'Deposits',
    'investor.wallet.c3.deposit_from': 'Deposit · {method}',
    'investor.wallet.c3.intent.pending': 'Not yet confirmed',
    'investor.wallet.c3.intent.unknown':
        "Not yet confirmed — we're checking with the provider",
    'investor.wallet.c3.intent.succeeded': 'Credited',
    'investor.wallet.c3.intent.failed': "Didn't go through — nothing credited",
    'investor.wallet.c3.entry.deposit_in': 'Deposit · {counterparty}',
    'investor.wallet.c3.entry.deposit_out': 'Transfer to {counterparty}',
    'investor.wallet.c3.entry.hold': 'Held for {name}',
    'investor.wallet.c3.entry.hold_release': 'Hold released · {name}',
    'investor.wallet.c3.entry.commitment': 'Investment · {name}',
    'investor.wallet.c3.entry.commitment_refund': 'Refund · {name}',
    'investor.wallet.c3.movement': 'Movement type',
    'investor.wallet.c3.period': 'Time range',
    'investor.wallet.c3.range.today': 'Today',
    'investor.wallet.c3.range.7d': '7 days',
    'investor.wallet.c3.range.30d': '30 days',
    'investor.wallet.c3.range.all': 'All time',
    'investor.wallet.c3.range.all_short': 'All',
    'investor.wallet.c3.filter.external': 'Cash in and out',
    'investor.wallet.c3.filter.internal': 'Holds and commitments',
    'investor.wallet.c3.transfer': '{from} → {to}',
    'investor.wallet.c3.history_loading': 'Loading transaction history…',
    'investor.wallet.c3.older': 'Show older',
    'investor.wallet.c3.receipt_policy': 'Policy version',
    'investor.wallet.c3.not_credited':
        'Recorded, not yet confirmed. Nothing has been credited; it will be only once the payment is confirmed.',
    'investor.wallet.c3.not_credited_failed':
        'The payment was confirmed as not completed. Nothing was credited.',
    'investor.wallet.c3.credit_receipt': 'Credit receipt',
    'investor.wallet.c3.funding': 'Move money',
    'investor.wallet.c3.withdraw_needs_account':
        "Withdrawals aren't open yet: this account has no verified payout account. Link one in your own name, then Rozine opens withdrawals once the account is cleared for money movement.",
    'investor.wallet.c3.withdraw_not_cleared':
        "Your payout account is verified. Withdrawals open once Rozine clears this account for money movement; you don't need to do anything else.",
    'investor.wallet.c3.withdraw_linking_closed':
        "Withdrawals aren't open yet. Rozine hasn't opened payout accounts for this wallet, so there is nothing to link or do now. Your balance stays safe here until it does.",
    'investor.wallet.c3.withdraw_not_allowed':
        'Your payout account is verified, but withdrawals from this wallet are not open to you right now. Contact Rozine support if you need to withdraw.',
    'investor.wallet.c3.withdraw_link_account': 'Go to Linked accounts',
    'investor.wallet.c3.withdraw_at_least': 'Minimum {amount}.',
    'investor.wallet.c3.withdraw_at_most': 'Maximum {amount} at a time.',
    'investor.wallet.c3.withdraw_over_available':
        'More than your available {amount}.',
    'investor.wallet.c3.withdraw_available': 'Available to withdraw: {amount}',
    'investor.wallet.c3.provider_charge': 'Provider charge',
    'investor.wallet.c3.withdraw_terms':
        'Withdraw between {minimum} and {maximum} at a time. Terms {version}.',
    'investor.wallet.c3.withdraw_only_on_success':
        'The amount leaves your available balance now and is paid out once the provider confirms. If the payout fails, it comes back to your wallet.',
    'investor.wallet.c3.withdrawals': 'Withdrawals',
    'investor.wallet.c3.withdrawal_to': 'Withdrawal · {method}',
    'investor.wallet.c3.withdrawal.pending': 'Sending',
    'investor.wallet.c3.withdrawal.unknown':
        "Sending — we're checking with the provider",
    'investor.wallet.c3.withdrawal.succeeded': 'Paid out',
    'investor.wallet.c3.withdrawal.failed':
        "Didn't go through — returned to your wallet",
    'investor.wallet.c3.withdrawal_waiting':
        'Held for this withdrawal until the provider confirms. It stays out of your available balance meanwhile.',
    'investor.wallet.c3.settled_receipt.WITHDRAWAL_PAID': 'Payout receipt',
    'investor.wallet.c3.settled_receipt.WITHDRAWAL_RETURNED': 'Return receipt',
    'investor.wallet.c3.entry.withdrawal_in': 'Returned from {counterparty}',
    'investor.wallet.c3.entry.withdrawal_out': 'Withdrawal · {counterparty}',
    'business.publish.intro':
        "{title} goes live for investors once every step below is met. Publishing uses the signatures you gave at Review, so there's nothing more to sign here.",
    'business.publish.release.awaiting':
        'Awaiting Rozine staff review. Staff release an application only when the rating engine, the signing authority and the audit report all pass.',
    'business.publish.release.released':
        'Released for listing by Rozine staff.',
    'business.publish.release.refused': 'Not released for listing',
    'business.publish.cause.ENGINE_GATE_FAILED':
        "The rating engine's credit gates don't pass.",
    'business.publish.cause.AUTHORITY_CHANGED':
        "The company's signing authority changed since you signed.",
    'business.publish.cause.REPORT_NOT_CURRENT':
        'The audit report is no longer current.',
    'business.publish.cause.EXPOSURE_RESERVATION_REQUIRED':
        "No borrowing reservation is on record for this application, so it can't be listed.",
    'business.publish.cause.SIGNATURES_REQUIRED':
        'Not every required signatory has signed at Review.',
    'business.publish.cause.STAFF_RELEASE_REQUIRED':
        "Rozine staff haven't released this application yet.",
    'business.publish.cause.TERMS_CHANGED':
        'The terms changed since you signed.',
    'business.publish.cause.FEE_DISCLOSURE_CHANGED':
        'The listing fee disclosure changed. Read the current one before you publish.',
    'business.publish.cause.QUOTE_STALE': 'Your quote is no longer current.',
    'business.publish.cause.RESTRICTION_ACTIVE':
        'A restriction applies to this business.',
    'business.publish.cause.RELEASE_CHECK_NOT_COMPLETED':
        "A release check couldn't be completed, so the application can't be released yet.",
    'business.publish.cause.other': "A release check didn't pass.",
    'business.publish.prerequisites': 'Before you publish',
    'business.publish.prerequisite.staff_release':
        'Released by Rozine staff after review',
    'business.publish.prerequisite.signatures_retained':
        'Your Review signatures are on record',
    'business.publish.prerequisite.quote_current':
        'Quote unchanged since you signed',
    'business.publish.prerequisite.terms_current':
        'Terms unchanged since you signed',
    'business.publish.met': 'Done',
    'business.publish.not_met': 'Not yet',
    'business.publish.fee_label': 'Listing fee',
    'business.publish.fee_waived': 'Waived for the MVP',
    'business.publish.disclosure_title': 'Fee disclosure',
    'business.publish.disclosure_version': 'Disclosure {version}',
    'business.publish.changed':
        'The quote or terms changed since you signed. Review them and sign again before publishing.',
    'business.publish.review_again': 'Review and sign again',
    'business.publish.blocked':
        'Publishing opens once every step above is met.',
    'business.publish.published.title': 'Listed for investors',
    'business.publish.published.body':
        '{title} is live on the Investor feed. Nothing was charged.',
    'business.publish.published.receipt': 'Listing receipt',
    'business.publish.published.reference': 'Reference',
    'business.publish.published.recorded': 'Recorded',
    'business.publish.published.disclosure': 'Fee disclosure',
    'business.publish.published.campaign': 'View campaign',
    'business.publish.published.home': 'Back to Home',
    'business.campaign.investors.title': 'Investors',
    'business.campaign.investors.all': 'View all {count} ›',
    'business.campaign.investors.fewer': 'Show fewer',
    'business.campaign.investors.raised': 'Total raised',
    'business.campaign.investors.holders': 'Holders',
    'business.campaign.investors.average': 'Avg ticket',
    'business.campaign.investors.mix': 'Holder mix',
    'business.campaign.investors.by_capital': 'by capital',
    'business.campaign.investors.kinds.person': 'Individuals',
    'business.campaign.investors.kinds.organization': 'Institutions',
    'business.campaign.investors.kind.person': 'Individual investor',
    'business.campaign.investors.kind.organization': 'Institutional investor',
    'business.campaign.investors.notes': {
        one: '{count} note',
        other: '{count} notes',
    },
    'business.campaign.investors.privacy':
        "Investors are not named here: their identities are protected under Rozine's privacy terms.",
    'business.campaign.state.live': 'Active',
    'business.campaign.state.fully_reserved': 'Fully reserved',
    'business.campaign.state.funded': 'Funded',
    'business.campaign.state.funded_pending_disbursement':
        'Funded · awaiting disbursement',
    'business.campaign.state.disbursing': 'Paying out',
    'business.campaign.state.issued': 'Notes issued',
    'business.campaign.state.expired': "Didn't fill",
    'business.campaign.state.cancelled': 'Cancelled',
    'business.campaign.state.failed_closing': 'Closed and refunded',
    'business.campaign.state.sold_out_pending_settlement': 'Fully committed',
    'business.campaign.state.inventory_unavailable': 'No notes available',
    'business.campaign.state.closing_pending_settlement': 'Closing',
    'business.campaign.state.unavailable': 'Status unavailable',
    'business.campaign.lifecycle.live': 'Raising · live',
    'business.campaign.lifecycle.fully_reserved': 'Raising · fully reserved',
    'business.campaign.lifecycle.sold_out_pending_settlement':
        'Fully committed · awaiting settlement',
    'business.campaign.lifecycle.inventory_unavailable':
        'Raising · no notes available',
    'business.campaign.lifecycle.closing_pending_settlement':
        'Deadline passed · closing',
    'business.campaign.restriction.RESTRICTION_ACTIVE':
        "Restricted since {date}. New commitments are paused while the restriction lasts; what's already committed stays.",
    'business.campaign.restriction.NOTE_INELIGIBLE':
        "Not eligible for new commitments since {date}; what's already committed stays.",
    'business.campaign.tile.committed': 'Committed',
    'business.campaign.tile.of_target': 'Of target',
    'business.campaign.tile.refunded': 'Refunded',
    'business.campaign.tracker.title': 'Commitment tracker',
    'business.campaign.tracker.committed_pct': '{pct}% committed',
    'business.campaign.not_yet_committed': 'Not yet committed or reserved',
    'business.campaign.closing_now': 'Closing',
    'business.campaign.committed': 'Committed',
    'business.campaign.reserved': 'Reserved',
    'business.campaign.reserved_note':
        "Notes held in an investor's checkout. They aren't confirmed yet.",
    'business.campaign.units':
        '{committed} of {total} notes committed · {reserved} reserved · {available} available · {unavailable} unavailable',
    'business.campaign.closes': 'Closes {date}',
    'business.campaign.fully_reserved':
        "Every available note is currently held in a checkout. New investors can't reserve right now.",
    'business.campaign.unavailable_note':
        "Unavailable notes were in a checkout or commitment that has ended. They aren't on sale.",
    'business.campaign.sold_out':
        "Every note is committed, so new investors can't join. The raise isn't funded until Rozine completes settlement.",
    'business.campaign.inventory_unavailable':
        "No notes are available to reserve and none are held in a checkout, so new investors can't commit right now.",
    'business.campaign.closing':
        "The deadline has passed, so new investors can't commit. Rozine is closing the raise and will show the outcome here.",
    'business.campaign.progress_unavailable':
        "This raise's progress can't be shown right now. Refresh the page to try again.",
    'business.campaign.tile.deadline': 'Deadline',
    'business.campaign.deadline_passed': 'Deadline passed {date}',
    'business.campaign.funded':
        'Fully funded on {date}. The raise can no longer be cancelled.',
    'business.campaign.closing_title': 'Disbursement',
    'business.campaign.closing.awaiting.title': 'Awaiting disbursement',
    'business.campaign.closing.awaiting.body':
        "Rozine is preparing the payment to your account. We'll show it here once it's sent and confirmed.",
    'business.campaign.closing.in_flight.title': 'Not yet confirmed',
    'business.campaign.closing.in_flight.pending':
        "The payment to your account is in progress. We'll show the disbursement here once it's confirmed.",
    'business.campaign.closing.in_flight.unknown':
        "The payment's outcome hasn't been confirmed yet. Nothing is final until it is; we'll show it here once it's confirmed.",
    'business.campaign.disbursed': '{amount} was disbursed to {destination}.',
    'business.campaign.disbursed_amount': 'Disbursed',
    'business.campaign.destination': 'To',
    'business.campaign.effective_at': 'Effective',
    'business.campaign.effective_date': 'Schedule date (Kigali)',
    'business.campaign.receipt.title': 'Disbursement receipt',
    'business.campaign.receipt.amount': 'Amount',
    'business.campaign.receipt.reference': 'Reference',
    'business.campaign.receipt.recorded': 'Recorded',
    'business.campaign.receipt.view': 'View receipt',
    'business.campaign.closed.expired':
        'This raise closed on {date} before it was fully funded. {amount} went back to investors in full, without fee.',
    'business.campaign.closed.cancelled':
        'This raise was cancelled on {date}. {amount} went back to investors in full, without fee.',
    'business.campaign.closed.failed_closing':
        'This raise closed on {date} without paying out. {amount} went back to investors in full, without fee.',
    'business.campaign.closed.expired_none':
        'This raise closed on {date} before any investor committed, so there was nothing to refund.',
    'business.campaign.closed.cancelled_none':
        'This raise was cancelled on {date} before any investor committed, so there was nothing to refund.',
    'business.campaign.closed.failed_closing_none':
        'This raise closed on {date} without paying out. No investor had committed, so there was nothing to refund.',
    'business.campaign.cancel.open': 'Cancel this raise',
    'business.campaign.cancel.cancelling': 'Cancelling…',
    'business.campaign.cancel.title': 'Cancel this raise?',
    'business.campaign.cancel.body':
        "The raise closes for good and stops taking investors. This can't be undone.",
    'business.campaign.cancel.reason': 'Reason (optional)',
    'business.campaign.cancel.confirm': 'Cancel raise',
    'business.campaign.cancel.keep': 'Keep raising',
    'admin.disbursements.col.provider': 'Provider',
    'admin.disbursements.state.queued': 'Intent recorded · queued',
    'admin.disbursements.state.succeeded': 'Paid · reconciled',
    'admin.disbursements.state.failed_closing': 'Failed closing · refunded',
    'admin.disbursements.provider.none': 'Not sent',
    'admin.disbursements.provider.pending': 'Pending · not yet confirmed',
    'admin.disbursements.provider.unknown': 'Unknown · not yet confirmed',
    'admin.disbursements.provider.succeeded': 'Succeeded · verified',
    'admin.disbursements.provider.failed': 'Failed · verified',
    'admin.disbursements.older': 'Older disbursements',
    'admin.disbursements.deadline': 'Deadline',
    'admin.disbursements.deadline_unavailable': 'No deadline sourced',
    'admin.disbursements.causes': 'Causes',
    'admin.disbursements.receipt.code': 'Receipt',
    'admin.disbursements.receipt.reference': 'Reference',
    'admin.disbursements.receipt.amount': 'Amount',
    'admin.disbursements.receipt.recorded_at': 'Recorded at',
    'admin.disbursements.receipt.revision': 'Revision',
    'admin.disbursements.check.not_run': 'Not run yet',
    'admin.disbursements.check.passed': 'Passed',
    'admin.disbursements.check.failed': 'Failed',
    'admin.disbursements.precheck.title': 'Precheck',
    'admin.disbursements.checked_at': 'Checked at',
    'admin.disbursements.policy_version': 'Policy version',
    'admin.disbursements.intent.title': 'Payment intent',
    'admin.disbursements.intent.not_payment':
        'Intent recorded — not a payment. The payment worker sends it only after its own recheck.',
    'admin.disbursements.intent.not_sent':
        "Not sent yet: the worker hasn't sent this payment.",
    'admin.disbursements.operation': 'Operation',
    'admin.disbursements.dispatch.title': 'Dispatch',
    'admin.disbursements.dispatch.sent_at': 'Sent at',
    'admin.disbursements.dispatch.recheck': 'Worker recheck',
    'admin.disbursements.outcome.title': 'Provider outcome',
    'admin.disbursements.outcome.pending':
        "Pending: the provider hasn't confirmed an outcome. This is not paid and not failed.",
    'admin.disbursements.outcome.unknown':
        "Unknown: the provider's outcome isn't confirmed. This is not paid and not failed.",
    'admin.disbursements.outcome.succeeded':
        'Succeeded: the provider verified the payment.',
    'admin.disbursements.outcome.failed':
        'Failed: the provider verified a final failure.',
    'admin.disbursements.outcome.failed_unreconciled':
        'Not reconciled yet: nothing is closed or refunded until the failure is reconciled.',
    'admin.disbursements.outcome.exception':
        "Reconciliation exception: the provider's answer conflicts or can't be resolved. This disbursement stays blocked and is not reconciled.",
    'admin.disbursements.outcome.reference': 'Provider reference',
    'admin.disbursements.outcome.error_code': 'Error code',
    'admin.disbursements.outcome.observed_at': 'Observed at',
    'admin.disbursements.outcome.effective_at': 'Effective at',
    'admin.disbursements.outcome.reconciliation': 'Reconciliation',
    'admin.disbursements.outcome.reconciled_at': 'Reconciled at',
    'admin.disbursements.outcome.requery_note':
        'Asking the provider again queries this same operation. It never sends the payment again. A scheduled reconciler asks too.',
    'admin.disbursements.reconciliation.unreconciled': 'Not reconciled yet',
    'admin.disbursements.reconciliation.matched': 'Reconciled',
    'admin.disbursements.reconciliation.exception':
        'Exception · blocked, not reconciled',
    'admin.disbursements.hold.title': 'Hold',
    'admin.disbursements.hold.release_note':
        'Releasing the hold lets automatic payment run again. Any authorised staff member may release it, including the one who placed it.',
    'admin.disbursements.issue.title': 'Issue',
    'admin.disbursements.issue.holdings': 'Holdings issued',
    'admin.disbursements.issue.issued_at': 'Issued at',
    'admin.disbursements.issue.effective_date': 'Effective date (Kigali)',
    'admin.disbursements.refund.title': 'Refunds',
    'admin.disbursements.refund.commitments': 'Commitments refunded',
    'admin.disbursements.refund.total': 'Total refunded',
    'admin.disbursements.ledger': 'Open in the ledger',
    'admin.disbursements.attention_count': '{count} need attention',
    'admin.disbursements.automation.title': 'Automatic payment',
    'admin.disbursements.automation.payout': 'Business receives',
    'admin.disbursements.automation.rule': 'Rule',
    'admin.disbursements.automation.waiting.verified_payout_destination':
        "Waiting for a verified payout destination. Payment starts automatically once the business's destination is verified.",
    'admin.disbursements.automation.waiting.next_run':
        'Payment starts automatically on the next run.',
    'admin.disbursements.command.release_hold': 'Release hold',
    'admin.disbursements.command.requery': 'Ask the provider again',
    'admin.disbursements.stage.release_hold.title': 'Release this hold',
    'admin.disbursements.stage.release_hold.body':
        'Automatic payment runs again once the hold is released. It goes back to where it was before the hold.',
    'admin.disbursements.stage.release_hold.cta': 'Release hold',
    'admin.disbursements.stage.release_hold.placeholder':
        'e.g. The business confirmed its new MoMo number.',
    'admin.disbursements.stage.requery.title': 'Ask the provider again',
    'admin.disbursements.stage.requery.body':
        "This asks the provider about the same operation. It never sends the payment again, and it doesn't mark anything reconciled: the answer goes to reconciliation like any other.",
    'admin.disbursements.stage.requery.cta': 'Ask the provider',
    'admin.disbursements.stage.requery.placeholder':
        "e.g. The provider's status page shows the outage is over.",
    'admin.applications.release.title': 'Release for listing',
    'admin.applications.release.state.awaiting_staff_review':
        'Awaiting staff review',
    'admin.applications.release.state.released': 'Released',
    'admin.applications.release.state.refused': 'Refused',
    'admin.applications.release.explain':
        "Releasing lets the business publish this raise. It needs the engine, authority and report checks to pass, and it can't override a failed check.",
    'admin.applications.release.gates': 'Release checks',
    'admin.applications.release.gate.engine': 'Underwriting engine',
    'admin.applications.release.gate.authority': 'Business authority',
    'admin.applications.release.gate.report': 'Listing audit report',
    'admin.applications.release.gate_state.passed': 'Passed',
    'admin.applications.release.gate_state.failed': 'Blocked',
    'admin.applications.release.cause.ENGINE_GATE_FAILED':
        "The rating engine's credit gates don't pass.",
    'admin.applications.release.cause.AUTHORITY_CHANGED':
        "The company's signing authority changed after it signed.",
    'admin.applications.release.cause.MANDATE_UNVERIFIED':
        "The business's signing mandate isn't verified.",
    'admin.applications.release.cause.REPORT_NOT_CURRENT':
        'The audit report is no longer current.',
    'admin.applications.release.cause.EXPOSURE_RESERVATION_REQUIRED':
        'No borrowing reservation is on record for this application.',
    'admin.applications.release.cause.SIGNATURES_REQUIRED':
        'Not every required signatory has signed at Review.',
    'admin.applications.release.cause.STAFF_RELEASE_REQUIRED':
        'The release on record no longer matches what the business signed.',
    'admin.applications.release.cause.TERMS_CHANGED':
        'The terms changed after the business signed.',
    'admin.applications.release.cause.FEE_DISCLOSURE_CHANGED':
        'The listing fee disclosure changed.',
    'admin.applications.release.cause.QUOTE_STALE':
        'The quote is no longer current.',
    'admin.applications.release.cause.RESTRICTION_ACTIVE':
        'A restriction applies to this business.',
    'admin.applications.release.cause.RELEASE_CHECK_NOT_COMPLETED':
        "This check couldn't be completed.",
    'admin.applications.release.cause.other': "This check didn't pass.",
    'admin.applications.release.blocked':
        "A check failed, so this application can't be released. Release never overrides a failed check.",
    'admin.applications.release.receipt': 'Release receipt',
    'admin.applications.release.command': 'Release for listing',
    'admin.applications.release.stage.title': 'Release this application',
    'admin.applications.release.stage.body':
        'The business can then publish its raise. Nothing is listed or funded until it does.',
    'admin.applications.release.stage.cta': 'Release',
    'admin.applications.release.stage.placeholder':
        'e.g. Engine, authority and sealed report all confirmed.',
    'investor.audit.tolerance': 'Governed tolerance',
    'investor.audit.reconciliation': 'RECONCILIATION STATEMENT',
    'investor.deal.ebitda_unavailable': 'Unavailable',
    'investor.deal.ebitda_not_sourced': 'Not sourced as EBITDA',
    'investor.deal.photos_none': "The business hasn't published any photos.",
    'investor.deal.photo_unavailable': 'Image unavailable',
    'investor.deal.restriction.NOTE_INELIGIBLE':
        "Investing is paused: this note isn't eligible right now",
    'investor.deal.restriction.RESTRICTION_ACTIVE':
        'Investing is paused: a restriction applies',
    'investor.deal.restriction.body':
        'Since {date}. The raise itself carries on as it stands; new reservations wait until the restriction lifts.',
    'investor.deal.lifecycle.live': 'Live',
    'investor.deal.lifecycle.fully_reserved': 'Fully reserved',
    'investor.deal.lifecycle.sold_out_pending_settlement': 'Fully committed',
    'investor.deal.lifecycle.inventory_unavailable': 'No notes available',
    'investor.deal.lifecycle.closing_pending_settlement': 'Closing',
    'investor.deal.lifecycle.funded': 'Fully funded',
    'investor.deal.lifecycle.disbursing': 'Paying out',
    'investor.deal.lifecycle.issued': 'Notes issued',
    'investor.deal.lifecycle.expired': "Didn't fill",
    'investor.deal.lifecycle.cancelled': 'Cancelled',
    'investor.deal.lifecycle.failed_closing': 'Closed and refunded',
    'investor.deal.notice.fully_reserved.title':
        'Every note is reserved right now',
    'investor.deal.notice.fully_reserved.body':
        "Every available note is currently held in other investors' checkouts. Check back later.",
    'investor.deal.notice.sold_out_pending_settlement.title':
        'Every note is committed',
    'investor.deal.notice.sold_out_pending_settlement.body':
        "Investors have committed to every note. Settlement hasn't been recorded yet; notes are issued only once the funds are paid to the business.",
    'investor.deal.notice.inventory_unavailable.title':
        'No notes are available right now',
    'investor.deal.notice.inventory_unavailable.body':
        'No notes can be reserved at the moment. Check back later.',
    'investor.deal.notice.closing_pending_settlement.title':
        'This raise has closed',
    'investor.deal.notice.closing_pending_settlement.body':
        'The deadline has passed and the raise is being closed. Any refund due appears in your wallet once it is recorded.',
    'investor.deal.notice.funded.title': 'Fully funded',
    'investor.deal.notice.funded.body':
        'Commitments are locked while the funds are paid to the business. Notes are issued once that payment is confirmed.',
    'investor.deal.notice.disbursing.title': 'Paying out to the business',
    'investor.deal.notice.disbursing.body':
        "The payment to the business isn't confirmed yet. Notes are issued only once it is.",
    'investor.deal.notice.issued.title': 'This raise has closed',
    'investor.deal.notice.issued.body':
        'The business was paid and notes were issued to its investors.',
    'investor.deal.notice.expired.title': "This raise didn't fill in time",
    'investor.deal.notice.expired.body':
        'Every commitment was refunded in full, with no fee.',
    'investor.deal.notice.cancelled.title': 'The business cancelled this raise',
    'investor.deal.notice.cancelled.body':
        'Every commitment was refunded in full, with no fee.',
    'investor.deal.notice.failed_closing.title':
        'This raise closed without paying out',
    'investor.deal.notice.failed_closing.body':
        "The payout to the business didn't go ahead, so every commitment was refunded in full.",
    'investor.deals.paused': 'Paused',
    'investor.deal.cap.max': {
        one: 'Up to {count} note: {reason}.',
        other: 'Up to {count} notes: {reason}.',
    },
    'investor.deal.cap.none': "You can't take any more notes here: {reason}.",
    'investor.deal.cap.reason.raise_cap':
        'your single-investor limit for this raise',
    'investor.deal.cap.reason.availability':
        "that's every note still available",
    'investor.deal.cap.reason.restriction': 'a restriction applies',
    'investor.deal.cap.reason.connected_party':
        "you're connected to this business",
    'investor.updates.published_photos': 'PUBLISHED PHOTOS',
    'investor.checkout.c3.processing': 'Sending…',
    'investor.checkout.c3.hold_ended':
        'Your hold has ended — these notes may have been released',
    'investor.checkout.c3.hold_left': 'Held for you · {time} left to confirm',
    'investor.checkout.c3.committed': 'Committed',
    'investor.checkout.c3.committed_body':
        'Committed — your notes are issued after the business is paid. Until then this is a commitment, not a holding.',
    'investor.checkout.c3.view_awaiting': 'See it in Awaiting issue',
    'investor.checkout.c3.reserved_title': 'Confirm your notes',
    'investor.checkout.c3.held_amount': 'Held from Available',
    'investor.checkout.c3.release': 'Release these notes',
    'investor.checkout.c3.confirm_fine_print':
        "Paying commits your held amount. You can withdraw it for a full refund until the raise reaches 100%. If the raise doesn't fill, your money comes back to your wallet.",
    'investor.checkout.c3.at_maturity': {
        one: 'Back over {count} month',
        other: 'Back over {count} months',
    },
    'investor.checkout.c3.indicative':
        'Indicative until you reserve: the exact rights of your notes are fixed when they are reserved.',
    'investor.checkout.c3.reserve': 'Reserve · {amount}',
    'investor.notices.title': 'Notifications',
    'investor.notices.unread_count': '{count} unread',
    'investor.notices.ago.now': 'Just now',
    'investor.notices.ago.minutes': '{count} min ago',
    'investor.notices.ago.hour': '1 hour ago',
    'investor.notices.ago.hours': '{count} hours ago',
    'investor.notices.ago.day': '1 day ago',
    'investor.notices.ago.days': '{count} days ago',
    'investor.notices.unread': 'Unread',
    'investor.notices.mark_all': 'Mark all read',
    'investor.notices.close': 'Close notifications',
    'investor.notices.loading': 'Loading notifications',
    'investor.notices.failed': 'Your notifications could not be loaded.',
    'investor.notices.retry': 'Try again',
    'investor.notices.empty': 'No notifications yet.',
    'investor.notices.business.deposit_received.title': 'Deposit received',
    'investor.notices.business.deposit_received.body':
        '{amount} is now in your Business wallet.',
    'investor.notices.business.application_approved.title':
        'Application approved',
    'investor.notices.business.application_approved.body':
        '{title} is approved for {amount} over {months} months. Review and sign to publish the raise.',
    'investor.notices.business.withdrawal_sent.title': 'Withdrawal sent',
    'investor.notices.business.withdrawal_sent.body':
        '{amount} was sent to {account}.',
    'investor.notices.business.withdrawal_returned.title':
        'Withdrawal returned',
    'investor.notices.business.withdrawal_returned.body':
        "The payout of {amount} to {account} didn't go through. It's back in your Business wallet.",
    'investor.notices.auditor.accreditation_approved.title':
        'Accreditation active',
    'investor.notices.auditor.accreditation_approved.body':
        'Licence {licence} is verified until {date}. You can now take audit jobs.',
    'investor.notices.auditor.accreditation_rejected.title':
        'Accreditation needs another look',
    'investor.notices.auditor.accreditation_rejected.body':
        '{reason} Upload a corrected certificate from your profile.',
    'investor.notices.auditor.accreditation_renewal_due.title':
        'Accreditation renewal due',
    'investor.notices.auditor.accreditation_renewal_due.body':
        'Licence {licence} expires on {date}. Upload the renewal from your profile to keep taking audit jobs.',
    'investor.notices.auditor.report_reminder.title': 'Audit report reminder',
    'investor.notices.auditor.report_reminder.body_monthly':
        'Rozine reminds you that the {month} audit for {business} is due by {date}.',
    'investor.notices.auditor.report_reminder.body_flash':
        'Rozine reminds you that the Flash Audit for {business} is due by {date}.',
    'investor.notices.support.complaint_answered.title':
        'Rozine answered your complaint',
    'investor.notices.support.complaint_answered.body':
        'There is a new answer on complaint {reference}.',
    'investor.notices.support.complaint_resolved.title': 'Complaint resolved',
    'investor.notices.support.complaint_resolved.body':
        'Complaint {reference} is resolved. Open it to read the resolution.',
    'support.chat.title': 'Rozine Support',
    'support.chat.open': 'Contact Rozine Support',
    'support.chat.clocks':
        'We acknowledge within 4 hours and resolve within 48',
    'support.chat.reference': 'Reference {reference}',
    'support.chat.greeting':
        'Tell us what went wrong. Your message opens a complaint with a reference number. We acknowledge it within 4 hours and resolve it within 48 hours, or tell you why and when.',
    'support.chat.cma':
        'You can take a complaint to the Capital Market Authority at any stage.',
    'support.chat.closed': 'This complaint is resolved.',
    'support.chat.new': 'New message',
    'support.chat.message': 'Your message',
    'support.chat.placeholder': 'Type your message…',
    'support.chat.send': 'Send',
    'support.chat.list': 'Your complaints',
    'support.chat.opened': 'Opened {at}',
    'support.chat.resolution': 'Resolution · {at}',
    'support.chat.status.awaiting': 'Awaiting reply',
    'support.chat.status.answered': 'Answered',
    'support.chat.status.resolved': 'Resolved',
    'investor.notices.auditor.job_offered.title': 'New audit job',
    'investor.notices.auditor.job_offered.body_flash':
        'Flash audit at {business}, {location}. Accept by {time}.',
    'investor.notices.auditor.job_offered.body_routine':
        'Routine audit at {business}, {location}. Accept by {time}.',
    'investor.notices.auditor.job_offered.body_statement_review':
        'Statement review for {business}, {location}. Accept by {time}.',
    'investor.notices.auditor.job_offered.type.flash': 'Flash audit',
    'investor.notices.auditor.job_offered.type.routine': 'Routine audit',
    'investor.notices.auditor.job_offered.type.statement_review':
        'Statement review',
    'investor.notices.auditor.job_offered.accepted':
        '{job} at {business}, {location}. You accepted it.',
    'investor.notices.auditor.job_offered.declined':
        '{job} at {business}, {location}. You declined it.',
    'investor.notices.auditor.job_offered.expired':
        '{job} at {business}, {location}. The offer expired.',
    'investor.notices.auditor.job_offered.withdrawn':
        '{job} at {business}, {location}. The offer was withdrawn.',
    'investor.notices.business.report_sealed.title':
        'Audit report ready for review',
    'investor.notices.business.report_sealed.body':
        '{auditor} sealed your audit report. Review it, and dispute it if something is wrong.',
    'investor.notices.business.report_sealed.body_due':
        '{auditor} sealed your audit report. Dispute it by {time} if something is wrong.',
    'investor.notices.business.report_review_closing.title':
        'Review window closing',
    'investor.notices.business.report_review_closing.body':
        'Your window to dispute the audit report closes at {time}. If you do nothing, it is published as sealed.',
    'investor.notices.business.reporting_window_open.title':
        'Monthly audit open',
    'investor.notices.business.reporting_window_open.body':
        "Your {month} audit has started. Have the month's statements and records ready so your Audit Partner can verify them and seal the audit report by {date}. You then review it and can raise a dispute.",
    'investor.notices.business.readiness_stage_changed.title':
        'Readiness stage changed',
    'investor.notices.business.readiness_stage_changed.body':
        'Your {month} report moved you from {previous} to {stage}.',
    'investor.notices.business.readiness_requirement_due.title':
        'New readiness requirement',
    'investor.notices.business.readiness_requirement_due.body':
        '{requirement} now applies to your business. Meet it by {date}.',
    'investor.notices.business.readiness_requirement_closing.title':
        'Requirement due soon',
    'investor.notices.business.readiness_requirement_closing.body':
        '{requirement} is due by {date}. Your CPA confirms it at the next visit.',
    'investor.notices.business.readiness_requirement_overdue.title':
        'Requirement overdue',
    'investor.notices.business.readiness_requirement_overdue.body':
        '{requirement} was due on {date}. Your next raise waits until it is met.',
    'investor.notices.business.visit_ready_open.title':
        'Get ready for this month’s visit',
    'investor.notices.business.visit_ready_open.body':
        'Your repayment is due on {date}. Your Audit Partner will ask for {items} things on this month’s visit; the checklist on your Repayments page says what each one is and where to get it.',
    'investor.notices.business.visit_ready_reminder.title':
        'Visit checklist not finished',
    'investor.notices.business.visit_ready_reminder.body':
        '{open} of {items} checklist items are still not ticked for the repayment due on {date}. Tick each one off when it is ready for your Audit Partner’s visit.',
    'investor.notices.business.raise_expired.title':
        'Raise closed without full funding',
    'investor.notices.business.raise_expired.body':
        '{title} closed at its deadline with {committed} of {target} committed. Investors were refunded and no fee was charged.',
    'investor.notices.wallet.deposit_received.title': 'Deposit received',
    'investor.notices.wallet.deposit_received.body':
        '{amount} is now in your wallet.',
    'investor.notices.wallet.withdrawal_sent.title': 'Withdrawal sent',
    'investor.notices.wallet.withdrawal_sent.body':
        '{amount} was sent to {account}.',
    'investor.notices.wallet.withdrawal_returned.title': 'Withdrawal returned',
    'investor.notices.wallet.withdrawal_returned.body':
        "The payout of {amount} to {account} didn't go through. It's back in your wallet.",
    'investor.notices.primary.refunded.title': 'Raise refunded',
    'investor.notices.primary.refunded.body':
        'The {business} raise closed without reaching its target. {amount} is back in your wallet, with no fee.',
    'investor.notices.primary.raise_funded.title': 'Raise funded',
    'investor.notices.primary.raise_funded.body': {
        one: 'The {business} raise is fully funded. Your {amount} for {count} note is set aside until the funds are sent.',
        other: 'The {business} raise is fully funded. Your {amount} for {count} notes is set aside until the funds are sent.',
    },
    'investor.notices.primary.commitment_confirmed.title': 'Purchase confirmed',
    'investor.notices.primary.commitment_confirmed.body': {
        one: 'You committed {amount} to {business} for {count} note, at a {rate}% earnings fee. Reference {reference}.',
        other: 'You committed {amount} to {business} for {count} notes, at a {rate}% earnings fee. Reference {reference}.',
    },
    'investor.notices.primary.holding_issued.title': 'Investment confirmed',
    'investor.notices.primary.holding_issued.body': {
        one: 'Your {count} note in {business} is issued: {amount} invested, {maturity} expected at maturity on {date}.',
        other: 'Your {count} notes in {business} are issued: {amount} invested, {maturity} expected at maturity on {date}.',
    },
    'investor.notices.business.raise_funded.title': 'Raise fully funded',
    'investor.notices.business.raise_funded.body':
        '{title} reached its target of {principal}. Rozine now prepares the payout to your account.',
    'investor.notices.business.disbursement_sent.title': 'Funds sent',
    'investor.notices.business.disbursement_sent.body':
        '{amount} for {title} was sent to {account}.',
    'investor.notices.business.disbursement_sent.body_fee':
        '{amount} for {title} was sent to {account}: the {principal} raised less the 3% platform fee of {fee}.',
    'investor.notices.primary.report_published.title':
        'Monthly report published',
    'investor.notices.primary.report_published.body':
        '{business} published its {month} report, verified by {partner}.',
    'investor.notices.security.password_changed.title':
        'Your password was changed',
    'investor.notices.security.password_changed.body':
        'If this was not you, reset your password now and contact Rozine support.',
    'investor.notices.security.new_device.title': 'A new device signed in',
    'investor.notices.security.new_device.body':
        '{device} signed in to your account. If this was not you, reset your password now and contact Rozine support.',
    'investor.notices.security.payout_account_linked.title':
        'Payout account linked',
    'investor.notices.security.payout_account_linked.body':
        '{account} is now linked for deposits and withdrawals. If this was not you, contact Rozine support now.',
    'investor.notices.security.payout_account_unlinked.title':
        'Payout account unlinked',
    'investor.notices.security.payout_account_unlinked.body':
        '{account} is no longer linked. If this was not you, contact Rozine support now.',
    'investor.notices.verification.approved.title': 'You are verified',
    'investor.notices.verification.approved.body':
        'Your identity is verified and your wallet is ready to invest.',
    'investor.notices.verification.approved.body_business':
        'Your identity is verified. You can now apply for a raise.',
    'investor.notices.verification.approved.body_auditor':
        'Your identity is verified. You can now take audit jobs.',
    'investor.notices.verification.rejected.title':
        'Your identity details need another look',
    'investor.notices.verification.rejected.body':
        'Compliance could not verify your details: {reason}',
    'investor.checkout.risks.title': 'Before you invest',
    'investor.checkout.risks.lead':
        'Please read these five statements. You confirm them once; checkout then has nothing extra to ask.',
    'investor.checkout.risks.understood': 'I understand all of the above',
    'investor.checkout.risks.accept': 'Continue',
    'investor.checkout.risks.failed':
        "We couldn't record your answer. Try again.",
    'investor.checkout.c3.reserve_after_risks':
        'Confirm the risk statements first to reserve.',
    'investor.checkout.c3.reserve_unavailable':
        "Reserving isn't available right now.",
    'investor.checkout.c3.reserve_fine_print':
        'Reserving holds the notes and the amount for 5 minutes while you confirm.',
    'investor.primary.status.confirmed':
        'Committed — issued after disbursement',
    'investor.primary.status.awaiting_disbursement':
        'Fully funded — awaiting payout',
    'investor.primary.status.in_flight_pending': 'Payout not yet confirmed',
    'investor.primary.status.in_flight_unknown':
        'Payout not yet confirmed — checking',
    'investor.primary.status.issued': 'Issued',
    'investor.primary.status.cancelled': 'Cancelled — refunded',
    'investor.primary.status.expired': "Didn't fill — refunded",
    'investor.primary.status.failed_closing': 'Closed — refunded',
    'investor.primary.status_body.confirmed':
        "The raise is still open. You can withdraw this commitment for a full refund until the raise reaches 100%. If the raise doesn't fill, your money comes back to your wallet.",
    'investor.primary.status_body.awaiting_disbursement':
        'The raise is fully funded, so cancelling is locked. Notes are issued once the payout to the business is confirmed.',
    'investor.primary.status_body.in_flight_pending':
        "The payout to the business has been sent and isn't confirmed yet. Nothing is issued until it is.",
    'investor.primary.status_body.in_flight_unknown':
        "We don't yet know whether the payout to the business went through, and we're checking. It isn't paid, failed or refunded; nothing is issued until it's confirmed.",
    'investor.primary.status_body.issued':
        'The business was paid and your notes were issued.',
    'investor.primary.status_body.cancelled':
        'Cancelled by {by}. Your principal was refunded in full, with no fee.',
    'investor.primary.status_body.expired':
        "The raise didn't fill in time. Your principal was refunded in full, with no fee.",
    'investor.primary.status_body.failed_closing':
        "The payout to the business didn't go ahead. Your principal was refunded in full, with no fee.",
    'investor.primary.cancelled_by.investor': 'you',
    'investor.primary.cancelled_by.business': 'the business',
    'investor.primary.cancelled_by.none': 'Rozine',
    'investor.primary.receipt.amount': 'Amount',
    'investor.primary.receipt.recorded': 'Recorded',
    'investor.primary.receipt.reference': 'Reference',
    'investor.primary.receipt.confirmation': 'CONFIRMATION RECEIPT',
    'investor.primary.receipt.refund': 'REFUND RECEIPT',
    'investor.primary.units': 'Units',
    'investor.primary.units_value': {
        one: '{count} note · {ordinals}',
        other: '{count} notes · {ordinals}',
    },
    'investor.primary.units_short': {
        one: '{count} note',
        other: '{count} notes',
    },
    'investor.primary.principal': 'Principal',
    'investor.primary.terms': 'Terms',
    'investor.primary.maturity': 'Maturity date',
    'investor.primary.maturity_at_issue': 'Set when notes are issued',
    'investor.primary.disclosure': 'Disclosure',
    'investor.primary.disclosure_value': 'Version of {date}',
    'investor.primary.view_holding': 'View holding',
    'investor.primary.awaiting_issue': 'Awaiting issue',
    'investor.primary.awaiting_issue_note': 'Commitments, not yet holdings',
    'investor.primary.rights.title': "Your notes' rights",
    'investor.primary.rights.instalment': 'Instalment',
    'investor.primary.rights.principal': 'Principal',
    'investor.primary.rights.return': 'Return',
    'investor.primary.rights.nth': 'No. {n}',
    'investor.primary.rights.total_return': 'Total return',
    'investor.primary.rights.undated':
        "Due dates are set when the notes are issued: the first falls one month after the payout's effective date.",
    'investor.primary.commitment_title': 'Commitment',
    'investor.primary.back_to_portfolio': 'Back to portfolio',
    'investor.checkout.fee_about': 'about {amount}',
    'investor.checkout.fee_estimate_note':
        'The exact fee is worked out on each repayment once your notes are reserved, so it can differ by a few francs.',
    'investor.primary.cancel': 'Withdraw commitment',
    'investor.primary.cancel_title': 'Withdraw this commitment?',
    'investor.primary.cancel_body':
        'Your full amount goes back to Available at once, with no fee. You can withdraw until the raise reaches 100%.',
    'investor.primary.cancel_confirm': 'Yes, withdraw and refund',
    'investor.primary.cancel_keep': 'Keep it',
    'investor.holding.issue.title': 'Issue record',
    'investor.holding.issue.issued_at': 'Issued',
    'investor.holding.issue.effective_at': 'Payout effective',
    'investor.holding.issue.effective_date': 'Effective date (Kigali)',
    'investor.holding.issue.schedule': 'Schedule',
    'investor.holding.issue.due': 'Due',
    /**
     * Plural example. Selection uses Intl.PluralRules for the active locale, so a locale needing
     * more categories than English simply declares them.
     */
    'auditor.engagement.head_title': 'Engagement terms',
    'auditor.engagement.title': 'Engagement terms',
    'auditor.engagement.lead':
        'Read both documents in full before you accept. Dispatch offers you new work only under terms you have accepted.',
    'auditor.engagement.synthetic_title': 'Synthetic test terms',
    'auditor.engagement.synthetic_body':
        'These are test terms, not for real engagements. Accepting them represents no real professional engagement.',
    'auditor.engagement.original_language':
        'The terms are shown in their original language.',
    'auditor.engagement.document.master_services': 'Master Services Agreement',
    'auditor.engagement.document.agreed_procedures': 'Agreed Procedures',
    'auditor.engagement.document_meta': 'Version {version} · SHA-256 {hash}…',
    'auditor.engagement.acceptance_title': 'Your acceptance',
    'auditor.engagement.release_meta':
        'Version {version} · procedure {procedure}',
    'auditor.engagement.release_hash': 'Release SHA-256 {hash}…',
    'auditor.engagement.accept_label':
        'I have read and accept the Master Services Agreement and the Agreed Procedures',
    'auditor.engagement.accept': 'Accept the terms',
    'auditor.engagement.accepting': 'Accepting…',
    'auditor.engagement.acceptance_required':
        'Tick the box to confirm you have read and accept both documents.',
    'auditor.engagement.accepted': 'You accepted version {version} on {date}',
    'auditor.engagement.accepted_receipt': 'Receipt SHA-256 {hash}…',
    'auditor.engagement.no_accept':
        'Accepting these terms isn’t available to you right now.',
    'auditor.engagement.unavailable':
        'No engagement terms are available right now',
    'auditor.engagement.unavailable_body':
        'New work is paused until Rozine publishes terms. Nothing is needed from you meanwhile.',
    'auditor.engagement.refused.AUDIT_ENGAGEMENT_VERSION_CONFLICT':
        'The engagement terms changed before your acceptance reached Rozine, so nothing was accepted. Read the current version in full before you accept.',
    'auditor.engagement.refused.AUDIT_ENGAGEMENT_TERMS_REQUIRED':
        'These engagement terms were withdrawn before your acceptance reached Rozine, so nothing was accepted.',
    'auditor.engagement.banner.required':
        'Review and accept the engagement terms to take new work',
    'auditor.engagement.banner.unavailable':
        'Engagement terms aren’t available; new work is paused',
    'auditor.command.refused.AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED':
        'Accept the current engagement terms to continue.',
    'auditor.command.review_terms': 'Review the terms',
    'auth.two_factor.recovery_codes_remaining': {
        one: '{count} recovery code remaining',
        other: '{count} recovery codes remaining',
    },
    'business.audit_cosign.head_title': 'Co-sign audit report',
    'business.audit_cosign.head_title_review': 'Review audit report',
    'business.audit_cosign.title': 'Co-sign the audit report',
    'business.audit_cosign.title_review': 'Review the audit report',
    'business.audit_cosign.back': 'Back',
    'business.audit_cosign.lead':
        'Your CPA sealed this report after the on-site audit. Read the factual findings before you co-sign.',
    'business.audit_cosign.lead_review':
        "Your CPA sealed this report after the on-site audit. Read the factual findings. You don't need to sign it.",
    'business.audit_cosign.kind.monthly': 'Monthly audit report',
    'business.audit_cosign.kind.flash': 'Flash audit report',
    'business.audit_cosign.period': 'Period',
    'business.audit_cosign.auditor': 'Audit Partner',
    'business.audit_cosign.auditor_value': '{name} · licence {licence}',
    'business.audit_cosign.procedure': 'Procedure',
    'business.audit_cosign.digest': 'Report digest',
    'business.audit_cosign.digest_short': '{digest}…',
    'business.audit_cosign.seal.title': 'Seal',
    'business.audit_cosign.seal.valid': 'Seal valid',
    'business.audit_cosign.seal.valid_at': 'Sealed {date}',
    'business.audit_cosign.seal.verify': 'Verify seal',
    'business.audit_cosign.seal.unavailable':
        "The seal can't be checked right now.",
    'business.audit_cosign.note_title': "Audit Partner's note",
    'business.audit_cosign.findings': 'Factual findings',
    'business.audit_cosign.findings_empty': 'No findings were recorded.',
    'business.audit_cosign.evidence': {
        one: '{count} evidence item',
        other: '{count} evidence items',
    },
    'business.audit_cosign.sealed_note':
        'This report is sealed. Nothing on this page changes it.',
    'business.audit_cosign.status.title': 'Co-signatures',
    'business.audit_cosign.status.title_review': 'Review',
    'business.audit_cosign.status.count': '{signed} of {required} signatures',
    'business.audit_cosign.status.signers': 'Signatories',
    'business.audit_cosign.status.you': 'You',
    'business.audit_cosign.status.signed_on': 'Signed · {date}',
    'business.audit_cosign.status.signed': 'Signed',
    'business.audit_cosign.status.pending': 'Waiting',
    'business.audit_cosign.status.not_signed': 'Not signed',
    'business.audit_cosign.due': 'Co-sign by {date}',
    'business.audit_cosign.overdue': 'Overdue — co-signing was due by {date}',
    'business.audit_cosign.published': 'Published {date}',
    'business.audit_cosign.yours.title': 'Your co-signature',
    'business.audit_cosign.yours.title_review': 'Your response',
    'business.audit_cosign.yours.accept':
        'I have reviewed the audit findings and co-sign this report.',
    'business.audit_cosign.yours.note': 'Your recap (optional)',
    'business.audit_cosign.yours.note_help':
        'Kept with your signature. It does not change the sealed report.',
    'business.audit_cosign.yours.identity':
        'Your verified account signs. Each required signatory co-signs separately.',
    'business.audit_cosign.yours.submit': 'Co-sign report',
    'business.audit_cosign.yours.submitting': 'Co-signing…',
    'business.audit_cosign.yours.signed': 'You co-signed this report.',
    'business.audit_cosign.yours.signed_on': 'You co-signed on {date}.',
    'business.audit_cosign.yours.waiting':
        'Waiting for {names} to co-sign. The report publishes once every required signature is in.',
    'business.audit_cosign.yours.all_in':
        'Every required signature is in. The report publishes once its publication checks pass.',
    'business.audit_cosign.yours.published':
        'Every required signature is in and the report is published.',
    'business.audit_cosign.yours.unavailable':
        "This report isn't open for co-signing right now.",
    'business.audit_cosign.yours.cannot': "You can't co-sign this report.",
    'business.audit_cosign.yours.not_needed':
        'Nothing to sign. If you agree, there is nothing to do: the report is published when the 24-hour window ends. If something is wrong, dispute it before then.',
    'business.audit_cosign.refused.with_code': '{reason} ({code})',
    'business.audit_cosign.refused.VERSION_CONFLICT':
        "This report's signatures changed while you were working on it. We've loaded the latest — check it and try again.",
    'business.audit_cosign.refused.IDEMPOTENCY_CONFLICT':
        "This request was already used with different details, so it was not sent again. We've loaded the latest.",
    'business.audit_cosign.refused.DIGEST_STALE':
        "The report you read is no longer the current one. We've loaded it — read it and try again.",
    'business.audit_cosign.refused.MANDATE_STALE':
        "The company's signing mandate changed. Check who can sign now, then try again.",
    'business.audit_cosign.refused.ACTION_FORBIDDEN':
        "You can't do this for this business.",
    'business.audit_cosign.refused.MANDATE_REQUIRED':
        "Your verified mandate doesn't let you sign for this business.",
    'business.audit_cosign.refused.NOT_FOUND':
        'This report is no longer available to you.',
    'business.audit_cosign.refused.denied':
        'Your access has changed. Return to your apps and try again.',
    'business.audit_cosign.refused.failed':
        "This wasn't recorded. Check the report as it stands now and try again.",
    'business.audit_prep.seal_by':
        'Your CPA seals the {month} report by {seal}. You cannot start or edit it.',
    'business.audit_cosign.count': '{count}/{limit}',
    'business.audit_cosign.published_auto':
        'Published automatically after the 24-hour window',
    'business.audit_cosign.dispute.open': 'Submit a dispute',
    'business.audit_cosign.dispute.submit': 'Submit dispute',
    'business.audit_cosign.dispute.submitting': 'Submitting…',
    'business.audit_cosign.dispute.cancel': 'Cancel',
    'business.audit_cosign.dispute.files_add': 'Add files',
    'business.audit_cosign.dispute.file_remove': 'Remove {name}',
    'audit.verify_seal.head_title': 'Verify audit seal',
    'audit.verify_seal.title': 'Audit seal check',
    'audit.verify_seal.lead':
        'Check whether a Rozine audit report carries a valid seal.',
    'audit.verify_seal.valid': 'Seal verified',
    'audit.verify_seal.valid_body': 'This digest matches the sealed report.',
    'audit.verify_seal.unavailable': "This seal can't be verified right now",
    'audit.verify_seal.unavailable_body': 'Try again later.',
    'audit.verify_seal.report_id': 'Report ID',
    'audit.verify_seal.digest': 'Report digest',
    'audit.verify_seal.amends': 'Amends report {id}',
    'audit.verify_seal.amended_by': 'Amended by report {id}',
    'audit.verify_seal.scope':
        'Only the report ID, its digest and the result of the seal check are shown here.',
    'business.audit_cosign.refused.AUDIT_REPORT_AMENDED':
        "The auditor has amended this report, so it can no longer be co-signed. The amended report will come to you for sign-off once it's sealed.",
    'common.upload.sending': 'Uploading…',
    'common.upload.percent': 'Uploading… {percent}%',
    'common.upload.checking': 'Checking the file…',
    'common.file_size.kb': '{size} KB',
    'common.file_size.mb': '{size} MB',
    'common.file_size.kind': '{kind} · {size}',
    'common.proof_file.download': 'Download {name}',
    'common.proof_file.download_short': 'Download',
    'business.audit_cosign.published_staff': 'Published by Rozine staff {date}',
    'business.audit_cosign.yours.published_auto':
        'Published at the end of the 24-hour window with no dispute. No signature is needed.',
    'business.audit_cosign.yours.published_staff':
        'Rozine staff resolved the dispute and published the report. No signature was recorded for it.',
    'business.audit_cosign.window.title': '24-hour review window',
    'business.audit_cosign.window.left': '{time} left',
    'business.audit_cosign.window.body':
        "Your CPA sealed this report. You don't sign it. If something is wrong, dispute it with supporting proof within 24 hours.",
    'business.audit_cosign.window.delivered': 'Arrived in your app {date}',
    'business.audit_cosign.window.due': 'Dispute by {date}',
    'business.audit_cosign.window.ended': 'The 24-hour window has ended.',
    'business.audit_cosign.window.auto':
        'With no dispute, the report stands and is published when the window ends.',
    'business.audit_cosign.dispute.intro':
        'Show what you dispute in the findings and your proof. Your dispute does not change the sealed report, and it pauses the 24-hour window while the CPA reviews it.',
    'business.audit_cosign.dispute.proof_rule':
        'Add supporting text, at least one file, or both.',
    'business.audit_cosign.dispute.supporting': 'Supporting text',
    'business.audit_cosign.dispute.supporting_help':
        'Plain text, up to 1,000 characters.',
    'business.audit_cosign.dispute.files': 'Proof files',
    'business.audit_cosign.dispute.files_help':
        'Up to {limit} files: PDF, JPEG or PNG, at most 10 MB each.',
    'business.audit_cosign.dispute.file_type':
        "{name} wasn't added: only PDF, JPEG or PNG files are accepted.",
    'business.audit_cosign.dispute.file_size':
        "{name} wasn't added: each file can be at most 10 MB.",
    'business.audit_cosign.dispute.file_limit':
        "{name} wasn't added: a dispute can carry at most {limit} files.",
    'business.audit_cosign.disputed.under_review.title': 'Dispute under review',
    'business.audit_cosign.disputed.under_review.body':
        'The timer is paused. The CPA is reviewing your proof.',
    'business.audit_cosign.disputed.escalated.title':
        'Rozine staff are reviewing',
    'business.audit_cosign.disputed.escalated.body':
        'Rozine staff are reviewing your dispute. The timer stays paused and the report is not published meanwhile.',
    'business.audit_cosign.disputed.amendment_required.title':
        'Amendment required',
    'business.audit_cosign.disputed.amendment_required.body':
        "The auditor must amend the report; you'll get a fresh 24-hour window. The timer stays paused until then.",
    'business.audit_cosign.disputed.amended.title': 'Report amended',
    'business.audit_cosign.disputed.amended.body':
        'The auditor amended the report. The amended report has its own 24-hour window.',
    'business.audit_cosign.disputed.upheld.title': 'Published by Rozine staff',
    'business.audit_cosign.disputed.upheld.body':
        'Rozine staff reviewed your dispute, kept the findings and published the report. No signature was recorded for it.',
    'business.audit_cosign.disputed.resolved.title': 'Dispute closed',
    'business.audit_cosign.disputed.resolved.body': 'This dispute is closed.',
    'business.audit_cosign.disputed.open_amendment':
        'Open the amended report ({report})',
    'business.audit_cosign.disputed.record_title': 'Your dispute',
    'business.audit_cosign.disputed.submitted': 'Submitted {date}',
    'business.audit_cosign.disputed.supporting_text': 'Your supporting text',
    'business.audit_cosign.disputed.files': 'Your proof files',
    'auditor.sealed.body_disputed':
        'The report is sealed. {party} disputed it, so its review window is paused and nothing is published while the dispute is open.',
    'auditor.sealed.dispute.title': 'Business dispute',
    'auditor.sealed.dispute.status.under_review': 'Under review',
    'auditor.sealed.dispute.status.escalated': 'With Rozine staff',
    'auditor.sealed.dispute.status.resolved': 'Resolved',
    'auditor.sealed.dispute.submitted': 'Submitted {date} · {time}',
    'auditor.sealed.dispute.guide.under_review':
        'Review the proof first. Then start a linked amendment, or uphold your findings.',
    'auditor.sealed.dispute.guide.escalated':
        "Rozine staff are reviewing this dispute. Sealing an amendment won't resolve it unless staff record that an amendment is required.",
    'auditor.sealed.dispute.guide.amendment_required':
        'Rozine staff require an amendment. Once you seal a linked amendment, the business gets a fresh 24-hour window.',
    'auditor.sealed.dispute.supporting_text': "The business's supporting text",
    'auditor.sealed.dispute.files': 'Proof files',
    'auditor.sealed.dispute.outcome.amendment_required':
        'Outcome: amendment required',
    'auditor.sealed.dispute.outcome.amended': 'Outcome: amended',
    'auditor.sealed.dispute.outcome.upheld': 'Outcome: findings upheld',
    'auditor.sealed.dispute.uphold': 'Uphold findings',
    'auditor.dispute_uphold.lead':
        "Your reason goes to Rozine staff, who review {business}'s dispute. Upholding does not publish the report.",
    'auditor.dispute_uphold.label': 'Your reason (required)',
    'auditor.dispute_uphold.placeholder':
        'State, as facts, why the findings stand after reviewing the proof.',
    'auditor.dispute_uphold.submit': 'Send to Rozine staff',
    'business.audit_cosign.refused.REPORT_WINDOW_CLOSED':
        "The 24-hour review window has closed, so this report can't be signed off or disputed any more. We've loaded it as it stands now.",
    'business.audit_cosign.refused.REPORT_REVIEW_CLOSED':
        "This report is already disputed, with Rozine staff or published, so it can't be signed off or disputed again. We've loaded it as it stands now.",
    'auditor.sealed.body_published_auto':
        'Published after the 24-hour window with no dispute, on {date}.',
    'auditor.sealed.body_published_staff':
        'Published by Rozine staff on {date}.',
    'auditor.sealed.cosign.not_signed': 'Not co-signed',
    'business.audit_cosign.head_title_read': 'Audit report',
    'business.audit_cosign.lead_read':
        'Your CPA sealed this report after the on-site audit. These are its factual findings.',
    'business.audit_cosign.heading.signed':
        'Audit report, co-signed and published',
    'business.audit_cosign.heading.auto_approved':
        'Audit report, published automatically after the 24-hour window',
    'business.audit_cosign.heading.staff_resolved':
        'Audit report, published by Rozine staff',
    'business.audit_cosign.heading.disputed':
        'Audit report, dispute under review',
    'business.audit_cosign.heading.amended':
        'Audit report, amended after your dispute',
    'business.audit_cosign.heading.dispute_closed':
        'Audit report, dispute closed',
    'business.audit_cosign.heading.unavailable': 'Audit report',
    'business.audit_cosign.disputed.note.cpa': "CPA's reason",
    'business.audit_cosign.disputed.note.staff': 'Rozine staff note',
    'business.audit_cosign.disputed.note.unknown': 'Review note',
    'auditor.sealed.dispute.note.cpa': 'Your reason for upholding',
    'auditor.sealed.dispute.note.staff': 'Rozine staff note',
    'auditor.sealed.dispute.note.unknown': 'Review note',
    'business.campaign.servicing.state.current': 'Repaying · on track',
    'business.campaign.servicing.state.due_today': 'Payment due today',
    'business.campaign.servicing.state.overdue': 'Payment overdue',
    'business.campaign.servicing.state.repaid': 'Fully repaid',
    'business.campaign.servicing.state.defaulted': 'In default',
    'business.campaign.servicing.dpd': {
        one: '{count} day past due',
        other: '{count} days past due',
    },
    'business.campaign.servicing.restriction.ARREARS':
        'In arrears since {date}. Resale of this note is paused until the overdue payment is received and reconciled.',
    'business.campaign.servicing.restriction.RESTRICTION_ACTIVE':
        'A restriction has applied since {date}. Repayments are still accepted.',
    'business.campaign.servicing.payments': '{made} of {total}',
    'business.campaign.servicing.total': 'Total to repay',
    'business.campaign.servicing.instalments_left': {
        one: '{count} instalment left',
        other: '{count} instalments left',
    },
    'business.campaign.servicing.next_title': 'Next instalment',
    'business.campaign.servicing.next_due': 'Instalment {index} · due {date}',
    'business.campaign.servicing.principal': 'Principal',
    'business.campaign.servicing.return': 'Return',
    'business.campaign.servicing.next_total': 'Total due',
    'business.campaign.servicing.open': 'Open repayments',
    'business.campaign.servicing.repaid':
        'Every instalment is paid: {amount} repaid by {date}.',
    'investor.servicing.title': 'Repayment',
    'investor.servicing.state.current': 'On track',
    'investor.servicing.state.due_today': 'Payment due today',
    'investor.servicing.state.overdue': 'Payment overdue',
    'investor.servicing.state.repaid': 'Fully repaid',
    'investor.servicing.state.defaulted': 'In default',
    'investor.servicing.dpd': {
        one: '{count} day past due',
        other: '{count} days past due',
    },
    'investor.servicing.restriction.ARREARS': 'In arrears since {date}',
    'investor.servicing.restriction.DEFAULT': 'In default since {date}',
    'investor.servicing.restriction.DISPUTED': 'Disputed since {date}',
    'investor.servicing.restriction.RESTRICTION_ACTIVE':
        'Restricted since {date}',
    'investor.servicing.restriction.NOTE_INELIGIBLE':
        'Note ineligible since {date}',
    'investor.servicing.received.principal': 'Principal received',
    'investor.servicing.received.return': 'Return received',
    'investor.servicing.received.late_fees': 'Late fees received',
    'investor.servicing.received.fees': 'Rozine fees',
    'investor.servicing.received.net': 'Net received',
    'investor.servicing.outstanding_principal': 'Principal outstanding',
    'investor.servicing.remaining_projected': 'Still scheduled',
    'investor.servicing.projection': 'Scheduled, not guaranteed',
    'investor.servicing.next_payment': 'Next payment',
    'investor.servicing.next_payment_value': '{amount} · {date}',
    'investor.servicing.basis': 'Basis',
    'investor.servicing.basis_for': 'Basis for {figure}',
    'investor.servicing.instalments': 'Instalments',
    'investor.servicing.instalment': 'Instalment {index} · due {date}',
    'investor.servicing.instalment_status.upcoming': 'Upcoming',
    'investor.servicing.instalment_status.due': 'Due',
    'investor.servicing.instalment_status.overdue': 'Overdue',
    'investor.servicing.instalment_status.processing':
        'Processing · not yet paid to you',
    'investor.servicing.instalment_status.partially_paid': 'Partly paid',
    'investor.servicing.instalment_status.paid': 'Paid',
    'investor.servicing.entitled':
        'Your share: principal {principal} · return {return}',
    'investor.servicing.paid':
        'Paid to you: principal {principal} · return {return}',
    'investor.servicing.payout_net': 'Payout {amount} net',
    'investor.servicing.late_fees.title': 'Late fees on this holding',
    'investor.servicing.late_fees.line':
        'Instalment {index} · {step} · {rate}%',
    'investor.servicing.late_fees.step.due_date': 'Due date',
    'investor.servicing.late_fees.step.day_7': 'Day 7',
    'investor.servicing.late_fees.step.day_30': 'Day 30',
    'investor.servicing.late_fees.status.projected': 'Projected',
    'investor.servicing.late_fees.status.assessed': 'Charged · not collected',
    'investor.servicing.late_fees.status.partially_collected':
        'Partly collected',
    'investor.servicing.late_fees.status.collected': 'Collected',
    'investor.servicing.late_fees.status.waived': 'Waived',
    'investor.servicing.late_fees.applies': 'Applies from {date}',
    'investor.servicing.late_fees.assessed': 'Your share',
    'investor.servicing.late_fees.collected': 'Collected',
    'investor.servicing.late_fees.outstanding': 'Not yet collected',
    'investor.servicing.late_fees.terms': 'Terms',
    'investor.servicing.late_fees.collection_only': 'Payable only if collected',
    'investor.servicing.late_fees.not_guaranteed': 'Not guaranteed by Rozine',
    'investor.servicing.late_fees.payout': 'View payout',
    'investor.servicing.late_fees.total.assessed': 'Your share, all lines',
    'investor.servicing.late_fees.total.collected': 'Collected',
    'investor.servicing.late_fees.total.outstanding': 'Not yet collected',
    'investor.servicing.late_fees.disclosure':
        'Late charges and penalty fees are accrued daily upon default and are payable to note holders only as and when collected from the borrower. Rozine does not guarantee or advance late fee recoveries. Disclosure {version}.',
    'investor.servicing.payouts.title': 'Payouts',
    'investor.servicing.payouts.empty': 'No payouts yet.',
    'investor.servicing.payouts.row': 'Instalment {index} · credited {date}',
    'investor.servicing.payouts.gross': 'Gross',
    'investor.servicing.payouts.fee': '{name} ({rate}%)',
    'investor.servicing.payouts.fee_code.INVESTOR_REPAYMENT_FEE':
        'Repayment fee',
    'investor.servicing.payouts.fee_code.PLUS_EARNINGS_FEE': 'Fee on earnings',
    'investor.servicing.payouts.net': 'Net to your wallet',
    'investor.servicing.payouts.receipt': 'Receipt {reference}',
    'investor.servicing.exit.title': 'Exit options',
    'investor.servicing.exit.unavailable':
        "Selling this holding isn't available.",
    'investor.servicing.exit.cause.FEATURE_DISABLED': "Resale isn't open yet",
    'investor.servicing.exit.cause.MARKET_HALTED': 'The market is halted',
    'investor.servicing.exit.cause.NOTE_HALTED': 'This note is halted',
    'investor.servicing.exit.cause.ARREARS':
        'The business is behind on payments',
    'investor.servicing.exit.cause.DEFAULT': 'The note is in default',
    'investor.servicing.exit.cause.DISPUTED': 'The note is disputed',
    'investor.servicing.exit.cause.RESTRICTION_ACTIVE': 'A restriction applies',
    'investor.servicing.exit.cause.REPORT_STALE':
        'The latest report is out of date',
    'investor.servicing.exit.cause.NO_AVAILABLE_UNITS':
        'No units are free to sell',
    'investor.servicing.exit.cause.NOTE_INELIGIBLE': "The note isn't eligible",
    'investor.servicing.exit.record_date': 'Next record date {date}',
    'investor.earnings.title': 'Earnings',
    'investor.earnings.invested': 'Invested',
    'investor.earnings.outstanding_principal': 'Principal outstanding',
    'investor.earnings.realised': 'Realised · paid to your wallet',
    'investor.earnings.principal_back': 'Principal back',
    'investor.earnings.return': 'Return',
    'investor.earnings.late_fees': 'Late fees',
    'investor.earnings.fees': 'Rozine fees',
    'investor.earnings.net_return': 'Net return',
    'investor.earnings.this_month': 'This month, net',
    'investor.earnings.avg_monthly': 'Monthly average, net',
    'investor.earnings.projected': 'Projected',
    'investor.earnings.remaining_return': 'Return still scheduled',
    'investor.earnings.next_3m': 'Next 3 months',
    'investor.earnings.next_payout': 'Next payout {amount} · {date}',
    'admin.nav.repayments': 'Repayments',
    'admin.section.repayments.title': 'Repayments',
    'admin.section.repayments.subtitle':
        'Receipts against notes, reconciled at the policy tolerance and allocated by the core. Exceptions stay blocked.',
    'admin.section.repayments.search':
        'Search repayments by business, note or reference…',
    'admin.repayments.title': 'Repayments',
    'admin.repayments.table': 'Repayments',
    'admin.repayments.older': 'Older repayments',
    'admin.repayments.empty_title': 'No repayments yet',
    'admin.repayments.empty_body':
        'Receipts against notes appear here once a business pays.',
    'admin.repayments.count.due_today': {
        one: '{count} due today',
        other: '{count} due today',
    },
    'admin.repayments.count.overdue': {
        one: '{count} overdue',
        other: '{count} overdue',
    },
    'admin.repayments.count.exceptions': {
        one: '{count} exception',
        other: '{count} exceptions',
    },
    'admin.repayments.col.reference': 'Repayment ref',
    'admin.repayments.col.source': 'Source',
    'admin.repayments.dpd': 'DPD',
    'admin.repayments.received_at': 'Received',
    'admin.repayments.state.received': 'Received · allocating',
    'admin.repayments.state.allocated': 'Allocated · reconciled',
    'admin.repayments.state.exception': 'Exception · blocked',
    'admin.repayments.source.wallet': 'Business wallet',
    'admin.repayments.source.inbound_receipt': 'Inbound receipt',
    'admin.repayments.drawer_label': 'Repayment {reference}',
    'admin.repayments.amount': 'Amount',
    'admin.repayments.dpd_at_receipt': 'DPD at receipt',
    'admin.repayments.source.title': 'Source',
    'admin.repayments.source.kind': 'From',
    'admin.repayments.source.wallet_entry': 'Wallet entry',
    'admin.repayments.source.wallet_note':
        'An internal ledger movement: no provider is involved and no staff step is needed.',
    'admin.repayments.source.receipt': 'Inbound receipt',
    'admin.repayments.provider.state': 'Provider',
    'admin.repayments.provider.requery_note':
        'Asking the provider again queries this same operation. It never collects the money again. A scheduled reconciler asks too.',
    'admin.repayments.reconciliation.title': 'Reconciliation',
    'admin.repayments.reconciliation.unreconciled': 'Not reconciled yet',
    'admin.repayments.reconciliation.matched': 'Matched · reconciled',
    'admin.repayments.reconciliation.exception':
        'Exception · blocked, not reconciled',
    'admin.repayments.reconciliation.expected': 'Expected',
    'admin.repayments.reconciliation.observed': 'Observed',
    'admin.repayments.reconciliation.difference': 'Difference',
    'admin.repayments.reconciliation.tolerance': 'Tolerance',
    'admin.repayments.reconciliation.checked_at': 'Checked at',
    'admin.repayments.reconciliation.policy': 'Policy',
    'admin.repayments.reconciliation.blocked':
        'This repayment is blocked: the reconciliation found an exception. Nothing is posted or shown as reconciled, and resolving it is not available yet.',
    'admin.repayments.servicing.title': 'Note servicing',
    'admin.repayments.servicing.before': 'Before this receipt',
    'admin.repayments.servicing.after': 'After allocation',
    'admin.repayments.servicing.not_posted': 'Not posted yet.',
    'admin.repayments.servicing.state': 'State',
    'admin.repayments.servicing.status.current': 'Current',
    'admin.repayments.servicing.status.due_today': 'Due today',
    'admin.repayments.servicing.status.overdue': 'Overdue',
    'admin.repayments.servicing.status.repaid': 'Repaid',
    'admin.repayments.servicing.status.defaulted': 'Defaulted',
    'admin.repayments.component.principal': 'Principal',
    'admin.repayments.component.return': 'Return',
    'admin.repayments.component.late_fees': 'Late fees',
    'admin.repayments.component.total': 'Outstanding',
    'admin.repayments.allocation.title': 'Allocation',
    'admin.repayments.allocation.pending':
        'Not allocated yet: the receipt is recorded and the allocation has not posted.',
    'admin.repayments.allocation.balanced': 'Balanced',
    'admin.repayments.allocation.unbalanced': 'Not balanced',
    'admin.repayments.allocation.receipt': 'Receipt {amount}',
    'admin.repayments.allocation.paid': 'What the receipt paid',
    'admin.repayments.allocation.distributed': 'Where it went',
    'admin.repayments.allocation.col.kind': 'Line',
    'admin.repayments.allocation.col.account': 'Account',
    'admin.repayments.allocation.col.amount': 'Amount',
    'admin.repayments.allocation.kind.principal': 'Principal',
    'admin.repayments.allocation.kind.return': 'Return',
    'admin.repayments.allocation.kind.late_fee': 'Late fee',
    'admin.repayments.allocation.kind.investor_gross': 'Investors, gross',
    'admin.repayments.allocation.kind.investor_fee': 'Investor fee',
    'admin.repayments.allocation.kind.investor_net': 'Investors, net',
    'admin.repayments.allocation.kind.investor_late_fee':
        'Investors, late fees',
    'admin.repayments.allocation.kind.psp_fee': 'PSP fee',
    'admin.repayments.allocation.kind.unapplied': 'Unapplied',
    'admin.repayments.allocation.instalment': '· instalment {index}',
    'admin.repayments.allocation.holders': {
        one: '· {count} holding',
        other: '· {count} holdings',
    },
    'admin.repayments.allocation.entitlements': {
        one: 'Distributed to {count} holding by largest remainder, record date {date}.',
        other: 'Distributed to {count} holdings by largest remainder, record date {date}.',
    },
    'admin.repayments.allocation.posted_at': 'Posted {at}',
    'admin.repayments.allocation.ledger': 'Open the postings',
    'admin.repayments.receipts': 'Receipts',
    'admin.repayments.business': 'Open the business',
    'admin.repayments.trail': 'Repayment trail',
    'admin.repayments.requery_body':
        'This asks the provider about the same inbound receipt. It never collects the money again.',
    'admin.nav.book': 'Book',
    'admin.nav.exceptions': 'Exceptions',
    'admin.section.book.title': 'Book',
    'admin.section.book.subtitle':
        'Every live note and its health, as the core records its servicing. Read-only.',
    'admin.section.book.search': 'Search by business or note…',
    'admin.section.exceptions.title': 'Risk Center',
    'admin.section.exceptions.subtitle':
        'Arrears, halts and variances, each aged and owned until it is resolved. Read-only.',
    'admin.section.exceptions.search': 'Search by business or reference…',
    'admin.book.title': 'Live notes',
    'admin.book.caption':
        'Servicing state and days past due as the core records them. Nothing here changes a note.',
    'admin.book.table': 'Live notes',
    'admin.book.filter': 'Filter by servicing state',
    'admin.book.stats.live_notes': 'Live notes',
    'admin.book.stats.principal_outstanding': 'Principal outstanding',
    'admin.book.stats.due_today': 'Due today',
    'admin.book.stats.overdue': 'Overdue',
    'admin.book.chip.all': 'All',
    'admin.book.col.note_id': 'Note ID',
    'admin.book.col.note': 'Business · note',
    'admin.book.col.principal': 'Principal outstanding',
    'admin.book.col.next_due': 'Next due',
    'admin.book.col.dpd': 'DPD',
    'admin.book.col.health': 'Health',
    'admin.book.nothing_due': 'Nothing scheduled',
    'admin.book.open_named': 'Open {note}',
    'admin.book.more': 'More notes',
    'admin.book.empty_title': 'No live notes',
    'admin.book.empty_body':
        'A note appears here once its raise is disbursed and servicing starts.',
    'admin.book.filtered_title': 'No notes match this filter',
    'admin.book.filtered_body': 'Choose All to see the whole book.',
    'admin.exceptions.title': 'Open exceptions',
    'admin.exceptions.caption':
        'Every exception stays here, aged, until it is resolved. Assigning, escalating and remedies are not available yet.',
    'admin.exceptions.table': 'Open exceptions',
    'admin.exceptions.filter': 'Filter by kind',
    'admin.exceptions.count.open': {
        one: '{count} open',
        other: '{count} open',
    },
    'admin.exceptions.count.unassigned': {
        one: '{count} unassigned',
        other: '{count} unassigned',
    },
    'admin.exceptions.chip.all': 'All',
    'admin.exceptions.kind.arrears': 'Arrears',
    'admin.exceptions.kind.halt': 'Halt',
    'admin.exceptions.kind.variance': 'Variance',
    'admin.exceptions.kind.audit': 'Overdue flash audit',
    'admin.exceptions.kind.complaint': 'Complaint',
    'admin.exceptions.kind.cart_release': 'Cart release',
    'admin.exceptions.retry': 'Retry release',
    'admin.exceptions.retry_named': 'Retry the release of {reference}',
    'admin.exceptions.retrying': 'Releasing…',
    'admin.exceptions.col.reference': 'Reference',
    'admin.exceptions.col.kind': 'Kind',
    'admin.exceptions.col.subject': 'Exception',
    'admin.exceptions.col.amount': 'Amount',
    'admin.exceptions.col.owner': 'Age · owner',
    'admin.exceptions.col.open': 'Record',
    'admin.exceptions.age': {
        one: 'Open {count} day',
        other: 'Open {count} days',
    },
    'admin.exceptions.dpd': '{count} DPD',
    'admin.exceptions.open_named': 'Open {reference}',
    'admin.exceptions.more': 'Older exceptions',
    'admin.exceptions.empty_title': 'No open exceptions',
    'admin.exceptions.empty_body':
        'Arrears, halts and variances appear here when the core opens one.',
    'admin.exceptions.filtered_title': 'No exceptions of this kind',
    'admin.exceptions.filtered_body': 'Choose All to see every open exception.',
    'admin.notes.tabs': 'Notes by state',
    'admin.notes.sort_label': 'Sort notes',
    'admin.notes.sort.newest': 'Sort: Newest',
    'admin.notes.sort.raised': 'Sort: Capital raised',
    'admin.notes.sort.yield': 'Sort: Yield',
    'admin.notes.sort.funded': 'Sort: % funded',
    'admin.notes.sort.investors': 'Sort: Investors',
    'admin.notes.sort.term': 'Sort: Term',
    'admin.notes.state.live': 'Live',
    'admin.notes.state.funded': 'Funded',
    'admin.notes.state.repaying': 'Repaying',
    'admin.notes.state.failed': 'Failed',
    'admin.notes.more': 'More notes',
    'admin.primary.title': 'Live raises',
    'admin.primary.caption':
        'Every raise still filling, newest first. Raises fill or fail on their own clock, so there is no moderation here.',
    'admin.primary.col.raise': 'Raise',
    'admin.primary.col.funded': 'Funded',
    'admin.primary.col.progress': 'Progress',
    'admin.primary.col.closes': 'Closes',
    'admin.primary.yield': '{rate}% yield',
    'admin.primary.term': {
        one: '{count} month',
        other: '{count} months',
    },
    'admin.primary.funded': '{pct}%',
    'admin.primary.raised_of': '{raised} of {target}',
    'admin.payments.title': 'Payouts in flight',
    'admin.payments.caption':
        'Withdrawals whose cash is held while the provider confirms the payout. Each one pays out, or returns to the wallet, on the verified outcome.',
    'admin.payments.kpi.count': {
        one: '{count} txn · last 14 days',
        other: '{count} txns · last 14 days',
    },
    'admin.payments.kpi.disbursed_count': {
        one: '{count} payout to businesses · last 14 days',
        other: '{count} payouts to businesses · last 14 days',
    },
    'admin.payments.kpi.in_flight': 'Payouts in flight',
    'admin.payments.kpi.in_flight.sub': 'Awaiting the provider',
    'admin.payments.col.reference': 'Ref',
    'admin.payments.col.who': 'User',
    'admin.payments.col.type': 'Type',
    'admin.payments.col.amount': 'Amount',
    'admin.payments.col.account': 'To account',
    'admin.payments.col.state': 'Status',
    'admin.payments.owner.investor': 'Investor withdrawal',
    'admin.payments.owner.business': 'Business withdrawal',
    'admin.payments.state.pending': 'Awaiting provider',
    'admin.payments.state.unknown': 'Unconfirmed, re-querying',
    'admin.payments.unnamed': 'Unnamed account',
    'admin.payments.empty_title': 'No payouts in flight',
    'admin.payments.empty_body':
        'Every withdrawal has been paid out or returned.',
    'admin.payments.more': 'Show all payouts',
    'admin.primary.investors': {
        one: '{count} investor',
        other: '{count} investors',
    },
    'admin.primary.days_left': {
        one: '{count} day left',
        other: '{count} days left',
    },
    'admin.primary.closes_today': 'Closing',
    'admin.primary.lifecycle.fully_reserved': 'Fully reserved',
    'admin.primary.lifecycle.inventory_unavailable': 'Unavailable',
    'admin.primary.lifecycle.sold_out_pending_settlement': 'Sold out',
    'admin.primary.lifecycle.closing_pending_settlement': 'Closing',
    'admin.primary.trend.last_14': 'Last 14 days',
    'admin.primary.more': 'More live raises',
    'admin.primary.empty_title': 'No live raises',
    'admin.primary.empty_body':
        'A raise appears here once its publication is rated and live.',
    'admin.nav.reconciliation': 'Reconciliation',
    'admin.nav.coverage': 'Partner coverage',
    'admin.nav.reports': 'Reports',
    'admin.section.reconciliation.title': 'Reconciliation',
    'admin.section.reconciliation.subtitle':
        'Each account’s ledger, statement and difference side by side, and every break until it is explained. Read-only.',
    'admin.section.reconciliation.search': 'Search by account or reference…',
    'admin.section.coverage.title': 'Partner coverage',
    'admin.section.coverage.subtitle':
        'Audit Partners and open audits in every district, and whether the district is covered. Read-only.',
    'admin.section.coverage.search': 'Search by district or province…',
    'admin.section.reports.title': 'Audit Reports',
    'admin.section.reports.subtitle':
        'Business performance reports published to investors',
    'admin.section.reports.search': 'Search…',
    'admin.reconciliation.day_close.label': 'Day close',
    'admin.reconciliation.day_close.title': 'Day close · {date}',
    'admin.reconciliation.day_close.state.reconciled': 'Reconciled',
    'admin.reconciliation.day_close.state.open_break': 'Open break',
    'admin.reconciliation.day_close.state.not_reconciled': 'Not yet reconciled',
    'admin.reconciliation.day_close.body.reconciled':
        'Every account reconciled; the day closed at {time}.',
    'admin.reconciliation.day_close.body.open_break':
        'The day cannot close while a break is unexplained. Each break stays below, aged, until it is explained.',
    'admin.reconciliation.day_close.body.not_reconciled':
        'The core has not reconciled this day yet, so it cannot close.',
    'admin.reconciliation.accounts.title': 'Balances',
    'admin.reconciliation.accounts.caption':
        'Ledger, statement and difference for each account, as the core states them.',
    'admin.reconciliation.accounts.table': 'Account balances',
    'admin.reconciliation.accounts.empty_title': 'No accounts reported',
    'admin.reconciliation.accounts.empty_body':
        'An account appears here once the core reconciles it.',
    'admin.reconciliation.col.account': 'Account',
    'admin.reconciliation.col.ledger': 'Ledger',
    'admin.reconciliation.col.statement': 'Statement',
    'admin.reconciliation.col.difference': 'Difference',
    'admin.reconciliation.col.feed': 'Statement feed',
    'admin.reconciliation.col.reference': 'Reference',
    'admin.reconciliation.col.break': 'Break',
    'admin.reconciliation.col.gap': 'Gap',
    'admin.reconciliation.col.owner': 'Age · owner',
    'admin.reconciliation.col.record': 'Record',
    'admin.reconciliation.as_of': 'As of {time}',
    'admin.reconciliation.no_statement': 'No statement',
    'admin.reconciliation.feed.available': 'Receiving',
    'admin.reconciliation.feed.unavailable': 'Unavailable',
    'admin.reconciliation.feed.since': 'Since {time}',
    'admin.reconciliation.breaks.title': 'Open breaks',
    'admin.reconciliation.breaks.caption':
        'Each break stays here, aged, until it is explained. Assigning and escalating are not available yet.',
    'admin.reconciliation.breaks.table': 'Open breaks',
    'admin.reconciliation.breaks.empty_title': 'No open breaks',
    'admin.reconciliation.breaks.empty_body':
        'A break appears here when a statement and the ledger disagree.',
    'admin.reconciliation.open_named': 'Open {reference}',
    'admin.reports.title': 'Packs',
    'admin.reports.caption':
        'Each pack for its period, and how final its figures are. Nothing here changes a figure.',
    'admin.reports.table': 'Report packs',
    'admin.reports.col.pack': 'Pack',
    'admin.reports.col.period': 'Period',
    'admin.reports.col.status': 'Status',
    'admin.reports.col.as_of': 'As of',
    'admin.reports.col.download': 'Download',
    'admin.reports.kind.regulator': 'Regulator',
    'admin.reports.kind.board': 'Board',
    'admin.reports.kind.export': 'Export',
    'admin.reports.status.complete': 'Complete',
    'admin.reports.status.incomplete': 'Period not closed',
    'admin.reports.status.moving': 'Figures moving',
    'admin.reports.period': '{start} – {end}',
    'admin.reports.snapshot': 'Snapshot',
    'admin.reports.pending': 'Pending:',
    'admin.reports.not_ready': 'When the period closes',
    'admin.reports.unavailable': 'Download not available',
    'admin.reports.download': 'Download',
    'admin.reports.download_snapshot': 'Download snapshot',
    'admin.reports.download_named': 'Download {label}',
    'admin.reports.empty_title': 'No packs yet',
    'admin.reports.empty_body': 'A pack appears here once its period starts.',
    'admin.coverage.title': 'Districts',
    'admin.coverage.caption':
        'Active Audit Partners and open audits in each district, with the core’s coverage verdict.',
    'admin.coverage.table': 'District coverage',
    'admin.coverage.stats.districts': 'Districts',
    'admin.coverage.stats.uncovered': 'Uncovered',
    'admin.coverage.stats.audits_open': 'Audits open',
    'admin.coverage.col.district': 'District',
    'admin.coverage.col.partners': 'Active Audit Partners',
    'admin.coverage.col.audits': 'Audits open',
    'admin.coverage.col.capacity': 'Coverage',
    'admin.coverage.col.open': 'Partners',
    'admin.coverage.capacity.covered': 'Covered',
    'admin.coverage.capacity.uncovered': 'Uncovered',
    'admin.coverage.open': 'View',
    'admin.coverage.open_named': 'Audit Partners in {district}',
    'admin.coverage.empty_title': 'No districts reported',
    'admin.coverage.empty_body':
        'Districts appear here once the core tracks Audit Partner coverage.',
    'admin.nav.complaints': 'Complaints',
    'admin.section.complaints.title': 'Complaints',
    'admin.section.complaints.subtitle':
        'Every complaint with its reference, its 4-hour acknowledgement and 48-hour resolution clocks, and its conversation.',
    'admin.section.complaints.search': 'Search by reference or customer…',
    'admin.kyc.complaints_link': 'Complaints',
    'admin.health.status.critical': 'Critical',
    'admin.health.status.watch': 'Watch',
    'admin.health.status.healthy': 'Healthy',
    'admin.health.status.untracked': 'Not tracked',
    'admin.health.overall.critical': 'Attention required',
    'admin.health.overall.watch': 'Operating with warnings',
    'admin.health.overall.healthy': 'All systems healthy',
    'admin.health.overall.critical.sub':
        '{critical} critical, {watch} watch signals',
    'admin.health.overall.watch.sub': {
        one: '{count} signal needs a look',
        other: '{count} signals need a look',
    },
    'admin.health.overall.healthy.sub':
        'Every tracked signal is within tolerance',
    'admin.health.not_tracked': 'Not tracked yet',
    'admin.health.ledger.label': 'Ledger integrity',
    'admin.health.ledger.value.healthy': 'Balanced',
    'admin.health.ledger.value.critical': 'Out of balance',
    'admin.health.ledger.value.watch': {
        one: '{count} open break',
        other: '{count} open breaks',
    },
    'admin.health.ledger.detail': {
        one: '{count} provider account reconciled',
        other: '{count} provider accounts reconciled',
    },
    'admin.health.ledger.rec.healthy':
        'All movements reconcile. No manual correction needed.',
    'admin.health.ledger.rec.watch':
        'A provider outcome the ledger could not follow is open. Review it in Reconciliation.',
    'admin.health.ledger.rec.critical':
        'A clearing account no longer matches the provider. Review it in Reconciliation.',
    'admin.health.ledger.action': 'Open Reconciliation',
    'admin.health.capital.label': 'Capital in flight',
    'admin.health.capital.detail': {
        one: '{count} raise filling or awaiting disbursement',
        other: '{count} raises filling or awaiting disbursement',
    },
    'admin.health.capital.rec':
        'Liquidity is flowing. Ensure disbursement queue stays clear.',
    'admin.health.queue.label': 'Underwriting queue',
    'admin.health.queue.detail': 'applications awaiting review',
    'admin.health.queue.rec.watch':
        'A backlog is building. Assign an underwriter.',
    'admin.health.queue.rec.healthy': 'Queue is clear.',
    'admin.health.queue.rec.within':
        'Within tolerance. No backlog is building.',
    'admin.health.queue.action': 'Open the queue',
    'admin.health.events.label': 'Event throughput',
    'admin.health.events.value': '{count}/h',
    'admin.health.events.detail': {
        one: '{count} event total',
        other: '{count} events total',
    },
    'admin.health.events.rec.healthy':
        'Actions are being recorded and propagated.',
    'admin.health.events.rec.watch':
        'No events in the last hour. Act in the apps to generate activity.',
    'admin.health.default_rate.label': 'Default rate',
    'admin.health.default_rate.detail': 'Starts when repayments are serviced',
    'admin.health.arrears.label': 'Arrears ratio',
    'admin.health.arrears.detail': 'Starts when repayments are serviced',
    'admin.health.outstanding.label': 'Outstanding repayments',
    'admin.health.outstanding.detail': 'Starts when repayments are serviced',
    'admin.health.treasury.label': 'Treasury position',
    'admin.health.treasury.detail': 'No treasury balance is recorded yet',
    'admin.health.frozen.label': 'Frozen accounts',
    'admin.health.frozen.detail': 'accounts with access paused',
    'admin.health.frozen.rec.healthy': {
        one: 'No operator accounts are currently restricted.',
        other: 'No operator accounts are currently restricted.',
    },
    'admin.health.frozen.rec.watch': {
        one: '{count} operator account is frozen. Review it in Staff & Roles.',
        other: '{count} operator accounts are frozen. Review them in Staff & Roles.',
    },
    'admin.health.frozen.action': 'Open Staff & Roles',
    'admin.complaints.title': 'Complaints',
    'admin.complaints.caption':
        'Acknowledge within 4 hours and resolve within 48, or tell the customer why and when (BRS CMP-R03).',
    'admin.complaints.table': 'Complaints',
    'admin.complaints.stats.unresolved': 'Unresolved',
    'admin.complaints.stats.awaiting': 'Awaiting acknowledgement',
    'admin.complaints.stats.late': 'Past a clock',
    'admin.complaints.col.reference': 'Reference',
    'admin.complaints.col.status': 'Status',
    'admin.complaints.col.clock': 'Next clock',
    'admin.complaints.col.open': 'Conversation',
    'admin.complaints.status.awaiting': 'Awaiting',
    'admin.complaints.status.answered': 'Answered',
    'admin.complaints.status.resolved': 'Resolved',
    'admin.complaints.late.acknowledge': 'Not acknowledged within 4 hours',
    'admin.complaints.late.resolve': 'Not resolved within 48 hours',
    'admin.complaints.role.investor': 'Investor',
    'admin.complaints.role.business': 'Business',
    'admin.complaints.acknowledge_by': 'Acknowledge by {time}',
    'admin.complaints.resolve_by': 'Resolve by {time}',
    'admin.complaints.done': 'Closed',
    'admin.complaints.open': 'Open',
    'admin.complaints.open_named': 'Open complaint {reference}',
    'admin.complaints.empty_title': 'No complaints',
    'admin.complaints.empty_body':
        'A complaint appears here as soon as a customer sends one in the app.',
    'admin.complaints.drawer': 'Complaint {reference}',
    'admin.complaints.from.customer': 'Customer · {time}',
    'admin.complaints.from.staff': 'Rozine · {time}',
    'admin.complaints.from.resolution': 'Resolution · {time}',
    'admin.complaints.answer.label': 'Your answer',
    'admin.complaints.answer.placeholder': 'Write to the customer…',
    'admin.complaints.answer.resolves': 'This answer resolves the complaint',
    'admin.complaints.answer.send': 'Send answer',
    'admin.complaints.answer.hint':
        'The customer reads this in the app and gets a notice. A resolution closes the complaint.',
    'admin.complaints.closed':
        'This complaint is resolved. Nothing more can be added.',
    'auditor.academy.title': 'Auditor Academy',
    'auditor.academy.intro':
        'Short lessons on verification, sealed reports and your pay on Rozine, covering current rules and documented future repayment terms. Future terms are marked in the lessons. Your progress is saved on this device only.',
    'auditor.academy.summary_start': 'Learn how verification works on Rozine',
    'auditor.academy.resume': 'Resume: {lesson}',
    'auditor.academy.review': 'Review the Academy',
    'auditor.academy.browse': 'Browse all lessons',
    'auditor.academy.group.role': 'Your role',
    'auditor.academy.group.assignments': 'How work reaches you',
    'auditor.academy.group.verification': 'Verifying a business',
    'auditor.academy.group.monthly': 'Every month',
    'auditor.academy.group.visits': 'Flash Audits and arrears visits',
    'auditor.academy.group.pay': 'How you are paid',
    'auditor.academy.lesson.role.title': 'Who you are',
    'auditor.academy.lesson.role.sub': 'The licensed, independent check',
    'auditor.academy.lesson.role.topic1.title': 'An independent CPA',
    'auditor.academy.lesson.role.topic1.body':
        'You are an Audit Partner: an independent, ICPAR-licensed Certified Public Accountant under contract with Rozine. You check in person what statements alone cannot prove, before a business raises and every month while its note is outstanding.',
    'auditor.academy.lesson.role.topic1.q':
        'Who can verify businesses on Rozine?',
    'auditor.academy.lesson.role.topic1.option1':
        'Only licensed, independent CPAs',
    'auditor.academy.lesson.role.topic1.option2': 'Any member of Rozine staff',
    'auditor.academy.lesson.role.topic1.option3':
        'The business’s own accountant',
    'auditor.academy.lesson.role.topic2.title': 'Your licence',
    'auditor.academy.lesson.role.topic2.body':
        'Your ICPAR licence is checked when you are accredited and again before every assignment. A lapsed licence blocks new assignments the same day.',
    'auditor.academy.lesson.role.topic2.q':
        'Your licence lapses today. What happens?',
    'auditor.academy.lesson.role.topic2.option1':
        'You keep a grace period of a month',
    'auditor.academy.lesson.role.topic2.option2':
        'You get no new assignments from today',
    'auditor.academy.lesson.role.topic2.option3':
        'Nothing until your next audit ends',
    'auditor.academy.lesson.independence.title': 'Independence',
    'auditor.academy.lesson.independence.sub': 'Conflicts and what you know',
    'auditor.academy.lesson.independence.topic1.title':
        'Declare every conflict',
    'auditor.academy.lesson.independence.topic1.body':
        'You declare any conflict of interest. You are never assigned to a business that you or your firm has a relationship with.',
    'auditor.academy.lesson.independence.topic1.q':
        'Your firm keeps a business’s books. Can you verify it?',
    'auditor.academy.lesson.independence.topic1.option1':
        'Yes, if you mention it in the report',
    'auditor.academy.lesson.independence.topic1.option2':
        'Only for a Flash Audit',
    'auditor.academy.lesson.independence.topic1.option3':
        'No, you cannot be assigned to it',
    'auditor.academy.lesson.independence.topic2.title':
        'Never trade on what you know',
    'auditor.academy.lesson.independence.topic2.body':
        'A report, rating or arrears is not public until Rozine publishes it. Buying a note you verify between sealing its report and publication opens a surveillance case.',
    'auditor.academy.lesson.independence.topic2.q':
        'You sealed a strong report today and it publishes tomorrow. Can you buy the note today?',
    'auditor.academy.lesson.independence.topic2.option1':
        'No, it opens a surveillance case',
    'auditor.academy.lesson.independence.topic2.option2':
        'Yes, the report is already sealed',
    'auditor.academy.lesson.independence.topic2.option3':
        'Yes, if the amount is small',
    'auditor.academy.lesson.dispatch.title': 'Assignments',
    'auditor.academy.lesson.dispatch.sub': 'Nearest eligible CPA, within 30 km',
    'auditor.academy.lesson.dispatch.topic1.title': 'Within 30 km',
    'auditor.academy.lesson.dispatch.topic1.body':
        'Work goes to the nearest eligible CPA whose office is within 30 km of the business premises. Eligible means a current licence, no declared conflict and rotation allowing it.',
    'auditor.academy.lesson.dispatch.topic1.q':
        'A business is 31 km from your office. What happens?',
    'auditor.academy.lesson.dispatch.topic1.option1':
        'It is offered to you if no one is nearer',
    'auditor.academy.lesson.dispatch.topic1.option2':
        'You are never offered it',
    'auditor.academy.lesson.dispatch.topic1.option3':
        'You can claim it from the list',
    'auditor.academy.lesson.dispatch.topic2.title': 'Rotation',
    'auditor.academy.lesson.dispatch.topic2.body':
        'CPAs rotate across businesses, so no single CPA verifies the same business indefinitely. Rozine sets how many months in a row one CPA may verify a business.',
    'auditor.academy.lesson.dispatch.topic2.q': 'Why do CPAs rotate?',
    'auditor.academy.lesson.dispatch.topic2.option1': 'To share pay out evenly',
    'auditor.academy.lesson.dispatch.topic2.option2':
        'Because licences expire every month',
    'auditor.academy.lesson.dispatch.topic2.option3':
        'So no CPA verifies the same business indefinitely',
    'auditor.academy.lesson.baseline.title': 'The baseline report',
    'auditor.academy.lesson.baseline.sub': 'No note lists without it',
    'auditor.academy.lesson.baseline.topic1.title': 'Before any listing',
    'auditor.academy.lesson.baseline.topic1.body':
        'No note lists until you have visited the business and sealed a baseline report. You perform ISRS 4400 agreed-upon procedures on site, and the sealed report is published on the listing.',
    'auditor.academy.lesson.baseline.topic1.q':
        'Can a raise open before its baseline report is sealed?',
    'auditor.academy.lesson.baseline.topic1.option1': 'No, never',
    'auditor.academy.lesson.baseline.topic1.option2':
        'Yes, with a warning to investors',
    'auditor.academy.lesson.baseline.topic1.option3': 'Yes, for small raises',
    'auditor.academy.lesson.baseline.topic2.title': 'Evidence rules',
    'auditor.academy.lesson.baseline.topic2.body':
        'Photos come only from the in-app camera with live location. Gallery uploads are blocked, and a photo taken outside the premises geofence is rejected.',
    'auditor.academy.lesson.baseline.topic2.q':
        'You took a photo with your phone’s own camera yesterday. Can you upload it?',
    'auditor.academy.lesson.baseline.topic2.option1': 'Yes, from your gallery',
    'auditor.academy.lesson.baseline.topic2.option2':
        'No, only live in-app photos count',
    'auditor.academy.lesson.baseline.topic2.option3': 'Yes, if you add a note',
    'auditor.academy.lesson.differences.title': 'Differences and the seal',
    'auditor.academy.lesson.differences.sub':
        'What blocks a seal and what it binds',
    'auditor.academy.lesson.differences.topic1.title':
        'Explain every difference',
    'auditor.academy.lesson.differences.topic1.body':
        'Any unexplained difference between the business’s records and what you find on site blocks the seal. Explain each difference in the report, or the report cannot be sealed.',
    'auditor.academy.lesson.differences.topic1.q':
        'You count less stock than the records show and cannot explain why. Can you seal?',
    'auditor.academy.lesson.differences.topic1.option1':
        'Yes, if the gap is small',
    'auditor.academy.lesson.differences.topic1.option2': 'Yes, with a comment',
    'auditor.academy.lesson.differences.topic1.option3':
        'No, the seal is blocked',
    'auditor.academy.lesson.differences.topic2.title': 'What the seal binds',
    'auditor.academy.lesson.differences.topic2.body':
        'A sealed report is locked with a SHA-256 seal that binds your identity, your licence, the report’s contents and the time. Once sealed, nothing in it can change.',
    'auditor.academy.lesson.differences.topic2.q': 'What does the seal bind?',
    'auditor.academy.lesson.differences.topic2.option1':
        'Your identity, licence, the contents and the time',
    'auditor.academy.lesson.differences.topic2.option2':
        'Only the business’s name',
    'auditor.academy.lesson.differences.topic2.option3':
        'The investors who hold the note',
    'auditor.academy.lesson.numbers.title': 'The numbers behind an offer',
    'auditor.academy.lesson.numbers.sub': 'What your checks confirm',
    'auditor.academy.lesson.numbers.topic1.title': 'CFADS',
    'auditor.academy.lesson.numbers.topic1.body':
        'Cash flow available for debt service is verified monthly cash in, minus operating costs, owner drawings and existing debt payments. RWF 4,200,000 after operating costs, less 600,000 of drawings and a 400,000 loan payment, gives 3,200,000.',
    'auditor.academy.lesson.numbers.topic1.q':
        'Cash in after operating costs is RWF 5,000,000, drawings 1,000,000 and an existing loan payment 500,000. What is CFADS?',
    'auditor.academy.lesson.numbers.topic1.option1': 'RWF 3,500,000',
    'auditor.academy.lesson.numbers.topic1.option2': 'RWF 4,000,000',
    'auditor.academy.lesson.numbers.topic1.option3': 'RWF 5,500,000',
    'auditor.academy.lesson.numbers.topic2.title': 'Coverage and the offer',
    'auditor.academy.lesson.numbers.topic2.body':
        'Coverage is CFADS divided by the proposed instalment. The offer is the lowest of the request, the amount at 1.50 coverage, 35% of verified annual revenue and RWF 100,000,000. Your on-site checks confirm the records these numbers are built on.',
    'auditor.academy.lesson.numbers.topic2.q':
        'At what coverage does Rozine size an offer?',
    'auditor.academy.lesson.numbers.topic2.option1': '1.00',
    'auditor.academy.lesson.numbers.topic2.option2': '1.50',
    'auditor.academy.lesson.numbers.topic2.option3': '1.25',
    'auditor.academy.lesson.cycle.title': 'The monthly cycle',
    'auditor.academy.lesson.cycle.sub': 'The current calendar window',
    'auditor.academy.lesson.cycle.topic1.title': 'By the 7th, in Kigali time',
    'auditor.academy.lesson.cycle.topic1.body':
        'In the current app, seal each audited month’s report by the end of the 7th of the following month (Kigali time). Use the due date shown on the report; it does not move with a note’s payment day.',
    'auditor.academy.lesson.cycle.topic1.q':
        'In the current app, September’s monthly audit must be sealed by which date?',
    'auditor.academy.lesson.cycle.topic1.option1': '7 October',
    'auditor.academy.lesson.cycle.topic1.option2': '15 October',
    'auditor.academy.lesson.cycle.topic1.option3': '18 October',
    'auditor.academy.lesson.cycle.topic2.title': 'When an audit is late',
    'auditor.academy.lesson.cycle.topic2.body':
        'The current app marks an unsealed monthly audit overdue after that window closes and uses the same deadline for your on-time record. A missing report does not by itself make the note default.',
    'auditor.academy.lesson.cycle.topic2.q':
        'The monthly seal deadline passes before you seal. What changes?',
    'auditor.academy.lesson.cycle.topic2.option1':
        'The note automatically defaults',
    'auditor.academy.lesson.cycle.topic2.option2':
        'The audit is overdue and a later seal counts as late',
    'auditor.academy.lesson.cycle.topic2.option3':
        'The deadline follows payment day instead',
    'auditor.academy.lesson.disputes.title': 'Approvals and disputes',
    'auditor.academy.lesson.disputes.sub': 'What happens after you seal',
    'auditor.academy.lesson.disputes.topic1.title': '24 hours to respond',
    'auditor.academy.lesson.disputes.topic1.body':
        'After you seal, the business representative has 24 hours to approve or dispute your report.',
    'auditor.academy.lesson.disputes.topic1.q':
        'How long does the business have to dispute a sealed report?',
    'auditor.academy.lesson.disputes.topic1.option1': 'Until payment day',
    'auditor.academy.lesson.disputes.topic1.option2': 'Seven days',
    'auditor.academy.lesson.disputes.topic1.option3': '24 hours',
    'auditor.academy.lesson.disputes.topic2.title': 'Desk review',
    'auditor.academy.lesson.disputes.topic2.body':
        'A dispute sends the report to desk review by the Head of Finance & Auditor Network. While it is disputed the note cannot be traded, and the decision and its reason are published with the report.',
    'auditor.academy.lesson.disputes.topic2.q':
        'What happens to the note while its report is disputed?',
    'auditor.academy.lesson.disputes.topic2.option1': 'It cannot be traded',
    'auditor.academy.lesson.disputes.topic2.option2': 'It defaults',
    'auditor.academy.lesson.disputes.topic2.option3': 'Nothing changes',
    'auditor.academy.lesson.flash.title': 'Flash Audits',
    'auditor.academy.lesson.flash.sub': 'Unannounced, within 24 hours',
    'auditor.academy.lesson.flash.topic1.title': 'Within 24 hours',
    'auditor.academy.lesson.flash.topic1.body':
        'Rozine can send you to a business unannounced. A Flash Audit must be completed within 24 hours of assignment, with the same evidence rules as a baseline report.',
    'auditor.academy.lesson.flash.topic1.q':
        'How long do you have to complete a Flash Audit?',
    'auditor.academy.lesson.flash.topic1.option1': '24 hours from assignment',
    'auditor.academy.lesson.flash.topic1.option2':
        '24 hours from when you arrive',
    'auditor.academy.lesson.flash.topic1.option3': 'Three days',
    'auditor.academy.lesson.flash.topic2.title': 'When the clock runs out',
    'auditor.academy.lesson.flash.topic2.body':
        'An unfinished Flash Audit at 24 hours raises an alert, and a missed CPA deadline alerts the CMA. Only take on what you can finish in time.',
    'auditor.academy.lesson.flash.topic2.q':
        'What happens when a Flash Audit is unfinished at 24 hours?',
    'auditor.academy.lesson.flash.topic2.option1':
        'The deadline extends itself',
    'auditor.academy.lesson.flash.topic2.option2': 'An alert is raised',
    'auditor.academy.lesson.flash.topic2.option3': 'The business is rejected',
    'auditor.academy.lesson.arrears.title': 'Arrears visits',
    'auditor.academy.lesson.arrears.sub': 'When a business is a week late',
    'auditor.academy.lesson.arrears.topic1.title': 'Day 7 of arrears',
    'auditor.academy.lesson.arrears.topic1.body':
        'When a business you verify is seven days late, a visit task is created for you. You visit straight away to check stock, cash and operations.',
    'auditor.academy.lesson.arrears.topic1.q':
        'When is an arrears visit triggered?',
    'auditor.academy.lesson.arrears.topic1.option1': 'On the first day late',
    'auditor.academy.lesson.arrears.topic1.option2': 'On day 30 of arrears',
    'auditor.academy.lesson.arrears.topic1.option3': 'On day 7 of arrears',
    'auditor.academy.lesson.arrears.topic2.title': 'A sealed note to holders',
    'auditor.academy.lesson.arrears.topic2.body':
        'After the visit you file a sealed note to holders, so investors hear what you found from the person who checked.',
    'auditor.academy.lesson.arrears.topic2.q':
        'What do you file after an arrears visit?',
    'auditor.academy.lesson.arrears.topic2.option1': 'A sealed note to holders',
    'auditor.academy.lesson.arrears.topic2.option2':
        'A new offer for the business',
    'auditor.academy.lesson.arrears.topic2.option3':
        'Nothing, Rozine writes to holders',
    'auditor.academy.lesson.earnings.title': 'Your earnings',
    'auditor.academy.lesson.earnings.sub':
        'A share of what Rozine earns on your notes',
    'auditor.academy.lesson.earnings.topic1.title': '40% of the platform fee',
    'auditor.academy.lesson.earnings.topic1.body':
        'The business pays a 3% platform fee on the principal at disbursement. 40% of that fee is credited to you, the Audit Partner who verified the note, at disbursement. On a RWF 12,000,000 raise the fee is 360,000 and your share is 144,000.',
    'auditor.academy.lesson.earnings.topic1.q':
        'A RWF 10,000,000 raise is disbursed. What is your share of the platform fee?',
    'auditor.academy.lesson.earnings.topic1.option1': 'RWF 300,000',
    'auditor.academy.lesson.earnings.topic1.option2': 'RWF 40,000',
    'auditor.academy.lesson.earnings.topic1.option3': 'RWF 120,000',
    'auditor.academy.lesson.earnings.topic2.title':
        '25% of the earnings fee (future terms)',
    'auditor.academy.lesson.earnings.topic2.body':
        'The documented future repayment terms provide for an earnings fee on the return part of each monthly payout to investors, with 25% of that fee credited to you on each payout of the notes you verify. These credits are not active yet.',
    'auditor.academy.lesson.earnings.topic2.q':
        'Under the documented future repayment terms, when would the 25% earnings-fee share be credited?',
    'auditor.academy.lesson.earnings.topic2.option1':
        'On each monthly payout of notes you verify, once active',
    'auditor.academy.lesson.earnings.topic2.option2':
        'Once per completed audit',
    'auditor.academy.lesson.earnings.topic2.option3':
        'Only when the note matures',
    'auditor.outcome.verified.title': 'Statements verified',
    'auditor.outcome.verified.body':
        "{business}'s statements and monthly figures are now on record as checked. They count toward its application once every other requirement is met.",
    'auditor.verify.title': 'Verify the statements',
    'auditor.verify.lead':
        'Open each statement, compare it with the figures the business saved, and record what you checked. This is a factual review, not a credit decision.',
    'auditor.verify.waiting_for_figures':
        "The business hasn't saved its monthly figures yet. You can verify the statements once it has.",
    'auditor.verify.statements': 'Statements on file',
    'auditor.verify.open': 'Open',
    'auditor.verify.reference': 'Where you checked it (page, line or note)',
    'auditor.verify.figures': "The business's monthly figures",
    'auditor.verify.month': 'Month',
    'auditor.verify.inflow': 'Money in',
    'auditor.verify.outflow': 'Money out',
    'auditor.verify.owner_draw': 'Owner draw',
    'auditor.verify.debt_service': 'Debt payments',
    'auditor.verify.checks': 'Checks',
    'auditor.verify.check.complete_rail_inventory':
        'Every account and wallet the business uses is listed',
    'auditor.verify.check.originals_authentic':
        'The statements are genuine originals',
    'auditor.verify.check.balances_and_transactions':
        'Balances and transactions match the figures',
    'auditor.verify.check.duplicates_and_classification':
        'No duplicates, and money in and out is classed correctly',
    'auditor.verify.check.debt_schedules':
        'Debts and their payment schedules are accounted for',
    'auditor.verify.check.baseline_and_gaps':
        'The months are complete, with any gaps explained',
    'auditor.verify.check.draws_returns_and_exceptions':
        'Owner draws, refunds and unusual items are explained',
    'auditor.verify.inventory': 'Where the full list of accounts is recorded',
    'auditor.verify.debts': 'Debts',
    'auditor.verify.no_debts':
        'No debts declared. Add any loan or credit you found; leaving none says the business has none.',
    'auditor.verify.debt': 'Debt {number}',
    'auditor.verify.debt_name': 'Lender and loan',
    'auditor.verify.debt_principal': 'Amount still owed (RWF)',
    'auditor.verify.debt_service_in': 'Paid in {month} (RWF)',
    'auditor.verify.debt_scheduled':
        'Scheduled monthly payment from {month} (RWF)',
    'auditor.verify.debt_last_payment': 'Last scheduled payment',
    'auditor.verify.debt_last_payment_pick': 'Choose a month',
    'auditor.verify.debt_beyond': 'After {month}',
    'auditor.verify.debt_schedule_hint':
        'Each coming month fills from the payment above. Change any month that differs, such as a stepped or final balloon payment.',
    'auditor.verify.debt_due_in': 'Due in {month} (RWF)',
    'auditor.verify.debt_sources': 'Statements showing this debt',
    'auditor.verify.add_debt': 'Add a debt',
    'auditor.verify.remove_debt': 'Remove this debt',
    'auditor.verify.owner': 'Owner draw and findings',
    'auditor.verify.owner_draw_amount': 'Usual monthly owner draw (RWF)',
    'auditor.verify.owner_draw_reference': 'Where you saw it',
    'auditor.verify.findings': 'Your findings',
    'auditor.verify.balances': 'Opening and closing balances',
    'auditor.verify.account': 'Account',
    'auditor.verify.opening_balance': 'Opening',
    'auditor.verify.closing_balance': 'Closing',
    'auditor.verify.amount_hint': 'Whole RWF, e.g. 150000',
    'auditor.verify.done.title': 'Verification recorded',
    'auditor.verify.done.body':
        '{business}’s statements are now on record as verified. Nothing more is needed from you on this review.',
    'auditor.verify.done.jobs': 'Back to Jobs',
    'auditor.verify.incomplete':
        'Tick every check and fill in each field to verify. Amounts are whole RWF.',
    'auditor.verify.submit': 'Verify statements',
    'auditor.verify.sending': 'Verifying…',
    'auditor.verify.refused.STATEMENT_REVIEW_INCOMPLETE':
        'Every check must be ticked before the statements can be verified.',
    'auditor.verify.refused.STATEMENT_SOURCE_REVIEW_REQUIRED':
        "A statement on file changed or wasn't reviewed. Reload the file and check each statement again.",
    'auditor.verify.refused.STATEMENT_REVIEW_REFERENCE_REQUIRED':
        'Each note must be plain text of up to 2,000 characters.',
    'auditor.verify.refused.STATEMENT_DEBT_EVIDENCE_REQUIRED':
        'Each debt needs a unique name, its statements and a payment for every month.',
    'auditor.verify.refused.STATEMENT_REVIEW_AMOUNT_INVALID':
        'Amounts must be whole RWF, without separators.',
    'auditor.verify.refused.STATEMENT_COMPLETE_MONTH_REQUIRED':
        "The figures include the current month, which can't be verified until it ends. Ask the business to save completed months only.",
    'auditor.verify.refused.STATEMENT_RECONCILIATION_REQUIRED':
        "The business's figures no longer reconcile with its statements. The business needs to save them again.",
    'auditor.verify.refused.STATEMENT_TRANSCRIPTION_STALE':
        'The business saved new figures since you opened the file. Reload it and review them.',
    'business.prospectus.title': 'Listing Builder',
    'business.prospectus.back': 'Back to Home',
    'business.prospectus.draft': 'Draft · version {version}',
    'business.prospectus.signed': 'Signed · version {version}',
    'business.prospectus.locked':
        'Signed on {date} and locked. Fingerprint {fingerprint}…',
    'business.prospectus.unavailable':
        'We can’t check your prospectus against your approved terms right now ({code}). Your drafts still save.',
    'business.prospectus.facts.title': 'Your approved terms',
    'business.prospectus.facts.note':
        'These come from your accepted offer and verified statements. You can’t edit them here.',
    'business.prospectus.facts.principal': 'Amount raised',
    'business.prospectus.facts.fee': 'Platform fee (3%)',
    'business.prospectus.facts.net': 'To spend on your plan',
    'business.prospectus.facts.term': 'Term',
    'business.prospectus.facts.instalment': 'Monthly instalment',
    'business.prospectus.facts.revenue': 'Verified average monthly revenue',
    'business.prospectus.checks.title': 'Checks · {passed} of {total} passed',
    'business.prospectus.checks.passed': 'Passed',
    'business.prospectus.purpose.title': 'Purpose and use of funds',
    'business.prospectus.purpose.label': 'What the money is for',
    'business.prospectus.funds.title': 'Use of funds',
    'business.prospectus.funds.label': 'Item',
    'business.prospectus.funds.amount': 'Amount (RWF)',
    'business.prospectus.funds.quote': 'Quote or invoice (RWF)',
    'business.prospectus.funds.add': 'Add a line',
    'business.prospectus.remove': 'Remove',
    'business.prospectus.funds.quote_document': 'Quote document',
    'business.prospectus.evidence.title': 'Quotes and contracts',
    'business.prospectus.evidence.note':
        'Upload the supplier quote or invoice for each line of 20% or more of the principal, and the signed contract for any month more than 25% from your verified revenue. Then choose each document beside its line or month. PDF, PNG or JPEG, up to 10 MiB. An uploaded document cannot be changed.',
    'business.prospectus.evidence.empty': 'No documents uploaded yet.',
    'business.prospectus.evidence.none': 'No document',
    'business.prospectus.evidence.kind': 'Document type',
    'business.prospectus.evidence.kind_quote': 'Quote or invoice',
    'business.prospectus.evidence.kind_contract': 'Signed contract',
    'business.prospectus.evidence.file': 'File',
    'business.prospectus.evidence.upload': 'Upload',
    'business.prospectus.evidence.uploading': 'Uploading…',
    'business.prospectus.forecast.title': 'Forecast',
    'business.prospectus.forecast.revenue': 'Revenue in {month} (RWF)',
    'business.prospectus.forecast.costs': 'Costs in {month} (RWF)',
    'business.prospectus.forecast.contract': 'Signed contract for {month}',
    'business.prospectus.forecast.assumptions': 'Assumptions',
    'business.prospectus.forecast.downside': 'If sales fall short',
    'business.prospectus.management.title': 'Management',
    'business.prospectus.management.name': 'Name as registered',
    'business.prospectus.management.role': 'Role',
    'business.prospectus.management.text': 'About {name}',
    'business.prospectus.management.add': 'Add a person',
    'business.prospectus.management.roles.representative':
        'Official representative',
    'business.prospectus.management.roles.director': 'Director',
    'business.prospectus.management.roles.owner': 'Owner (10% or more)',
    'business.prospectus.risks.title': 'Risk factors',
    'business.prospectus.risks.share':
        'Largest customer’s share of revenue (%)',
    'business.prospectus.risks.text': 'Risk {number}',
    'business.prospectus.risks.concentration': 'Customer concentration',
    'business.prospectus.risks.add': 'Add a risk',
    'business.prospectus.save': 'Save draft',
    'business.prospectus.saving': 'Saving…',
    'business.prospectus.declaration.title': 'Declaration',
    'business.prospectus.declaration.text':
        'I confirm this prospectus is true and complete, and that investors may rely on it. Once signed it is locked and published as written.',
    'business.prospectus.declaration.ready':
        'Every check passes. The official representative can sign now.',
    'business.prospectus.declaration.save_first':
        'Save your changes before signing.',
    'business.prospectus.declaration.waiting':
        'Signing opens for the official representative once every check passes.',
    'business.prospectus.sign': 'Sign and lock',
    'business.prospectus.signing': 'Signing…',
    'business.prospectus.facts.months': {
        one: '{count} month',
        other: '{count} months',
    },
} as const;

export default en;
