import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminAuditReports from '@/pages/admin/audit-reports';
import type { AdminAuditReportsProps } from '@/types/admin';
import registerFixture from '../../../resources/fixtures/ui/admin-audit-reports.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = () =>
    structuredClone(registerFixture.props) as AdminAuditReportsProps;

const rowOf = (business: string) => {
    const [row] = screen
        .getAllByRole('row')
        .filter((candidate) => within(candidate).queryByText(business));

    return row;
};

beforeEach(resetInertia);

describe('Audit reports register', () => {
    it('shows each audit’s state, digest and weighted variance with the server’s tiles', () => {
        render(<AdminAuditReports {...props()} />);

        expect(
            within(
                screen.getByRole('navigation', { name: 'Console navigation' }),
            ).getByRole('link', { name: 'Reports' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(screen.getByText('67%')).toBeInTheDocument();

        const published = rowOf('Kigali Fresh Foods Ltd');
        expect(published).toHaveTextContent('Published');
        expect(published).toHaveTextContent('9f3c2a71be04');
        expect(published).toHaveTextContent('0.0%');
        expect(published).toHaveTextContent('ICPAR-2024-0117');

        const disputed = rowOf('Rubavu Fish Market Co.');
        expect(disputed).toHaveTextContent('Flash Audit');
        expect(disputed).toHaveTextContent('Disputed');
        expect(within(disputed).getByText('12.5%')).toHaveClass(
            'text-[#b3352a]',
        );
    });

    it('offers Remind only where the server allows it, and the sealed PDF', () => {
        render(<AdminAuditReports {...props()} />);

        const awaiting = rowOf('Musanze Agro Traders');
        expect(awaiting).toHaveTextContent('Awaiting co-sign');
        expect(
            within(awaiting).getByRole('button', { name: 'Remind' }),
        ).toHaveAttribute('data-method', 'post');
        expect(
            within(awaiting).getByRole('link', {
                name: 'Download the sealed report for Musanze Agro Traders (PDF)',
            }),
        ).toHaveAttribute('href', '/preview/admin-audit-reports');

        const overdue = rowOf('Huye Hardware Supplies');
        expect(overdue).toHaveTextContent('Overdue');
        expect(
            within(overdue).queryByRole('button', { name: 'Remind' }),
        ).not.toBeInTheDocument();
        expect(overdue).toHaveTextContent('Reminded');
        expect(within(overdue).queryByRole('link')).not.toBeInTheDocument();
    });

    it('says so when no audit matches the filter', () => {
        render(<AdminAuditReports {...props()} rows={[]} />);

        expect(screen.queryAllByRole('row')).toHaveLength(1);
        expect(screen.getByText('No audits in this view')).toBeInTheDocument();
    });
});
