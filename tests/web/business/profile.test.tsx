import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import BusinessProfile from '@/pages/business/profile';
import type {
    BusinessProfileProps,
    CompanyProfile,
    CompanyRegistration,
} from '@/types/business';
import expiredFixture from '../../../resources/fixtures/ui/business-profile-company-expired.json';
import companyFixture from '../../../resources/fixtures/ui/business-profile-company.json';
import linkedFixture from '../../../resources/fixtures/ui/business-profile-linked.json';
import recordCompanyFixture from '../../../resources/fixtures/ui/business-profile-on-record-company.json';
import recordFixture from '../../../resources/fixtures/ui/business-profile-on-record.json';
import permissionsFixture from '../../../resources/fixtures/ui/business-profile-permissions.json';
import privacyFixture from '../../../resources/fixtures/ui/business-profile-privacy.json';
import securityFixture from '../../../resources/fixtures/ui/business-profile-security.json';
import supportFixture from '../../../resources/fixtures/ui/business-profile-support.json';
import termsFixture from '../../../resources/fixtures/ui/business-profile-terms.json';
import landingFixture from '../../../resources/fixtures/ui/business-profile.json';

const inertia = vi.hoisted(() => ({
    puts: [] as { url: string; data: Record<string, unknown> }[],
    errors: {} as Record<string, string>,
    processing: false,
}));

vi.mock('@inertiajs/react', async () => {
    const { useState } = await import('react');

    return {
        Head: ({ title }: { title: string }) => (
            <span data-testid="head">{title}</span>
        ),
        Link: ({
            href,
            children,
            as,
            ...props
        }: Omit<ComponentProps<'a'>, 'href'> & {
            href: { url: string; method: string };
            as?: string;
        }) => (
            <a
                href={href.url}
                data-method={href.method}
                data-as={as}
                {...props}
            >
                {children}
            </a>
        ),
        useForm: <T extends Record<string, unknown>>(initial: T) => {
            const [data, setState] = useState(initial);

            return {
                data,
                setData: (
                    key: keyof T | ((current: T) => T),
                    value?: unknown,
                ) =>
                    setState((current) =>
                        typeof key === 'function'
                            ? key(current)
                            : { ...current, [key]: value },
                    ),
                errors: inertia.errors,
                processing: inertia.processing,
                put: (url: string, options: { onSuccess?: () => void }) => {
                    inertia.puts.push({ url, data });
                    options.onSuccess?.();
                },
            };
        },
    };
});

/** The design's seven Profile rows (Business.dc.html L5022–5028), in its order. */
const MENU = [
    'Company information›',
    'Security center›',
    'Permissions & roles›',
    'Linked accounts›',
    'Support center›',
    'Terms & Conditions›',
    'Privacy Note›',
];

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessProfileProps;

beforeEach(() => {
    inertia.puts = [];
    inertia.errors = {};
    inertia.processing = false;
});

describe('Business Profile menu', () => {
    it("shows the business and the design's seven sections", () => {
        render(<BusinessProfile {...props(landingFixture)} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Company information',
        );
        expect(
            screen.getByRole('heading', { name: 'Profile' }),
        ).toBeInTheDocument();
        expect(screen.getByText('North, Musanze')).toBeInTheDocument();
        expect(screen.getByText('✓ Verified')).toBeInTheDocument();
        expect(screen.getByText('Score 4.8')).toBeInTheDocument();

        const menu = within(
            screen.getByRole('navigation', { name: 'Profile menu' }),
        );

        expect(
            menu.getAllByRole('link').map((link) => link.textContent),
        ).toEqual(MENU);
        expect(
            menu.getByRole('link', { name: /Company information/ }),
        ).toHaveAttribute('aria-current', 'page');
        expect(screen.getByRole('link', { name: 'Sign out' })).toHaveAttribute(
            'data-method',
            'post',
        );
    });

    it('hides the verified chip for an unverified business', () => {
        const page = props(landingFixture);

        page.business.verified = false;
        render(<BusinessProfile {...page} />);

        expect(screen.queryByText('✓ Verified')).not.toBeInTheDocument();
    });
});

