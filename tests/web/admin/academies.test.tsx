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
    it('lists the Investor Academy lessons by track with their topics, and counts them', () => {
        render(<AdminPendingSection {...props()} />);

        const region = screen.getByRole('region', { name: 'Academies' });

        expect(region).toHaveTextContent('Lessons live8');
        expect(region).toHaveTextContent('Tracks4');
        expect(region).toHaveTextContent('Quick checks16');
        expect(region).toHaveTextContent(
            'Lessons completed—Kept on each learner’s device',
        );
        expect(
            screen.getByRole('tab', { name: 'Investor Academy' }),
        ).toHaveAttribute('aria-selected', 'true');

        const rows = within(
            screen.getByRole('table', { name: 'Lessons' }),
        ).getAllByRole('row');

        expect(rows).toHaveLength(9);
        expect(cells(rows[1] as HTMLElement)).toEqual([
            '📄Rozine notes',
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

    it('keeps the Business and Auditor academies empty and shows every lesson under All', () => {
        render(<AdminPendingSection {...props()} />);

        fireEvent.click(screen.getByRole('tab', { name: 'Business Academy' }));
        expect(screen.getByText('No lessons to show yet.')).toBeVisible();

        fireEvent.click(screen.getByRole('tab', { name: 'Auditor Academy' }));
        expect(screen.getByText('No lessons to show yet.')).toBeVisible();

        fireEvent.click(screen.getByRole('tab', { name: 'All' }));
        expect(
            within(screen.getByRole('table', { name: 'Lessons' })).getAllByRole(
                'row',
            ),
        ).toHaveLength(9);
    });
});
