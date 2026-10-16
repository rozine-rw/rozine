import { act, render, screen, waitFor, within } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import en from '@/lib/i18n/catalogs/en';
import fr from '@/lib/i18n/catalogs/fr';
import rw from '@/lib/i18n/catalogs/rw';
import AuditorFile from '@/pages/auditor/file';
import AuditorJobs from '@/pages/auditor/jobs';
import type { AuditorFileProps, AuditorJobsProps } from '@/types/auditor';
import fileFixture from '../../../resources/fixtures/ui/auditor-file.json';
import closingFixture from '../../../resources/fixtures/ui/auditor-jobs-offer-closing.json';
import jobsFixture from '../../../resources/fixtures/ui/auditor-jobs.json';
import { renderWithUser } from '../helpers/render-with-user';
import { fails, inertia } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const jobs = (fixture: { props: unknown } = jobsFixture) =>
    structuredClone(fixture.props) as AuditorJobsProps;

beforeEach(() => inertia.reset());

afterEach(() => {
    vi.useRealTimers();
});

describe('The offer clock', () => {
    it('names when a flash audit is due and counts the offer down from the server’s time', () => {
        render(<AuditorJobs {...jobs()} />);
        const card = screen.getByRole('article', { name: 'Huye Motors' });

        /* Due 24h from dispatch (14:00Z), shown in Kigali time. */
        expect(
            within(card).getByRole('button', {
                name: 'Accept · due 4 Oct · 16:00',
            }),
        ).toBeInTheDocument();
        expect(
            within(card).getByRole('timer', {
                name: 'Time left to accept this offer',
            }),
        ).toHaveTextContent(
            /^Offer open until 3 Oct · 19:40 · 00:(40:00|39:5\d) left$/u,
        );
    });

    it('offers a routine job with no due time of its own', () => {
        const base = jobs();

        render(
            <AuditorJobs
                {...base}
                eligible={[{ ...base.eligible[0], complete_by: null }]}
            />,
        );

        expect(
            screen.getByRole('button', { name: 'Accept' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /due/u }),
        ).not.toBeInTheDocument();
    });

    it('closes an offer when its time runs out, leaving what the record still allows', () => {
        vi.useFakeTimers({ now: Date.parse('2026-09-24T09:00:00Z') });
        render(<AuditorJobs {...jobs(closingFixture)} />);
        const closing = screen.getByRole('article', { name: 'Huye Motors' });
        const closed = screen.getByRole('article', { name: 'Isoko Energy' });

        expect(within(closed).getByRole('status')).toHaveTextContent(
            'This offer has closed',
        );
        expect(
            within(closed).queryByRole('button', { name: /Accept/u }),
        ).not.toBeInTheDocument();
        expect(
            within(closed).getByRole('button', { name: 'Decline' }),
        ).toBeInTheDocument();
        expect(
            within(closing).getByRole('timer', { name: /accept/u }),
        ).toHaveTextContent('00:04:00 left');

        act(() => {
            vi.advanceTimersByTime(4 * 60 * 1000);
        });

        expect(within(closing).getByRole('status')).toHaveTextContent(
            'This offer has closed',
        );
        expect(
            within(closing).queryByRole('button', { name: /Accept/u }),
        ).not.toBeInTheDocument();
        expect(
            within(closing).getByRole('button', { name: 'Declare a conflict' }),
        ).toBeInTheDocument();
    });

    it('says an offer closed before the acceptance arrived, and refreshes', async () => {
        inertia.queue.push(
            fails(409, { code: 'ASSIGNMENT_ACCEPTANCE_EXPIRED' }),
        );
        const { user } = renderWithUser(<AuditorJobs {...jobs()} />);

        await user.click(
            screen.getByRole('button', { name: 'Accept · due 4 Oct · 16:00' }),
        );

        expect(
            await screen.findByText(
                "This offer closed before your acceptance reached Rozine, so it wasn't accepted. The page has been refreshed.",
            ),
        ).toBeInTheDocument();
        await waitFor(() => expect(inertia.reloads).toEqual([undefined]));
    });

    it('closes an offered file whose offer has run out', () => {
        const base = structuredClone(fileFixture.props) as AuditorFileProps;

        render(
            <AuditorFile
                {...base}
                job={{ ...base.job, accept_by: '2026-10-03T16:30:00Z' }}
            />,
        );

        const sheet = screen.getByRole('dialog', {
            name: 'Huye Motors business file',
        });

        expect(within(sheet).getByRole('status')).toHaveTextContent(
            'This offer has closed',
        );
        expect(
            within(sheet).queryByRole('button', { name: /Accept/u }),
        ).not.toBeInTheDocument();
    });

    it('shows an offered file’s flash clock from dispatch, and none for a routine offer', () => {
        const base = structuredClone(fileFixture.props) as AuditorFileProps;
        const { unmount } = render(<AuditorFile {...base} />);

        expect(
            screen.getByRole('timer', { name: 'Time left on this job' }),
        ).toHaveTextContent(/^Time left(21:00:00|20:59:5\d)$/u);
        unmount();

        render(
            <AuditorFile {...base} job={{ ...base.job, complete_by: null }} />,
        );

        expect(
            screen.queryByRole('timer', { name: 'Time left on this job' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Accept' }),
        ).toBeInTheDocument();
    });

    it('leaves no copy saying acceptance starts the clock', () => {
        for (const catalog of [en, fr, rw]) {
            const text = Object.values(catalog)
                .filter((value) => typeof value === 'string')
                .join('\n');

            expect(text).not.toMatch(
                /start[s]? (a|the) \{?\w*\}?[- ]?h(our)? ?clock/iu,
            );
            expect(text).not.toMatch(/lance un délai/u);
        }

        expect(Object.keys(en)).not.toContain('auditor.jobs.accept');
    });
});
