import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { EngagementBanner } from '@/components/auditor/engagement/engagement-banner';
import AuditorFile from '@/pages/auditor/file';
import AuditorHome from '@/pages/auditor/home';
import AuditorJobs from '@/pages/auditor/jobs';
import AuditorProfile from '@/pages/auditor/profile';
import type {
    AuditorFileProps,
    AuditorHomeProps,
    AuditorJobsProps,
    AuditorProfileProps,
    EngagementSummary,
} from '@/types/auditor';
import fileRequiredFixture from '../../../resources/fixtures/ui/auditor-file-engagement-required.json';
import fileFixture from '../../../resources/fixtures/ui/auditor-file.json';
import homeRequiredFixture from '../../../resources/fixtures/ui/auditor-home-engagement-required.json';
import homeFixture from '../../../resources/fixtures/ui/auditor-home.json';
import jobsRequiredFixture from '../../../resources/fixtures/ui/auditor-jobs-engagement-required.json';
import jobsUnavailableFixture from '../../../resources/fixtures/ui/auditor-jobs-engagement-unavailable.json';
import jobsFixture from '../../../resources/fixtures/ui/auditor-jobs.json';
import profileRequiredFixture from '../../../resources/fixtures/ui/auditor-profile-engagement-required.json';
import profileFixture from '../../../resources/fixtures/ui/auditor-profile.json';
import { renderWithUser } from '../helpers/render-with-user';
import { fails, inertia } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const clone = <T,>(fixture: { props: unknown }): T =>
    structuredClone(fixture.props) as T;

const REQUIRED = 'Review and accept the engagement terms to take new work';
const UNAVAILABLE = 'Engagement terms aren’t available; new work is paused';

const summary = (status: EngagementSummary['status']): EngagementSummary => ({
    status,
    link: { url: '/preview/auditor-engagement', method: 'get' },
});

const bannerLinks = () =>
    screen.queryAllByRole('link', { name: new RegExp(REQUIRED, 'u') });

beforeEach(() => inertia.reset());

