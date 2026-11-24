import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminUsers from '@/pages/admin/users';
import type { AdminUsersProps, UserDetail, UserRow } from '@/types/admin';
import coverageFixture from '../../../resources/fixtures/ui/admin-coverage.json';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const shell = structuredClone(coverageFixture.props) as unknown as Omit<
    AdminUsersProps,
    'tabs' | 'stats' | 'chips' | 'filters' | 'shown' | 'total' | 'rows' | 'user'
>;

const link = (url: string) => ({ url, method: 'get' as const });

const row = (
    overrides: Partial<UserRow> & Pick<UserRow, 'id' | 'name'>,
): UserRow => ({
    email: `${overrides.id}@example.test`,
    email_verified: true,
    signed_up_at: '2027-01-20T08:00:00+02:00',
    last_seen_at: null,
    memberships: [],
    staff: null,
    requested: [],
    kyc: 'not_started',
    restricted: false,
    closed_at: null,
    closure_reason: null,
    link: link(`/admin/users?user=${overrides.id}`),
    ...overrides,
});

const ROWS: UserRow[] = [
    row({
        id: '1',
        name: 'Aline Investor',
        kyc: 'verified',
        last_seen_at: '2027-01-23T08:00:00+02:00',
        memberships: [
            {
                role: 'investor',
                status: 'active',
                since: '2027-01-20T08:00:00+02:00',
            },
        ],
    }),
    row({
        id: '2',
        name: 'Bosco Mixed',
        kyc: 'in_review',
        memberships: [
            {
                role: 'investor',
                status: 'active',
                since: '2027-01-20T08:00:00+02:00',
            },
        ],
        requested: ['auditor'],
    }),
    row({
        id: '3',
        name: 'Chantal Auditor',
        kyc: 'in_progress',
        restricted: true,
        memberships: [
            {
                role: 'auditor',
                status: 'pending',
                since: '2027-01-20T08:00:00+02:00',
            },
            {
                role: 'business',
                status: 'suspended',
                since: '2027-01-20T08:00:00+02:00',
            },
        ],
    }),
    row({
        id: '4',
        name: '',
        email_verified: false,
        kyc: 'rejected',
    }),
    row({
        id: '5',
        name: 'Odette Officer',
        staff: { roles: ['compliance', 'core'], enabled: false },
    }),
    row({
        id: '6',
        name: 'Paul Staff',
        staff: { roles: [], enabled: true },
    }),
];

const page = (overrides: Partial<AdminUsersProps> = {}): AdminUsersProps => ({
    ...shell,
    nav: { ...shell.nav, users: link('/admin/users') },
    search: '',
    tabs: [
        { key: 'active', count: 6, link: link('/admin/users'), active: true },
        {
            key: 'deleted',
            count: 1,
            link: link('/admin/users?status=deleted'),
            active: false,
        },
    ],
    stats: [
        { key: 'total_users', value: { kind: 'count', value: 6 } },
        { key: 'new_this_week', value: { kind: 'count', value: 2 } },
        { key: 'kyc_verified', value: { kind: 'percent', value: '16' } },
        { key: 'awaiting_kyc', value: { kind: 'count', value: 1 } },
    ],
    chips: (
        ['all', 'investor', 'business', 'auditor', 'staff', 'no_role'] as const
    ).map((key, index) => ({
        key,
        count: index,
        link: link(key === 'all' ? '/admin/users' : `/admin/users?role=${key}`),
        active: key === 'all',
    })),
    filters: [
        {
            key: 'kyc',
            value: 'any',
            options: [
                'any',
                'verified',
                'in_review',
                'in_progress',
                'rejected',
                'not_started',
            ],
        },
        { key: 'sort', value: 'newest', options: ['newest', 'oldest', 'name'] },
    ],
    shown: ROWS.length,
    total: ROWS.length,
    rows: ROWS,
    user: null,
    ...overrides,
});

const rowOf = (name: string) =>
    screen
        .getAllByRole('row')
        .filter((candidate) => within(candidate).queryByText(name))[0];

const detail = (overrides: Partial<UserDetail> = {}): UserDetail => {
    const { link: _link, ...base } = ROWS[1];

    return {
        ...base,
        email_verified_at: '2027-01-20T08:05:00+02:00',
        two_factor: false,
        passkeys: 1,
        submitted_at: '2027-01-21T08:00:00+02:00',
        requests: [
            {
                role: 'auditor',
                organization: 'Bosco & Co',
                reference: 'ICPAR-77',
                sent_at: '2027-01-21T09:00:00+02:00',
            },
        ],
        history: [
            {
                id: 'e1',
                at: '2027-01-21T10:00:00+02:00',
                actor: 'Bosco Mixed',
                action: 'investor.self_grant',
                reason: 'Chose Investor in the launcher.',
            },
            {
                id: 'e2',
                at: '2027-01-21T11:00:00+02:00',
                actor: '',
                action: 'something.new',
                reason: '',
            },
        ],
        links: {
            close: link('/admin/users'),
            investor: link('/admin/investors?verification=01CASE'),
            business: null,
            auditor: null,
            staff: null,
            access_requests: link('/admin/access-requests'),
        },
        ...overrides,
    };
};

