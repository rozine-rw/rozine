import { act, fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { LogoLockup, LogoMark } from '@/components/rozine/logo';
import Launcher from '@/pages/dashboard';
import type { IdentityCode, IdentityContext } from '@/types';
import auditorFixture from '../../../resources/fixtures/ui/launcher-auditor.json';
import readyFixture from '../../../resources/fixtures/ui/launcher-ready.json';
import pendingFixture from '../../../resources/fixtures/ui/launcher-verification-pending.json';

const mocks = vi.hoisted(() => ({
    command: { processing: false, transform: vi.fn(), submit: vi.fn() },
    visit: vi.fn(),
    reload: vi.fn(),
}));
vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => (
        <span data-testid="page-title">{title}</span>
    ),
    Link: ({
        children,
        href,
        as,
        preserveState: _preserveState,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & {
        href: string | { url: string };
        as?: string;
        preserveState?: boolean;
    }) =>
        as === 'button' ? (
            <button>{children}</button>
        ) : (
            <a href={typeof href === 'string' ? href : href.url} {...props}>
                {children}
            </a>
        ),
    useHttp: () => mocks.command,
    router: { visit: mocks.visit, reload: mocks.reload },
}));
const identityFrom = (fixture: { props: { identity: unknown } }) =>
    fixture.props.identity as IdentityContext;
const ready = identityFrom(readyFixture);
const selected = (
    role: 'investor' | 'business' | 'auditor',
): IdentityContext => ({
    ...ready,
    active_role: role,
    context_revision: 1,
    allowed_actions: ['identity.select_role', 'identity.view_role'],
});
beforeEach(() => {
    vi.clearAllMocks();
    mocks.command.processing = false;
});

