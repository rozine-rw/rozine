import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorPortfolio from '@/pages/auditor/portfolio';
import type { AuditorPortfolioProps } from '@/types/auditor';
import conflictFixture from '../../../resources/fixtures/ui/auditor-portfolio-conflict.json';
import emptyFixture from '../../../resources/fixtures/ui/auditor-portfolio-empty.json';
import lateFixture from '../../../resources/fixtures/ui/auditor-portfolio-late.json';
import scopedFixture from '../../../resources/fixtures/ui/auditor-portfolio-scoped.json';
import portfolioFixture from '../../../resources/fixtures/ui/auditor-portfolio.json';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

const props = (fixture: { props: unknown } = portfolioFixture) =>
    structuredClone(fixture.props) as AuditorPortfolioProps;

beforeEach(() => inertia.reset());

describe('Auditor Portfolio', () => {
    it('lists every filed report with its co-signature state', () => {
        render(<AuditorPortfolio {...props()} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Portfolio');
        expect(
            screen.getByRole('heading', { name: 'Portfolio' }),
        ).toBeInTheDocument();
        expect(screen.getByText('5 total')).toBeInTheDocument();

        const filters = screen.getByRole('navigation', {
            name: 'Filter reports',
        });

        expect(
            within(filters).getByRole('link', { name: /^All/ }),
        ).toHaveAttribute('aria-current', 'true');
        expect(
            within(filters).getByRole('link', { name: /^Late/ }),
        ).toHaveAttribute('href', '/preview/auditor-portfolio-late');

        expect(
            screen.getByRole('link', { name: /Kivu Coffee Roasters/ }),
        ).toHaveTextContent('Published');
        expect(
            screen.getByRole('link', { name: /Huye Motors/ }),
        ).toHaveTextContent('Flash Audit');
        expect(
            screen.getByText('August 2026 report · filed 2 days late'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/cash count sheet attached is for July/),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Start a linked amendment →' }),
        ).toHaveAttribute('href', '/preview/auditor-audit-count');
        expect(screen.getAllByText('Rubavu')).toHaveLength(1);
        expect(screen.getByText('2 Oct 2026')).toBeInTheDocument();
        expect(screen.getAllByText('OCT', { selector: 'span' })).toHaveLength(
            2,
        );
        expect(screen.getAllByText('SEP', { selector: 'span' })).toHaveLength(
            3,
        );
        expect(screen.getAllByText('Awaiting co-sign')).toHaveLength(2);
    });

    it('filters on the server and offers a way back when nothing matches', () => {
        const late = props(lateFixture);

        render(<AuditorPortfolio {...late} reports={[]} />);

        expect(
            screen.getByText('No reports match this filter.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Show all reports' }),
        ).toHaveAttribute('href', '/preview/auditor-portfolio');
    });

    it('explains an empty filter even without an all-reports link', () => {
        const late = props(lateFixture);

        render(
            <AuditorPortfolio
                {...late}
                reports={[]}
                filters={late.filters.filter((item) => item.key !== 'all')}
            />,
        );

        expect(
            screen.getByText(
                'Nothing filed yet. Reports you seal appear here with their co-signature status.',
            ),
        ).toBeInTheDocument();
    });

    it('explains a first month with nothing filed and nothing assigned', () => {
        render(<AuditorPortfolio {...props(emptyFixture)} />);

        expect(
            screen.getByText(
                'Nothing filed yet. Reports you seal appear here with their co-signature status.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('navigation', { name: 'Filter reports' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText('You have no files assigned to verify right now.'),
        ).toBeInTheDocument();
        expect(screen.queryByText('On the record')).not.toBeInTheDocument();
    });

    it('declares an interest on an assigned file after confirming its kind', async () => {
        const { user } = renderWithUser(<AuditorPortfolio {...props()} />);

        expect(
            screen.getByText('Declare on any of your 3 assigned files'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Rubavu Foods · RNP-2026-0097'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Close family or business tie · 12 Aug 2026'),
        ).toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'GreenLeaf Agro' }),
        );
        const sheet = screen.getByRole('dialog', {
            name: 'Declare an interest in GreenLeaf Agro',
        });

        await user.click(within(sheet).getByRole('radio', { name: 'Other' }));
        await user.click(
            within(sheet).getByLabelText('Factual explanation (required)'),
        );
        await user.paste('My cousin keeps their books.');
        await user.click(
            within(sheet).getByRole('button', { name: 'Declare interest' }),
        );

        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/auditor-portfolio-conflict',
            body: {
                assignment_id: 'mr_greenleaf',
                expected_revision: 5,
                kind: 'other',
                note: 'My cousin keeps their books.',
                identity_context_revision: 3,
            },
        });

        await user.click(within(sheet).getByRole('button', { name: 'Cancel' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('lists assigned files without a way to declare when no file allows it', () => {
        const base = props();

        render(
            <AuditorPortfolio
                {...base}
                conflicts={{
                    ...base.conflicts,
                    files: base.conflicts.files.map((file) => ({
                        ...file,
                        allowed_actions: [],
                    })),
                }}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'GreenLeaf Agro' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText('Rubavu Foods · RNP-2026-0097'),
        ).toBeInTheDocument();
    });

    it('offers a declaration only on the files that allow one', () => {
        render(<AuditorPortfolio {...props(scopedFixture)} />);

        expect(
            screen.getByText('Declare on any of your 2 assigned files'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'GreenLeaf Agro' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Huye Motors' }),
        ).toBeInTheDocument();
    });

    it('uses the singular label for one assigned file and reports the outcome', () => {
        const base = props(conflictFixture);

        render(
            <AuditorPortfolio
                {...base}
                conflicts={{
                    ...base.conflicts,
                    files: base.conflicts.files.slice(0, 1),
                }}
            />,
        );

        expect(
            screen.getByText('Declare on your 1 assigned file'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('alertdialog', { name: 'Interest declared' }),
        ).toBeInTheDocument();
    });
});