describe('Company information', () => {
    it('keeps the RDB name read-only and saves contact details', async () => {
        const user = userEvent.setup();

        render(<BusinessProfile {...props(companyFixture)} />);

        expect(
            screen.getByRole('link', { name: 'Back to Profile' }),
        ).toHaveAttribute('href', '/preview/business-profile');
        expect(
            screen.getByRole('textbox', { name: 'Company name' }),
        ).toBeDisabled();

        const phone = screen.getByRole('textbox', { name: 'Business phone' });

        await user.clear(phone);
        await user.type(phone, '0788-555 111');

        expect(phone).toHaveValue('0788555111');

        await user.clear(
            screen.getByRole('textbox', { name: 'Business email' }),
        );
        await user.type(
            screen.getByRole('textbox', { name: 'Business email' }),
            'hello@greenleaf.rw',
        );
        await user.selectOptions(
            screen.getByRole('combobox', { name: 'Province' }),
            'kigali',
        );

        expect(screen.getByRole('combobox', { name: 'District' })).toHaveValue(
            '',
        );

        await user.selectOptions(
            screen.getByRole('combobox', { name: 'District' }),
            'gasabo',
        );
        await user.clear(screen.getByRole('textbox', { name: 'Sector' }));
        await user.type(
            screen.getByRole('textbox', { name: 'Sector' }),
            'Remera',
        );
        await user.clear(screen.getByRole('textbox', { name: 'Cell' }));
        await user.type(
            screen.getByRole('textbox', { name: 'Cell' }),
            'Rukiri',
        );
        await user.clear(screen.getByRole('textbox', { name: 'Street' }));
        await user.type(
            screen.getByRole('textbox', { name: 'Street' }),
            'KG 11 Ave',
        );
        await user.click(screen.getByRole('button', { name: 'Save changes' }));

        expect(inertia.puts).toEqual([
            {
                url: '/preview/business-profile-company',
                data: {
                    email: 'hello@greenleaf.rw',
                    phone: '0788555111',
                    province: 'kigali',
                    district: 'gasabo',
                    sector: 'Remera',
                    cell: 'Rukiri',
                    street: 'KG 11 Ave',
                },
            },
        ]);
        expect(
            screen.getByText('Company information saved'),
        ).toBeInTheDocument();
    });

    it('offers no districts for a province the server does not list', async () => {
        const user = userEvent.setup();
        const page = props(companyFixture);

        (page.company as CompanyProfile).address.province = 'unknown';
        render(<BusinessProfile {...page} />);

        expect(
            within(
                screen.getByRole('combobox', { name: 'District' }),
            ).getAllByRole('option'),
        ).toHaveLength(1);

        await user.selectOptions(
            screen.getByRole('combobox', { name: 'Province' }),
            'northern',
        );

        expect(
            within(
                screen.getByRole('combobox', { name: 'District' }),
            ).getAllByRole('option'),
        ).toHaveLength(6);
    });

    it('shows saving and the fields the server refused', () => {
        inertia.processing = true;
        inertia.errors = {
            email: 'Enter a valid business email',
            phone: 'Phone number must be 10 digits (9 so far)',
            district: 'Choose a district',
        };
        render(<BusinessProfile {...props(companyFixture)} />);

        expect(screen.getByRole('button', { name: 'Saving…' })).toBeDisabled();
        expect(
            screen.getByRole('textbox', { name: 'Business phone' }),
        ).toHaveAttribute('aria-invalid', 'true');
        expect(
            screen.getByRole('textbox', { name: 'Business email' }),
        ).toHaveAttribute('aria-invalid', 'true');
        expect(
            screen.getByText('Phone number must be 10 digits (9 so far)'),
        ).toBeInTheDocument();
        expect(screen.getByText('Choose a district')).toBeInTheDocument();
    });

    it('shows the certificate and the signatories on the mandate', () => {
        render(<BusinessProfile {...props(companyFixture)} />);

        expect(screen.getByText('RDB/2019/004512')).toBeInTheDocument();
        expect(screen.getByText('Verified')).toBeInTheDocument();
        expect(screen.getByText('No expiry')).toBeInTheDocument();
        expect(
            screen.getByText('2 on the mandate · need 2+'),
        ).toBeInTheDocument();
        expect(screen.getByText('Aline Uwase')).toBeInTheDocument();
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('warns when the certificate has expired', () => {
        render(<BusinessProfile {...props(expiredFixture)} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Your RDB certificate has expired.',
        );
        expect(screen.getByText('Expired')).toBeInTheDocument();
        expect(screen.getByText('31 Aug 2026')).toBeInTheDocument();
    });
});

describe('Linked accounts', () => {
    it('lets only removable accounts be unlinked', () => {
        render(<BusinessProfile {...props(linkedFixture)} />);

        expect(
            screen.getByRole('heading', { name: 'Linked accounts' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Business account ····2231'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Unlink Bank of Kigali' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Unlink MTN Mobile Money' }),
        ).toHaveAttribute('data-method', 'delete');
        expect(
            screen.queryByRole('link', { name: '+ Link a payout account' }),
        ).not.toBeInTheDocument();
    });

    it('offers to link a payout account when there is none', () => {
        const page = props(linkedFixture);

        page.linked = {
            accounts: [],
            add: { url: '/business/bank', method: 'get' },
        };
        render(<BusinessProfile {...page} />);

        expect(
            screen.getByRole('link', { name: '+ Link a payout account' }),
        ).toHaveAttribute('href', '/business/bank');
    });

    it('shows nothing until the accounts arrive', () => {
        const page = props(linkedFixture);

        page.linked = null;
        render(<BusinessProfile {...page} />);

        expect(screen.queryByRole('listitem')).not.toBeInTheDocument();
    });
});

describe('Legal documents', () => {
    it('shows the Terms version the business accepted', () => {
        render(<BusinessProfile {...props(termsFixture)} />);

        expect(
            screen.getByText('Last updated 4 August 2026 · Version 2.5'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/Please read these Terms carefully/),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('heading', {
                name: '1. Acceptance of these Terms',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('legal@rozine.rw')).toBeInTheDocument();
    });

    it('shows the Privacy Note', () => {
        render(<BusinessProfile {...props(privacyFixture)} />);

        expect(
            screen.getByText(
                /This Privacy Note explains what information Rozine collects/,
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('privacy@rozine.rw')).toBeInTheDocument();
    });

    it('shows nothing until the document arrives', () => {
        const page = props(termsFixture);

        page.legal = null;
        render(<BusinessProfile {...page} />);

        expect(screen.queryByText(/Last updated/)).not.toBeInTheDocument();
    });
});

describe('Company information on record', () => {
    it('shows the verified registration and mandate read-only, beside every design section', () => {
        render(<BusinessProfile {...props(recordFixture)} />);

        expect(screen.queryByText(/^Score/u)).not.toBeInTheDocument();
        expect(screen.getByText('✓ Verified')).toBeInTheDocument();
        expect(
            within(screen.getByRole('navigation', { name: 'Profile menu' }))
                .getAllByRole('link')
                .map((link) => link.textContent),
        ).toEqual(MENU);
        expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Save changes' }),
        ).not.toBeInTheDocument();
        expect(screen.queryByText('RDB certificate')).not.toBeInTheDocument();

        expect(screen.getByText('On record')).toBeInTheDocument();
        expect(screen.getByText('RDB company code')).toBeInTheDocument();
        expect(screen.getByText('RDB-104512')).toBeInTheDocument();
        expect(screen.getByText('Agriculture')).toBeInTheDocument();
        expect(screen.getByText('Established')).toBeInTheDocument();
        expect(screen.getByText('2019')).toBeInTheDocument();
        expect(
            screen.getByText(
                'From your verified registration and mandate. They change only when Rozine verifies new records.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('People on the mandate')).toBeInTheDocument();
        expect(
            screen.getByText('3 on the mandate · need 2+'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Controller · Owner · Signatory'),
        ).toBeInTheDocument();
        expect(screen.getByText('Director · Signatory')).toBeInTheDocument();
        expect(screen.getByText('Representative')).toBeInTheDocument();
        expect(screen.getAllByText('Required signatory')).toHaveLength(2);
    });

    it('opens the record as its own page on a phone, and leaves out what is not on file', () => {
        const page = props(recordCompanyFixture);
        const registration = page.registration as CompanyRegistration;

        registration.company_code = null;
        registration.established_year = null;
        registration.people = [
            {
                name: 'Claudine Ingabire',
                roles: ['beneficial_owner', 'owner', 'signatory'],
                signatory: true,
            },
        ];
        registration.signatories_required = 1;
        render(<BusinessProfile {...page} />);

        expect(page.landing).toBe(false);
        expect(
            screen.getByRole('heading', { name: 'Company information' }),
        ).toBeInTheDocument();
        expect(screen.queryByText('RDB company code')).not.toBeInTheDocument();
        expect(screen.queryByText('Established')).not.toBeInTheDocument();
        expect(
            screen.getByText('Beneficial owner · Owner · Signatory'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('1 on the mandate · need 1+'),
        ).toBeInTheDocument();
    });

    it('shows contact details read-only when the server offers no save', () => {
        const page = props(companyFixture);

        page.actions.save_company = null;
        render(<BusinessProfile {...page} />);

        expect(
            screen.queryByRole('button', { name: 'Save changes' }),
        ).not.toBeInTheDocument();
        expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
        expect(screen.getByText('RDB/2019/004512')).toBeInTheDocument();
        expect(screen.queryByText('On record')).not.toBeInTheDocument();
    });
});

describe('Business account sections', () => {
    it('reports two-factor sign-in and opens the security settings for it and the password', () => {
        const { unmount } = render(
            <BusinessProfile {...props(securityFixture)} />,
        );

        expect(
            screen.getByRole('heading', { name: 'Security center' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /Two-factor authentication/u }),
        ).toHaveTextContent('On · authenticator app code at sign-in');
        expect(
            screen.getByRole('link', { name: /Change password/u }),
        ).toHaveAttribute('href', '/preview/business-profile-security');
        expect(screen.queryByText(/Delete account/u)).not.toBeInTheDocument();
        unmount();

        const off = props(securityFixture);

        off.security.two_factor = false;
        render(<BusinessProfile {...off} />);
        expect(
            screen.getByRole('link', { name: /Two-factor authentication/u }),
        ).toHaveTextContent('Off · add an authenticator app code at sign-in');
    });

    it('lists the people on the mandate with their roles and permissions, read-only', () => {
        render(<BusinessProfile {...props(permissionsFixture)} />);

        expect(
            screen.getByRole('heading', { name: 'Permissions & roles' }),
        ).toBeInTheDocument();
        const people = screen.getAllByRole('listitem');

        expect(people).toHaveLength(3);
        expect(people[0]).toHaveTextContent('Robert Mugisha');
        expect(people[0]).toHaveTextContent('ControllerOwnerSignatory');
        expect(people[1]).toHaveTextContent(
            'Sign applications · View the business · Co-sign reports',
        );
        expect(people[2]).toHaveTextContent('View the business');
        expect(
            screen.queryByRole('button', { name: /Invite|Remove|Transfer/u }),
        ).not.toBeInTheDocument();
    });

    it('leaves out a section the server sends no link for', () => {
        const page = props(landingFixture);

        page.links.sections.support = null;
        render(<BusinessProfile {...page} />);
        expect(
            within(screen.getByRole('navigation', { name: 'Profile menu' }))
                .getAllByRole('link')
                .map((link) => link.textContent),
        ).toEqual(MENU.filter((row) => row !== 'Support center›'));
    });

    it('says when a person on the mandate holds no permission', () => {
        const page = props(permissionsFixture);

        page.team = [{ ...page.team[2], permissions: [] }];
        render(<BusinessProfile {...page} />);
        expect(
            screen.getByText('No permissions on this business'),
        ).toBeInTheDocument();
    });

    it.each([
        [supportFixture, 'Support center', 'Coming to the app soon', null],
        [linkedFixture, 'Linked accounts', 'No payout account yet', 'linked'],
        [termsFixture, 'Terms & Conditions', 'Coming to the app soon', 'legal'],
        [privacyFixture, 'Privacy Note', 'Coming to the app soon', 'legal'],
    ] as const)(
        'shows %#: the design section, empty until there is something to read',
        (fixture, heading, title, empty) => {
            const page = props(fixture);

            if (empty === 'linked') {
                page.linked = null;
            }

            if (empty === 'legal') {
                page.legal = null;
            }

            render(<BusinessProfile {...page} />);
            expect(
                screen.getByRole('heading', { name: heading }),
            ).toBeInTheDocument();
            expect(screen.getByText(title)).toBeInTheDocument();
        },
    );
});
