import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminBook from '@/pages/admin/book';
import AdminRepayments from '@/pages/admin/repayments';
import type { AdminBookProps, AdminRepaymentsProps } from '@/types/admin';
import emptyFixture from '../../../resources/fixtures/ui/admin-book-empty.json';
import bookFixture from '../../../resources/fixtures/ui/admin-book.json';
import repaymentsFixture from '../../../resources/fixtures/ui/admin-repayments.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminBookProps;

const rowOf = (text: string) => {
    const [row] = screen
        .getAllByRole('row')
        .filter((candidate) => within(candidate).queryByText(text));

    return row;
};

const nav = () =>
    screen.getByRole('navigation', { name: 'Console navigation' });

beforeEach(resetInertia);

describe('Book', () => {
    it('lists every live note with its principal, next due, DPD and health, read-only', () => {
        render(<AdminBook {...props(bookFixture)} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Book');
        expect(
            within(nav()).getByRole('link', { name: 'Book' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(nav()).getByRole('link', { name: 'Exceptions' }),
        ).toHaveAttribute('href', '/preview/admin-exceptions');

        expect(
            screen.getByRole('heading', { name: 'Live notes' }),
        ).toBeInTheDocument();
        expect(screen.getByText('RWF 46.2M')).toBeInTheDocument();

        const filter = screen.getByRole('navigation', {
            name: 'Filter by servicing state',
        });

        expect(
            within(filter).getByRole('link', { name: /^All\s*6$/ }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(filter).getByRole('link', { name: /^Overdue\s*3$/ }),
        ).toBeInTheDocument();

        expect(rowOf('note_0301')).toHaveTextContent(
            'Umucyo Foods · Cold-Chain Hub',
        );
        expect(rowOf('note_0301')).toHaveTextContent('RWF 9M');
        expect(rowOf('note_0301')).toHaveTextContent('23 Feb 2027');
        expect(rowOf('note_0301')).toHaveTextContent('RWF 3.5M');
        expect(rowOf('note_0301')).toHaveTextContent('—');
        expect(rowOf('note_0301')).toHaveTextContent('Current');
        expect(rowOf('note_0302')).toHaveTextContent('Due today');
        expect(rowOf('note_0287')).toHaveTextContent('38');
        expect(rowOf('note_0287')).toHaveTextContent('Overdue');
        expect(
            screen.getByRole('link', { name: 'Open note_0303' }),
        ).toHaveAttribute(
            'href',
            '/preview/admin-repayments-exception-mismatch',
        );

        expect(
            screen.queryAllByRole('button', {
                name: /assign|escalate|halt|remedy|freeze|approve/i,
            }),
        ).toEqual([]);
        expect(
            screen.queryByRole('link', { name: 'More notes' }),
        ).not.toBeInTheDocument();
    });

    it('says when nothing is scheduled, and offers a further page', () => {
        const page = props(bookFixture);

        page.notes[0].next_due = null;
        page.pagination = {
            next: { url: '/preview/admin-book', method: 'get' },
        };
        render(<AdminBook {...page} />);

        expect(rowOf('note_0301')).toHaveTextContent('Nothing scheduled');
        expect(
            screen.getByRole('link', { name: 'More notes' }),
        ).toHaveAttribute('href', '/preview/admin-book');
    });

    it('shows none live when the book is empty', () => {
        render(<AdminBook {...props(emptyFixture)} />);

        expect(screen.getByText('No live notes')).toBeInTheDocument();
        expect(screen.getByText('RWF 0')).toBeInTheDocument();
        expect(
            screen.queryByText('No notes match this filter'),
        ).not.toBeInTheDocument();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    it('tells a filter or a search that matched nothing apart from an empty book', () => {
        const page = props(emptyFixture);

        page.chips = page.chips.map((chip) => ({
            ...chip,
            active: chip.key === 'overdue',
        }));
        page.search = 'Kivu';
        page.stats = [];
        const { rerender } = render(<AdminBook {...page} />);

        expect(
            screen.getByText('No notes match this filter'),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(/Kivu/);

        rerender(<AdminBook {...page} chips={[]} />);

        expect(screen.getByText('No live notes')).toBeInTheDocument();
        expect(
            screen.queryByRole('navigation', {
                name: 'Filter by servicing state',
            }),
        ).not.toBeInTheDocument();
    });
});

describe('The optional Phase 2 sidebar items', () => {
    it('show only when their link is sent', () => {
        const repayments = structuredClone(
            repaymentsFixture.props,
        ) as AdminRepaymentsProps;
        const { rerender } = render(<AdminRepayments {...repayments} />);

        expect(
            within(nav()).queryByRole('link', { name: 'Book' }),
        ).not.toBeInTheDocument();
        expect(
            within(nav()).queryByRole('link', { name: 'Exceptions' }),
        ).not.toBeInTheDocument();

        rerender(
            <AdminRepayments
                {...repayments}
                nav={{
                    ...repayments.nav,
                    book: { url: '/preview/admin-book', method: 'get' },
                    exceptions: null,
                }}
            />,
        );

        expect(
            within(nav()).getByRole('link', { name: 'Book' }),
        ).not.toHaveAttribute('aria-current');
        expect(
            within(nav()).queryByRole('link', { name: 'Exceptions' }),
        ).not.toBeInTheDocument();
    });
});
