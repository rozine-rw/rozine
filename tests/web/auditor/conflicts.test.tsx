import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorConflicts from '@/pages/auditor/conflicts';
import type { AuditorConflictsProps } from '@/types/auditor';
import receiptFixture from '../../../resources/fixtures/ui/auditor-conflict-receipt.json';
import emptyFixture from '../../../resources/fixtures/ui/auditor-conflicts-empty.json';
import conflictsFixture from '../../../resources/fixtures/ui/auditor-conflicts.json';
import { inertia } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const props = (fixture: { props: unknown } = conflictsFixture) =>
    structuredClone(fixture.props) as AuditorConflictsProps;

const receipt = (reference: string) =>
    screen.getByRole('article', {
        name: `Business on record · Ref. ${reference}`,
    });

beforeEach(() => inertia.reset());

describe('Auditor conflicts', () => {
    it('lists each own receipt under Portfolio, named only by its assignment', () => {
        render(<AuditorConflicts {...props()} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Your conflicts');
        expect(
            screen.getByRole('heading', { name: 'Your declared conflicts' }),
        ).toBeInTheDocument();
        expect(screen.getAllByRole('article')).toHaveLength(4);

        for (const nav of screen.getAllByRole('navigation', {
            name: 'App navigation',
        })) {
            expect(
                within(nav).getByRole('link', { name: /Portfolio/u }),
            ).toHaveAttribute('aria-current', 'page');
        }

        /* No Business name, note ID or Business ID reaches the page. */
        expect(
            screen.queryByText(/01k5zq1b7c3d9e2f6g0h4j8k2m/u),
        ).not.toBeInTheDocument();
        expect(screen.queryByText(/RNP-/u)).not.toBeInTheDocument();
    });

    it("shows the declarant's own kind, note, date and coarse status", () => {
        render(<AuditorConflicts {...props()} />);

        const pending = receipt('…6o0p3a');

        expect(
            within(pending).getByText('Close family or business tie'),
        ).toBeInTheDocument();
        expect(
            within(pending).getByText(
                'My brother-in-law is a director of this business. I learned of the tie at the site-visit briefing.',
            ),
        ).toBeInTheDocument();
        expect(
            within(pending).getByText('3 Oct 2026 · 16:12'),
        ).toBeInTheDocument();
        expect(
            within(pending).getByText('Reassignment pending'),
        ).toBeInTheDocument();
        expect(
            within(receipt('…n5p9q3')).getByText('Reassigned'),
        ).toBeInTheDocument();
        expect(
            within(receipt('…1l5z9x')).getByText('Closed by Audit Operations'),
        ).toBeInTheDocument();
        expect(
            within(receipt('…j3k7l1')).getByText('Recorded'),
        ).toBeInTheDocument();
    });

    it('describes a closed assignment as closed by Audit Operations, never as reassigned', () => {
        render(<AuditorConflicts {...props(receiptFixture)} />);

        const closed = screen.getByRole('article');

        expect(closed).toHaveAccessibleName(
            'Business on record · Ref. …1l5z9x',
        );
        expect(
            within(closed).getByText(
                'Your conflict has been recorded. Audit Operations has closed this assignment, and you no longer have access to its file.',
            ),
        ).toBeInTheDocument();
        expect(
            within(closed).getByText('Closed by Audit Operations'),
        ).toBeInTheDocument();
        expect(closed).not.toHaveTextContent(/reassign/iu);
        expect(
            screen.queryByRole('link', { name: 'Show more' }),
        ).not.toBeInTheDocument();
    });

    it('ends a page with more behind it in Show more', () => {
        render(<AuditorConflicts {...props()} />);

        expect(screen.getByRole('link', { name: 'Show more' })).toHaveAttribute(
            'href',
            '/auditor/conflicts?before=01k6a1b5c9d3e7f1g5h9j3k7l1',
        );
    });

    it('says so when nothing has been declared', () => {
        render(<AuditorConflicts {...props(emptyFixture)} />);

        expect(
            screen.getByText('You have not declared any conflicts.'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Show more' }),
        ).not.toBeInTheDocument();
    });

    it('keeps offering Show more after an empty page', () => {
        render(
            <AuditorConflicts
                {...props(emptyFixture)}
                pagination={{
                    next: { url: '/auditor/conflicts?before=x', method: 'get' },
                }}
            />,
        );

        expect(
            screen.getByText(
                'Nothing to show on this page. Earlier declarations may follow.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByText('You have not declared any conflicts.'),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Show more' })).toHaveAttribute(
            'href',
            '/auditor/conflicts?before=x',
        );
    });

    it('reads a short assignment ID as sent', () => {
        const base = props(receiptFixture);

        render(
            <AuditorConflicts
                {...base}
                conflicts={[{ ...base.conflicts[0], assignment_id: 'asg_42' }]}
            />,
        );

        expect(
            screen.getByRole('article', {
                name: 'Business on record · Ref. asg_42',
            }),
        ).toBeInTheDocument();
    });
});
