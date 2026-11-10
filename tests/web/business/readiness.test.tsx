import { render, screen, within } from '@testing-library/react';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import BusinessReadiness from '@/pages/business/readiness';
import type { BusinessReadinessProps } from '@/types/business';
import growthFixture from '../../../resources/fixtures/ui/business-readiness-growth.json';
import marketFixture from '../../../resources/fixtures/ui/business-readiness-market.json';
import newFixture from '../../../resources/fixtures/ui/business-readiness-new.json';
import readinessFixture from '../../../resources/fixtures/ui/business-readiness.json';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({
        href,
        children,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
        <a href={href.url} {...props}>
            {children}
        </a>
    ),
}));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessReadinessProps;

const lines = () =>
    screen
        .getAllByRole('listitem')
        .map((item) => item.textContent ?? '')
        .join('\n');

describe('Readiness', () => {
    it('names the settled stage and status, and the stage the latest readings point to', () => {
        render(<BusinessReadiness {...props(readinessFixture)} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Readiness' }),
        ).toBeInTheDocument();
        expect(screen.getAllByText('Stage 2 · Growth').length).toBe(2);
        expect(screen.getByText('Building')).toBeInTheDocument();
        expect(screen.getByText('RWF 31.7M')).toBeInTheDocument();
        expect(lines()).toContain(
            '2 readings in a row point to Stage 3 · Scale. The stage moves up after 3 readings in a row, with 2 of the last 3 months there too.',
        );
        expect(lines()).toContain(
            'Stage 3 · Scale starts at RWF 30M a month. Your average is there: hold it for 3 readings in a row.',
        );
        expect(lines()).not.toContain('?');

        expect(
            screen
                .getAllByRole('listitem')
                .filter((item) => item.getAttribute('aria-current') === 'step')
                .map((item) => item.textContent),
        ).toEqual(['Stage 2 · GrowthFrom RWF 10M a monthCurrent']);
        expect(screen.getByText('September 2026')).toBeInTheDocument();
        expect(screen.getByText('RWF 36M')).toBeInTheDocument();
    });

    it('waits for 3 published reports before naming a stage', () => {
        render(<BusinessReadiness {...props(newFixture)} />);

        expect(screen.getByText('Stage not set yet')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Your stage is set once 3 monthly reports are published. 2 more to go.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByText('Verified monthly revenue'),
        ).not.toBeInTheDocument();
        expect(
            screen
                .getAllByRole('listitem')
                .some((item) => item.getAttribute('aria-current') === 'step'),
        ).toBe(false);
    });

    it('states the gap to the next stage, and a reading pointing down', () => {
        render(<BusinessReadiness {...props(growthFixture)} />);

        expect(lines()).toContain(
            '1 reading so far points to Stage 1 · Foundation. The stage moves down only after 3 readings in a row, with 2 of the last 3 months there too.',
        );
        expect(lines()).toContain(
            'Stage 3 · Scale starts at RWF 30M a month: RWF 15.5M to go.',
        );
    });

    it('names the top stage with no next stage, and no months before the first report', () => {
        const market = props(marketFixture);
        const { unmount } = render(<BusinessReadiness {...market} />);

        expect(lines()).toContain('You are in the top stage.');
        expect(lines()).not.toContain('to go');
        unmount();

        render(
            <BusinessReadiness
                {...market}
                readiness={{
                    ...market.readiness,
                    stage: 0,
                    status: 'ready',
                    months: [],
                    next: null,
                }}
            />,
        );
        expect(screen.getAllByText('Below Foundation').length).toBe(1);
        expect(screen.getByText('Ready')).toBeInTheDocument();
        expect(
            screen.getByText('No monthly report is published yet.'),
        ).toBeInTheDocument();
    });

    it('lists every requirement with its status and the line that matters for it', () => {
        const order = ['C1', 'F1', 'F2', 'F3', 'F4', 'P1', 'P2', 'G1', 'G2'];
        const items = () =>
            within(
                screen.getByRole('list', { name: 'Requirements' }),
            ).getAllByRole('listitem');
        const requirement = (code: string) =>
            items()[order.indexOf(code)].textContent;
        const { unmount } = render(
            <BusinessReadiness {...props(readinessFixture)} />,
        );

        expect(
            screen.getByRole('heading', { level: 2, name: 'Requirements' }),
        ).toBeInTheDocument();
        expect(items().length).toBe(9);
        expect(requirement('C1')).toContain('Met');
        expect(requirement('C1')).toContain('Checked when you joined Rozine');
        expect(requirement('F2')).toContain(
            'Due by 20 November 2026 · 10 days left',
        );
        expect(requirement('F3')).toContain('Applies from Stage 3 · Scale');
        expect(requirement('P1')).toContain('Applies from 10 employees');
        unmount();

        const view = render(<BusinessReadiness {...props(growthFixture)} />);
        expect(requirement('C1')).toContain('Confirmed on 14 October 2026');
        expect(requirement('F2')).toContain(
            'Overdue since 1 October 2026. Your next raise waits until it is met.',
        );
        view.unmount();

        render(<BusinessReadiness {...props(marketFixture)} />);
        expect(requirement('F3')).toContain('Lapsed');
        expect(requirement('F3')).toContain(
            'Meet it again by 20 December 2026 · 40 days left',
        );
        expect(requirement('G2')).toContain('Due by 30 March 2027');
    });

    it('gives each measured signal its value, target, verdict and one action, and never a question', () => {
        render(<BusinessReadiness {...props(readinessFixture)} />);

        const guidance = within(
            screen.getByRole('list', { name: 'Monthly guidance' }),
        );
        const [gap, forecast] = guidance.getAllByRole('listitem');

        expect(gap).toHaveTextContent(
            'Record GapOn target2.7% over the last 3 months. Target: 10% or less.Keep recording every sale and every payment in your books on the day it happens.',
        );
        expect(forecast).toHaveTextContent(
            "Forecast ErrorOff target30.0% over the last 2 months. Target: 25% or less.Your forecasts were above the money that came in.Start next month's forecast from your verified average, and add only orders you hold in writing.",
        );
        expect(
            screen.getByText(
                'After each published report, each signal is measured against the target for your stage, with one action.',
            ),
        ).toBeInTheDocument();
        expect(lines()).not.toContain('?');
    });

    it('names a single month, a forecast that ran under and a record gap to fix', () => {
        render(<BusinessReadiness {...props(growthFixture)} />);

        const [gap, forecast] = within(
            screen.getByRole('list', { name: 'Monthly guidance' }),
        ).getAllByRole('listitem');

        expect(gap).toHaveTextContent(
            'Record GapOff target6.4% over the last 1 month. Target: 5% or less.Record every cash sale and every payment in your books on the same day, and keep each receipt.',
        );
        expect(forecast).toHaveTextContent(
            'Your forecasts were below the money that came in.Keep starting each forecast from your verified average and the orders you hold in writing.',
        );
    });

    it('says when guidance starts before any sent month is verified', () => {
        render(<BusinessReadiness {...props(newFixture)} />);

        expect(
            screen.getByText(
                'Your guidance starts once a month you sent figures for is verified in a published report.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('list', { name: 'Monthly guidance' }),
        ).not.toBeInTheDocument();
    });
});
