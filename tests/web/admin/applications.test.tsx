import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminApplications from '@/pages/admin/applications';
import AdminLogin from '@/pages/admin/auth/login';
import type { AdminApplicationsProps, ApplicationState } from '@/types/admin';
import approveFixture from '../../../resources/fixtures/ui/admin-applications-approve.json';
import emptyFixture from '../../../resources/fixtures/ui/admin-applications-empty.json';
import missingFixture from '../../../resources/fixtures/ui/admin-applications-missing-audit.json';
import reviewFixture from '../../../resources/fixtures/ui/admin-applications-review.json';
import queueFixture from '../../../resources/fixtures/ui/admin-applications.json';
import loginFixture from '../../../resources/fixtures/ui/admin-login.json';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminApplicationsProps;

beforeEach(resetInertia);

describe('Admin sign-in', () => {
    it('posts staff credentials to the shared login with the design copy', () => {
        render(
            <AdminLogin
                {...(loginFixture.props as Parameters<typeof AdminLogin>[0])}
            />,
        );

        expect(screen.getByTestId('head')).toHaveTextContent('Sign in');
        expect(
            screen.getByText(
                'Internal access only. Every action is logged to the audit trail.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('Work email')).toHaveAttribute(
            'placeholder',
            'you@rozine.com',
        );
        expect(screen.getByLabelText('Password')).toHaveAttribute(
            'type',
            'password',
        );
        expect(
            screen.getByRole('button', { name: 'Sign in to console' }),
        ).toBeEnabled();
        expect(inertia.forms[0]).toMatchObject({ method: 'post' });
        expect(inertia.forms[0].action).toMatch(/\/login$/);
        expect(
            screen.getByRole('link', { name: '← Back to all apps' }),
        ).toHaveAttribute('href', '/preview/launcher-ready');
    });

    it('shows a failed sign-in, a status and the busy label', () => {
        inertia.formState = {
            processing: true,
            errors: { email: 'These credentials do not match our records.' },
        };
        render(
            <AdminLogin
                status="Your password was reset."
                links={{ launcher: { url: '/', method: 'get' } }}
            />,
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            'These credentials do not match our records.',
        );
        expect(screen.getByRole('status')).toHaveTextContent(
            'Your password was reset.',
        );
        expect(
            screen.getByRole('button', { name: 'Signing in…' }),
        ).toBeDisabled();
        expect(screen.getByLabelText('Work email')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
    });

    it('flags a password error', () => {
        inertia.formState = {
            processing: false,
            errors: { password: 'Wrong.' },
        };
        render(
            <AdminLogin links={{ launcher: { url: '/', method: 'get' } }} />,
        );

        expect(screen.getByLabelText('Password')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(screen.getByRole('alert')).toHaveTextContent('Wrong.');
    });
});

describe('Applications queue', () => {
    it('shows the policy in force, the state tabs and the engine decision', () => {
        render(<AdminApplications {...props(queueFixture)} />);

        const policy = screen.getByRole('region', {
            name: 'Underwriting rules in effect',
        });

        expect(within(policy).getByText('1.50×')).toBeInTheDocument();
        expect(within(policy).getByText('10–15%')).toBeInTheDocument();
        const tabs = screen.getByRole('navigation', {
            name: 'Application states',
        });

        expect(
            within(tabs).getByRole('link', { name: 'Pending 1' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(tabs).getByRole('link', { name: 'Rejected 4' }),
        ).not.toHaveAttribute('aria-current');

        const row = screen.getAllByRole('row')[1];

        expect(within(row).getByText('Kigali Motors')).toHaveAttribute(
            'href',
            '/preview/admin-applications-review',
        );
        expect(within(row).getByText('RWF 50M')).toBeInTheDocument();
        expect(within(row).getByText('6mo · 12.9% flat')).toBeInTheDocument();
        expect(within(row).getByText('Stable')).toBeInTheDocument();
        expect(within(row).getByText('21d ago')).toBeInTheDocument();
        expect(within(row).getByText('Flag: reject')).toHaveAttribute(
            'title',
            'Requested exceeds CFADS debt capacity (max RWF 15M at DSCR 1.25)',
        );
        expect(
            within(row).getByRole('link', { name: 'Approve Kigali Motors' }),
        ).toHaveAttribute('href', '/preview/admin-applications-approve');
        expect(
            within(row).getByRole('progressbar', { name: 'Capacity used' }),
        ).toHaveValue(63);
    });

    it('colours each decision and shows resolved states and unrated rows', () => {
        const fixture = props(queueFixture);
        const base = fixture.applications[0];
        const states: ApplicationState[] = [
            'under_review',
            'info_requested',
            'escalated',
            'approved',
            'rejected',
        ];

        fixture.applications = [
            {
                ...base,
                id: 'a1',
                decision: { code: 'approve', reason: 'ok' },
                rating: null,
            },
            {
                ...base,
                id: 'a2',
                decision: { code: 'audit', reason: 'audit' },
                rating: { band: 'strong', score: '4.4' },
            },
            {
                ...base,
                id: 'a3',
                decision: { code: 'review', reason: 'review' },
                rating: { band: 'weak', score: '2.4' },
            },
            {
                ...base,
                id: 'a4',
                rating: { band: 'distressed', score: '1.4' },
                approve_link: null,
            },
            ...states.map((state, index) => ({
                ...base,
                id: `s${index}`,
                state,
            })),
        ];
        render(<AdminApplications {...fixture} />);

        expect(screen.getByText('Auto-approve')).toBeInTheDocument();
        expect(screen.getByText('Route to audit')).toBeInTheDocument();
        expect(screen.getByText('Needs review')).toBeInTheDocument();
        expect(screen.getByText('Pending audit')).toBeInTheDocument();
        expect(screen.getByText('Weak')).toBeInTheDocument();
        expect(screen.getAllByText('Approved').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Rejected').length).toBeGreaterThan(1);
        expect(
            screen.getAllByRole('link', { name: 'Approve Kigali Motors' }),
        ).toHaveLength(6);
    });

    it('says when a queue is empty and when the search matched nothing', () => {
        const fixture = props(emptyFixture);

        render(<AdminApplications {...fixture} search="zzz" />);

        expect(
            screen.getByText('No applications in this queue.'),
        ).toBeInTheDocument();
        expect(screen.getByText('No matches on this page')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Nothing in Applications Queue matches “zzz”. This search only looks inside the current page.',
            ),
        ).toBeInTheDocument();
    });
});

describe('Underwriting review', () => {
    it('shows the engine decision and evidence read-only, with no score input', () => {
        render(<AdminApplications {...props(reviewFixture)} />);

        const drawer = screen.getByRole('dialog', {
            name: 'Underwriting review, Kigali Motors',
        });

        expect(
            within(drawer).getByText('Recommend: Reject'),
        ).toBeInTheDocument();
        expect(within(drawer).getByText('12.9% flat')).toBeInTheDocument();
        expect(within(drawer).getByText('6 months')).toBeInTheDocument();
        expect(within(drawer).getByText('· 3.5 / 5')).toBeInTheDocument();
        expect(
            within(drawer).getByText('Approved capacity RWF 80M'),
        ).toBeInTheDocument();
        expect(
            within(drawer).getByText('3 active notes · RWF 79.3M out'),
        ).toBeInTheDocument();
        expect(
            within(drawer).getByText('Statement record'),
        ).toBeInTheDocument();
        expect(within(drawer).queryByText(/Tax/)).not.toBeInTheDocument();
        expect(
            within(drawer).getByText(/^Submitted 2 Sept? 2026$/),
        ).toBeInTheDocument();
        expect(
            within(drawer).getByText('Reviewer: Unassigned'),
        ).toBeInTheDocument();
        expect(
            within(drawer).getByText('Application submitted'),
        ).toBeInTheDocument();
        expect(
            within(drawer).queryByRole('spinbutton'),
        ).not.toBeInTheDocument();
        expect(within(drawer).queryByRole('textbox')).not.toBeInTheDocument();
        expect(
            within(drawer).getByRole('link', {
                name: 'View full business profile →',
            }),
        ).toHaveAttribute('href', '/preview/admin-businesses-frozen');
    });

    it.each([
        ['Reject', 'Reject application', 'Confirm rejection', 'reject'],
        ['Request info', 'Request more information', 'Send request', 'info'],
        ['Escalate', 'Escalate to Credit Committee', 'Escalate', 'escalate'],
        ['Take for review', 'Take for review', 'Take for review', 'take'],
        [
            'Approve & create note',
            'Approve & create note',
            'Confirm approval',
            'approve',
        ],
    ])(
        '%s needs a written reason before it commits',
        async (button, title, cta, action) => {
            const { user } = renderWithUser(
                <AdminApplications {...props(reviewFixture)} />,
            );

            await user.click(screen.getByRole('button', { name: button }));
            const stage = screen.getByRole('form', { name: title });
            const confirm = within(stage).getByRole('button', { name: cta });

            expect(confirm).toBeDisabled();
            await user.type(within(stage).getByRole('textbox'), 'Because.');
            await user.click(confirm);
            expect(inertia.posts).toEqual([
                {
                    url: `/admin/applications/APP-20250091/${action}`,
                    data: { reason: 'Because.' },
                },
            ]);
            await user.click(
                within(stage).getByRole('button', { name: 'Cancel' }),
            );
            expect(
                screen.queryByRole('form', { name: title }),
            ).not.toBeInTheDocument();
        },
    );

    it('shows a server error on the reason and the busy state', async () => {
        inertia.errors = { reason: 'A reason is required.' };
        inertia.processing = true;
        const { user } = renderWithUser(
            <AdminApplications {...props(reviewFixture)} />,
        );

        await user.click(screen.getByRole('button', { name: 'Reject' }));
        expect(screen.getByText('A reason is required.')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Confirm rejection' }),
        ).toHaveAttribute('aria-busy', 'true');
    });

    it('opens straight on the approval stage from the row, with the offer in the copy', () => {
        render(<AdminApplications {...props(approveFixture)} />);

        expect(
            screen.getByText(
                'This creates a live RWF 50M note at 12.9% flat over 6 months and publishes it to investors. The business will be notified.',
            ),
        ).toBeInTheDocument();
    });

    it('ignores an opening stage the server does not allow', () => {
        const fixture = props(missingFixture);

        render(<AdminApplications {...fixture} stage="approve" />);

        expect(
            screen.queryByRole('form', { name: 'Approve & create note' }),
        ).not.toBeInTheDocument();
    });

    it('blocks approval while the listing audit is missing', () => {
        render(<AdminApplications {...props(missingFixture)} />);

        expect(screen.getByText('Listing audit missing')).toBeInTheDocument();
        const approve = screen.getByRole('button', {
            name: 'Approve & create note',
        });

        expect(approve).toBeDisabled();
        expect(approve).toHaveAccessibleDescription(/Listing audit missing/);
        expect(
            screen.getByText('Recommend: Route to audit'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Approve Kigali Motors' }),
        ).not.toBeInTheDocument();
    });

    it('shows approval unavailable without an audit gap when the server withholds it', () => {
        const fixture = props(reviewFixture);

        if (fixture.review === null) {
            throw new Error('fixture has a review');
        }

        delete fixture.review.actions.approve;
        render(<AdminApplications {...fixture} />);

        const approve = screen.getByRole('button', {
            name: 'Approve & create note',
        });

        expect(approve).toBeDisabled();
        expect(approve).not.toHaveAttribute('aria-describedby');
    });

    it('shows an audit in progress, a manual-review recommendation and a resolved application', () => {
        const fixture = props(reviewFixture);

        if (fixture.review === null) {
            throw new Error('fixture has a review');
        }

        fixture.review = {
            ...fixture.review,
            state: 'approved',
            audit: { state: 'pending', sealed_at: null },
            decision: { code: 'review', reason: 'Borderline.' },
            rating: null,
            reviewer: 'Eric Ndoli',
            actions: {},
            trail: [],
        };
        render(<AdminApplications {...fixture} />);

        expect(
            screen.getByText('Listing audit in progress'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Recommend: Manual review'),
        ).toBeInTheDocument();
        expect(screen.getByText('Reviewer: Eric Ndoli')).toBeInTheDocument();
        expect(
            screen.getByText('No decisions recorded yet.'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Approve & create note' }),
        ).not.toBeInTheDocument();
    });

    it('colours capacity by how much the raise uses', () => {
        const fixture = props(reviewFixture);

        if (fixture.review === null) {
            throw new Error('fixture has a review');
        }

        fixture.review.capacity.used_pct = 120;
        fixture.review.decision = { code: 'approve', reason: 'Fine.' };
        const { unmount } = render(<AdminApplications {...fixture} />);

        expect(screen.getByText('120%')).toBeInTheDocument();
        expect(screen.getByText('Recommend: Approve')).toBeInTheDocument();
        unmount();
        fixture.review.capacity.used_pct = 90;
        render(<AdminApplications {...fixture} />);
        expect(screen.getByText('90%')).toBeInTheDocument();
    });

    it('closes on Escape by visiting the queue', () => {
        const { unmount } = render(
            <AdminApplications {...props(reviewFixture)} />,
        );

        fireEvent.keyDown(window, { key: 'Enter' });
        fireEvent.keyDown(window, { key: 'Escape' });
        expect(inertia.visits).toEqual(['/preview/admin-applications']);
        unmount();
        fireEvent.keyDown(window, { key: 'Escape' });
        expect(inertia.visits).toHaveLength(1);
    });
});