describe('Suite launcher', () => {
    it('shows authorized roles and selects the role before visiting its authorized return position', async () => {
        mocks.command.submit.mockResolvedValue({ data: selected('investor') });
        render(<Launcher identity={ready} />);
        expect(screen.getByRole('img', { name: 'Rozine' })).toBeInTheDocument();
        expect(screen.getByTestId('page-title')).toHaveTextContent('Your apps');
        const apps = screen.getAllByRole('listitem');
        expect(apps.map((app) => app.dataset.audience)).toEqual([
            'investor',
            'business',
        ]);
        expect(within(apps[0]).getByRole('button')).toHaveTextContent(
            'Discover verified businesses, invest, track returns.',
        );
        expect(screen.queryByText('Auditor')).not.toBeInTheDocument();
        await userEvent.click(within(apps[0]).getByRole('button'));
        expect(mocks.command.transform.mock.calls[0][0]()).toEqual({
            role: 'investor',
            expected_revision: 0,
            request_id: expect.any(String),
        });
        expect(mocks.command.submit).toHaveBeenCalledWith(
            expect.objectContaining({
                url: '/identity/active-role',
                method: 'post',
            }),
            expect.any(Object),
        );
        expect(mocks.visit).toHaveBeenCalledWith(
            expect.objectContaining({ url: '/identity/roles/investor/resume' }),
        );
    });
    it('uses explicitly marked synthetic preview links without changing a real account role', async () => {
        render(
            <Launcher
                identity={ready}
                preview_links={readyFixture.props.preview_links as never}
            />,
        );
        expect(
            screen.getByText('Preview with sample data'),
        ).toBeInTheDocument();
        await userEvent.click(screen.getByRole('button', { name: 'Investor' }));
        expect(mocks.visit).toHaveBeenCalledWith({
            url: '/preview/investor-deals',
            method: 'get',
        });
        expect(mocks.command.submit).not.toHaveBeenCalled();
    });
    it('resumes an already selected authorized role without generating another switch', async () => {
        render(<Launcher identity={selected('business')} />);
        await userEvent.click(screen.getByRole('button', { name: 'Business' }));
        expect(mocks.command.submit).not.toHaveBeenCalled();
        expect(mocks.visit).toHaveBeenCalledWith(
            expect.objectContaining({ url: '/identity/roles/business/resume' }),
        );
    });
    it('shows the Auditor app only for that membership and enables MFA setup when selection is denied', async () => {
        mocks.command.submit.mockResolvedValue({ data: selected('auditor') });
        const view = render(
            <Launcher identity={identityFrom(auditorFixture)} />,
        );
        expect(screen.getAllByRole('listitem')).toHaveLength(1);
        await userEvent.click(screen.getByRole('button', { name: 'Auditor' }));
        expect(mocks.visit).toHaveBeenCalledWith(
            expect.objectContaining({ url: '/identity/roles/auditor/resume' }),
        );
        view.rerender(
            <Launcher
                identity={{
                    ...identityFrom(auditorFixture),
                    allowed_actions: [],
                }}
            />,
        );
        expect(screen.getByRole('button', { name: 'Auditor' })).toBeDisabled();
        expect(
            screen.getByRole('link', {
                name: 'Set up two-factor authentication',
            }),
        ).toHaveAttribute('href', '/settings/security');
    });
    it('does not treat an available role or ready code as permission to select it', () => {
        render(<Launcher identity={{ ...ready, allowed_actions: [] }} />);
        expect(screen.getByRole('button', { name: 'Investor' })).toBeDisabled();
    });
    it('allows resuming the selected role when viewing is granted but selection is unavailable', async () => {
        render(
            <Launcher
                identity={{
                    ...selected('investor'),
                    allowed_actions: ['identity.view_role'],
                }}
            />,
        );
        expect(screen.getByRole('button', { name: 'Business' })).toBeDisabled();
        await userEvent.click(screen.getByRole('button', { name: 'Investor' }));
        expect(mocks.visit).toHaveBeenCalledOnce();
    });
    it('gives an explicit staff grant its own entry without inventing a marketplace role', () => {
        const view = render(
            <Launcher
                identity={identityFrom(pendingFixture)}
                staff_access={{
                    contract_version: 'staff-access-v1',
                    can_open_admin: true,
                    allowed_actions: ['admin.open'],
                }}
            />,
        );
        expect(
            screen.getByRole('link', { name: 'Open staff workspace' }),
        ).toHaveAttribute('href', '/admin');
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
        view.rerender(
            <Launcher
                identity={ready}
                staff_access={{
                    contract_version: 'staff-access-v1',
                    can_open_admin: true,
                    allowed_actions: [],
                }}
            />,
        );
        expect(
            screen.queryByRole('link', { name: 'Open staff workspace' }),
        ).not.toBeInTheDocument();
        view.rerender(
            <Launcher
                identity={ready}
                staff_access={{
                    contract_version: 'staff-access-v1',
                    can_open_admin: false,
                    allowed_actions: [],
                }}
            />,
        );
        expect(
            screen.queryByRole('link', { name: 'Open staff workspace' }),
        ).not.toBeInTheDocument();
    });
    it('keeps the role buttons disabled while a command is in flight', async () => {
        let resolve: (value: { data: IdentityContext }) => void = () => {};
        mocks.command.submit.mockReturnValue(
            new Promise((done) => {
                resolve = done;
            }),
        );
        render(<Launcher identity={ready} />);
        await userEvent.click(screen.getByRole('button', { name: 'Investor' }));
        expect(screen.getByRole('status')).toHaveTextContent(
            'Opening your app',
        );
        expect(screen.getByRole('button', { name: 'Business' })).toBeDisabled();
        await act(async () => {
            resolve({ data: selected('investor') });
        });
    });
    it('ignores a duplicate click when the HTTP request has started before the next render', () => {
        render(<Launcher identity={ready} />);
        mocks.command.processing = true;
        fireEvent.click(screen.getByRole('button', { name: 'Investor' }));
        expect(mocks.command.submit).not.toHaveBeenCalled();
    });
    it.each([
        { ...selected('business') },
        { ...selected('investor'), allowed_actions: [] },
    ])(
        'does not navigate on an old replay or response lacking current authority',
        async (data) => {
            mocks.command.submit.mockResolvedValue({ data });
            render(<Launcher identity={ready} />);
            await userEvent.click(
                screen.getByRole('button', { name: 'Investor' }),
            );
            expect(await screen.findByRole('alert')).toHaveTextContent(
                'Your access or active app has changed',
            );
            expect(mocks.visit).not.toHaveBeenCalled();
        },
    );
    it.each([
        'ACTIVE_ROLE_REVISION_CONFLICT',
        'IDEMPOTENCY_KEY_REUSED',
        'ROLE_NOT_AVAILABLE',
        'ACTIVE_ROLE_REQUIRED',
        'MFA_REQUIRED',
        'IDENTITY_VERIFICATION_REQUIRED',
    ])('clears stale role content and offers recovery for %s', async (code) => {
        mocks.command.submit.mockImplementation(async (_route, options) => {
            options.onHttpException({
                status:
                    code.includes('CONFLICT') ||
                    code === 'IDEMPOTENCY_KEY_REUSED'
                        ? 409
                        : 403,
                data: JSON.stringify({ code }),
            });

            throw new Error(code);
        });
        render(<Launcher identity={ready} />);
        await userEvent.click(screen.getByRole('button', { name: 'Investor' }));
        expect(await screen.findByRole('alert')).toBeInTheDocument();
        expect(screen.queryByRole('list')).not.toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Refresh access' }),
        ).toHaveAttribute('href', '/dashboard');
        expect(
            screen.queryByRole('button', { name: 'Retry switch' }),
        ).not.toBeInTheDocument();

        if (code === 'MFA_REQUIRED') {
            expect(
                screen.getByRole('link', {
                    name: 'Set up two-factor authentication',
                }),
            ).toHaveAttribute('href', '/settings/security');
        }

        expect(mocks.visit).not.toHaveBeenCalled();
    });
    it.each(['not-json', '{}', '{"code":42}', 'null'])(
        'handles an unexpected server error body without displaying it (%s)',
        async (data) => {
            mocks.command.submit.mockImplementation(async (_route, options) => {
                options.onHttpException({ status: 500, data });

                throw new Error('failed');
            });
            render(<Launcher identity={ready} />);
            await userEvent.click(
                screen.getByRole('button', { name: 'Investor' }),
            );
            expect(await screen.findByRole('alert')).toHaveTextContent(
                'Refresh your access',
            );
            expect(mocks.visit).not.toHaveBeenCalled();
        },
    );
    it('handles input validation as a failed command requiring a fresh decision', async () => {
        mocks.command.submit.mockImplementation(async (_route, options) => {
            options.onError({ request_id: 'Invalid' });

            throw new Error('validation');
        });
        render(<Launcher identity={ready} />);
        await userEvent.click(screen.getByRole('button', { name: 'Investor' }));
        expect(await screen.findByRole('alert')).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Retry switch' }),
        ).not.toBeInTheDocument();
    });
    it('retries an uncertain network result with the identical command UUID and payload', async () => {
        mocks.command.submit
            .mockRejectedValueOnce(new Error('Connection lost'))
            .mockResolvedValueOnce({ data: selected('investor') });
        render(<Launcher identity={ready} />);
        await userEvent.click(screen.getByRole('button', { name: 'Investor' }));
        expect(await screen.findByRole('alert')).toHaveTextContent(
            'Check your connection',
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Retry switch' }),
        );
        expect(mocks.command.transform.mock.calls[0][0]()).toEqual(
            mocks.command.transform.mock.calls[1][0](),
        );
        expect(mocks.visit).toHaveBeenCalledOnce();
    });
    it('disables app choices while fresh access is being loaded after returning to the tab', () => {
        render(<Launcher identity={ready} />);
        act(() => {
            window.dispatchEvent(new Event('focus'));
        });
        expect(screen.getByRole('button', { name: 'Investor' })).toBeDisabled();
        act(() => {
            mocks.reload.mock.calls[0][0].onFinish();
        });
        expect(screen.getByRole('button', { name: 'Investor' })).toBeEnabled();
    });
    it('explains pending verification with a real next step', () => {
        render(<Launcher identity={identityFrom(pendingFixture)} />);
        expect(screen.queryByRole('list')).not.toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            'Your identity is being verified',
        );
        expect(
            screen.getByRole('link', { name: 'Contact Rozine support →' }),
        ).toHaveAttribute('href', 'mailto:hello@rozine.rw');
    });
    it.each<[Exclude<IdentityCode, 'IDENTITY_READY'>, string, string, string]>([
        [
            'EMAIL_VERIFICATION_REQUIRED',
            'Verify your email first',
            'Verify your email →',
            '/email/verify',
        ],
        [
            'IDENTITY_NOT_LINKED',
            'Your identity is not set up yet',
            'Contact Rozine support →',
            'mailto:hello@rozine.rw',
        ],
        [
            'PARTY_AUTHORITY_REQUIRED',
            'Signing authority is needed',
            'Contact Rozine support →',
            'mailto:hello@rozine.rw',
        ],
        [
            'ROLE_MEMBERSHIP_INVALID',
            'Your account needs attention',
            'Contact Rozine support →',
            'mailto:hello@rozine.rw',
        ],
        [
            'ROLE_MEMBERSHIP_CONFLICT',
            'These apps cannot be combined',
            'Contact Rozine support →',
            'mailto:hello@rozine.rw',
        ],
        [
            'ROLE_MEMBERSHIP_REQUIRED',
            'No apps yet',
            'Contact Rozine support →',
            'mailto:hello@rozine.rw',
        ],
    ])(
        'gives %s a titled notice and its next step',
        (code, title, action, href) => {
            render(
                <Launcher
                    identity={{
                        ...identityFrom(pendingFixture),
                        code,
                        available_roles: [],
                    }}
                />,
            );

            const notice = screen.getByRole('status');

            expect(notice).toHaveTextContent(title);
            expect(
                within(notice).getByRole('link', { name: action }),
            ).toHaveAttribute('href', href);
        },
    );
});

describe('Rozine logo', () => {
    it('names itself once when titled and hides itself when decorative', () => {
        render(
            <>
                <LogoLockup title="Rozine" data-testid="lockup" />
                <LogoMark data-testid="mark" />
            </>,
        );

        expect(screen.getByRole('img', { name: 'Rozine' })).toBe(
            screen.getByTestId('lockup'),
        );
        expect(screen.getByTestId('mark')).toHaveAttribute(
            'aria-hidden',
            'true',
        );
        expect(screen.getByTestId('mark')).not.toHaveAttribute('role');
    });
});
