import { screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminStagingMailTesters from '@/pages/admin/staging-mail-testers';
import type { AdminStagingMailTestersProps } from '@/types/admin';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const link = (url: string) => ({ url, method: 'get' as const });
const action = (url: string) => ({ url, method: 'post' as const });

const page = (
    overrides: Partial<AdminStagingMailTestersProps> = {},
): AdminStagingMailTestersProps => ({
    contract_version: 'staff-staging-mail-testers-v1',
    server_time: '2026-10-07T10:00:00+00:00',
    viewer: {
        id: '3',
        name: 'Grace Mukamana',
        email: 'grace@rozine.rw',
        initials: 'G',
        role: 'superadmin',
    },
    nav: {
        mail_testers: link('/admin/staging-mail-testers'),
        launcher: link('/dashboard'),
        today: null,
        applications: null,
        disbursements: null,
        repayments: null,
        businesses: null,
        investors: null,
        auditors: null,
        staff: null,
        ledger: null,
        events: null,
    },
    badges: { applications: null, disbursements: null },
    search: '',
    server_recipients: ['@rozine.rw'],
    testers: [
        {
            id: '01j9tester00000000000000a',
            email: 'aline@example.org',
            added_by: 'Grace Mukamana',
            added_at: '2026-10-07T09:00:00+00:00',
            remove: action('/admin/staging-mail-testers/a/remove'),
        },
    ],
    add: action('/admin/staging-mail-testers'),
    ...overrides,
});

beforeEach(() => {
    resetInertia();
    vi.spyOn(crypto, 'randomUUID').mockReturnValue(
        '9b2f6f5c-6c1e-4f2b-8a37-2f1c6f4b9d10',
    );
});

describe('Staging mail testers', () => {
    it('shows the server recipients, the named testers and its own sidebar entry', () => {
        renderWithUser(<AdminStagingMailTesters {...page()} />);

        expect(
            screen.getByText(
                /Staging sends real email only to approved testers/,
            ),
        ).toBeInTheDocument();
        const server = screen.getByRole('region', {
            name: 'Always approved on this server',
        });
        expect(within(server).getByText('@rozine.rw')).toBeInTheDocument();
        expect(
            within(server).getByText(
                'Set on the staging server. Change them there, not here.',
            ),
        ).toBeInTheDocument();

        const row = screen.getAllByRole('row')[1];
        expect(within(row).getByText('aline@example.org')).toBeInTheDocument();
        expect(within(row).getByText('Grace Mukamana')).toBeInTheDocument();
        expect(
            within(row).getByRole('button', {
                name: 'Remove aline@example.org',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getAllByRole('link', { name: /Staging mail testers/ })[0],
        ).toHaveAttribute('href', '/admin/staging-mail-testers');
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('says when there are no named testers yet, or none match the search', () => {
        const { rerender } = renderWithUser(
            <AdminStagingMailTesters {...page({ testers: [] })} />,
        );

        expect(screen.getByText('No named testers yet.')).toBeInTheDocument();

        rerender(
            <AdminStagingMailTesters
                {...page({ testers: [], search: 'robert' })}
            />,
        );

        expect(
            screen.queryByText('No named testers yet.'),
        ).not.toBeInTheDocument();
        expect(screen.getByText(/robert/)).toBeInTheDocument();
    });

    it('adds a tester through the reason stage with the trimmed address and a new request id', async () => {
        inertia.succeed = true;
        vi.spyOn(crypto, 'randomUUID')
            .mockReturnValueOnce('11111111-1111-4111-8111-111111111111')
            .mockReturnValueOnce('22222222-2222-4222-8222-222222222222');
        const { user } = renderWithUser(
            <AdminStagingMailTesters {...page()} />,
        );

        const add = screen.getByRole('button', { name: 'Add tester' });
        expect(add).toBeDisabled();
        await user.type(
            screen.getByLabelText('Tester email address'),
            '  robert@example.net ',
        );
        await user.click(add);

        const stage = screen.getByRole('form', {
            name: 'Add robert@example.net as a tester',
        });
        expect(stage).toHaveClass('border-rz-accent-fill');
        expect(
            within(stage).getByText(
                'Logged to the audit trail as Grace Mukamana · Super-admin',
            ),
        ).toBeInTheDocument();
        await user.type(
            within(stage).getByRole('textbox'),
            'Joining the test round.',
        );
        await user.click(
            within(stage).getByRole('button', { name: 'Add tester' }),
        );

        expect(inertia.posts).toEqual([
            {
                url: '/admin/staging-mail-testers',
                data: {
                    reason: 'Joining the test round.',
                    request_id: '22222222-2222-4222-8222-222222222222',
                    email: 'robert@example.net',
                },
            },
        ]);
        expect(
            screen.queryByRole('form', {
                name: 'Add robert@example.net as a tester',
            }),
        ).not.toBeInTheDocument();
    });

    it('removes a tester through the red stage, which can be cancelled', async () => {
        inertia.succeed = true;
        const { user } = renderWithUser(
            <AdminStagingMailTesters {...page()} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Remove aline@example.org' }),
        );
        let stage = screen.getByRole('form', {
            name: 'Remove aline@example.org',
        });
        expect(stage).toHaveClass('border-[#e5484d]');
        expect(
            screen.queryByRole('form', { name: 'Add a tester' }),
        ).not.toBeInTheDocument();
        await user.click(within(stage).getByRole('button', { name: 'Cancel' }));
        expect(
            screen.getByRole('form', { name: 'Add a tester' }),
        ).toBeInTheDocument();
        expect(inertia.posts).toEqual([]);

        await user.click(
            screen.getByRole('button', { name: 'Remove aline@example.org' }),
        );
        stage = screen.getByRole('form', { name: 'Remove aline@example.org' });
        expect(
            within(stage).getByText(
                'Staging will stop sending this address email straight away.',
            ),
        ).toBeInTheDocument();
        await user.type(
            within(stage).getByRole('textbox'),
            'Test round finished.',
        );
        await user.click(
            within(stage).getByRole('button', { name: 'Remove tester' }),
        );

        expect(inertia.posts).toEqual([
            {
                url: '/admin/staging-mail-testers/a/remove',
                data: {
                    reason: 'Test round finished.',
                    request_id: '9b2f6f5c-6c1e-4f2b-8a37-2f1c6f4b9d10',
                },
            },
        ]);
    });

    it('shows a refused change and an invalid address from the server', () => {
        inertia.errors = {
            form: 'This address is already an approved tester.',
            email: 'Enter one email address.',
        };
        renderWithUser(<AdminStagingMailTesters {...page()} />);

        const alerts = screen.getAllByRole('alert');
        expect(alerts[0]).toHaveTextContent(
            'This address is already an approved tester.',
        );
        expect(alerts[1]).toHaveTextContent('Enter one email address.');
        const field = screen.getByLabelText('Tester email address');
        expect(field).toHaveAttribute('aria-invalid', 'true');
        expect(field).toHaveAttribute('aria-describedby', 'tester-email-error');

        inertia.errors = {};
    });
});
