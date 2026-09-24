import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorAccess from '@/pages/investor/auth/access';
import InvestorIntro from '@/pages/investor/auth/intro';
import InvestorSignUp from '@/pages/investor/auth/sign-up';
import InvestorVerification from '@/pages/investor/verification';
import InvestorVerified from '@/pages/investor/verified';
import type {
    InvestorAuthProps,
    InvestorIntroProps,
    InvestorSignUpProps,
    InvestorVerificationProps,
    InvestorVerifiedProps,
} from '@/types/investor';
import introFixture from '../../../resources/fixtures/ui/investor-intro.json';
import loginFixture from '../../../resources/fixtures/ui/investor-login.json';
import registerInstitutionFixture from '../../../resources/fixtures/ui/investor-register-institution.json';
import registerFixture from '../../../resources/fixtures/ui/investor-register.json';
import signUpAddress from '../../../resources/fixtures/ui/investor-sign-up-address.json';
import signUpAgree from '../../../resources/fixtures/ui/investor-sign-up-agree.json';
import signUpContact from '../../../resources/fixtures/ui/investor-sign-up-contact.json';
import signUpInstitution from '../../../resources/fixtures/ui/investor-sign-up-institution.json';
import signUpPaymentSent from '../../../resources/fixtures/ui/investor-sign-up-payment-sent.json';
import signUpPayment from '../../../resources/fixtures/ui/investor-sign-up-payment.json';
import signUpSecurity from '../../../resources/fixtures/ui/investor-sign-up-security.json';
import signUpIdentity from '../../../resources/fixtures/ui/investor-sign-up.json';
import declarationsFixture from '../../../resources/fixtures/ui/investor-verification-declarations.json';
import documentFixture from '../../../resources/fixtures/ui/investor-verification-document.json';
import entityFixture from '../../../resources/fixtures/ui/investor-verification-entity.json';
import livenessFixture from '../../../resources/fixtures/ui/investor-verification-liveness.json';
import representativeFixture from '../../../resources/fixtures/ui/investor-verification-representative.json';
import personalFixture from '../../../resources/fixtures/ui/investor-verification.json';
import verifiedFixture from '../../../resources/fixtures/ui/investor-verified.json';
import { inertia, resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const clone = <T,>(fixture: { props: unknown }) =>
    structuredClone(fixture.props) as T;

beforeEach(() => {
    resetInertia();
    setWide(false);
});

describe('Intro', () => {
    it('walks the three slides with policy figures, then starts', async () => {
        const user = userEvent.setup();

        render(<InvestorIntro {...clone<InvestorIntroProps>(introFixture)} />);

        expect(
            screen.getByRole('heading', {
                name: 'Every business is audited before you see it.',
            }),
        ).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Back' })).toBeDisabled();
        expect(screen.getByRole('link', { name: 'Skip' })).toHaveAttribute(
            'href',
            '/preview/investor-register',
        );

        await user.click(screen.getByRole('button', { name: 'Next' }));
        expect(
            screen.getByRole('heading', {
                name: 'A flat 10–15%, paid back monthly.',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Terms run 3 to 6 months, repaid every month.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'From RWF 5,000 a note, across as many businesses as you like.',
            ),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Back' }));
        await user.click(screen.getByRole('button', { name: 'Next' }));
        await user.click(screen.getByRole('button', { name: 'Next' }));
        expect(
            screen.getByRole('heading', {
                name: 'Need your money early? Sell it back.',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Get started' }),
        ).toHaveAttribute('href', '/preview/investor-register');
        expect(screen.getByRole('link', { name: 'Log in' })).toHaveAttribute(
            'href',
            '/preview/investor-login',
        );
    });
});

describe('Log in and role choice', () => {
    it('logs in on a phone with a password or a PIN', async () => {
        const user = userEvent.setup();

        render(<InvestorAccess {...clone<InvestorAuthProps>(loginFixture)} />);

        expect(
            screen.getByRole('heading', { name: 'Welcome back' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Password' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        await user.type(
            screen.getByLabelText('EMAIL OR PHONE'),
            'robert.m@example.rw',
        );
        await user.type(screen.getByLabelText('PASSWORD'), 'secret123');
        await user.click(screen.getByRole('button', { name: 'Log in' }));
        expect(inertia.posts[0]).toEqual({
            url: '/preview/investor-deals',
            data: { login: 'robert.m@example.rw', password: 'secret123' },
        });

        await user.click(screen.getByRole('tab', { name: 'PIN' }));
        expect(screen.getByLabelText('4-DIGIT PIN')).toHaveAttribute(
            'maxlength',
            '4',
        );
        expect(
            screen.getByText('No OTP required with PIN login.'),
        ).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'Use password instead' }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Use PIN instead' }),
        );
        expect(screen.getByLabelText('4-DIGIT PIN')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Create account' }),
        ).toHaveAttribute('href', '/preview/investor-register');
    });

    it('shows a refusal, a status and progress', () => {
        inertia.errors = { login: 'Those details do not match.' };
        inertia.processing = true;
        render(
            <InvestorAccess
                {...clone<InvestorAuthProps>(loginFixture)}
                status="Password reset."
            />,
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Those details do not match.',
        );
        expect(screen.getByRole('status')).toHaveTextContent('Password reset.');
        expect(
            screen.getByRole('button', { name: 'Please wait…' }),
        ).toBeDisabled();
        expect(screen.getByLabelText('EMAIL OR PHONE')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
    });

    it('offers the two investor types on a phone', () => {
        render(
            <InvestorAccess {...clone<InvestorAuthProps>(registerFixture)} />,
        );

        expect(screen.getByTestId('head')).toHaveTextContent('Get started');
        expect(screen.getByRole('img', { name: 'Rozine' })).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /I am an individual/u }),
        ).toHaveAttribute('href', '/preview/investor-sign-up');
        expect(
            screen.getByRole('link', { name: /I am an individual/u }),
        ).toHaveTextContent('from RWF 5,000 a note');
        expect(
            screen.getByRole('link', { name: /I am an institution/u }),
        ).toHaveAttribute('href', '/preview/investor-sign-up-institution');
    });

    it('draws the desktop auth panel for log in and for sign-up', async () => {
        setWide(true);
        const user = userEvent.setup();
        const { unmount } = render(
            <InvestorAccess {...clone<InvestorAuthProps>(loginFixture)} />,
        );

        expect(
            screen.getByRole('heading', {
                name: 'Earn up to 15% backing profitable Rwandan businesses.',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('A flat 10–15% per term, repaid monthly'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /Individual/u }),
        ).toHaveAttribute('aria-current', 'true');
        expect(screen.queryByRole('tab')).not.toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Use a PIN' }));
        await user.click(
            screen.getByRole('button', { name: 'Use a password' }),
        );
        expect(
            screen.getByRole('link', { name: 'Create an account' }),
        ).toBeInTheDocument();
        unmount();

        render(
            <InvestorAccess {...clone<InvestorAuthProps>(registerFixture)} />,
        );
        expect(
            screen.getByRole('heading', { name: 'Create your account' }),
        ).toBeInTheDocument();
        await user.type(screen.getByLabelText('FIRST NAME'), 'Robert');
        await user.type(screen.getByLabelText('LAST NAME'), 'Mugisha');
        await user.type(screen.getByLabelText('EMAIL'), 'robert@example.rw');
        await user.type(screen.getByLabelText('PASSWORD'), 'secret123');
        await user.click(screen.getByRole('button', { name: 'Continue' }));
        expect(inertia.posts.at(-1)).toEqual({
            url: '/preview/investor-sign-up-address',
            data: {
                investor_type: 'individual',
                first_name: 'Robert',
                last_name: 'Mugisha',
                email: 'robert@example.rw',
                password: 'secret123',
            },
        });
    });

    it('asks an institution for its entity and representative on the desktop panel', () => {
        setWide(true);
        inertia.errors = { email: 'That email is taken.' };
        inertia.processing = true;
        render(
            <InvestorAccess
                {...clone<InvestorAuthProps>(registerInstitutionFixture)}
            />,
        );

        expect(
            screen.getByRole('link', { name: /Institution/u }),
        ).toHaveAttribute('aria-current', 'true');
        expect(screen.getByLabelText('ENTITY NAME')).toHaveAttribute(
            'placeholder',
            'Horizon Capital Partners',
        );
        expect(screen.getByLabelText('REPRESENTATIVE')).toBeInTheDocument();
        expect(screen.getByRole('alert')).toHaveTextContent(
            'That email is taken.',
        );
        expect(
            screen.getByRole('button', { name: 'Please wait…' }),
        ).toBeDisabled();
    });
});

describe('Sign-up', () => {
    it('collects identity and saves the step', async () => {
        const user = userEvent.setup();

        render(
            <InvestorSignUp {...clone<InvestorSignUpProps>(signUpIdentity)} />,
        );

        expect(
            screen.getByText('Step 1 of 6 · Individual'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('progressbar', { name: 'Sign-up progress' }),
        ).toHaveValue(0);
        await user.type(screen.getByLabelText('FIRST NAME'), 'Robert');
        await user.type(screen.getByLabelText('LAST NAME'), 'Mugisha');
        await user.click(screen.getByRole('radio', { name: 'Passport' }));
        expect(
            screen.getByLabelText('ID / PASSPORT / LICENSE NUMBER'),
        ).toHaveAttribute('placeholder', 'PC1234567');
        await user.type(
            screen.getByLabelText('ID / PASSPORT / LICENSE NUMBER'),
            'PC1234567',
        );
        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(inertia.posts[0].url).toBe('/preview/investor-sign-up-address');
        expect(inertia.posts[0].data).toMatchObject({
            step: 'identity',
            first_name: 'Robert',
            last_name: 'Mugisha',
            id_type: 'passport',
            id_number: 'PC1234567',
        });

        await user.click(
            screen.getByRole('radio', { name: 'Institution · Plus' }),
        );
        expect(screen.getByLabelText('INSTITUTION NAME')).toBeInTheDocument();
        expect(screen.getByLabelText('CONTACT PERSON')).toBeInTheDocument();
        expect(screen.queryByText(/\bTIN\b/u)).not.toBeInTheDocument();
    });

    it('asks an institution for its RDB company code rather than a tax identifier', async () => {
        const user = userEvent.setup();

        render(
            <InvestorSignUp
                {...clone<InvestorSignUpProps>(signUpInstitution)}
            />,
        );

        expect(
            screen.getByRole('heading', { name: 'Institution details' }),
        ).toBeInTheDocument();
        await user.type(
            screen.getByLabelText('ADDRESS / LOCATION'),
            'KN 4 Ave',
        );
        await user.type(
            screen.getByLabelText('RDB COMPANY CODE'),
            '10a3847291',
        );
        expect(screen.getByLabelText('RDB COMPANY CODE')).toHaveValue(
            '103847291',
        );
        expect(
            screen.getByText('The 9-digit code on your RDB certificate'),
        ).toBeInTheDocument();
    });

    it('collects address, contact and security', async () => {
        const user = userEvent.setup();
        const { unmount: unmountFirst } = render(
            <InvestorSignUp {...clone<InvestorSignUpProps>(signUpAddress)} />,
        );

        await user.click(screen.getByRole('radio', { name: 'Kenya' }));
        await user.type(screen.getByLabelText('PROVINCE'), 'Kigali City');
        await user.type(screen.getByLabelText('CELL'), 'Bibare');
        await user.click(screen.getByRole('button', { name: 'Continue' }));
        expect(inertia.posts[0].data).toMatchObject({
            step: 'address',
            country: 'Kenya',
            province: 'Kigali City',
            cell: 'Bibare',
        });
        unmountFirst();

        const { unmount: unmountSecond } = render(
            <InvestorSignUp {...clone<InvestorSignUpProps>(signUpContact)} />,
        );

        await user.type(screen.getByLabelText('EMAIL ADDRESS'), 'r@example.rw');
        await user.clear(screen.getByLabelText('Country code'));
        await user.type(screen.getByLabelText('Country code'), '+254');
        await user.type(screen.getByLabelText('PHONE NUMBER'), '788-123-456');
        await user.click(screen.getByRole('button', { name: 'Continue' }));
        expect(inertia.posts[1].data).toMatchObject({
            email: 'r@example.rw',
            phone_country: '+254',
            phone: '788123456',
        });
        unmountSecond();

        render(
            <InvestorSignUp {...clone<InvestorSignUpProps>(signUpSecurity)} />,
        );
        await user.type(screen.getByLabelText('4-DIGIT PIN'), '12a34');
        expect(screen.getByLabelText('4-DIGIT PIN')).toHaveValue('1234');
        await user.click(screen.getByRole('radio', { name: 'Password' }));
        await user.type(screen.getByLabelText('PASSWORD'), 'secret123');
        await user.click(screen.getByRole('button', { name: 'Continue' }));
        expect(inertia.posts[2].data).toMatchObject({
            secret: 'password',
            password: 'secret123',
        });
    });

    it('links a payment method with a one-time code', async () => {
        const user = userEvent.setup();
        const { unmount: unmountFirst } = render(
            <InvestorSignUp {...clone<InvestorSignUpProps>(signUpPayment)} />,
        );

        expect(screen.getByRole('button', { name: 'Send OTP' })).toBeDisabled();
        await user.click(screen.getByRole('radio', { name: /Airtel Money/u }));
        await user.click(screen.getByRole('button', { name: 'Send OTP' }));
        expect(inertia.routerPost).toHaveBeenCalledWith(
            '/preview/investor-sign-up-payment-sent',
            { method: 'airtel' },
            { preserveScroll: true, only: ['draft'] },
        );
        unmountFirst();

        render(
            <InvestorSignUp
                {...clone<InvestorSignUpProps>(signUpPaymentSent)}
            />,
        );
        expect(screen.getByRole('status')).toHaveTextContent('OTP sent');
        await user.type(screen.getByLabelText('One-time code'), '12x3456');
        expect(screen.getByLabelText('One-time code')).toHaveValue('123456');
    });

    it('agrees to the terms and creates the account', async () => {
        const user = userEvent.setup();

        inertia.errors = {
            terms: 'Accept the Terms and Privacy Note to continue',
        };
        render(<InvestorSignUp {...clone<InvestorSignUpProps>(signUpAgree)} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Accept the Terms and Privacy Note to continue',
        );
        expect(
            screen.getByRole('link', { name: 'Terms & Conditions' }),
        ).toHaveAttribute('href', '/preview/investor-profile');
        await user.click(
            screen.getByRole('checkbox', { name: /Terms & Conditions/u }),
        );
        await user.click(
            screen.getByRole('checkbox', { name: /Privacy Note/u }),
        );
        await user.click(
            screen.getByRole('checkbox', { name: /Privacy Note/u }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Create account' }),
        );
        expect(inertia.posts[0]).toMatchObject({
            url: '/preview/investor-verification',
            data: { step: 'agree', terms: true, privacy: false },
        });
    });

    it('shows progress while a step saves', () => {
        inertia.processing = true;
        render(<InvestorSignUp {...clone<InvestorSignUpProps>(signUpAgree)} />);

        expect(
            screen.getByRole('button', { name: 'Please wait…' }),
        ).toBeDisabled();
    });
});

describe('Verification', () => {
    it('verifies an individual: date of birth, document photos, then a selfie', async () => {
        const user = userEvent.setup();
        const { unmount: unmountFirst } = render(
            <InvestorVerification
                {...clone<InvestorVerificationProps>(personalFixture)}
            />,
        );

        expect(screen.getByText('IDENTITY VERIFICATION')).toBeInTheDocument();
        expect(screen.getByText('Rwanda')).toBeInTheDocument();
        await user.type(
            screen.getByLabelText('DATE OF BIRTH'),
            '01 / 02 / 1990',
        );
        await user.click(screen.getByRole('button', { name: 'Continue' }));
        expect(inertia.posts[0]).toMatchObject({
            url: '/preview/investor-verification-document',
            data: { step: 'personal', date_of_birth: '01 / 02 / 1990' },
        });
        unmountFirst();

        const { unmount: unmountSecond } = render(
            <InvestorVerification
                {...clone<InvestorVerificationProps>(documentFixture)}
            />,
        );

        expect(
            screen.getByRole('progressbar', { name: 'Verification progress' }),
        ).toHaveValue(33);
        expect(screen.getByText('Front uploaded')).toBeInTheDocument();
        await user.click(
            screen.getByRole('radio', { name: 'Driver’s License' }),
        );
        const file = new File(['id'], 'back.jpg', { type: 'image/jpeg' });

        await user.upload(screen.getByLabelText('Upload back'), file);
        expect(inertia.routerPost).toHaveBeenCalledWith(
            '/preview/investor-verification-document',
            { slot: 'id_back', file },
            { forceFormData: true, preserveScroll: true },
        );
        unmountSecond();

        inertia.processing = true;
        render(
            <InvestorVerification
                {...clone<InvestorVerificationProps>(livenessFixture)}
            />,
        );
        expect(screen.getByLabelText('Tap to capture selfie')).toHaveAttribute(
            'capture',
            'user',
        );
        expect(screen.getByRole('status')).toHaveTextContent(
            'Verifying your identity…',
        );
        expect(
            screen.getByRole('button', { name: 'Submit for verification' }),
        ).toBeDisabled();
    });

    it('verifies an institution without a tax identifier', async () => {
        const user = userEvent.setup();
        const { unmount: unmountFirst } = render(
            <InvestorVerification
                {...clone<InvestorVerificationProps>(entityFixture)}
            />,
        );

        expect(screen.getByText('ENTITY VERIFICATION')).toBeInTheDocument();
        await user.click(screen.getByRole('radio', { name: 'SACCO' }));
        await user.type(screen.getByLabelText('RDB COMPANY CODE'), '103847291');
        await user.type(screen.getByLabelText('INCORPORATED'), '03 / 2019');
        expect(screen.queryByText(/\bTIN\b/u)).not.toBeInTheDocument();
        await user.upload(
            screen.getByLabelText('Upload certificate'),
            new File(['c'], 'c.pdf', { type: 'application/pdf' }),
        );
        await user.upload(screen.getByLabelText('Upload certificate'), []);
        await user.click(screen.getByRole('button', { name: 'Continue' }));
        expect(inertia.posts[0].data).toMatchObject({
            step: 'entity',
            entity_type: 'sacco',
            company_code: '103847291',
            incorporated: '03 / 2019',
        });
        unmountFirst();

        const { unmount: unmountMissing } = render(
            <InvestorVerification
                {...clone<InvestorVerificationProps>(representativeFixture)}
            />,
        );

        expect(screen.getByText('Upload board resolution')).toBeInTheDocument();
        expect(
            screen.getByText('Liveness check on the representative'),
        ).toBeInTheDocument();
        unmountMissing();

        const rep = clone<InvestorVerificationProps>(representativeFixture);

        if (rep.investor_type === 'institution') {
            rep.uploads.resolution = { status: 'uploaded' };
            rep.uploads.selfie = { status: 'verified' };
        }

        const { unmount: unmountSecond } = render(
            <InvestorVerification {...rep} />,
        );

        expect(
            screen.getByText('Board resolution uploaded'),
        ).toBeInTheDocument();
        expect(screen.getByText('Selfie captured')).toBeInTheDocument();
        await user.type(screen.getByLabelText('FULL NAME'), 'Aline Uwase');
        await user.type(screen.getByLabelText('ROLE'), 'Head of Treasury');
        unmountSecond();

        inertia.processing = true;
        render(
            <InvestorVerification
                {...clone<InvestorVerificationProps>(declarationsFixture)}
            />,
        );
        await user.click(
            screen.getByRole('radio', { name: 'Member deposits' }),
        );
        await user.click(
            screen.getByRole('checkbox', { name: /beneficial owners/u }),
        );
        await user.click(
            screen.getByRole('checkbox', { name: /target, not a guarantee/u }),
        );
        expect(screen.getByRole('status')).toHaveTextContent(
            'Verifying the entity…',
        );
    });

    it('submits the last step for verification', async () => {
        const user = userEvent.setup();

        render(
            <InvestorVerification
                {...clone<InvestorVerificationProps>(livenessFixture)}
            />,
        );
        await user.click(
            screen.getByRole('button', { name: 'Submit for verification' }),
        );

        expect(inertia.posts[0]).toMatchObject({
            url: '/preview/investor-verified',
            data: { step: 'liveness' },
        });
    });

    it('shows an individual’s uploaded selfie and a certificate on file', () => {
        const liveness = clone<InvestorVerificationProps>(livenessFixture);

        if (liveness.investor_type === 'individual') {
            liveness.uploads.selfie = { status: 'verified' };
        }

        inertia.errors = { date_of_birth: 'Enter your date of birth' };
        const { unmount } = render(<InvestorVerification {...liveness} />);

        expect(screen.getByText('Selfie captured')).toBeInTheDocument();
        expect(screen.getByRole('alert')).toHaveTextContent(
            'Enter your date of birth',
        );
        unmount();

        const entity = clone<InvestorVerificationProps>(entityFixture);

        if (entity.investor_type === 'institution') {
            entity.uploads.certificate = { status: 'verified' };
        }

        render(<InvestorVerification {...entity} />);
        expect(screen.getByText('Certificate uploaded')).toBeInTheDocument();
    });
});

describe('Verified', () => {
    it('confirms verification with the server’s wallet and deal count', () => {
        render(
            <InvestorVerified
                {...clone<InvestorVerifiedProps>(verifiedFixture)}
            />,
        );

        expect(
            screen.getByRole('heading', { name: "You're verified" }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/6 verified opportunities are waiting for you/u),
        ).toBeInTheDocument();
        expect(screen.getByText('RWF 0')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Start exploring' }),
        ).toHaveAttribute('href', '/preview/investor-deals');
    });
});
