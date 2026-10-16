import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import BusinessOnboarding from '@/pages/business/onboarding';
import type { BusinessOnboardingProps } from '@/types/business';
import bankLinkedFixture from '../../../resources/fixtures/ui/business-onboarding-bank-linked.json';
import bankFixture from '../../../resources/fixtures/ui/business-onboarding-bank.json';
import confirmFixture from '../../../resources/fixtures/ui/business-onboarding-confirm.json';
import verifiedFixture from '../../../resources/fixtures/ui/business-onboarding-documents-verified.json';
import documentsFixture from '../../../resources/fixtures/ui/business-onboarding-documents.json';
import finishFixture from '../../../resources/fixtures/ui/business-onboarding-finish.json';

const inertia = vi.hoisted(() => ({
    posts: [] as { url: string; data: Record<string, unknown> }[],
    uploads: [] as { url: string; data: Record<string, unknown> }[],
    visits: [] as string[],
    errors: {} as Record<string, string>,
    processing: false,
}));

vi.mock('@inertiajs/react', async () => {
    const { useState } = await import('react');

    return {
        Head: () => null,
        Link: ({
            href,
            children,
            ...props
        }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
            <a href={href.url} {...props}>
                {children}
            </a>
        ),
        router: {
            post: (url: string, data: Record<string, unknown>) =>
                inertia.uploads.push({ url, data }),
            visit: (link: { url: string }) => inertia.visits.push(link.url),
        },
        useForm: <T extends Record<string, unknown>>(initial: T) => {
            const [data, setState] = useState(initial);

            return {
                data,
                setData: (key: keyof T, value: unknown) =>
                    setState((current) => ({ ...current, [key]: value })),
                errors: inertia.errors,
                processing: inertia.processing,
                post: (url: string) => inertia.posts.push({ url, data }),
            };
        },
    };
});

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessOnboardingProps;

beforeEach(() => {
    inertia.posts = [];
    inertia.uploads = [];
    inertia.visits = [];
    inertia.errors = {};
    inertia.processing = false;
});

describe('Business onboarding frame', () => {
    it('shows where the business is in the four steps and the way back', () => {
        render(<BusinessOnboarding {...props(bankFixture)} />);

        expect(screen.getByText('STEP 3 OF 4')).toBeInTheDocument();
        expect(
            screen.getByRole('progressbar', { name: 'Setup progress' }),
        ).toHaveValue(67);
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/business-onboarding-documents-verified',
        );
    });
});

describe('Confirm your business', () => {
    it('shows the RDB record by company code and never a tax number', () => {
        render(<BusinessOnboarding {...props(confirmFixture)} />);

        expect(screen.getByText('STEP 1 OF 4 · RDB')).toBeInTheDocument();
        expect(screen.getByText('Company code')).toBeInTheDocument();
        expect(screen.getByText('102938475')).toBeInTheDocument();
        expect(screen.queryByText('TIN')).not.toBeInTheDocument();
        expect(screen.getByText('14 Mar 2019')).toBeInTheDocument();
        expect(screen.getByText('● Active')).toBeInTheDocument();
        expect(screen.getByText('120 employees')).toBeInTheDocument();
        expect(
            screen.getByText(
                "We detected this from your RDB registration (Logistics & Freight Transport). Change it if it doesn't fit.",
            ),
        ).toBeInTheDocument();
        expect(
            screen.getAllByRole('listitem').map((item) => item.textContent),
        ).toEqual([
            'Robert MugishaCEO · Managing Director',
            'Aline UwaseBoard Chair',
            'Jean BoscoFinance Director',
            'Robert Mugisha52%',
            'Aline Uwase30%',
            'Kivu Capital Partners18%',
        ]);
    });

    it('lets the business correct the detected industry, then confirms it', async () => {
        const user = userEvent.setup();

        render(<BusinessOnboarding {...props(confirmFixture)} />);

        const trigger = screen.getByRole('button', { name: 'Industry' });

        expect(trigger).toHaveTextContent('Logistics & Transport');

        await user.click(trigger);
        await user.click(trigger);

        expect(screen.queryByRole('listbox')).not.toBeInTheDocument();

        await user.click(trigger);

        const options = within(screen.getByRole('listbox'));

        expect(
            options.getByRole('option', { name: 'Logistics & Transport' }),
        ).toHaveAttribute('aria-selected', 'true');

        await user.click(
            options.getByRole('button', { name: 'Food & Beverage' }),
        );

        expect(screen.queryByRole('listbox')).not.toBeInTheDocument();
        expect(trigger).toHaveTextContent('Food & Beverage');

        await user.click(
            screen.getByRole('button', { name: 'Confirm & continue' }),
        );

        expect(inertia.posts).toEqual([
            {
                url: '/preview/business-onboarding-documents',
                data: { industry: 'food_beverage' },
            },
        ]);
    });

    it('falls back to the raw values the registry did not describe', () => {
        const record = props(confirmFixture);

        record.industry = 'aquaculture';
        record.registry.staff = null;
        record.registry.status = 'dormant';
        inertia.errors = { industry: 'Choose an industry from the list' };

        render(<BusinessOnboarding {...record} />);

        expect(
            screen.getByRole('button', { name: 'Industry' }),
        ).toHaveTextContent('aquaculture');
        expect(screen.getByText('—')).toBeInTheDocument();
        expect(screen.getByText('● Dormant')).toBeInTheDocument();
        expect(screen.getByRole('alert')).toHaveTextContent(
            'Choose an industry from the list',
        );
    });
});

