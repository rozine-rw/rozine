import {
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorProfile from '@/pages/auditor/profile';
import type { Accreditation, AuditorProfileProps } from '@/types/auditor';
import availabilityFixture from '../../../resources/fixtures/ui/auditor-profile-availability.json';
import expiredFixture from '../../../resources/fixtures/ui/auditor-profile-expired.json';
import firstTimePendingFixture from '../../../resources/fixtures/ui/auditor-profile-first-time-pending.json';
import firstTimeFixture from '../../../resources/fixtures/ui/auditor-profile-first-time.json';
import pendingFixture from '../../../resources/fixtures/ui/auditor-profile-pending.json';
import profileFixture from '../../../resources/fixtures/ui/auditor-profile.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, inertia, invalid, operation } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const props = (fixture: { props: unknown } = profileFixture) =>
    structuredClone(fixture.props) as AuditorProfileProps;

beforeEach(() => inertia.reset());

describe('Auditor Profile', () => {
    it('shows the partner, their accreditation standing and the section menu', () => {
        render(<AuditorProfile {...props()} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Profile');
        expect(screen.getByText('Diane Uwase, CPA')).toBeInTheDocument();
        expect(
            screen.getByRole('img', { name: 'Partner rating 99 out of 100' }),
        ).toBeInTheDocument();
        expect(screen.getByText('96%')).toBeInTheDocument();
        expect(screen.getByText('2019')).toBeInTheDocument();
        expect(screen.queryByText(/pass rate/i)).not.toBeInTheDocument();

        const menu = screen.getByRole('navigation', {
            name: 'Profile sections',
        });

        expect(
            within(menu).getByRole('link', { name: /Accreditation/ }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(menu).getByRole('link', { name: /Availability/ }),
        ).toHaveAttribute('href', '/preview/auditor-profile-availability');

        expect(
            screen.getByText('Licence ICPAR/P-2026/0481 · expires 31 Mar 2027'),
        ).toBeInTheDocument();
        expect(screen.getByText('✓ Active')).toBeInTheDocument();
        expect(
            screen.getByRole('progressbar', { name: 'Days of standing left' }),
        ).toHaveValue(179);
        expect(
            screen.getByText('179 days until renewal is due'),
        ).toBeInTheDocument();
    });

    it('submits a renewal with its certificate for staff review', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'ACCREDITATION_SUBMITTED',
                    data: {
                        next: {
                            url: '/preview/auditor-profile-pending',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(<AuditorProfile {...props()} />);

        await user.click(
            screen.getByRole('button', { name: 'Renew accreditation' }),
        );
        await user.clear(screen.getByLabelText('Licence number'));
        await user.type(
            screen.getByLabelText('Licence number'),
            'ICPAR/CPA/1234',
        );
        await user.type(screen.getByLabelText('New expiry date'), '2028-03-31');

        const certificate = new File(['%PDF'], 'icpar.pdf', {
            type: 'application/pdf',
        });

        await user.upload(
            screen.getByLabelText('Licence certificate'),
            certificate,
        );
        expect(screen.getByText('icpar.pdf')).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'Submit for review' }),
        );

        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/auditor-profile-pending',
            body: {
                licence: 'ICPAR/CPA/1234',
                expires_on: '2028-03-31',
                certificate,
                expected_revision: 6,
                identity_context_revision: 3,
            },
        });
        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-profile-pending' },
            ]),
        );
        expect(
            screen.getByRole('button', { name: 'Renew accreditation' }),
        ).toBeInTheDocument();
    });

    it('closes the form with Cancel', async () => {
        const { user } = renderWithUser(<AuditorProfile {...props()} />);

        await user.click(
            screen.getByRole('button', { name: 'Renew accreditation' }),
        );
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(
            screen.getByRole('button', { name: 'Renew accreditation' }),
        ).toBeInTheDocument();
    });

    it('shows renewal field errors and clears a removed certificate', async () => {
        inertia.queue.push(
            invalid({
                licence: 'Enter your licence.',
                expires_on: 'Pick a future date.',
                certificate: 'Attach the certificate.',
            }),
        );
        const { user } = renderWithUser(<AuditorProfile {...props()} />);

        await user.click(
            screen.getByRole('button', { name: 'Renew accreditation' }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Submit for review' }),
        );
        expect(
            await screen.findByText('Enter your licence.'),
        ).toBeInTheDocument();
        expect(screen.getByText('Pick a future date.')).toBeInTheDocument();
        expect(screen.getByText('Attach the certificate.')).toBeInTheDocument();

        const input = screen.getByLabelText('Licence certificate');

        await user.upload(
            input,
            new File(['x'], 'scan.png', { type: 'image/png' }),
        );
        expect(screen.getByText('scan.png')).toBeInTheDocument();
        fireEvent.change(input, { target: { files: [] } });
        expect(
            screen.getByText('Drop the ICPAR certificate'),
        ).toBeInTheDocument();
    });

    it('shows a pending renewal, its certificate evidence, and withdraws it', async () => {
        const { user } = renderWithUser(
            <AuditorProfile {...props(pendingFixture)} />,
        );

        expect(screen.getByText('Renewal pending')).toBeInTheDocument();
        expect(screen.getByText('Renewal under review')).toBeInTheDocument();
        expect(
            screen.getByText(
                /REQ-2026-0419 · licence ICPAR\/P-2026\/0481 to 31 Mar 2028, submitted 2 Oct 2026/,
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Renew accreditation' }),
        ).not.toBeInTheDocument();

        expect(
            screen.getByText(
                /^Certificate ev_cert_2026_0419 · SHA-256 [0-9a-f]{12}…$/u,
            ),
        ).toBeInTheDocument();

        const withdraw = screen.getByRole('button', { name: 'Withdraw' });

        await user.click(withdraw);
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/auditor-profile',
            body: { submission_id: 'REQ-2026-0419', expected_revision: 6 },
        });
        expect(withdraw).toBeDisabled();
    });

    it('offers no withdrawal or renewal the server does not allow', () => {
        render(
            <AuditorProfile {...props(pendingFixture)} allowed_actions={[]} />,
        );

        expect(
            screen.queryByRole('button', { name: 'Withdraw' }),
        ).not.toBeInTheDocument();

        render(<AuditorProfile {...props()} allowed_actions={[]} />);
        expect(
            screen.queryByRole('button', { name: 'Renew accreditation' }),
        ).not.toBeInTheDocument();
    });

    it('asks a first-time partner for their licence, and says it confers no standing yet', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'ACCREDITATION_SUBMITTED',
                    data: {
                        next: {
                            url: '/preview/auditor-profile-first-time-pending',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(
            <AuditorProfile {...props(firstTimeFixture)} />,
        );

        expect(screen.getByText('Not accredited')).toBeInTheDocument();
        expect(screen.getByText('No licence on record')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Submit your ICPAR licence for review. Submitting gives you no standing: you can take work only after an authorized Rozine staff member records the ICPAR check and its dates.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('progressbar', {
                name: 'Days of standing left',
            }),
        ).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'Submit your accreditation' }),
        );
        expect(screen.getByLabelText('Licence number')).toHaveValue('');
        await user.type(
            screen.getByLabelText('Licence number'),
            'ICPAR/P-2026/0512',
        );
        await user.type(
            screen.getByLabelText('Licence expiry date'),
            '2027-12-31',
        );
        await user.click(
            screen.getByRole('button', { name: 'Submit for review' }),
        );

        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/auditor-profile-pending',
            body: {
                licence: 'ICPAR/P-2026/0512',
                expires_on: '2027-12-31',
                certificate: null,
                expected_revision: 6,
            },
        });
    });

    it('shows a first-time submission under review', () => {
        render(<AuditorProfile {...props(firstTimePendingFixture)} />);

        expect(screen.getByText('Under review')).toBeInTheDocument();
        expect(
            screen.getByText('First accreditation under review'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Withdraw' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Submit your accreditation' }),
        ).not.toBeInTheDocument();
    });

    it('shows an expired licence and a declined renewal', () => {
        render(<AuditorProfile {...props(expiredFixture)} />);

        expect(
            screen.getByText('Expired', { selector: 'span' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Expired 12 days ago')).toBeInTheDocument();
        expect(screen.getByText('Renewal declined')).toBeInTheDocument();
        expect(
            screen.getByText(
                'REQ-2026-0388 was declined — the certificate scan is unreadable. Correct the details and submit again.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Renew accreditation' }),
        ).toBeInTheDocument();
    });

    it.each([
        [30, 'bg-[#c0392b]'],
        [120, 'bg-[#c2661f]'],
        [200, 'bg-[#17795a]'],
    ])('colours %i days of standing left', (days, tone) => {
        const base = props();

        render(
            <AuditorProfile
                {...base}
                accreditation={
                    { ...base.accreditation, days_left: days } as Accreditation
                }
            />,
        );

        expect(screen.getByTestId('accreditation-bar')).toHaveClass(tone);
    });

    it('shows a suspended licence and a partner with no score or on-time record yet', () => {
        const base = props();

        render(
            <AuditorProfile
                {...base}
                quality_score={null}
                on_time_pct={null}
                auditor={{ ...base.auditor, avatar_url: '/me.jpg' }}
                accreditation={
                    {
                        ...base.accreditation,
                        status: 'suspended',
                    } as Accreditation
                }
            />,
        );

        expect(screen.getByText('Suspended')).toBeInTheDocument();
        expect(
            screen.queryByRole('img', { name: /Partner rating/ }),
        ).not.toBeInTheDocument();
        expect(screen.getByText('—')).toBeInTheDocument();
    });

    it('switches dispatch availability and shows the policy it runs under', async () => {
        const { user } = renderWithUser(
            <AuditorProfile {...props(availabilityFixture)} />,
        );

        expect(
            within(
                screen.getByRole('navigation', { name: 'Profile sections' }),
            ).getByRole('link', {
                name: /Availability/,
            }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            screen.getByText(
                'You appear in the dispatch pool and can be offered flash audits.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('Jobs at a time')).toBeInTheDocument();
        expect(screen.getByText('3')).toBeInTheDocument();
        expect(screen.getByText('30km')).toBeInTheDocument();

        await user.click(
            screen.getByRole('switch', { name: 'Accepting audits' }),
        );
        expect(inertia.posts[0]).toMatchObject({
            url: '/preview/auditor-profile',
            data: { accepting: false },
        });
    });

    it('reads a paused partner', () => {
        const base = props(availabilityFixture);

        render(
            <AuditorProfile
                {...base}
                availability={{ ...base.availability, accepting: false }}
            />,
        );

        expect(screen.getByText('Paused')).toBeInTheDocument();
        expect(
            screen.getByText(
                'No new jobs are offered. Accepted jobs still run their clock.',
            ),
        ).toBeInTheDocument();
    });
});
