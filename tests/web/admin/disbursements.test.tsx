import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminDisbursements from '@/pages/admin/disbursements';
import type { AdminDisbursementsProps } from '@/types/admin';
import checkFixture from '../../../resources/fixtures/ui/admin-disbursements-check.json';
import failedFixture from '../../../resources/fixtures/ui/admin-disbursements-failed.json';
import readyFixture from '../../../resources/fixtures/ui/admin-disbursements-ready.json';
import selfFixture from '../../../resources/fixtures/ui/admin-disbursements-self.json';
import queueFixture from '../../../resources/fixtures/ui/admin-disbursements.json';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminDisbursementsProps;

beforeEach(resetInertia);

describe('Disbursements queue', () => {
    it('lists releases with their state and never releases from the row', () => {
        render(<AdminDisbursements {...props(queueFixture)} />);

        expect(
            screen.getByText('2 awaiting second approver'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('region', { name: 'Release rules in effect' }),
        ).toHaveTextContent('RWF 5,000,000');
        expect(screen.getByText('PAY-2026-0612')).toBeInTheDocument();
        expect(screen.getByText('MoMo ••• 4521')).toBeInTheDocument();
        expect(screen.getByText('RWF 18M')).toBeInTheDocument();
        expect(screen.getByText('Today')).toBeInTheDocument();
        expect(screen.getByText('Tomorrow')).toBeInTheDocument();
        expect(screen.getByText('In 2 days')).toBeInTheDocument();
        expect(screen.getByText('Overdue 2d')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Open PAY-2026-0612' }),
        ).toHaveTextContent('Release');
        expect(
            screen.getByRole('link', { name: 'Open PAY-2026-0611' }),
        ).toHaveTextContent('Check');
        expect(
            screen.getByRole('link', { name: 'Open PAY-2026-0604' }),
        ).toHaveTextContent('Inspect');
        expect(
            screen.queryByRole('button', { name: 'Release all' }),
        ).not.toBeInTheDocument();
    });

    it('shows settled rows and the empty and search states', () => {
        const fixture = props(queueFixture);
        const base = fixture.disbursements[0];

        fixture.disbursements = [
            { ...base, id: 'x1', reference: 'R1', state: 'paid' },
            { ...base, id: 'x2', reference: 'R2', state: 'on_hold' },
            { ...base, id: 'x3', reference: 'R3', state: 'dispatched' },
        ];
        fixture.awaiting_second_approver = 0;
        const { unmount } = render(<AdminDisbursements {...fixture} />);

        expect(screen.getByText('Paid')).toBeInTheDocument();
        expect(screen.getByText('On hold')).toBeInTheDocument();
        expect(screen.getByText('Sent to provider')).toBeInTheDocument();
        expect(screen.getAllByText('Open')).toHaveLength(3);
        expect(
            screen.queryByText(/awaiting second approver/),
        ).not.toBeInTheDocument();
        unmount();

        render(
            <AdminDisbursements
                {...fixture}
                policy={[]}
                disbursements={[]}
                search="kivu"
            />,
        );
        expect(
            screen.queryByRole('region', { name: 'Release rules in effect' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText('All disbursements released'),
        ).toBeInTheDocument();
        expect(screen.getByText('No matches on this page')).toBeInTheDocument();
    });
});

describe('Maker-checker', () => {
    it('lets a first approver authorize with a reason', async () => {
        const { user } = renderWithUser(
            <AdminDisbursements {...props(readyFixture)} />,
        );
        const drawer = screen.getByRole('dialog', {
            name: 'Disbursement PAY-2026-0612',
        });

        expect(within(drawer).getByText('RWF 18,000,000')).toBeInTheDocument();
        expect(
            within(drawer).getByText(/At or above RWF 5,000,000/),
        ).toBeInTheDocument();
        expect(
            within(drawer).getByText('Nobody has authorized this release yet.'),
        ).toBeInTheDocument();
        expect(
            within(drawer).getByText(
                'A different approver checks the release after it is authorized.',
            ),
        ).toBeInTheDocument();
        expect(
            within(drawer).getByText('Raise fully funded'),
        ).toBeInTheDocument();

        await user.click(
            within(drawer).getByRole('button', { name: 'Authorize release' }),
        );
        const stage = within(drawer).getByRole('form', {
            name: 'Authorize this release',
        });

        expect(stage).toHaveTextContent(
            'You authorize paying RWF 18,000,000 to GreenLeaf Agro.',
        );
        await user.type(within(stage).getByRole('textbox'), 'Funded.');
        await user.click(
            within(stage).getByRole('button', { name: 'Authorize' }),
        );
        expect(inertia.posts).toEqual([
            {
                url: '/admin/disbursements/dsb_1/authorize',
                data: { reason: 'Funded.' },
            },
        ]);
    });

    it('lets a different approver check a release awaiting them', async () => {
        const { user } = renderWithUser(
            <AdminDisbursements {...props(checkFixture)} />,
        );
        const approvals = screen.getByRole('list', { name: 'Approvals' });

        expect(
            within(approvals).getByText('Awaiting second approver'),
        ).toBeInTheDocument();
        expect(
            within(approvals).getByText('Eric Ndoli · 2026-09-23 09:12:00'),
        ).toBeInTheDocument();
        expect(
            within(approvals).getByText(
                'Reason: Raise fully funded; destination verified against the RDB mandate.',
            ),
        ).toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'Approve release' }),
        );
        await user.type(screen.getByRole('textbox'), 'Checked.');
        await user.click(
            screen.getByRole('button', { name: 'Approve & release' }),
        );
        expect(inertia.posts[0].url).toBe('/admin/disbursements/dsb_2/approve');

        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        await user.click(screen.getByRole('button', { name: 'Reject' }));
        expect(
            screen.getByRole('form', { name: 'Reject this release' }),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        await user.click(screen.getByRole('button', { name: 'Hold' }));
        expect(
            screen.getByRole('form', { name: 'Put this release on hold' }),
        ).toBeInTheDocument();
    });

    it('never lets the maker approve their own release', () => {
        render(<AdminDisbursements {...props(selfFixture)} />);

        expect(
            screen.getByText(
                "You authorized this release, so you can't approve it too. A different approver must check it.",
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Approve release' }),
        ).toBeDisabled();
    });

    it('shows a failed payout in full before offering a retry', async () => {
        const { user } = renderWithUser(
            <AdminDisbursements {...props(failedFixture)} />,
        );
        const failure = screen.getByRole('region', { name: 'Payout failed' });

        expect(
            within(failure).getByText('PROVIDER_REJECTED'),
        ).toBeInTheDocument();
        expect(within(failure).getByText('MM-88213')).toBeInTheDocument();
        expect(
            within(failure).getByText('2026-09-21 09:06:12'),
        ).toBeInTheDocument();
        expect(
            within(failure).getByText(/Inspect before retrying/),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Grace Kalisa · 2026-09-21 09:05:00'),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Retry payout' }));
        expect(
            screen.getByRole('form', { name: 'Retry this payout' }),
        ).toHaveTextContent('RWF 9,500,000 will be sent to Isoko Farms again.');
    });

    it('explains a single-approver release with no actions left', () => {
        const fixture = props(readyFixture);

        if (fixture.disbursement === null) {
            throw new Error('fixture has a disbursement');
        }

        fixture.disbursement = {
            ...fixture.disbursement,
            state: 'paid',
            requires_second_approver: false,
            maker: {
                actor: 'Eric Ndoli',
                at: '2026-09-20T10:00:00+02:00',
                reason: null,
            },
            actions: {},
        };
        render(<AdminDisbursements {...fixture} />);

        expect(
            screen.getByText(
                'Below RWF 5,000,000, one authorized approver releases this payout.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /release|Hold/ }),
        ).not.toBeInTheDocument();
    });
});