describe('RDB certificate, logo & photos', () => {
    it('holds the business on this step until the certificate is verified', async () => {
        const user = userEvent.setup();

        render(<BusinessOnboarding {...props(documentsFixture)} />);

        expect(screen.getByText('Required')).toBeInTheDocument();
        expect(
            screen.getAllByText(
                'Drop your RDB certificate (PDF scan or photo)',
            ),
        ).toHaveLength(1);
        expect(screen.getByText('Logo')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Upload and verify your RDB certificate to continue — no business lists without it',
        );
        expect(inertia.visits).toEqual([]);
    });

    it('sends the certificate number for verification', async () => {
        const user = userEvent.setup();

        render(<BusinessOnboarding {...props(documentsFixture)} />);

        await user.type(
            screen.getByRole('textbox', { name: 'Certificate number' }),
            'RDB/2019/004512',
        );
        await user.click(
            screen.getByRole('button', { name: 'Verify certificate' }),
        );

        expect(inertia.posts).toEqual([
            {
                url: '/preview/business-onboarding-documents-verified',
                data: { number: 'RDB/2019/004512' },
            },
        ]);
    });

    it('uploads each chosen file under its slot', async () => {
        const user = userEvent.setup();
        const file = new File(['%PDF'], 'certificate.pdf', {
            type: 'application/pdf',
        });
        const logo = new File(['png'], 'logo.png', { type: 'image/png' });

        render(<BusinessOnboarding {...props(documentsFixture)} />);

        await user.upload(
            screen.getByLabelText(
                'Drop your RDB certificate (PDF scan or photo)',
            ),
            file,
        );
        await user.upload(screen.getByLabelText('Upload your logo'), logo);
        await user.upload(screen.getByLabelText('Team'), logo);
        await user.upload(screen.getByLabelText('Team'), []);

        expect(inertia.uploads).toEqual([
            {
                url: '/preview/business-onboarding-documents',
                data: { slot: 'certificate', file },
            },
            {
                url: '/preview/business-onboarding-documents',
                data: { slot: 'logo', file: logo },
            },
            {
                url: '/preview/business-onboarding-documents',
                data: { slot: 'team', file: logo },
            },
        ]);
    });

    it('shows a verified certificate, the logo and uploaded photos, then moves on', async () => {
        const user = userEvent.setup();

        render(<BusinessOnboarding {...props(verifiedFixture)} />);

        expect(screen.getByText('Verified')).toBeInTheDocument();
        expect(
            screen.getByText('karongi-incorporation.pdf'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('textbox', { name: 'Certificate number' }),
        ).toBeDisabled();
        expect(
            screen.getByRole('button', { name: 'Certificate on file' }),
        ).toBeDisabled();
        expect(screen.getByText('Logo set')).toBeInTheDocument();
        expect(screen.getAllByText('Uploaded')).toHaveLength(2);

        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(inertia.visits).toEqual(['/preview/business-onboarding-bank']);
    });

    it('shows verification in progress and a refused number', () => {
        inertia.processing = true;
        inertia.errors = { number: 'RDB does not recognise this number' };

        render(<BusinessOnboarding {...props(documentsFixture)} />);

        expect(
            screen.getByRole('button', { name: 'Verifying…' }),
        ).toBeDisabled();
        expect(
            screen.getByRole('textbox', { name: 'Certificate number' }),
        ).toHaveAttribute('aria-invalid', 'true');
        expect(
            screen.getAllByText('RDB does not recognise this number'),
        ).toHaveLength(2);
    });
});

describe('Business bank account', () => {
    it('links a business account once every field and enough signatories are in', async () => {
        const user = userEvent.setup();

        render(<BusinessOnboarding {...props(bankFixture)} />);

        const link = screen.getByRole('button', {
            name: 'Link business account',
        });
        const count = screen.getByText('0 selected · need 2+');

        expect(
            screen.getByText(
                "Where you'll receive funds you raise. It must be a Rwandan business account in your company's name with at least 2 signatories.",
            ),
        ).toBeInTheDocument();
        expect(link).toBeDisabled();

        await user.selectOptions(
            screen.getByRole('combobox', { name: 'Bank' }),
            'Bank of Kigali',
        );

        const attestation = screen.getByRole('checkbox', {
            name: 'This is a registered business account, not a personal account.',
        });

        await user.click(attestation);

        expect(attestation).toBeChecked();

        await user.click(
            screen.getByRole('button', { name: 'Use company name' }),
        );

        expect(
            screen.getByRole('textbox', { name: 'Account name' }),
        ).toHaveValue('Karongi Freight Ltd');

        await user.type(
            screen.getByRole('textbox', { name: 'Account number' }),
            '0001-2231 7788',
        );

        expect(
            screen.getByRole('textbox', { name: 'Account number' }),
        ).toHaveValue('000122317788');

        const robert = screen.getByRole('checkbox', {
            name: 'Robert MugishaCEO · Managing Director',
        });
        const aline = screen.getByRole('checkbox', {
            name: 'Aline UwaseBoard Chair',
        });

        await user.click(robert);
        await user.click(aline);
        await user.click(
            screen.getByRole('checkbox', {
                name: 'Jean BoscoFinance Director',
            }),
        );
        await user.click(
            screen.getByRole('checkbox', {
                name: 'Jean BoscoFinance Director',
            }),
        );

        expect(robert).toBeChecked();
        expect(count).toHaveTextContent('2 selected · need 2+');
        expect(link).toBeEnabled();

        await user.click(link);

        expect(inertia.posts).toEqual([
            {
                url: '/preview/business-onboarding-bank-linked',
                data: {
                    bank: 'bank_of_kigali',
                    business_account: true,
                    account_name: 'Karongi Freight Ltd',
                    account_number: '000122317788',
                    signatories: ['sig-1', 'sig-2'],
                },
            },
        ]);
    });

    it('asks for more signatories when the mandate needs them', async () => {
        const user = userEvent.setup();
        const account = props(bankFixture);

        account.signatories_required = 3;
        render(<BusinessOnboarding {...account} />);

        await user.type(
            screen.getByRole('textbox', { name: 'Account name' }),
            'Karongi Freight Ltd',
        );

        expect(screen.getByText('0 selected · need 3+')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Link business account' }),
        ).toBeDisabled();
    });

    it('will not continue until a payout account is linked', async () => {
        const user = userEvent.setup();

        render(<BusinessOnboarding {...props(bankFixture)} />);

        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Link a verified business bank account to continue',
        );
    });

    it('shows linking in progress and the fields the bank refused', () => {
        inertia.processing = true;
        inertia.errors = {
            bank: 'Pick your bank',
            account_name: 'Must exactly match your registered business name.',
            account_number: 'Enter the full account number',
            signatories: 'These people are not on the mandate',
        };

        render(<BusinessOnboarding {...props(bankFixture)} />);

        expect(screen.getByRole('button', { name: 'Linking…' })).toBeDisabled();
        expect(
            screen.getByRole('textbox', { name: 'Account name' }),
        ).toHaveAttribute('aria-invalid', 'true');
        expect(screen.getAllByText('Pick your bank')).toHaveLength(2);
        expect(
            screen.getByText('Enter the full account number'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('These people are not on the mandate'),
        ).toBeInTheDocument();
    });

    it('shows the linked account, locked to support-only changes', async () => {
        const user = userEvent.setup();

        render(<BusinessOnboarding {...props(bankLinkedFixture)} />);

        expect(
            screen.getByText('✓ Business bank account linked'),
        ).toBeInTheDocument();
        expect(screen.getByText('Bank of Kigali')).toBeInTheDocument();
        expect(
            screen.getByText('Business account ····2231'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('combobox', { name: 'Bank' }),
        ).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(inertia.visits).toEqual(['/preview/business-onboarding-finish']);
    });
});

describe("You're all set", () => {
    it('summarises the workspace and finishes setup', async () => {
        const user = userEvent.setup();

        render(<BusinessOnboarding {...props(finishFixture)} />);

        expect(screen.getByText('STEP 4 OF 4')).toBeInTheDocument();
        expect(screen.getByText('✓ RDB')).toBeInTheDocument();
        expect(screen.getByText('Logistics & Transport')).toBeInTheDocument();
        expect(screen.getByText('op***@ka***.rw')).toBeInTheDocument();
        expect(screen.getByText('Bank of Kigali ··2231')).toBeInTheDocument();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Finish setup' }));

        expect(inertia.posts).toEqual([
            {
                url: '/preview/business-home',
                data: { industry: 'logistics_transport' },
            },
        ]);
    });

    it('shows the workspace being set up', () => {
        inertia.processing = true;
        const finish = props(finishFixture);

        finish.bank_account = null;
        finish.industry = 'aquaculture';
        render(<BusinessOnboarding {...finish} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Setting up your workspace…',
        );
        expect(
            screen.getByRole('button', { name: 'Setting up your workspace…' }),
        ).toBeDisabled();
        expect(screen.getByText('—')).toBeInTheDocument();
        expect(screen.getByText('aquaculture')).toBeInTheDocument();
    });
});
