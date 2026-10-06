import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminParties from '@/pages/admin/parties';
import type { AdminPartiesProps, PartyDetail } from '@/types/admin';
import licenceFixture from '../../../resources/fixtures/ui/admin-auditors-licence.json';
import auditorsFixture from '../../../resources/fixtures/ui/admin-auditors.json';
import frozenFixture from '../../../resources/fixtures/ui/admin-businesses-frozen.json';
import businessesFixture from '../../../resources/fixtures/ui/admin-businesses.json';
import overdueFixture from '../../../resources/fixtures/ui/admin-investors-kyc-overdue.json';
import investorsFixture from '../../../resources/fixtures/ui/admin-investors.json';
import staffFixture from '../../../resources/fixtures/ui/admin-staff.json';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminPartiesProps;

const party = (fixture: { props: unknown }): PartyDetail => {
    const detail = props(fixture).party;

    if (detail === null) {
        throw new Error('fixture has a party');
    }

    return detail;
};

beforeEach(resetInertia);

describe('Party directories', () => {
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
    });

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

    it('lists staff with their role and marks the viewer', () => {
        const fixture = props(staffFixture);

        if (fixture.directory.kind === 'staff') {
            fixture.directory.rows.push({
                ...fixture.directory.rows[3],
                id: 'stf_05',
                name: 'Nadia K.',
                role: 'superadmin',
            });
        }

        render(<AdminParties {...fixture} />);

        expect(screen.getByText('4 operators')).toBeInTheDocument();
        expect(screen.getByText('You')).toBeInTheDocument();
        expect(screen.getAllByText('Approver').length).toBeGreaterThan(1);
        expect(screen.getByText('Analyst')).toBeInTheDocument();
        expect(screen.getByText('Super-admin')).toBeInTheDocument();
        expect(screen.queryByRole('combobox')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('region', {
                name: 'Business eligibility rules',
            }),
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
                'Frozen by Grace Kalisa at 2026-09-18 10:20:00. See Controls for the reason and release.',
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
                'Frozen by Grace Kalisa · 2026-09-18 10:20:00',
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
                data: { reason: 'Cleared.' },
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
});
