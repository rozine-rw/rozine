import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorFile from '@/pages/auditor/file';
import type { AuditorFileProps } from '@/types/auditor';
import reassignedFixture from '../../../resources/fixtures/ui/auditor-file-reassigned.json';
import fileFixture from '../../../resources/fixtures/ui/auditor-file.json';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

const props = (fixture: { props: unknown } = fileFixture) =>
    structuredClone(fixture.props) as AuditorFileProps;

beforeEach(() => inertia.reset());

describe('Auditor business file', () => {
    it('previews an offered file read-only, over Jobs, with the clock not yet started', () => {
        render(<AuditorFile {...props()} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Huye Motors · file',
        );

        const sheet = screen.getByRole('dialog', {
            name: 'Huye Motors business file',
        });

        expect(
            within(sheet).getByText('Application preview'),
        ).toBeInTheDocument();
        expect(within(sheet).getByRole('timer')).toHaveTextContent('24:00:00');
        expect(within(sheet).getByText('Gasabo · 11.2km')).toBeInTheDocument();
        expect(within(sheet).getByText('RWF 51.2M')).toBeInTheDocument();
        expect(within(sheet).getByText('6 months')).toBeInTheDocument();
        expect(within(sheet).getByText('10.0% total')).toBeInTheDocument();
        expect(
            within(sheet).queryByText(/rate \/ yr/i),
        ).not.toBeInTheDocument();
        expect(within(sheet).getByText(/· Logistics/)).toBeInTheDocument();
        expect(within(sheet).getAllByText('✓ OCR')).toHaveLength(2);
        expect(within(sheet).getAllByText('✓ Verified')).toHaveLength(2);
        expect(within(sheet).getAllByText('Met')).toHaveLength(3);
        expect(within(sheet).getByText('Flag')).toBeInTheDocument();
        expect(within(sheet).getByText('First visit')).toBeInTheDocument();
        expect(
            within(sheet).getByText('No flags on file.'),
        ).toBeInTheDocument();
        expect(within(sheet).getAllByRole('listitem').length).toBeGreaterThan(
            10,
        );
        expect(
            screen.getAllByRole('link', { name: 'Close' })[0],
        ).toHaveAttribute('href', '/preview/auditor-jobs');
        expect(
            screen.getAllByRole('navigation', { name: 'App navigation' }),
        ).toHaveLength(1);
    });

    it('accepts the offered file and offers decline and conflict', async () => {
        const { user } = renderWithUser(<AuditorFile {...props()} />);
        const sheet = screen.getByRole('dialog', {
            name: 'Huye Motors business file',
        });

        await user.click(
            within(sheet).getByRole('button', {
                name: 'Accept & start 24h clock',
            }),
        );
        expect(inertia.posts[0].url).toBe('/preview/auditor-audit-review');

        await user.click(
            within(sheet).getByRole('button', { name: 'Decline' }),
        );
        expect(
            screen.getByRole('dialog', { name: 'Decline Huye Motors' }),
        ).toBeInTheDocument();
    });

    it('shows a reassigned file with its history and continues the procedure', async () => {
        const { user } = renderWithUser(
            <AuditorFile {...props(reassignedFixture)} />,
        );
        const sheet = screen.getByRole('dialog', {
            name: 'Sebeya Logistics business file',
        });

        expect(screen.getByText('Business file')).toBeInTheDocument();
        expect(screen.getByRole('note')).toHaveTextContent(
            'Reassigned to you from Chantal Rwema, CPA',
        );
        expect(
            screen.getByText(
                'Last audit 14 Apr 2026 · Flash Audit · Chantal Rwema, CPA',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText('August 2026 statements uploaded after the 3rd'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Continue the audit' }),
        ).toHaveAttribute('href', '/preview/auditor-audit-ledger');
        expect(
            within(sheet).queryByRole('button', { name: 'Decline' }),
        ).not.toBeInTheDocument();

        await user.click(
            within(sheet).getByRole('button', { name: 'Declare a conflict' }),
        );
        expect(
            screen.getByRole('dialog', {
                name: 'Declare an interest in Sebeya Logistics',
            }),
        ).toBeInTheDocument();
    });

    it('reads a monthly last audit, a missing document and no procedure link', () => {
        const base = props(reassignedFixture);

        render(
            <AuditorFile
                {...base}
                links={{ ...base.links, procedure: null }}
                file={{
                    ...base.file,
                    documents: [
                        { ...base.file.documents[0], status: 'missing' },
                    ],
                    history: {
                        ...base.file.history,
                        last_audit: {
                            ...base.file.history.last_audit!,
                            kind: 'monthly',
                        },
                    },
                }}
            />,
        );

        expect(screen.getByText('Missing')).toBeInTheDocument();
        expect(
            screen.getByText(/Monthly report · Chantal Rwema, CPA/),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Continue the audit' }),
        ).not.toBeInTheDocument();
    });
});
