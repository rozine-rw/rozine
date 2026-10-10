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

    it('lists a Treasury operator, and one without a role as a dash', () => {
        const fixture = props(staffFixture);

        if (fixture.directory.kind === 'staff') {
            fixture.directory.rows = [
                { ...fixture.directory.rows[1], role: 'treasury' },
                { ...fixture.directory.rows[2], role: null },
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
        expect(screen.queryByText('Analyst')).not.toBeInTheDocument();
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
                data: { reason: 'Fraud alert.' },
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
