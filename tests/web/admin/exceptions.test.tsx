import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminExceptions from '@/pages/admin/exceptions';
import type { AdminExceptionsProps } from '@/types/admin';
import emptyFixture from '../../../resources/fixtures/ui/admin-exceptions-empty.json';
import exceptionsFixture from '../../../resources/fixtures/ui/admin-exceptions.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminExceptionsProps;

const rowOf = (reference: string) => {
    const [row] = screen
        .getAllByRole('row')
        .filter((candidate) => within(candidate).queryByText(reference));

    return row;
};

const nav = () =>
    screen.getByRole('navigation', { name: 'Console navigation' });

beforeEach(resetInertia);

describe('Exceptions', () => {
    it('lists every open arrears, halt and variance with its age, owner and record, read-only', () => {
        render(<AdminExceptions {...props(exceptionsFixture)} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Exceptions');
        expect(
            within(nav()).getByRole('link', { name: 'Exceptions' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(nav()).getByRole('link', { name: 'Book' }),
        ).not.toHaveAttribute('aria-current');
        expect(screen.getByText('5 open')).toBeInTheDocument();
        expect(screen.getByText('2 unassigned')).toBeInTheDocument();

        const filter = screen.getByRole('navigation', {
            name: 'Filter by kind',
        });

        expect(
            within(filter).getByRole('link', { name: /^All\s*5$/ }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(filter).getByRole('link', { name: /^Arrears\s*3$/ }),
        ).toBeInTheDocument();

        expect(rowOf('ARR-2026-0412')).toHaveTextContent('Arrears');
        expect(rowOf('ARR-2026-0412')).toHaveTextContent(
            'Ubuki Crafts · Loom Expansion',
        );
        expect(rowOf('ARR-2026-0412')).toHaveTextContent('38 DPD');
        expect(rowOf('ARR-2026-0412')).toHaveTextContent('RWF 2,340,000');
        expect(rowOf('ARR-2026-0412')).toHaveTextContent('Open 37 days');
        expect(rowOf('ARR-2026-0412')).toHaveTextContent('Owner: Grace Kalisa');

        /* The Book's overdue GreenLeaf note, its payment still being requeried. */
        const unconfirmed = rowOf('ARR-2027-0433');

        expect(unconfirmed).toHaveTextContent(
            'GreenLeaf Agro · Warehouse Robotics',
        );
        expect(unconfirmed).toHaveTextContent('2 DPD');
        expect(unconfirmed).toHaveTextContent('RWF 4,833,334');
        expect(unconfirmed).toHaveTextContent('Open 1 day');
        expect(unconfirmed).toHaveTextContent('Unassigned');

        const variance = rowOf('RPY-2026-0303');

        expect(variance).toHaveTextContent('Variance');
        expect(variance).toHaveTextContent('RWF 115,000');
        expect(variance).toHaveTextContent('Open 0 days');
        expect(variance).toHaveTextContent('Unassigned');
        expect(variance).not.toHaveTextContent('DPD');
        expect(
            screen.getByRole('link', { name: 'Open RPY-2026-0303' }),
        ).toHaveAttribute(
            'href',
            '/preview/admin-repayments-exception-mismatch',
        );

        const halt = rowOf('FRZ-2026-0355');

        expect(halt).toHaveTextContent('Halt');
        expect(halt).toHaveTextContent('Rugali Freight');
        expect(halt).not.toHaveTextContent('Rugali Freight ·');
        expect(halt).toHaveTextContent('—');

        expect(
            screen.queryAllByRole('button', {
                name: /assign|escalate|halt|remedy|resolve/i,
            }),
        ).toEqual([]);
        expect(
            screen.queryByRole('link', { name: 'Older exceptions' }),
        ).not.toBeInTheDocument();
    });

    it('shows one day in the singular and offers a further page', () => {
        const page = props(exceptionsFixture);

        page.exceptions[0].age_days = 1;
        page.pagination = {
            next: { url: '/preview/admin-exceptions', method: 'get' },
        };
        render(<AdminExceptions {...page} />);

        expect(rowOf('ARR-2026-0412')).toHaveTextContent('Open 1 day');
        expect(
            screen.getByRole('link', { name: 'Older exceptions' }),
        ).toHaveAttribute('href', '/preview/admin-exceptions');
    });

    it('shows none open when nothing is open', () => {
        render(<AdminExceptions {...props(emptyFixture)} />);

        expect(screen.getByText('No open exceptions')).toBeInTheDocument();
        expect(screen.queryByText(/open$/)).not.toBeInTheDocument();
        expect(screen.queryByText(/unassigned/)).not.toBeInTheDocument();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    it('tells a filter or a search that matched nothing apart from none open', () => {
        const page = props(emptyFixture);

        page.chips = page.chips.map((chip) => ({
            ...chip,
            active: chip.key === 'halt',
        }));
        page.search = 'Kivu';
        const { rerender } = render(<AdminExceptions {...page} />);

        expect(
            screen.getByText('No exceptions of this kind'),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(/Kivu/);

        rerender(<AdminExceptions {...page} chips={[]} />);

        expect(screen.getByText('No open exceptions')).toBeInTheDocument();
        expect(
            screen.queryByRole('navigation', { name: 'Filter by kind' }),
        ).not.toBeInTheDocument();
    });
});
