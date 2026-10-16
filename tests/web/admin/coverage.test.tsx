import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminCoverage from '@/pages/admin/coverage';
import type { AdminCoverageProps } from '@/types/admin';
import coverageFixture from '../../../resources/fixtures/ui/admin-coverage.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminCoverageProps;

const rowOf = (district: string) => {
    const [row] = screen
        .getAllByRole('row')
        .filter((candidate) => within(candidate).queryByText(district));

    return row;
};

const nav = () =>
    screen.getByRole('navigation', { name: 'Console navigation' });

beforeEach(resetInertia);

describe('Partner coverage', () => {
    it('lists every district with the server’s counts and coverage verdict', () => {
        const page = props(coverageFixture);

        render(<AdminCoverage {...page} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Partner coverage',
        );
        expect(
            within(nav()).getByRole('link', { name: 'Auditors' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(nav()).queryByRole('link', { name: 'Partner coverage' }),
        ).not.toBeInTheDocument();

        /* The tiles are the server's, and they agree with its rows. */
        expect(
            screen.getByText('Districts', { selector: 'span' }),
        ).toBeInTheDocument();
        expect(screen.getByText('30')).toBeInTheDocument();
        expect(screen.getByText('19')).toBeInTheDocument();
        expect(screen.getByText('25')).toBeInTheDocument();
        expect(page.districts).toHaveLength(30);
        expect(
            page.districts.filter((row) => row.capacity === 'uncovered'),
        ).toHaveLength(19);
        expect(
            page.districts.reduce((sum, row) => sum + row.audits_open, 0),
        ).toBe(25);

        const gasabo = rowOf('Gasabo');

        expect(gasabo).toHaveTextContent('Kigali City');
        expect(gasabo).toHaveTextContent('Covered');
        expect(
            screen.getByRole('link', { name: 'Audit Partners in Gasabo' }),
        ).toHaveAttribute('href', '/preview/admin-auditors');

        const rubavu = rowOf('Rubavu');

        expect(rubavu).toHaveTextContent('Western Province');
        expect(rubavu).toHaveTextContent(/^Rubavu.*Uncovered02—$/u);
        expect(
            screen.queryByRole('link', { name: 'Audit Partners in Rubavu' }),
        ).not.toBeInTheDocument();

        expect(rowOf('Musanze')).toHaveTextContent(/Covered13/u);
        expect(
            screen.getByRole('link', { name: 'Audit Partners in Musanze' }),
        ).toBeInTheDocument();
        expect(
            screen.queryAllByRole('button', {
                name: /dispatch|assign|suspend/i,
            }),
        ).toEqual([]);
    });

    it('shows the server’s verdict even where a partner is active', () => {
        const page = props(coverageFixture);
        const musanze = page.districts.find(
            (district) => district.district === 'Musanze',
        );

        musanze!.capacity = 'uncovered';
        render(<AdminCoverage {...page} />);

        expect(rowOf('Musanze')).toHaveTextContent(/Uncovered13/u);
        expect(
            screen.getByRole('link', { name: 'Audit Partners in Musanze' }),
        ).toBeInTheDocument();
    });

    it('tells a search that matched no district apart from none reported', () => {
        const page = props(coverageFixture);

        page.districts = [];
        page.stats = [];
        page.search = 'Kivu';
        const { rerender } = render(<AdminCoverage {...page} />);

        expect(screen.getByText('No districts reported')).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(/Kivu/);
        expect(screen.queryByText('Uncovered')).not.toBeInTheDocument();

        rerender(<AdminCoverage {...page} search="" />);

        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    it('renders when the server sends no legacy links or queue sizes', () => {
        const fixture = props(coverageFixture);

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
        render(<AdminCoverage {...fixture} />);

        expect(
            within(nav()).queryByRole('link', { name: 'Auditors' }),
        ).not.toBeInTheDocument();
        expect(
            within(nav()).queryByRole('link', { current: 'page' }),
        ).not.toBeInTheDocument();
    });
});
