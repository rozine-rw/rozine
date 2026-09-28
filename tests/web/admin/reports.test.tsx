import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminReports from '@/pages/admin/reports';
import type { AdminReportsProps } from '@/types/admin';
import incompleteFixture from '../../../resources/fixtures/ui/admin-reports-incomplete.json';
import reportsFixture from '../../../resources/fixtures/ui/admin-reports.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminReportsProps;

const rowOf = (text: string) => {
    const [row] = screen
        .getAllByRole('row')
        .filter((candidate) => within(candidate).queryByText(text));

    return row;
};

const nav = () =>
    screen.getByRole('navigation', { name: 'Console navigation' });

beforeEach(resetInertia);

describe('Reports', () => {
    it('lists each pack for its period, final or as a snapshot with what is pending', () => {
        render(<AdminReports {...props(reportsFixture)} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Reports');
        expect(
            within(nav()).getByRole('link', { name: 'Reports' }),
        ).toHaveAttribute('aria-current', 'page');

        const regulator = rowOf('Regulator pack · Q4 2026');

        expect(regulator).toHaveTextContent('Regulator');
        expect(regulator).toHaveTextContent('1 Oct 2026 – 31 Dec 2026');
        expect(regulator).toHaveTextContent('Complete');
        expect(regulator).toHaveTextContent('6 Jan 2027 · 09:00');
        expect(regulator).not.toHaveTextContent('Snapshot');
        expect(
            screen.getByRole('link', {
                name: 'Download Regulator pack · Q4 2026',
            }),
        ).toHaveTextContent(/^Download$/);

        expect(rowOf('Board pack · December 2026')).toHaveTextContent('Board');

        const moving = rowOf('Ledger export · 16–22 Jan 2027');

        expect(moving).toHaveTextContent('Export');
        expect(moving).toHaveTextContent('16 Jan 2027 – 22 Jan 2027');
        expect(moving).toHaveTextContent('Figures moving');
        expect(moving).toHaveTextContent('23 Jan 2027 · 06:00');
        expect(moving).toHaveTextContent('Snapshot');
        expect(within(moving).getByRole('note')).toHaveTextContent(
            'Pending: The 22 Jan 2027 day close has an open break of RWF 3,100 (STM-2027-0122).',
        );

        const download = screen.getByRole('link', {
            name: 'Download Ledger export · 16–22 Jan 2027',
        });

        expect(download).toHaveTextContent('Download snapshot');
        expect(download).toHaveAttribute('href', '/preview/admin-reports');
        expect(
            screen.queryAllByRole('button', {
                name: /generate|publish|send|export/iu,
            }),
        ).toEqual([]);
    });

    it('offers nothing to download for a period that has not closed', () => {
        render(<AdminReports {...props(incompleteFixture)} />);

        const board = rowOf('Board pack · January 2027');

        expect(board).toHaveTextContent('1 Jan 2027 – 31 Jan 2027');
        expect(board).toHaveTextContent('Period not closed');
        expect(board).toHaveTextContent('—');
        expect(board).toHaveTextContent('When the period closes');
        expect(
            screen.queryByRole('link', { name: /^Download/u }),
        ).not.toBeInTheDocument();
        expect(screen.queryByRole('note')).not.toBeInTheDocument();
    });

    it('tells a search that matched no pack apart from none yet', () => {
        const page = props(incompleteFixture);

        page.packs = [];
        page.search = 'Kivu';
        const { rerender } = render(<AdminReports {...page} />);

        expect(screen.getByText('No packs yet')).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(/Kivu/);

        rerender(<AdminReports {...page} search="" />);

        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    it('renders when the server sends no legacy links or queue sizes', () => {
        const fixture = props(reportsFixture);

        fixture.nav = {
            ...fixture.nav,
            today: null,
            applications: null,
            disbursements: null,
            repayments: null,
            businesses: null,
            investors: null,
            auditors: null,
            staff: null,
            ledger: null,
            events: null,
            book: null,
            exceptions: null,
        };
        fixture.badges = { applications: null, disbursements: null };
        render(<AdminReports {...fixture} />);

        expect(
            within(nav()).getByRole('link', { name: 'Reports' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(nav()).queryByRole('link', { name: /Applications/u }),
        ).not.toBeInTheDocument();
        expect(
            within(nav()).queryByRole('link', { name: 'Activity & Audit' }),
        ).not.toBeInTheDocument();
    });
});
