import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminPendingSection from '@/pages/admin/section';
import type { AdminPendingSectionProps } from '@/types/admin';
import sectionFixture from '../../../resources/fixtures/ui/admin-section.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (): AdminPendingSectionProps => ({
    ...(structuredClone(sectionFixture.props) as AdminPendingSectionProps),
    section: 'academies',
});

const cells = (row: HTMLElement) =>
    within(row)
        .getAllByRole('cell')
        .map((cell) => cell.textContent);

beforeEach(resetInertia);

describe('Academies', () => {
    it('lists the Investor Academy lessons by track with their topics, and counts every academy', () => {
        render(<AdminPendingSection {...props()} />);

        const region = screen.getByRole('region', { name: 'Academies' });

        expect(region).toHaveTextContent('Lessons live19');
        expect(region).toHaveTextContent('Tracks10');
        expect(region).toHaveTextContent('Quick checks38');
        expect(region).toHaveTextContent(
            'Lessons completed—Kept on each learner’s device',
        );
        expect(
            screen.getAllByRole('tab').map((tab) => tab.textContent),
        ).toEqual([
            'Investor Academy 8',
            'Business Academy 0',
            'Auditor Academy 11',
            'All 19',
        ]);
        expect(
            screen.getByRole('tab', { name: 'Investor Academy 8' }),
        ).toHaveAttribute('aria-selected', 'true');
        expect(region).toHaveTextContent('Lessons8 live in Investor app');

        const rows = within(
            screen.getByRole('table', { name: 'Lessons' }),
        ).getAllByRole('row');

        expect(rows).toHaveLength(9);
        expect(cells(rows[1] as HTMLElement)).toEqual([
            'LiveRozine notesWhat you buy and how it pays',
            'Rozine basics',
            '2',
        ]);
        expect(cells(rows[8] as HTMLElement)).toEqual([
            expect.stringContaining('Repayments and payouts'),
            'Investing on Rozine',
            '2',
        ]);
        expect(within(region).queryAllByRole('button')).toEqual([]);
        expect(within(region).queryAllByRole('link')).toEqual([]);
    });

    it('lists the Auditor Academy, keeps the Business academy empty and shows every lesson under All', () => {
        render(<AdminPendingSection {...props()} />);

        fireEvent.click(
            screen.getByRole('tab', { name: 'Business Academy 0' }),
        );
        expect(screen.getByText('No lessons to show yet.')).toBeVisible();
        expect(screen.queryByText(/live in/u)).not.toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('tab', { name: 'Auditor Academy 11' }),
        );
        expect(screen.getByText('11 live in Auditor app')).toBeVisible();

        const auditor = within(
            screen.getByRole('table', { name: 'Lessons' }),
        ).getAllByRole('row');

        expect(auditor).toHaveLength(12);
        expect(cells(auditor[1] as HTMLElement)).toEqual([
            expect.stringMatching(/^LiveWho you are/u),
            'Your role',
            '2',
        ]);

        fireEvent.click(screen.getByRole('tab', { name: 'All 19' }));
        expect(screen.getByText('19 live')).toBeVisible();
        expect(
            within(screen.getByRole('table', { name: 'Lessons' })).getAllByRole(
                'row',
            ),
        ).toHaveLength(20);
    });
});
