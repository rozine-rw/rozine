import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminParties from '@/pages/admin/parties';
import type { AdminPartiesProps, PartyDetail } from '@/types/admin';
import type { LicenceQueues } from '@/types/admin-audit';
import licenceFixture from '../../../resources/fixtures/ui/admin-auditors-licence.json';
import auditorsFixture from '../../../resources/fixtures/ui/admin-auditors.json';
import frozenFixture from '../../../resources/fixtures/ui/admin-businesses-frozen.json';
import businessesFixture from '../../../resources/fixtures/ui/admin-businesses.json';
import overdueFixture from '../../../resources/fixtures/ui/admin-investors-kyc-overdue.json';
import investorsFixture from '../../../resources/fixtures/ui/admin-investors.json';
import staffFixture from '../../../resources/fixtures/ui/admin-staff.json';
import { renderWithUser } from '../helpers/render-with-user';
import { textContrastOnSurface } from '../helpers/text-contrast';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminPartiesProps & LicenceQueues;

const party = (fixture: { props: unknown }): PartyDetail => {
    const detail = props(fixture).party;

    if (detail === null) {
        throw new Error('fixture has a party');
    }

    return detail;
};

beforeEach(resetInertia);

describe('Party directories', () => {
    it.each(['light', 'dark'] as const)(
        'keeps the rendered licence queue buttons and translucent chips readable in %s mode',
        (theme) => {
            render(<AdminParties {...props(auditorsFixture)} />);
            const surface = theme === 'light' ? '#ffffff' : '#101a2e';

            for (const [name, chip] of [
                ['Partner change requests', 'RENEWAL'],
                ['ICPAR verification desk', 'PENDING VERIFICATION'],
            ]) {
                const queue = within(screen.getByRole('region', { name }));

                for (const control of [
                    queue.getByRole('link', { name: 'Approve' }),
                    queue.getByText(chip),
                ]) {
                    expect(
                        textContrastOnSurface(control, theme, surface),
                    ).toBeGreaterThanOrEqual(4.5);
                }
            }
        },
    );

    it('lists businesses with their state badges, filters and eligibility policy', () => {
        render(<AdminParties {...props(businessesFixture)} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Business Directory',
        );
        expect(
            screen.getByRole('region', { name: 'Business eligibility rules' }),
        ).toHaveTextContent('RWF 30M');
        expect(screen.getByText('Stable 3.3 / 5')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'All 43' })).toHaveAttribute(
            'aria-current',
            'page',
        );
        expect(
            screen.getByText('Showing first 6 of 43 businesses'),
        ).toBeInTheDocument();
        expect(screen.getAllByText('Frozen').length).toBeGreaterThan(0);
        expect(screen.getByText('KYC overdue')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Rugali Freight' }),
        ).toHaveAttribute('href', '/preview/admin-businesses-frozen');
        expect(screen.getAllByText('Distressed').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Watch').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Healthy').length).toBeGreaterThan(1);
        expect(screen.getByText('1,657')).toBeInTheDocument();
        /* Raised and investors count funded raises only, so they say so: a live raise is not funded. */
        expect(screen.getByText('Capital Funded')).toBeInTheDocument();
        expect(screen.getAllByText('Funded').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Funders').length).toBeGreaterThan(0);

        fireEvent.change(screen.getByRole('combobox', { name: 'Sector' }), {
            target: { value: 'agriculture' },
        });
        expect(inertia.reload).toEqual([{ data: { sector: 'agriculture' } }]);
    });

    it('lists investors with KYC, restriction and frozen states', () => {
        render(<AdminParties {...props(investorsFixture)} />);

        expect(screen.getByText('91%')).toBeInTheDocument();
        expect(screen.getByText('RWF 4.9M')).toBeInTheDocument();
        expect(screen.getAllByText('Restricted').length).toBeGreaterThan(1);
        expect(screen.getByText('Overdue')).toBeInTheDocument();
        expect(screen.getAllByText('Pending').length).toBeGreaterThan(1);
        expect(screen.getAllByText('Frozen').length).toBeGreaterThan(1);
        expect(screen.getAllByText('Verified').length).toBeGreaterThan(1);
        /* The portfolio includes live commitments, while holdings count issued notes only. */
        expect(
            screen.getAllByText('Invested + committed').length,
        ).toBeGreaterThan(0);
    });

    it('names an investor still in onboarding as pending, never blank', () => {
        const fixture = props(investorsFixture);

        if (fixture.directory.kind !== 'investor') {
            throw new Error('fixture lists investors');
        }

        fixture.directory.rows[0] = { ...fixture.directory.rows[0], name: '' };
        render(
            <AdminParties
                {...fixture}
                party={{ ...party(overdueFixture), name: ' ' }}
            />,
        );

        expect(
            screen.getAllByRole('link', { name: 'Name pending' }).length,
        ).toBeGreaterThan(0);
        expect(
            within(screen.getByRole('dialog')).getAllByText('Name pending')
                .length,
        ).toBeGreaterThan(0);
    });

    it("lists an investor's holdings by Business, with the principal and notes held", () => {
        render(
            <AdminParties
                {...props(investorsFixture)}
                party={{
                    ...party(overdueFixture),
                    list: {
                        key: 'holdings',
                        rows: [
                            {
                                id: 'bp_1',
                                title: 'GreenLeaf Agro',
                                tone: 'green',
                                detail: {
                                    kind: 'holding',
                                    value: {
                                        currency: 'RWF',
                                        amount: '800000',
                                    },
                                    notes: 2,
                                },
                            },
                        ],
                    },
                }}
            />,
        );

        const drawer = screen.getByRole('dialog');
        expect(
            within(drawer).getByRole('heading', { name: 'Holdings' }),
        ).toBeInTheDocument();
        expect(within(drawer).getByText('GreenLeaf Agro')).toBeInTheDocument();
        expect(
            within(drawer).getByText('RWF 800,000 · 2 notes'),
        ).toBeInTheDocument();
    });

    it.each([
        ['23300000', true],
        ['0', false],
    ] as const)(
        'explains committed cash %s that is not a holding yet',
        (amount, shown) => {
            render(
                <AdminParties
                    {...props(investorsFixture)}
                    party={{
                        ...party(overdueFixture),
                        list: { key: 'holdings', rows: [] },
                        committed: { currency: 'RWF', amount },
                    }}
                />,
            );

            const note = screen.queryByText(
                /is committed to raises whose notes are not issued yet/u,
            );

            if (shown) {
                expect(note).toHaveTextContent(
                    'RWF 23.3M is committed to raises whose notes are not issued yet. The portfolio counts it; it joins Holdings once the notes issue.',
                );
            } else {
                expect(note).not.toBeInTheDocument();
            }
        },
    );

    it('lists Audit Partners with their standing', () => {
        render(<AdminParties {...props(auditorsFixture)} />);

        expect(screen.getByText('4 Audit Partners')).toBeInTheDocument();
        expect(
            screen.getByText('Uwase & Partners · PPC-0412'),
        ).toBeInTheDocument();
        expect(screen.getByText('96%')).toBeInTheDocument();
        expect(screen.getByText('—')).toBeInTheDocument();
        expect(
            screen.getAllByText('Pending verification').length,
        ).toBeGreaterThan(0);
        expect(screen.getAllByText('Licence expired').length).toBeGreaterThan(
            1,
        );
        expect(screen.getAllByText('Active').length).toBeGreaterThan(1);
        expect(screen.getAllByText('Frozen').length).toBeGreaterThan(0);
    });

    it('queues renewals and first licences above the Audit Partner directory, each opening its decision', () => {
        render(<AdminParties {...props(auditorsFixture)} />);

        const requests = screen.getByRole('region', {
            name: 'Partner change requests',
        });
        const desk = screen.getByRole('region', {
            name: 'ICPAR verification desk',
        });

        expect(
            screen.getByRole('heading', { name: 'CPA registry' }),
        ).toBeInTheDocument();

        expect(
            within(requests).getByText('Jean Habimana, CPA'),
        ).toBeInTheDocument();
        expect(within(requests).getByText('RENEWAL')).toBeInTheDocument();
        expect(within(requests).getByText('PPC-0388-R')).toBeInTheDocument();
        expect(
            within(desk).getByText('Claude Mukamana, CPA'),
        ).toBeInTheDocument();
        expect(
            within(desk).getByText('PENDING VERIFICATION'),
        ).toBeInTheDocument();
        expect(within(desk).getByText('1 Oct 2026')).toBeInTheDocument();
        expect(within(desk).getByText('30 Jun 2027')).toBeInTheDocument();
        expect(
            within(desk).getByRole('link', { name: 'Approve' }),
        ).toHaveAttribute('href', '/preview/admin-auditors-licence');
        expect(
            within(desk).getByRole('link', { name: 'Reject' }),
        ).toHaveAttribute('href', '/preview/admin-auditors-licence');
        expect(within(desk).getByRole('link', { name: 'Reject' })).toHaveClass(
            'bg-rz-surface',
            'text-[#b3352a]',
            'dark:text-[#ff6b6f]',
        );
        expect(within(desk).getByText('Licence')).toHaveClass(
            'text-[#7a5d33]',
            'dark:text-[#f0a060]',
        );
    });

    it('keeps a licence awaiting a name identifiable and reviewable', () => {
        const fixture = props(auditorsFixture);

        fixture.desk = fixture.desk?.map((entry) => ({ ...entry, name: ' ' }));
        render(<AdminParties {...fixture} />);

        const desk = screen.getByRole('region', {
            name: 'ICPAR verification desk',
        });

        expect(within(desk).getByText('Name pending')).toBeInTheDocument();
        expect(within(desk).getByText('?')).toHaveAttribute(
            'aria-hidden',
            'true',
        );
        expect(
            within(desk).getByText('PENDING VERIFICATION'),
        ).toBeInTheDocument();
        expect(
            within(desk).getByRole('link', { name: 'Approve' }),
        ).toHaveAttribute('href', '/preview/admin-auditors-licence');
    });

    it('offers only Review to a viewer who may not decide, and says when a queue is empty', () => {
        const fixture = props(auditorsFixture);

        fixture.desk = [];
        fixture.requests = fixture.requests?.map((entry) => ({
            ...entry,
            links: { review: entry.links.review },
        }));
        render(<AdminParties {...fixture} />);

        expect(
            screen.getByText('No auditor applications awaiting verification.'),
        ).toBeInTheDocument();
        const requests = screen.getByRole('region', {
            name: 'Partner change requests',
        });

        expect(
            within(requests).getByRole('link', { name: 'Review' }),
        ).toBeInTheDocument();
        expect(
            within(requests).queryByRole('link', { name: 'Approve' }),
        ).not.toBeInTheDocument();
    });

    it('shows no licence queues outside the Audit Partner network', () => {
        render(<AdminParties {...props(businessesFixture)} />);

        expect(
            screen.queryByRole('region', { name: 'ICPAR verification desk' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('heading', { name: 'CPA registry' }),
        ).not.toBeInTheDocument();
    });

    it('opens Partner coverage from the Audit Partner directory for staff who may see it', () => {
        const auditors = props(auditorsFixture);
        const { unmount } = render(<AdminParties {...auditors} />);

        expect(
            screen.queryByRole('link', { name: 'Partner coverage' }),
        ).not.toBeInTheDocument();
        unmount();

        render(
            <AdminParties
                {...auditors}
                nav={{
                    ...auditors.nav,
                    coverage: { url: '/admin/coverage', method: 'get' },
                }}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'Partner coverage' }),
        ).toHaveAttribute('href', '/admin/coverage');
    });

    it('lists staff with their role and marks the viewer', () => {
        const fixture = props(staffFixture);

        if (fixture.directory.kind === 'staff') {
            fixture.directory.rows.push({
                ...fixture.directory.rows[3],
                id: 'stf_05',
                name: 'Nadia K.',
                role: 'superadmin',
                roles: ['superadmin'],
            });
        }

        render(<AdminParties {...fixture} />);

        const operators = screen.getByRole('table', { name: 'Operators' });

        expect(screen.getByText('4 operators')).toBeInTheDocument();
        expect(screen.getByText('You')).toBeInTheDocument();
        expect(
            within(operators).getAllByText('Approver').length,
        ).toBeGreaterThan(1);
        expect(within(operators).getByText('Analyst')).toBeInTheDocument();
        expect(
            within(operators).getAllByText('Suspended').length,
        ).toBeGreaterThan(0);
        expect(within(operators).getByText('Super-admin')).toBeInTheDocument();
        expect(screen.queryByRole('combobox')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('region', {
                name: 'Business eligibility rules',
            }),
        ).not.toBeInTheDocument();
    });

    it('says a live Business health is not tracked, in the row and its 360, with no frozen badge', () => {
        const businesses = props(businessesFixture);

        if (businesses.directory.kind === 'business') {
            businesses.directory.rows = [
                {
                    ...businesses.directory.rows[0],
                    health: 'not_tracked',
                    frozen: null,
                },
            ];
        }

        const { unmount } = render(<AdminParties {...businesses} />);
        const row = screen.getAllByRole('row')[1];

        expect(within(row).getByText('Not tracked')).toBeInTheDocument();
        expect(within(row).queryByText('Healthy')).not.toBeInTheDocument();
        expect(within(row).queryByText('Frozen')).not.toBeInTheDocument();
        unmount();

        const frozen = props(frozenFixture);

        if (frozen.party !== null) {
            frozen.party = {
                ...frozen.party,
                health: 'not_tracked',
                freeze: null,
            };
        }

        render(<AdminParties {...frozen} />);

        const drawer = screen.getByRole('dialog');

        expect(within(drawer).getByText('Not tracked')).toBeInTheDocument();
        expect(
            within(drawer).queryByText('Distressed'),
        ).not.toBeInTheDocument();
    });

    it('shows a dash where a live Business or partner has nothing recorded', () => {
        const businesses = props(businessesFixture);
        const auditors = props(auditorsFixture);

        if (businesses.directory.kind === 'business') {
            businesses.directory.rows = [
                {
                    ...businesses.directory.rows[0],
                    capacity_used_pct: null,
                    frozen: false,
                },
            ];
        }

        const { unmount } = render(<AdminParties {...businesses} />);

        expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
        // The head row comes first, then the one Business.
        expect(
            within(screen.getAllByRole('row')[1]).getByText('—'),
        ).toBeInTheDocument();
        unmount();

        if (auditors.directory.kind === 'auditor') {
            auditors.directory.rows = [
                {
                    ...auditors.directory.rows[0],
                    firm: null,
                    district: null,
                    on_time_pct: null,
                    share_mtd: null,
                },
                {
                    ...auditors.directory.rows[1],
                    firm: null,
                    licence: null,
                },
            ];
        }

        render(<AdminParties {...auditors} />);

        const [, diane, jean] = screen.getAllByRole('row');

        expect(within(diane).getByText('Diane Uwase, CPA')).toBeInTheDocument();
        expect(within(diane).getByText('PPC-0412')).toBeInTheDocument();
        expect(within(diane).getAllByText('—')).toHaveLength(3);
        expect(
            within(jean).getByText('Jean Habimana, CPA'),
        ).toBeInTheDocument();
        expect(within(jean).getByText('Huye')).toBeInTheDocument();
    });

    it("shows a staff member's every role in full across the overview", () => {
        const fixture = props(staffFixture);
        const roles = 'Compliance, Approver, Treasury, Analyst';

        fixture.party = {
            ...party(frozenFixture),
            kind: 'staff',
            stats: [{ key: 'role', value: { kind: 'text', value: roles } }],
        };
        render(<AdminParties {...fixture} />);

        const tile = within(screen.getByRole('dialog')).getByText(roles);

        expect(tile).toHaveClass('break-words');
        expect(tile).not.toHaveClass('truncate');
    });

    it('lists the admin action log, newest first, or says it is empty', () => {
        const fixture = props(staffFixture);

        fixture.action_log = [
            {
                id: 'log-1',
                at: fixture.server_time,
                actor: 'A. Diane',
                target: 'Jean-Paul M.',
                action: 'staff_suspended',
                role: null,
                reason: 'On leave until November.',
            },
            {
                id: 'log-2',
                at: fixture.server_time,
                actor: 'Server console',
                target: 'Eric Ndoli',
                action: 'staff_enabled',
                role: null,
                reason: 'Joined operations.',
            },
            {
                id: 'log-3',
                at: fixture.server_time,
                actor: 'A. Diane',
                target: 'Eric Kamanzi',
                action: 'staff_granted',
                role: 'treasury',
                reason: 'Joins the treasury desk.',
            },
        ];
        const { unmount } = render(<AdminParties {...fixture} />);
        const log = screen.getByRole('table', { name: 'Admin action log' });
        const rows = within(log).getAllByRole('row');

        expect(rows[1]).toHaveTextContent('Suspended access');
        expect(rows[1]).toHaveTextContent('A. Diane');
        expect(rows[1]).toHaveTextContent('Jean-Paul M.');
        expect(rows[1]).toHaveTextContent('On leave until November.');
        expect(rows[2]).toHaveTextContent('Enabled access');
        expect(rows[3]).toHaveTextContent('Added as Treasury');
        unmount();

        render(<AdminParties {...{ ...fixture, action_log: [] }} />);
        expect(
            screen.getByText('No admin actions logged yet.'),
        ).toBeInTheDocument();
    });

    it('lets a superadmin invite an operator with one role and a reason', () => {
        inertia.succeed = true;
        render(
            <AdminParties
                {...{
                    ...props(staffFixture),
                    grant: { url: '/admin/staff', method: 'post' },
                }}
            />,
        );

        fireEvent.click(
            screen.getByRole('button', { name: '+ Invite operator' }),
        );
        const form = screen.getByRole('form', { name: 'Invite an operator' });
        const submit = within(form).getByRole('button', {
            name: 'Give access',
        });

        expect(submit).toBeDisabled();
        fireEvent.change(within(form).getByLabelText('Account email'), {
            target: { value: 'eric.kamanzi@example.test' },
        });
        fireEvent.change(within(form).getByLabelText('Role'), {
            target: { value: 'treasury' },
        });
        fireEvent.change(within(form).getByLabelText('Reason for the record'), {
            target: { value: 'Joins the treasury desk.' },
        });
        fireEvent.click(submit);

        expect(inertia.posts).toEqual([
            {
                url: '/admin/staff',
                data: expect.objectContaining({
                    email: 'eric.kamanzi@example.test',
                    role: 'treasury',
                    reason: 'Joins the treasury desk.',
                }),
            },
        ]);
        expect(
            screen.getByRole('button', { name: '+ Invite operator' }),
        ).toBeInTheDocument();
    });

    it('lets a verifier onboard a company with its representative and evidence', () => {
        inertia.succeed = true;
        render(
            <AdminParties
                {...{
                    ...props(businessesFixture),
                    onboard: {
                        url: '/admin/businesses/onboard',
                        method: 'post',
                    },
                }}
            />,
        );

        fireEvent.click(
            screen.getByRole('button', { name: '+ Onboard a business' }),
        );
        const form = screen.getByRole('form', { name: 'Onboard a business' });
        const submit = within(form).getByRole('button', {
            name: 'Onboard business',
        });
        const field = (label: string, value: string) =>
            fireEvent.change(within(form).getByLabelText(label), {
                target: { value },
            });

        expect(submit).toBeDisabled();
        field('RDB company code', '12a3456789');
        expect(within(form).getByLabelText('RDB company code')).toHaveValue(
            '123456789',
        );
        field('Business name', 'Kigali Fresh Ltd');
        field('Sector', 'Agribusiness');
        field('District', 'Gasabo');
        field("Representative's account email", 'aline@example.test');
        field('Evidence checked', 'RDB certificate and ID checked');
        field('Reason for the record', 'Onboarding for the pilot.');
        expect(submit).toBeDisabled();
        fireEvent.click(within(form).getByRole('checkbox'));
        fireEvent.click(submit);

        expect(inertia.posts).toEqual([
            {
                url: '/admin/businesses/onboard',
                data: expect.objectContaining({
                    kind: 'organization',
                    rdb_code: '123456789',
                    name: 'Kigali Fresh Ltd',
                    established_year: null,
                    representative_email: 'aline@example.test',
                    attested: true,
                }),
            },
        ]);
        expect(
            screen.getByRole('button', { name: '+ Onboard a business' }),
        ).toBeInTheDocument();
    });

    it('onboards a sole trader without an RDB code and can be cancelled', () => {
        render(
            <AdminParties
                {...{
                    ...props(businessesFixture),
                    onboard: {
                        url: '/admin/businesses/onboard',
                        method: 'post',
                    },
                }}
            />,
        );

        fireEvent.click(
            screen.getByRole('button', { name: '+ Onboard a business' }),
        );
        const form = screen.getByRole('form', { name: 'Onboard a business' });
        fireEvent.click(within(form).getByLabelText('Sole trader'));
        expect(
            within(form).queryByLabelText('RDB company code'),
        ).not.toBeInTheDocument();
        for (const [label, value] of [
            ['Business name', 'Aline Tailoring'],
            ['Sector', 'Retail'],
            ['District', 'Huye'],
            ['Year established (optional)', '20x19'],
            ["Representative's account email", 'aline@example.test'],
            ['Evidence checked', 'National ID checked'],
            ['Reason for the record', 'Sole trader joins the pilot.'],
        ]) {
            fireEvent.change(within(form).getByLabelText(label), {
                target: { value },
            });
        }
        fireEvent.click(within(form).getByRole('checkbox'));
        fireEvent.click(
            within(form).getByRole('button', { name: 'Onboard business' }),
        );

        expect(inertia.posts[0]?.data).toEqual(
            expect.objectContaining({
                kind: 'person',
                rdb_code: null,
                established_year: '2019',
            }),
        );
        fireEvent.click(within(form).getByRole('button', { name: 'Cancel' }));
        expect(
            screen.queryByRole('form', { name: 'Onboard a business' }),
        ).not.toBeInTheDocument();
    });

    it('shows onboarding refusals beside their fields', () => {
        inertia.errors = { rdb_code: 'The RDB company code is 9 digits.' };
        render(
            <AdminParties
                {...{
                    ...props(businessesFixture),
                    onboard: {
                        url: '/admin/businesses/onboard',
                        method: 'post',
                    },
                }}
            />,
        );
        fireEvent.click(
            screen.getByRole('button', { name: '+ Onboard a business' }),
        );

        expect(screen.getByLabelText('RDB company code')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(
            screen.getByText('The RDB company code is 9 digits.'),
        ).toBeInTheDocument();
    });

    it('says whether the new business representative can open the Business app yet', () => {
        const fixture = props(businessesFixture);
        const { unmount } = render(
            <AdminParties
                {...{
                    ...fixture,
                    onboarded: {
                        business_id: '01jbusiness',
                        representative: 'Aline Uwase',
                        business_access: false,
                    },
                }}
            />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(
            'Aline Uwase needs Business access from an identity operator',
        );
        expect(
            screen.queryByRole('button', { name: '+ Onboard a business' }),
        ).not.toBeInTheDocument();
        unmount();
        render(
            <AdminParties
                {...{
                    ...fixture,
                    onboarded: {
                        business_id: '01jbusiness',
                        representative: 'Aline Uwase',
                        business_access: true,
                    },
                }}
            />,
        );
        expect(screen.getByRole('status')).toHaveTextContent(
            'Aline Uwase can open it in the Business app now.',
        );
    });

    it('says which role the viewer signed in as and how much it may do', () => {
        const fixture = props(staffFixture);
        const { unmount } = render(
            <AdminParties
                {...{ ...fixture, access: { granted: 4, total: 22 } }}
            />,
        );

        expect(screen.getByText('Signed in as')).toBeInTheDocument();
        expect(
            screen.getByText('4 of 22 capabilities', { exact: false }),
        ).toBeInTheDocument();
        unmount();
        render(
            <AdminParties
                {...{ ...fixture, access: { granted: 22, total: 22 } }}
            />,
        );
        expect(
            screen.getByText('Full access', { exact: false }),
        ).toBeInTheDocument();
    });

    it('hides the invite for operators who cannot manage staff', () => {
        render(<AdminParties {...props(staffFixture)} />);
        expect(
            screen.queryByRole('button', { name: '+ Invite operator' }),
        ).not.toBeInTheDocument();
    });

    it('shows every role an operator holds and what each role may do', () => {
        render(<AdminParties {...props(staffFixture)} />);

        const eric = screen.getByRole('row', { name: /Eric Ndoli/ });

        expect(within(eric).getByText('Approver')).toBeInTheDocument();
        expect(within(eric).getByText('Treasury')).toBeInTheDocument();

        const matrix = screen.getByRole('table', { name: 'Role permissions' });

        expect(
            within(matrix)
                .getAllByRole('columnheader')
                .map((cell) => cell.textContent),
        ).toEqual([
            'Capability',
            'What it allows',
            'Super-admin',
            'Compliance',
            'Approver',
            'Treasury',
            'Analyst',
        ]);
        expect(
            screen.getByText(
                'What each tier may do. Lower tiers are blocked from destructive actions, not just hidden from them.',
            ),
        ).toBeInTheDocument();

        const remove = within(matrix).getByRole('row', { name: /^Delete/ });

        expect(
            within(remove).getByText(
                'No role, by rule: ledger and audit entries are never deleted',
            ),
        ).toBeInTheDocument();
        expect(within(remove).getAllByLabelText('Not allowed')).toHaveLength(5);

        // Capabilities the design names carry its words.
        expect(
            within(matrix).getByRole('row', { name: /^KYC decisions/ }),
        ).toHaveTextContent('Verify or reject investor identity documents');
        expect(
            within(matrix).getByRole('row', { name: /^Approvals/ }),
        ).toBeInTheDocument();
        expect(
            within(matrix).getByRole('row', { name: /^Freeze \/ suspend/ }),
        ).toHaveTextContent('Pause an account’s access to either app.');

        const requery = within(matrix).getByRole('row', {
            name: /Requery disbursements/,
        });

        expect(
            within(requery).getByText(
                "Ask the payment provider for a disbursement's current state.",
            ),
        ).toBeInTheDocument();
        expect(
            within(requery)
                .getAllByRole('cell')
                .slice(2)
                .map((cell) => cell.getAttribute('aria-label')),
        ).toEqual([
            'Not allowed',
            'Not allowed',
            'Not allowed',
            'Allowed',
            'Not allowed',
        ]);
        expect(within(matrix).getAllByRole('row')).toHaveLength(28);
    });

    it('shows no permission matrix outside Staff & Roles', () => {
        render(<AdminParties {...props(investorsFixture)} />);

        expect(
            screen.queryByRole('table', { name: 'Role permissions' }),
        ).not.toBeInTheDocument();
    });

    it('lists a Treasury operator, and one without a role as a dash', () => {
        const fixture = props(staffFixture);

        if (fixture.directory.kind === 'staff') {
            fixture.directory.rows = [
                {
                    ...fixture.directory.rows[1],
                    role: 'treasury',
                    roles: ['treasury'],
                },
                { ...fixture.directory.rows[2], role: null, roles: [] },
            ];
        }

        render(<AdminParties {...fixture} />);

        expect(
            within(screen.getByRole('row', { name: /Eric Ndoli/ })).getByText(
                'Treasury',
            ),
        ).toBeInTheDocument();
        expect(
            within(screen.getByRole('row', { name: /Grace Kalisa/ })).getByText(
                '—',
            ),
        ).toBeInTheDocument();
        expect(
            within(
                screen.getByRole('table', { name: 'Operators' }),
            ).queryByText('Analyst'),
        ).not.toBeInTheDocument();
    });

    it('says when nothing matches, and without stats', () => {
        render(
            <AdminParties
                {...props(businessesFixture)}
                stats={[]}
                shown={0}
                total={0}
                search="zzz"
                directory={{ kind: 'business', rows: [] }}
            />,
        );

        expect(
            screen.getByText('No businesses match these filters.'),
        ).toBeInTheDocument();
        expect(screen.getByText('0 businesses')).toBeInTheDocument();
        expect(screen.getByText('No matches on this page')).toBeInTheDocument();
    });
});