describe('Engagement summary banner', () => {
    it('links to the agreement page only while acceptance is required', () => {
        const { rerender } = render(
            <EngagementBanner engagement={summary('required')} />,
        );

        expect(bannerLinks()).toHaveLength(1);
        expect(bannerLinks()[0]).toHaveAttribute(
            'href',
            '/preview/auditor-engagement',
        );

        rerender(<EngagementBanner engagement={summary('unavailable')} />);
        expect(bannerLinks()).toHaveLength(0);
        expect(screen.getByRole('status')).toHaveTextContent(UNAVAILABLE);
        expect(screen.queryByRole('link')).not.toBeInTheDocument();

        for (const quiet of [summary('current'), null, undefined]) {
            rerender(<EngagementBanner engagement={quiet} />);
            expect(screen.queryByRole('link')).not.toBeInTheDocument();
            expect(screen.queryByRole('status')).not.toBeInTheDocument();
        }
    });

    it('shows on Home when required, and nothing when current or absent', () => {
        const { rerender } = render(
            <AuditorHome {...clone<AuditorHomeProps>(homeRequiredFixture)} />,
        );

        expect(bannerLinks()).toHaveLength(1);

        rerender(
            <AuditorHome
                {...clone<AuditorHomeProps>(homeFixture)}
                engagement={summary('current')}
            />,
        );
        expect(bannerLinks()).toHaveLength(0);

        rerender(<AuditorHome {...clone<AuditorHomeProps>(homeFixture)} />);
        expect(bannerLinks()).toHaveLength(0);
        expect(screen.queryByText(UNAVAILABLE)).not.toBeInTheDocument();
    });

    it('shows on Jobs, where an offer without assignment.accept keeps decline and conflict only', () => {
        render(
            <AuditorJobs {...clone<AuditorJobsProps>(jobsRequiredFixture)} />,
        );

        expect(bannerLinks()).toHaveLength(1);
        expect(
            screen.queryByRole('button', { name: /^Accept/u }),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Decline' })).toBeEnabled();
        expect(
            screen.getByRole('button', { name: 'Declare a conflict' }),
        ).toBeEnabled();
    });

    it('keeps an offer’s Accept while its own allowed_actions list it, whatever the banner says', () => {
        render(
            <AuditorJobs
                {...clone<AuditorJobsProps>(jobsFixture)}
                engagement={summary('required')}
            />,
        );

        expect(bannerLinks()).toHaveLength(1);
        expect(
            screen.getByRole('button', { name: 'Accept · due 4 Oct · 16:00' }),
        ).toBeEnabled();
    });

    it('shows the muted line on Jobs while no terms are available', () => {
        render(
            <AuditorJobs
                {...clone<AuditorJobsProps>(jobsUnavailableFixture)}
            />,
        );

        expect(screen.getByText(UNAVAILABLE)).toBeInTheDocument();
        expect(bannerLinks()).toHaveLength(0);
        expect(
            screen.getByRole('button', { name: 'Accept · due 4 Oct · 16:00' }),
        ).toBeEnabled();
    });

    it('shows once in the file sheet, not again in the Jobs beneath it', () => {
        render(
            <AuditorFile {...clone<AuditorFileProps>(fileRequiredFixture)} />,
        );

        expect(bannerLinks()).toHaveLength(1);
        expect(
            within(screen.getByRole('dialog')).getByRole('link', {
                name: new RegExp(REQUIRED, 'u'),
            }),
        ).toBeInTheDocument();
        expect(
            within(screen.getByRole('dialog')).queryByRole('button', {
                name: /^Accept/u,
            }),
        ).not.toBeInTheDocument();
    });

    it('leaves the banner out of a file withheld by a blocking conflict', () => {
        const base = clone<AuditorFileProps>(fileRequiredFixture);

        render(
            <AuditorFile
                {...base}
                file={null}
                blocked={{
                    conflict_id: '01k6x0conflict000000000001',
                    kind: 'family_or_business',
                    note: 'A relative keeps their books.',
                    declared_at: '2026-10-03T16:00:00Z',
                    blocking: true,
                    status: 'reassignment_pending',
                }}
            />,
        );

        expect(bannerLinks()).toHaveLength(0);
    });

    it('shows on Profile when required', () => {
        const { rerender } = render(
            <AuditorProfile
                {...clone<AuditorProfileProps>(profileRequiredFixture)}
            />,
        );

        expect(bannerLinks()).toHaveLength(1);

        rerender(
            <AuditorProfile {...clone<AuditorProfileProps>(profileFixture)} />,
        );
        expect(bannerLinks()).toHaveLength(0);
    });
});

describe('A refusal for terms not yet accepted', () => {
    it('links to the agreement page the summary names, without reloading', async () => {
        inertia.queue.push(
            fails(403, { code: 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED' }),
        );
        const { user } = renderWithUser(
            <AuditorJobs
                {...clone<AuditorJobsProps>(jobsFixture)}
                engagement={summary('required')}
            />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Accept · due 4 Oct · 16:00' }),
        );

        const alert = await screen.findByRole('alert');

        expect(alert).toHaveTextContent(
            'Accept the current engagement terms to continue. Review the terms',
        );
        expect(
            within(alert).getByRole('link', { name: 'Review the terms' }),
        ).toHaveAttribute('href', '/preview/auditor-engagement');
        expect(inertia.reloads).toEqual([]);
    });

    it('links from the file sheet too, where the notice takes its own spacing', async () => {
        inertia.queue.push(
            fails(403, { code: 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED' }),
        );
        const { user } = renderWithUser(
            <AuditorFile
                {...clone<AuditorFileProps>(fileFixture)}
                engagement={summary('required')}
            />,
        );
        const sheet = screen.getByRole('dialog');

        await user.click(
            within(sheet).getByRole('button', { name: /^Accept/u }),
        );

        const alert = await within(sheet).findByRole('alert');

        expect(
            within(alert).getByRole('link', { name: 'Review the terms' }),
        ).toHaveAttribute('href', '/preview/auditor-engagement');
        expect(inertia.reloads).toEqual([]);
    });

    it('is explained without a link where the page names no agreement page', async () => {
        inertia.queue.push(
            fails(403, { code: 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED' }),
        );
        const { user } = renderWithUser(
            <AuditorJobs {...clone<AuditorJobsProps>(jobsFixture)} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Accept · due 4 Oct · 16:00' }),
        );

        const alert = await screen.findByRole('alert');

        expect(alert).toHaveTextContent(
            'Accept the current engagement terms to continue.',
        );
        expect(within(alert).queryByRole('link')).not.toBeInTheDocument();
        expect(inertia.reloads).toEqual([]);
    });
});
