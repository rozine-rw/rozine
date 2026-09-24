import { act, render, screen, waitFor, within } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import AuditorHome from '@/pages/auditor/home';
import type {
    AssignedJob,
    AuditorActivity,
    AuditorHomeProps,
} from '@/types/auditor';
import liveMinimalFixture from '../../../resources/fixtures/ui/auditor-home-live-minimal.json';
import homeFixture from '../../../resources/fixtures/ui/auditor-home.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, fails, inertia, operation } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const fixture = () => structuredClone(homeFixture.props) as AuditorHomeProps;
const money = (amount: number) => ({
    currency: 'RWF' as const,
    amount: String(amount),
});

beforeEach(() => inertia.reset());

afterEach(() => {
    vi.useRealTimers();
});

describe('Auditor Home', () => {
    it('lays out identity, standing, nearby work and activity from server facts', () => {
        render(<AuditorHome {...fixture()} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Home');
        expect(screen.getByText('RWF 644,319')).toBeInTheDocument();
        expect(screen.getByText('Good evening,')).toBeInTheDocument();
        expect(screen.getByText('Diane Uwase, CPA')).toBeInTheDocument();
        expect(
            screen.getByText('Uwase & Co. · ICPAR · CPA'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('img', { name: 'Partner rating 99 out of 100' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Yield · OCT')).toBeInTheDocument();
        expect(screen.getByText('644K')).toBeInTheDocument();
        expect(screen.getByText('30')).toBeInTheDocument();
        expect(screen.getByText('Mar 2027')).toBeInTheDocument();
        expect(screen.queryByText(/pass rate/i)).not.toBeInTheDocument();
        expect(
            screen.getByText('30 km radius · max 3 jobs at a time'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /1 Flash Audit nearby/ }),
        ).toHaveAttribute('href', '/preview/auditor-jobs');
        expect(
            screen.getByText('Closest 11.2km · first to accept locks the file'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /Murakoze Tech/ }),
        ).toHaveAttribute('href', '/preview/auditor-audit-review');
        expect(
            screen.getByText('Kicukiro · 7.7km · Step 1 of 5'),
        ).toBeInTheDocument();
        expect(
            screen.getAllByRole('timer', { name: 'Time left on this job' })[0],
        ).toHaveTextContent(/20:59:5\d/);
        expect(screen.getByText('96%')).toBeInTheDocument();
        expect(screen.getAllByText('2.1%')[0]).toHaveClass('text-rz-positive');
        expect(screen.getByText('Yield-share payout')).toBeInTheDocument();
        expect(screen.getByText('+RWF 102K')).toBeInTheDocument();
        expect(
            screen.getByText('Payment deferred · Musanze Traders'),
        ).toBeInTheDocument();
        expect(screen.getByText('1d ago · demand fell')).toBeInTheDocument();
        expect(
            screen.getByText('Filed · Kivu Coffee Roasters'),
        ).toBeInTheDocument();
        expect(screen.getByText('2d ago · 2.1% variance')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Notifications, 2 unread' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Withdraw' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Statement' }),
        ).toBeInTheDocument();
    });

    it('marks Home current in the sidebar and tab bar, with the open-jobs badge on the phone', () => {
        render(<AuditorHome {...fixture()} />);

        const navs = screen.getAllByRole('navigation', {
            name: 'App navigation',
        });

        expect(navs).toHaveLength(2);

        for (const nav of navs) {
            expect(
                within(nav).getByRole('link', { name: /^Home/ }),
            ).toHaveAttribute('aria-current', 'page');
            expect(
                within(nav).getByRole('link', { name: /Portfolio/ }),
            ).toHaveAttribute('href', '/preview/auditor-portfolio');
        }

        expect(screen.getAllByLabelText('1 open Flash Audits')).toHaveLength(2);
    });

    it('asks the server to pause dispatch as a command and waits for its answer', async () => {
        const { user } = renderWithUser(<AuditorHome {...fixture()} />);
        const toggle = screen.getByRole('switch', { name: 'Accepting audits' });

        expect(toggle).toBeChecked();
        await user.click(toggle);

        expect(inertia.calls[0]).toEqual({
            url: '/preview/auditor-home',
            method: 'post',
            body: {
                accepting: false,
                expected_revision: 5,
                identity_context_revision: 3,
                request_id: expect.stringMatching(/^[0-9a-f-]{36}$/u),
            },
        });
        expect(toggle).toBeDisabled();
        expect(toggle).toHaveAttribute('aria-busy', 'true');
    });

    it('reloads the page in place once the server records the change', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'AVAILABILITY_UPDATED',
                    data: {
                        next: { url: '/preview/auditor-home', method: 'get' },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(<AuditorHome {...fixture()} />);

        await user.click(
            screen.getByRole('switch', { name: 'Accepting audits' }),
        );

        await waitFor(() => expect(inertia.reloads).toEqual([undefined]));
        expect(inertia.visits).toEqual([]);
    });

    it('explains a refused change and keeps the switch as the server has it', async () => {
        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        const { user } = renderWithUser(<AuditorHome {...fixture()} />);
        const toggle = screen.getByRole('switch', { name: 'Accepting audits' });

        await user.click(toggle);

        expect(
            await screen.findByText(/This record changed since you opened it/u),
        ).toBeInTheDocument();
        expect(toggle).toBeChecked();
        expect(toggle).toBeEnabled();
    });

    it('looks up a lost change, refreshes the page first, then offers the identical retry', async () => {
        inertia.holdReload = true;
        inertia.queue.push(
            fails(503),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
        );
        const view = renderWithUser(<AuditorHome {...fixture()} />);

        await view.user.click(
            screen.getByRole('switch', { name: 'Accepting audits' }),
        );

        await waitFor(() =>
            expect(inertia.reloads).toEqual([
                { onFinish: expect.any(Function) },
            ]),
        );
        expect(screen.getByText('Checking what happened')).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Try again' }),
        ).not.toBeInTheDocument();

        act(() => inertia.finishReload.forEach((finish) => finish()));
        expect(
            await screen.findByRole('button', { name: 'Try again' }),
        ).toBeInTheDocument();

        /* The refreshed props move on; the held command does not. */
        const base = fixture();

        view.rerender(
            <AuditorHome
                {...base}
                identity_context_revision={4}
                availability={{ ...base.availability, revision: 6 }}
            />,
        );
        await view.user.click(
            screen.getByRole('button', { name: 'Try again' }),
        );

        expect(inertia.calls[2]).toEqual(inertia.calls[0]);
        expect(inertia.calls[2].body).toMatchObject({
            expected_revision: 5,
            identity_context_revision: 3,
        });
    });

    it('shows the switch without offering it when the server does not allow the change', () => {
        render(<AuditorHome {...fixture()} allowed_actions={[]} />);

        expect(
            screen.getByRole('switch', { name: 'Accepting audits' }),
        ).toBeDisabled();
        expect(
            screen.getByText(
                "Your availability can't be changed from here right now.",
            ),
        ).toBeInTheDocument();
    });

    it('says what the server cannot state yet, and hides destinations that do not exist', () => {
        render(
            <AuditorHome
                {...(structuredClone(
                    liveMinimalFixture.props,
                ) as AuditorHomeProps)}
            />,
        );

        expect(screen.getAllByText('Unavailable')).toHaveLength(2);
        expect(screen.getByText('Wallet balance')).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: /Wallet balance/ }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Withdraw' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Statement' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: /Notifications/ }),
        ).not.toBeInTheDocument();
        expect(screen.queryByText(/RWF/u)).not.toBeInTheDocument();
        expect(
            screen.getByRole('switch', { name: 'Accepting audits' }),
        ).toBeDisabled();
    });

    it('reads a quiet morning: paused, no score, no earnings, nothing nearby or running', () => {
        const props = fixture();

        render(
            <AuditorHome
                {...props}
                server_time="2026-10-03T06:00:00Z"
                quality_score={null}
                earned_this_month={null}
                availability={{ ...props.availability, accepting: false }}
                nearby={{ count: 0, closest_km: null }}
                in_progress={[]}
                activity={[]}
                unread_notifications={0}
                auditor={{ ...props.auditor, avatar_url: '/avatar.png' }}
                standing={{
                    current: true,
                    reason: null,
                    on_time_pct: null,
                    avg_variance_pct: null,
                    variance_flagged: true,
                    jobs_done: 0,
                    clock_expiries: 2,
                }}
            />,
        );

        expect(screen.getByText('Good morning,')).toBeInTheDocument();
        expect(
            screen.queryByRole('img', { name: /Partner rating/ }),
        ).not.toBeInTheDocument();
        expect(screen.getByText('Paused')).toBeInTheDocument();
        expect(
            screen.getByText('Tap to start accepting flash audits again'),
        ).toBeInTheDocument();
        expect(screen.queryByText(/nearby/)).not.toBeInTheDocument();
        expect(screen.queryByText('In progress')).not.toBeInTheDocument();
        expect(
            screen.getByText('Nothing has happened on your files yet.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Notifications' }),
        ).toBeInTheDocument();
        expect(screen.getAllByText('—')).toHaveLength(3);
        expect(screen.getByText('2')).toHaveClass('text-rz-ink');
        expect(screen.queryAllByLabelText(/open Flash Audits/)).toHaveLength(0);
    });

    it('greets in the afternoon and counts several nearby jobs', () => {
        render(
            <AuditorHome
                {...fixture()}
                server_time="2026-10-03T12:00:00Z"
                nearby={{ count: 3, closest_km: null }}
            />,
        );

        expect(screen.getByText('Good afternoon,')).toBeInTheDocument();
        expect(screen.getByText('3 Flash Audits nearby')).toBeInTheDocument();
        expect(screen.queryByText(/Closest/)).not.toBeInTheDocument();
    });

    it('leaves out the tabs and the nearby-work link whose routes the server has not published', () => {
        const base = fixture();

        render(
            <AuditorHome
                {...base}
                links={{ ...base.links, jobs: null, portfolio: null }}
            />,
        );

        expect(
            screen.queryByText(/Flash Audit nearby/),
        ).not.toBeInTheDocument();

        for (const nav of screen.getAllByRole('navigation', {
            name: 'App navigation',
        })) {
            expect(
                within(nav).getByRole('link', { name: /^Home/ }),
            ).toBeInTheDocument();
            expect(
                within(nav).queryByRole('link', { name: /Jobs/ }),
            ).not.toBeInTheDocument();
            expect(
                within(nav).queryByRole('link', { name: /Portfolio/ }),
            ).not.toBeInTheDocument();
        }
    });

    it('renders every kind of activity the server records', () => {
        const activity: AuditorActivity[] = [
            {
                kind: 'report_filed',
                at: '2026-10-03T16:30:00Z',
                business: 'Alpha',
                variance_pct: null,
            },
            {
                kind: 'report_published',
                at: '2026-10-03T12:00:00Z',
                business: 'Beta',
                month: '2026-09-01',
            },
            {
                kind: 'repayment_received',
                at: '2026-10-02T12:00:00Z',
                business: 'Gamma',
                amount: money(950),
            },
            {
                kind: 'repayment_missed',
                at: '2026-10-01T12:00:00Z',
                business: 'Delta',
            },
        ];

        render(<AuditorHome {...fixture()} activity={activity} />);

        expect(screen.getByText('Filed · Alpha')).toBeInTheDocument();
        expect(screen.getByText('30m ago')).toBeInTheDocument();
        expect(
            screen.getByText('September 2026 report approved'),
        ).toBeInTheDocument();
        expect(screen.getByText('5h ago · Beta')).toBeInTheDocument();
        expect(screen.getByText('Repayment received')).toBeInTheDocument();
        expect(screen.getByText('RWF 950')).toBeInTheDocument();
        expect(
            screen.getByText('Repayment missed · Delta'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('2d ago · your share pauses'),
        ).toBeInTheDocument();
    });

    it('ticks the clock from the server time and colours it by what is left', () => {
        vi.useFakeTimers({ now: new Date('2026-10-03T17:00:00Z') });
        const job = fixture().in_progress[0];
        const jobs: AssignedJob[] = [
            {
                ...job,
                id: 'a',
                business: 'Red',
                deadline: { due_at: '2026-10-03T19:00:00Z' },
            },
            {
                ...job,
                id: 'b',
                business: 'Amber',
                deadline: { due_at: '2026-10-04T01:00:00Z' },
            },
            {
                ...job,
                id: 'c',
                business: 'Late',
                status: 'overdue',
                reassigned_from: 'Chantal Rwema, CPA',
                deadline: { due_at: '2026-10-03T16:00:00Z' },
            },
            { ...job, id: 'd', business: 'Waiting', status: 'awaiting_cosign' },
        ];

        render(<AuditorHome {...fixture()} in_progress={jobs} />);

        const [red, amber, late] = screen.getAllByRole('timer');

        expect(red).toHaveTextContent('02:00:00');
        expect(red).toHaveClass('text-rz-danger-text');
        expect(amber).toHaveClass('bg-rz-accent-soft');
        expect(late).toHaveTextContent('00:00:00');
        expect(screen.getByText('Overdue')).toBeInTheDocument();
        expect(
            screen.getByText('Reassigned from Chantal Rwema, CPA'),
        ).toBeInTheDocument();
        expect(screen.getByText('Awaiting co-signature')).toBeInTheDocument();

        act(() => {
            vi.advanceTimersByTime(3000);
        });

        expect(red).toHaveTextContent('01:59:57');
    });
});