describe('Party 360', () => {
    it('opens a frozen business with its attribution and releases only with a reason', async () => {
        inertia.succeed = true;
        const { user } = renderWithUser(
            <AdminParties {...props(frozenFixture)} />,
        );
        const drawer = screen.getByRole('dialog', {
            name: 'Rugali Freight, party record',
        });

        expect(
            within(drawer).getByText(
                'Frozen by Grace Kalisa at 2026-09-18 10:20:00 CAT. See Controls for the reason and release.',
            ),
        ).toBeInTheDocument();
        expect(
            within(drawer).getByText('Distressed 1.8 / 5'),
        ).toBeInTheDocument();
        expect(within(drawer).getByText('100%')).toBeInTheDocument();
        expect(within(drawer).getAllByText('Repaying')).toHaveLength(2);
        expect(within(drawer).getByText('Failed')).toBeInTheDocument();

        await user.click(within(drawer).getByRole('tab', { name: 'Activity' }));
        expect(
            within(drawer).getByText('Investment RWF 48.5M received'),
        ).toBeInTheDocument();

        await user.click(within(drawer).getByRole('tab', { name: 'Controls' }));
        expect(
            within(drawer).getByText(
                'Frozen by Grace Kalisa · 2026-09-18 10:20:00 CAT',
            ),
        ).toBeInTheDocument();
        expect(
            within(drawer).getByRole('region', { name: 'KYC & verification' }),
        ).toHaveTextContent('Re-verification due 1 Mar 2027');
        await user.click(
            within(drawer).getByRole('button', { name: 'Reactivate account' }),
        );
        const stage = within(drawer).getByRole('form', {
            name: 'Reactivate Rugali Freight',
        });

        expect(stage).toHaveTextContent(
            'Access to the Business app comes back at once.',
        );
        await user.type(within(stage).getByRole('textbox'), 'Cleared.');
        await user.click(
            within(stage).getByRole('button', { name: 'Reactivate account' }),
        );
        expect(inertia.posts).toEqual([
            {
                url: '/admin/parties/biz_rugali/release',
                data: {
                    reason: 'Cleared.',
                    request_id: expect.any(String),
                },
            },
        ]);
        expect(within(drawer).getByText('Freeze history')).toBeInTheDocument();
    });

    it('shows a KYC-overdue investor and verifies, rejects or freezes with a reason', async () => {
        const { user } = renderWithUser(
            <AdminParties {...props(overdueFixture)} />,
        );

        expect(
            screen.getByText(/KYC re-verification overdue since 1 Sept? 2026/),
        ).toBeInTheDocument();
        expect(screen.getByText('GreenLeaf Agro')).toBeInTheDocument();
        expect(screen.getByText('RWF 400,000')).toBeInTheDocument();

        await user.click(screen.getByRole('tab', { name: 'Controls' }));
        await user.click(screen.getByRole('button', { name: '✓ Verify KYC' }));
        expect(
            screen.getByRole('form', { name: 'Verify KYC for Marie Mugisha' }),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        await user.click(screen.getByRole('button', { name: 'Reject' }));
        expect(
            screen.getByRole('form', { name: 'Reject KYC for Marie Mugisha' }),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        await user.click(
            screen.getByRole('button', { name: 'Freeze account' }),
        );
        const stage = screen.getByRole('form', {
            name: 'Freeze Marie Mugisha',
        });

        expect(stage).toHaveTextContent(
            'Access to the Investor app stops at once.',
        );
        expect(
            screen.getByText('This account has never been frozen.'),
        ).toBeInTheDocument();
    });

    it('opens a live investor waiting for review on Controls, shows their documents and decides back to the directory', async () => {
        inertia.succeed = true;
        vi.spyOn(crypto, 'randomUUID').mockReturnValue(
            '9b2f6f5c-6c1e-4f2b-8a37-2f1c6f4b9d10',
        );
        const link = (url: string) => ({ url, method: 'get' as const });
        const detail: PartyDetail = {
            ...party(overdueFixture),
            actions: {},
            verification: {
                id: 'case-a',
                revision: 6,
                status: 'submitted',
                submitted_at: '2026-10-06T08:00:00+00:00',
                account: { name: 'Marie Mugisha', email: 'marie@example.rw' },
                date_of_birth: '01 / 05 / 1990',
                id_type: 'national_id',
                id_number: '1199080012345678',
                decision: null,
                documents: [
                    {
                        id: 'doc-front',
                        slot: 'front',
                        filename: 'front.png',
                        media_type: 'image/png',
                        size_bytes: 1500,
                        sha256: 'b'.repeat(64),
                        uploaded_at: '2026-10-06T07:10:00+00:00',
                        current: true,
                        link: link(
                            '/admin/investor-verifications/case-a/documents/doc-front',
                        ),
                        view: link(
                            '/admin/investor-verifications/case-a/documents/doc-front?disposition=inline',
                        ),
                    },
                ],
                history: [
                    {
                        revision: 6,
                        status: 'submitted',
                        command: 'verification.submit',
                        reason: null,
                        at: '2026-10-06T08:00:00+00:00',
                    },
                ],
                links: { close: link('/admin/investors') },
                actions: {
                    approve: {
                        url: '/admin/investor-verifications/case-a/approve',
                        method: 'post',
                    },
                },
            },
        };
        const { user } = renderWithUser(
            <AdminParties {...props(overdueFixture)} party={detail} />,
        );

        expect(screen.getByRole('tab', { name: 'Controls' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        const submission = screen.getByRole('region', {
            name: 'Identity submission',
        });
        expect(
            within(submission).getByRole('button', { name: 'View ID front' }),
        ).toBeInTheDocument();
        expect(
            within(submission).queryByRole('button', { name: 'Reject' }),
        ).not.toBeInTheDocument();

        await user.click(
            within(submission).getByRole('button', { name: 'Approve' }),
        );
        const stage = screen.getByRole('form', {
            name: 'Approve this identity',
        });
        await user.type(
            within(stage).getByRole('textbox'),
            'Photo, number and selfie match.',
        );
        await user.click(
            within(stage).getByRole('button', { name: 'Approve identity' }),
        );

        expect(inertia.posts).toEqual([
            {
                url: '/admin/investor-verifications/case-a/approve',
                data: {
                    reason: 'Photo, number and selfie match.',
                    request_id: '9b2f6f5c-6c1e-4f2b-8a37-2f1c6f4b9d10',
                    expected_revision: 6,
                    return_to: 'directory',
                },
            },
        ]);
    });

    it('opens a live investor without a waiting submission on Overview', () => {
        renderWithUser(
            <AdminParties
                {...props(overdueFixture)}
                party={{ ...party(overdueFixture), verification: null }}
            />,
        );

        expect(screen.getByRole('tab', { name: 'Overview' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
    });

    it('verifies or rejects an Audit Partner licence with a reason', async () => {
        const { user } = renderWithUser(
            <AdminParties {...props(licenceFixture)} />,
        );

        expect(screen.getByText('Nothing here yet.')).toBeInTheDocument();
        await user.click(screen.getByRole('tab', { name: 'Controls' }));
        const licence = screen.getByRole('region', {
            name: 'ICPAR licence & verification',
        });

        expect(within(licence).getByText('ICPAR-M-2231')).toBeInTheDocument();
        expect(within(licence).getByText('Pending')).toBeInTheDocument();
        await user.click(
            within(licence).getByRole('button', { name: '✓ Verify licence' }),
        );
        expect(
            screen.getByRole('form', {
                name: 'Verify licence for Claude Mukamana, CPA',
            }),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        await user.click(
            within(licence).getByRole('button', { name: 'Reject' }),
        );
        expect(
            screen.getByRole('form', {
                name: 'Reject licence for Claude Mukamana, CPA',
            }),
        ).toBeInTheDocument();
    });

    it('says an Audit Partner gets no jobs without a verified office, and records one with its evidence', async () => {
        inertia.succeed = true;
        vi.spyOn(crypto, 'randomUUID').mockReturnValue(
            '2c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f',
        );
        const fixture = props(licenceFixture);

        fixture.party = {
            ...party(licenceFixture),
            office: {
                state: 'missing',
                latitude: null,
                longitude: null,
                uncertainty_m: null,
                verified_at: null,
                reference: null,
                revision: 0,
                verify: {
                    url: '/admin/auditors/aud_claude/office',
                    method: 'post',
                    verified_at: '2026-10-26T05:00:00Z',
                },
            },
        };
        const { user } = renderWithUser(<AdminParties {...fixture} />);

        await user.click(screen.getByRole('tab', { name: 'Controls' }));
        const office = screen.getByRole('region', {
            name: 'Registered office',
        });

        expect(within(office).getByText('Not verified')).toBeInTheDocument();
        expect(office).toHaveTextContent(
            'No office location is on record, so this partner is not offered jobs.',
        );
        await user.click(
            within(office).getByRole('button', {
                name: 'Record verified office',
            }),
        );
        const form = within(office).getByRole('form', {
            name: 'Verify the registered office',
        });
        const submit = within(form).getByRole('button', {
            name: 'Record as verified',
        });

        expect(submit).toBeDisabled();
        await user.type(within(form).getByLabelText('Latitude'), '-1.9441');
        await user.type(within(form).getByLabelText('Longitude'), '30.0619');
        await user.type(within(form).getByLabelText('Accuracy (metres)'), '50');
        await user.type(
            within(form).getByLabelText('Evidence checked'),
            'ICPAR register',
        );
        await user.type(
            within(form).getByLabelText('Reason'),
            'Confirmed by phone.',
        );
        await user.click(submit);

        expect(inertia.posts).toEqual([
            {
                url: '/admin/auditors/aud_claude/office',
                data: {
                    latitude: '-1.9441',
                    longitude: '30.0619',
                    uncertainty_m: '50',
                    reference: 'ICPAR register',
                    reason: 'Confirmed by phone.',
                    request_id: '2c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f',
                    expected_revision: 0,
                    verified_at: '2026-10-26T05:00:00Z',
                },
            },
        ]);
        expect(
            within(office).queryByRole('form', {
                name: 'Verify the registered office',
            }),
        ).not.toBeInTheDocument();
    });

    it.each([
        ['verified', 'Verified', 'Verify office again', null],
        [
            'expired',
            'Verification lapsed',
            'Verify office again',
            'This office was verified more than a year ago',
        ],
    ] as const)(
        'shows a %s office with its point, date and evidence',
        async (state, chip, button, note) => {
            const fixture = props(licenceFixture);

            fixture.party = {
                ...party(licenceFixture),
                office: {
                    state,
                    latitude: '-1.9441000',
                    longitude: '30.0619000',
                    uncertainty_m: 20,
                    verified_at: '2026-09-24T10:00:00Z',
                    reference: 'ICPAR register entry',
                    revision: 2,
                    verify: {
                        url: '/admin/auditors/aud_claude/office',
                        method: 'post',
                        verified_at: '2026-10-26T05:00:00Z',
                    },
                },
            };
            const { user } = renderWithUser(<AdminParties {...fixture} />);

            await user.click(screen.getByRole('tab', { name: 'Controls' }));
            const office = screen.getByRole('region', {
                name: 'Registered office',
            });

            expect(within(office).getByText(chip)).toBeInTheDocument();
            expect(office).toHaveTextContent('-1.9441000, 30.0619000 (±20 m)');
            expect(office).toHaveTextContent('24 Sept 2026');
            expect(office).toHaveTextContent('ICPAR register entry');
            expect(
                within(office).getByRole('button', { name: button }),
            ).toBeInTheDocument();

            if (note === null) {
                expect(office).not.toHaveTextContent('not offered jobs');
            } else {
                expect(office).toHaveTextContent(note);
            }
        },
    );

    it('says no Audit Partner is dispatched to a Business without verified premises, and records them', async () => {
        inertia.succeed = true;
        vi.spyOn(crypto, 'randomUUID').mockReturnValue(
            '3c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f',
        );
        const fixture = props(frozenFixture);

        fixture.party = {
            ...party(frozenFixture),
            premises: {
                state: 'expired',
                latitude: '-2.6000000',
                longitude: '29.7400000',
                uncertainty_m: 30,
                verified_at: '2025-09-24T08:00:00Z',
                reference: 'Site visit',
                revision: 2,
                verify: {
                    url: '/admin/businesses/biz_1/premises',
                    method: 'post',
                    verified_at: '2026-10-26T05:00:00Z',
                },
            },
        };
        const { user } = renderWithUser(<AdminParties {...fixture} />);

        await user.click(screen.getByRole('tab', { name: 'Controls' }));
        const premises = screen.getByRole('region', {
            name: 'Business premises',
        });

        expect(
            within(premises).getByText('Verification lapsed'),
        ).toBeInTheDocument();
        expect(premises).toHaveTextContent(
            'no Audit Partner is dispatched here until they are verified again',
        );
        await user.click(
            within(premises).getByRole('button', {
                name: 'Verify premises again',
            }),
        );
        const form = within(premises).getByRole('form', {
            name: 'Verify the business premises',
        });

        await user.type(within(form).getByLabelText('Latitude'), '-2.6');
        await user.type(within(form).getByLabelText('Longitude'), '29.74');
        await user.type(within(form).getByLabelText('Accuracy (metres)'), '30');
        await user.type(
            within(form).getByLabelText('Evidence checked'),
            'Site visit',
        );
        await user.type(within(form).getByLabelText('Reason'), 'Visited.');
        await user.click(
            within(form).getByRole('button', { name: 'Record as verified' }),
        );

        expect(inertia.posts).toEqual([
            {
                url: '/admin/businesses/biz_1/premises',
                data: {
                    latitude: '-2.6',
                    longitude: '29.74',
                    uncertainty_m: '30',
                    reference: 'Site visit',
                    reason: 'Visited.',
                    request_id: '3c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f',
                    expected_revision: 2,
                    verified_at: '2026-10-26T05:00:00Z',
                },
            },
        ]);
    });

    it('shows the office read-only to a viewer who may not verify it', async () => {
        const fixture = props(licenceFixture);

        fixture.party = {
            ...party(licenceFixture),
            office: {
                state: 'missing',
                latitude: null,
                longitude: null,
                uncertainty_m: null,
                verified_at: null,
                reference: null,
                revision: 0,
                verify: null,
            },
        };
        const { user } = renderWithUser(<AdminParties {...fixture} />);

        await user.click(screen.getByRole('tab', { name: 'Controls' }));
        const office = screen.getByRole('region', {
            name: 'Registered office',
        });

        expect(within(office).queryByRole('button')).not.toBeInTheDocument();
    });

    it('opens on the decision the review queue chose, with its reason stage ready', () => {
        const fixture = props(licenceFixture);
        const detail = party(licenceFixture);

        fixture.party = {
            ...detail,
            licence: detail.licence && {
                ...detail.licence,
                review: { revision: 3, submission_id: 'sub-a' },
            },
            stage: 'reject_licence',
        };
        render(<AdminParties {...fixture} />);

        expect(screen.getByRole('tab', { name: 'Controls' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(
            screen.getByRole('form', {
                name: 'Reject licence for Claude Mukamana, CPA',
            }),
        ).toBeInTheDocument();
    });

    it('decides a live waiting licence with the revision and submission it answers', async () => {
        inertia.succeed = true;
        vi.spyOn(crypto, 'randomUUID').mockReturnValue(
            '1c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f',
        );
        const detail = party(licenceFixture);
        const fixture = props(licenceFixture);

        fixture.party = {
            ...detail,
            licence: detail.licence && {
                ...detail.licence,
                member_id: null,
                district: null,
                review: { revision: 3, submission_id: 'sub-a' },
            },
            actions: {
                ...detail.actions,
                freeze: {
                    url: '/admin/parties/aud_claude/freeze',
                    method: 'post',
                },
            },
        };
        const { user } = renderWithUser(<AdminParties {...fixture} />);

        await user.click(screen.getByRole('tab', { name: 'Controls' }));
        const licence = screen.getByRole('region', {
            name: 'ICPAR licence & verification',
        });

        expect(within(licence).getAllByText('—')).toHaveLength(2);
        expect(within(licence).getByText('PPC-0501')).toBeInTheDocument();

        for (const [button, form, reason] of [
            [
                '✓ Verify licence',
                'Verify licence for Claude Mukamana, CPA',
                'Register checked today.',
            ],
            [
                'Reject',
                'Reject licence for Claude Mukamana, CPA',
                'Not on the register.',
            ],
        ] as const) {
            await user.click(
                within(licence).getByRole('button', { name: button }),
            );
            const stage = screen.getByRole('form', { name: form });

            await user.type(within(stage).getByRole('textbox'), reason);
            await user.click(
                within(stage).getByRole('button', {
                    name:
                        button === 'Reject'
                            ? 'Reject licence'
                            : 'Verify licence',
                }),
            );
        }

        await user.click(
            screen.getByRole('button', { name: 'Freeze account' }),
        );
        const freeze = screen.getByRole('form', {
            name: 'Freeze Claude Mukamana, CPA',
        });

        await user.type(within(freeze).getByRole('textbox'), 'Fraud alert.');
        await user.click(
            within(freeze).getByRole('button', { name: 'Freeze account' }),
        );

        const answers = {
            request_id: '1c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f',
            expected_revision: 3,
            submission_id: 'sub-a',
        };

        expect(inertia.posts).toEqual([
            {
                url: '/admin/parties/aud_claude/licence/verify',
                data: { reason: 'Register checked today.', ...answers },
            },
            {
                url: '/admin/parties/aud_claude/licence/reject',
                data: { reason: 'Not on the register.', ...answers },
            },
            {
                url: '/admin/parties/aud_claude/freeze',
                data: {
                    reason: 'Fraud alert.',
                    request_id: answers.request_id,
                },
            },
        ]);
    });

    it('reads a legal hold, verified and expired licences, and a staff record', async () => {
        const detail = party(licenceFixture);
        const fixture = props(licenceFixture);

        fixture.party = {
            ...detail,
            licence: detail.licence && { ...detail.licence, state: 'verified' },
            freeze: {
                actor: 'Legal',
                at: '2026-09-01T09:00:00+02:00',
                reason: null,
            },
            release_blocked: 'LEGAL_HOLD',
            actions: {},
            list: null,
            history: [],
            health: 'watch',
            stats: [{ key: 'rating', value: { kind: 'rating', value: null } }],
        };
        const { user, unmount } = renderWithUser(<AdminParties {...fixture} />);

        expect(screen.getByText('Pending audit')).toBeInTheDocument();

        await user.click(screen.getByRole('tab', { name: 'Activity' }));
        expect(
            screen.getByText('No activity recorded yet.'),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('tab', { name: 'Controls' }));
        expect(
            screen.getByText(
                'A legal hold keeps this account frozen. Only Compliance or Legal can release it.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('Verified')).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Reactivate account' }),
        ).not.toBeInTheDocument();
        unmount();

        fixture.party = {
            ...detail,
            kind: 'staff',
            health: 'active',
            licence: detail.licence && { ...detail.licence, state: 'expired' },
            kyc: { state: 'rejected', due_on: null },
            list: {
                key: 'engagements',
                rows: [
                    {
                        id: 'e1',
                        title: 'Engagement',
                        tone: 'grey',
                        detail: { kind: 'text', value: 'Sealed' },
                    },
                ],
            },
            actions: {},
        };
        const view = renderWithUser(<AdminParties {...fixture} />);

        expect(screen.getByText('Sealed')).toBeInTheDocument();
        await view.user.click(screen.getByRole('tab', { name: 'Controls' }));
        expect(screen.getByText('Expired')).toBeInTheDocument();
        expect(screen.getByText('Rejected')).toBeInTheDocument();
        expect(screen.getByText(/in the Admin app/)).toBeInTheDocument();
    });

    it('reads a frozen operator once, with who disabled them and their access history', async () => {
        const fixture = props(staffFixture);

        fixture.party = {
            id: '7',
            kind: 'staff',
            name: 'Jean-Paul M.',
            subtitle: 'jp@rozine.rw',
            health: 'frozen',
            stats: [
                {
                    key: 'role',
                    value: { kind: 'text', value: 'Approver, Treasury' },
                },
            ],
            list: null,
            history: [
                {
                    id: 'h1',
                    at: '2026-09-22T09:00:00+02:00',
                    actor: 'Server console',
                    action: {
                        code: 'staff.configure',
                        label: 'Changed staff access',
                        tone: 'purple',
                    },
                    reason: 'Left the operations team.',
                },
            ],
            kyc: null,
            licence: null,
            freeze: {
                actor: 'Server console',
                at: '2026-09-22T09:00:00+02:00',
                reason: 'Left the operations team.',
            },
            release_blocked: null,
            restrictions: [],
            links: { close: { url: '/admin/staff', method: 'get' } },
            actions: {},
        };
        const { user } = renderWithUser(<AdminParties {...fixture} />);
        const drawer = screen.getByRole('dialog', {
            name: /Jean-Paul M\./,
        });

        expect(within(drawer).getAllByText('Frozen')).toHaveLength(1);
        expect(
            within(drawer).getByText('Approver, Treasury'),
        ).toBeInTheDocument();
        await user.click(within(drawer).getByRole('tab', { name: 'Activity' }));
        expect(
            within(drawer).getByText('Changed staff access'),
        ).toBeInTheDocument();
    });
});

describe('Super-admin approvals', () => {
    const proposal = {
        id: 'proposal-1',
        recipient: 'New Operator',
        email: 'operator@example.test',
        requester: 'First Manager',
        operation: 'grant' as const,
        reason: 'Joining administration.',
        approve: {
            url: '/admin/staff/approvals/proposal-1',
            method: 'post' as const,
        },
    };

    it('shows pending access unchanged and submits only the independent approval reason and key', async () => {
        const fixture = props(staffFixture);
        fixture.pending_approvals = [proposal];
        const { user } = renderWithUser(<AdminParties {...fixture} />);
        const region = screen.getByRole('region', {
            name: 'Awaiting super-admin approval',
        });
        expect(
            within(region).getByText(/access stays unchanged/),
        ).toBeInTheDocument();
        expect(
            within(region).getByText(
                'First Manager requested super-admin access.',
            ),
        ).toBeInTheDocument();
        const form = within(region).getByRole('form', {
            name: 'Approve access for New Operator',
        });
        const button = within(form).getByRole('button', {
            name: 'Approve super-admin access',
        });
        expect(button).toBeDisabled();
        await user.type(
            within(form).getByRole('textbox'),
            'Independent review.',
        );
        await user.click(button);
        expect(inertia.posts).toEqual([
            {
                url: proposal.approve.url,
                data: {
                    request_id: expect.any(String),
                    reason: 'Independent review.',
                },
            },
        ]);
    });

    it('explains the second approval before a super-admin request is submitted', async () => {
        const fixture = props(staffFixture);
        fixture.grant = { url: '/admin/staff', method: 'post' };
        const { user } = renderWithUser(<AdminParties {...fixture} />);
        await user.click(
            screen.getByRole('button', { name: /Invite operator/ }),
        );
        await user.selectOptions(screen.getByRole('combobox'), 'superadmin');
        expect(screen.getByText(/access stays unchanged/)).toBeInTheDocument();
    });

    it('shows no approval control to the requester or on read-only projections and handles an empty queue', () => {
        const fixture = props(staffFixture);
        fixture.pending_approvals = [
            { ...proposal, operation: 'reactivate', approve: null },
        ];
        const { unmount } = render(<AdminParties {...fixture} />);
        expect(
            screen.getByText(
                'First Manager requested super-admin reactivation.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Waiting for a different super-admin to approve.'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', {
                name: 'Approve super-admin access',
            }),
        ).not.toBeInTheDocument();
        unmount();
        render(<AdminParties {...fixture} pending_approvals={[]} />);
        expect(
            screen.queryByRole('region', {
                name: 'Awaiting super-admin approval',
            }),
        ).not.toBeInTheDocument();
    });
});