beforeEach(resetInertia);

describe('Users', () => {
    it('lists every account with its roles, KYC state, sign-up date and last activity', () => {
        render(<AdminUsers {...page()} />);

        expect(
            within(
                screen.getByRole('navigation', { name: 'Console navigation' }),
            ).getByRole('link', { name: 'Users' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(screen.getByText('Total users')).toBeInTheDocument();
        expect(screen.getByText('16%')).toBeInTheDocument();
        expect(screen.getByText('6 users')).toBeInTheDocument();

        const aline = rowOf('Aline Investor');
        expect(aline).toHaveTextContent('Investor');
        expect(aline).toHaveTextContent('Verified');
        expect(aline).toHaveTextContent('2h ago');
        expect(
            within(aline).getByRole('link', { name: 'Aline Investor' }),
        ).toHaveAttribute('href', '/admin/users?user=1');

        const bosco = rowOf('Bosco Mixed');
        expect(bosco).toHaveTextContent('Auditor requested');
        expect(bosco).toHaveTextContent('In review');
        expect(bosco).toHaveTextContent('Never');

        const chantal = rowOf('Chantal Auditor');
        expect(chantal).toHaveTextContent('Auditor · Pending');
        expect(chantal).toHaveTextContent('Business · Suspended');
        expect(chantal).toHaveTextContent('Restricted');
        expect(chantal).toHaveTextContent('In progress');

        const unnamed = rowOf('Name pending');
        expect(unnamed).toHaveTextContent('Email not verified');
        expect(unnamed).toHaveTextContent('No role yet');
        expect(unnamed).toHaveTextContent('Rejected');

        expect(screen.getByText('Staff: Compliance, core')).toHaveClass(
            'line-through',
        );
        expect(rowOf('Paul Staff')).toHaveTextContent('Staff');
        expect(rowOf('Odette Officer')).toHaveTextContent('Not started');
    });

    it('filters by role with chips and by KYC and sort on the server, closing any open account', () => {
        render(<AdminUsers {...page({ shown: 6, total: 80 })} />);

        expect(screen.getByText('Showing 6 of 80 users')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Auditors 3' }),
        ).toHaveAttribute('href', '/admin/users?role=auditor');

        fireEvent.change(
            screen.getByRole('combobox', { name: 'Filter by KYC' }),
            {
                target: { value: 'in_review' },
            },
        );
        fireEvent.change(screen.getByRole('combobox', { name: 'Sort users' }), {
            target: { value: 'name' },
        });

        expect(inertia.reload).toEqual([
            expect.objectContaining({
                data: { kyc: 'in_review', user: undefined },
            }),
            expect.objectContaining({
                data: { sort: 'name', user: undefined },
            }),
        ]);
        expect(
            screen.getByRole('option', { name: 'KYC: Not started' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('option', { name: 'Sort: Name (A–Z)' }),
        ).toBeInTheDocument();
    });

    it('says when nothing matches a search, and when nothing matches the filters', () => {
        const { unmount } = render(
            <AdminUsers
                {...page({ search: 'zzz', rows: [], shown: 0, total: 0 })}
            />,
        );

        expect(screen.getByRole('status')).toHaveTextContent('zzz');
        expect(
            screen.queryByText('No users match these filters'),
        ).not.toBeInTheDocument();
        unmount();

        render(<AdminUsers {...page({ rows: [], shown: 0, total: 0 })} />);
        expect(
            screen.getByText('No users match these filters'),
        ).toBeInTheDocument();
    });

    it("opens an account's roles, identity, sign-in security, requests and history in the drawer", () => {
        render(<AdminUsers {...page({ user: detail() })} />);

        const drawer = screen.getByRole('dialog', {
            name: 'Bosco Mixed, user account',
        });
        expect(drawer).toHaveTextContent('2@example.test');
        expect(
            within(drawer).getByRole('region', { name: 'Roles' }),
        ).toHaveTextContent('Investor · Active');
        expect(drawer).toHaveTextContent('Auditor requested');
        const account = within(drawer).getByRole('region', { name: 'Account' });
        expect(account).toHaveTextContent('In review');
        expect(account).toHaveTextContent('Never');
        expect(account).toHaveTextContent('Off');
        expect(account).toHaveTextContent('KYC submitted');
        expect(account).toHaveTextContent('User ID2');
        expect(
            within(drawer).getByRole('region', { name: 'Access requested' }),
        ).toHaveTextContent('Auditor · Bosco & Co');
        expect(drawer).toHaveTextContent('ICPAR-77');
        expect(
            within(drawer).getByRole('link', { name: 'Investor directory →' }),
        ).toHaveAttribute('href', '/admin/investors?verification=01CASE');
        expect(
            within(drawer).getByRole('link', { name: 'Access requests →' }),
        ).toHaveAttribute('href', '/admin/access-requests');

        const history = within(drawer).getByRole('region', {
            name: 'Identity history',
        });
        expect(history).toHaveTextContent('Took the Investor role');
        expect(history).toHaveTextContent(
            'Bosco Mixed · Chose Investor in the launcher.',
        );
        expect(history).toHaveTextContent('something.new');
        expect(history).toHaveTextContent('System');
        expect(history).toHaveTextContent('Signed up');
    });

    it('shows a staff account, a restricted person and an account with nothing to open', () => {
        const { link: _link, ...officer } = ROWS[4];
        render(
            <AdminUsers
                {...page({
                    user: detail({
                        ...officer,
                        name: '',
                        restricted: true,
                        email_verified_at: null,
                        two_factor: true,
                        last_seen_at: '2027-01-23T09:30:00+02:00',
                        submitted_at: null,
                        requests: [
                            {
                                role: 'business',
                                organization: '',
                                reference: null,
                                sent_at: '2027-01-21T09:00:00+02:00',
                            },
                        ],
                        history: [],
                        links: {
                            close: link('/admin/users'),
                            investor: null,
                            business: null,
                            auditor: null,
                            staff: null,
                            access_requests: null,
                        },
                    }),
                })}
            />,
        );

        const drawer = screen.getByRole('dialog', {
            name: '5@example.test, user account',
        });
        expect(drawer).toHaveTextContent('Restricted');
        expect(drawer).toHaveTextContent('This staff account is disabled.');
        expect(drawer).toHaveTextContent('Email not verified');
        expect(drawer).toHaveTextContent('On');
        expect(drawer).toHaveTextContent('30m ago');
        expect(drawer).not.toHaveTextContent('KYC submitted');
        expect(
            within(drawer).queryByRole('region', { name: 'Open in' }),
        ).not.toBeInTheDocument();
        expect(
            within(drawer).getByRole('region', { name: 'Access requested' }),
        ).toHaveTextContent('Business');
    });

    it('lists the accounts their holders closed on the Deleted accounts tab, with the date and reason', () => {
        const closed = row({
            id: '9',
            name: 'Gone Investor',
            closed_at: '2027-02-01T08:00:00+02:00',
            closure_reason: 'Moving abroad',
        });
        const { link: _link, ...base } = closed;
        const { unmount } = render(
            <AdminUsers
                {...page({
                    tabs: [
                        {
                            key: 'active',
                            count: 6,
                            link: link('/admin/users'),
                            active: false,
                        },
                        {
                            key: 'deleted',
                            count: 1,
                            link: link('/admin/users?status=deleted'),
                            active: true,
                        },
                    ],
                    rows: [closed],
                    shown: 1,
                    total: 1,
                    user: detail({
                        ...base,
                        history: [
                            {
                                id: 'e9',
                                at: '2027-02-01T08:00:00+02:00',
                                actor: 'Gone Investor',
                                action: 'account.close',
                                reason: 'Moving abroad',
                            },
                        ],
                    }),
                })}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'Deleted accounts 1' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            screen.getByRole('link', { name: 'Active accounts 6' }),
        ).toHaveAttribute('href', '/admin/users');
        expect(screen.getByText('Closed')).toBeInTheDocument();
        expect(rowOf('Gone Investor')).toHaveTextContent('Closed on');
        const drawer = screen.getByRole('dialog', {
            name: 'Gone Investor, user account',
        });
        expect(drawer).toHaveTextContent('Reason given: Moving abroad');
        expect(drawer).toHaveTextContent('Closed their account');
        unmount();

        render(
            <AdminUsers
                {...page({
                    tabs: [
                        {
                            key: 'active',
                            count: 6,
                            link: link('/admin/users'),
                            active: false,
                        },
                        {
                            key: 'deleted',
                            count: 0,
                            link: link('/admin/users?status=deleted'),
                            active: true,
                        },
                    ],
                    rows: [],
                    shown: 0,
                    total: 0,
                    user: detail({ ...base, closure_reason: null }),
                })}
            />,
        );
        expect(
            screen.getByText('No deleted accounts match these filters'),
        ).toBeInTheDocument();
        expect(screen.getByRole('dialog')).toHaveTextContent(
            'No reason given.',
        );
    });
});
