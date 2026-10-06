import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AccessDenied from '@/pages/identity/access-denied';
import RoleHome from '@/pages/identity/role-home';
import StaffHome from '@/pages/identity/staff-home';
import type { IdentityContext } from '@/types/identity';

const mocks = vi.hoisted(() => ({
    request: { processing: false, transform: vi.fn(), submit: vi.fn() },
    visit: vi.fn(),
}));
vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({
        href,
        children,
    }: {
        href: string | { url: string };
        children: React.ReactNode;
    }) => <a href={typeof href === 'string' ? href : href.url}>{children}</a>,
    useHttp: () => mocks.request,
    router: { visit: mocks.visit },
}));
const identity: IdentityContext = {
    contract_version: 'identity-v2',
    policy_version: 'engineering-2026-09-23.4',
    code: 'IDENTITY_READY',
    party: {
        id: 'synthetic-person',
        kind: 'person',
        verification_status: 'verified',
    },
    available_roles: ['investor'],
    active_role: 'investor',
    context_revision: 2,
    allowed_actions: ['identity.select_role', 'identity.view_role'],
};

beforeEach(() => {
    vi.clearAllMocks();
    mocks.request.processing = false;
});

describe('authorized role entry', () => {
    it('persists the chosen section with current context before navigating to its server destination', async () => {
        mocks.request.submit.mockResolvedValue({
            data: { url: '/investor?section=access' },
        });
        render(
            <RoleHome identity={identity} role="investor" section="overview" />,
        );
        expect(
            screen.getByText('Your account has access to this workspace.'),
        ).toBeInTheDocument();
        await userEvent.click(
            screen.getByRole('button', { name: 'Account access' }),
        );
        expect(mocks.request.transform.mock.calls[0][0]()).toEqual({
            role: 'investor',
            route: 'investor.home',
            parameters: {},
            query: { section: 'access' },
            expected_revision: 2,
            request_id: expect.any(String),
        });
        expect(mocks.request.submit).toHaveBeenCalledWith(
            expect.objectContaining({
                method: 'post',
                url: '/identity/bookmarks',
            }),
        );
        expect(mocks.visit).toHaveBeenCalledWith('/investor?section=access');
    });
    it('renders the restored access section and allows a different position', async () => {
        mocks.request.submit.mockResolvedValue({
            data: { url: '/investor?section=overview' },
        });
        render(
            <RoleHome identity={identity} role="investor" section="access" />,
        );
        expect(
            screen.getByRole('button', { name: 'Account access' }),
        ).toHaveAttribute('aria-pressed', 'true');
        expect(
            screen.getByText('Your identity is verified.'),
        ).toBeInTheDocument();
        await userEvent.click(screen.getByRole('button', { name: 'Overview' }));
        expect(mocks.visit).toHaveBeenCalledWith('/investor?section=overview');
    });
    it('shows progress and prevents duplicate commands during an in-flight save', () => {
        mocks.request.processing = true;
        const view = render(
            <RoleHome identity={identity} role="auditor" section="overview" />,
        );
        expect(screen.getByRole('status')).toHaveTextContent(
            'Saving your position',
        );
        expect(
            screen.getByRole('button', { name: 'Account access' }),
        ).toBeDisabled();
        mocks.request.processing = false;
        view.rerender(
            <RoleHome identity={identity} role="auditor" section="overview" />,
        );
        mocks.request.processing = true;
        fireEvent.click(screen.getByRole('button', { name: 'Account access' }));
        expect(mocks.request.submit).not.toHaveBeenCalled();
    });
    it('clears stale role content on failed saves and gives a real recovery destination', async () => {
        mocks.request.submit.mockRejectedValue(new Error('Context revoked.'));
        render(
            <RoleHome identity={identity} role="business" section="overview" />,
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Account access' }),
        );
        expect(await screen.findByRole('alert')).toHaveTextContent(
            'Return to the launcher',
        );
        expect(
            screen.queryByRole('button', { name: 'Account access' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Choose an app' }),
        ).toHaveAttribute('href', '/dashboard');
        expect(mocks.visit).not.toHaveBeenCalled();
    });
    it('keeps a command identity while it is retried and uses a new identity for a new context', async () => {
        mocks.request.submit.mockResolvedValue({
            data: { url: '/investor?section=access' },
        });
        const view = render(
            <RoleHome identity={identity} role="investor" section="overview" />,
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Account access' }),
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Account access' }),
        );
        expect(mocks.request.transform.mock.calls[0][0]().request_id).toBe(
            mocks.request.transform.mock.calls[1][0]().request_id,
        );
        view.rerender(
            <RoleHome
                identity={{ ...identity, context_revision: 3 }}
                role="investor"
                section="overview"
            />,
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Account access' }),
        );
        await waitFor(() =>
            expect(mocks.request.submit).toHaveBeenCalledTimes(3),
        );
        expect(mocks.request.transform.mock.calls[2][0]().request_id).not.toBe(
            mocks.request.transform.mock.calls[0][0]().request_id,
        );
    });
});

it.each([true, false])(
    'renders the staff entry state without implying operational permissions (%s)',
    (allowed) => {
        render(
            <StaffHome
                staff_access={{
                    contract_version: 'staff-access-v1',
                    can_open_admin: allowed,
                    allowed_actions: allowed ? ['admin.open'] : [],
                }}
            />,
        );
        expect(
            screen.getByRole('heading', { name: 'Staff workspace' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            allowed
                ? 'Your staff account has permission'
                : 'Your account can no longer',
        );
        expect(
            screen.getByRole('link', { name: 'Account settings' }),
        ).toHaveAttribute('href', '/settings/profile');
    },
);

it('replaces denied role pages with an accessible recovery page', () => {
    render(<AccessDenied />);
    expect(screen.getByRole('alert')).toHaveTextContent(
        'Choose an app to refresh your access.',
    );
    expect(screen.getByRole('link', { name: 'Choose an app' })).toHaveAttribute(
        'href',
        '/dashboard',
    );
});

it('says an offer closed, not that access must be checked, for an expired offer', () => {
    render(<AccessDenied code="ASSIGNMENT_ACCEPTANCE_EXPIRED" />);
    expect(
        screen.getByRole('heading', { name: 'This offer has closed' }),
    ).toBeInTheDocument();
    expect(screen.getByRole('alert')).toHaveTextContent(
        'The time to accept this job ran out',
    );
    expect(
        screen.queryByText(/Access needs to be checked/u),
    ).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Choose an app' })).toHaveAttribute(
        'href',
        '/dashboard',
    );
});

it('keeps the account-access wording for every other refusal code', () => {
    render(<AccessDenied code="ROLE_MEMBERSHIP_REQUIRED" />);
    expect(
        screen.getByRole('heading', { name: 'Access needs to be checked' }),
    ).toBeInTheDocument();
});
