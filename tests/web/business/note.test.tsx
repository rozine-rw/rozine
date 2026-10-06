import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import BusinessNote from '@/pages/business/note';
import type { BusinessNoteProps, NoteProgress } from '@/types/business';
import completedFixture from '../../../resources/fixtures/ui/business-note-completed.json';
import repayingFixture from '../../../resources/fixtures/ui/business-note-repaying.json';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({
        href,
        children,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
        <a href={href.url} {...props}>
            {children}
        </a>
    ),
}));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessNoteProps;
const link = (url: string) => ({ url, method: 'get' as const });
const money = (amount: number) => ({
    currency: 'RWF' as const,
    amount: String(amount),
});

const noteSheet = (page: BusinessNoteProps) =>
    screen.getByRole('dialog', { name: page.note.title });

/**
 * The Phase 1B raise phases keep their shape, but their previews moved to the C3 campaign page
 * (business-campaign-*); they are drawn here from the repaying note with the phase swapped in.
 */
const withProgress = (
    progress: NoteProgress,
    status: BusinessNoteProps['note']['status'],
): BusinessNoteProps => {
    const page = props(repayingFixture);

    page.note.title = 'Cold-Chain Hub';
    page.note.status = status;
    page.note.progress = progress;

    return page;
};

const raising = () =>
    withProgress(
        {
            phase: 'raising',
            raised: money(11900000),
            target: money(18000000),
            remaining: money(6100000),
            funded_pct: 66,
            investors: 284,
            closes_on: '2026-10-02',
            days_left: 9,
        },
        'active',
    );

describe('Note dashboard frame', () => {
    it('opens over Home and leads back to it', () => {
        const page = props(repayingFixture);

        render(<BusinessNote {...page} />);
        const sheet = within(noteSheet(page));

        expect(
            sheet.getByRole('heading', { name: 'Fleet Expansion' }),
        ).toBeInTheDocument();
        expect(sheet.getByText('Repaying')).toBeInTheDocument();
        expect(sheet.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/business-home',
        );
        expect(
            screen.getByRole('link', { name: 'Back to Home' }),
        ).toHaveAttribute('href', '/preview/business-home');
    });
});

describe('A repaying note', () => {
    it('shows what is owed, the tracker and the next payment', () => {
        const page = props(repayingFixture);

        render(<BusinessNote {...page} />);
        const sheet = within(noteSheet(page));

        expect(sheet.getByText('RWF 11.1M')).toBeInTheDocument();
        expect(sheet.getByText('3 / 5')).toBeInTheDocument();
        expect(sheet.getByText('647')).toBeInTheDocument();
        expect(sheet.getByText('On time')).toBeInTheDocument();
        expect(
            sheet.getByRole('progressbar', { name: 'Repayment tracker' }),
        ).toHaveValue(60);
        expect(sheet.getByText('60% repaid')).toBeInTheDocument();
        expect(sheet.getByText('RWF 16,650,000')).toBeInTheDocument();
        expect(sheet.getByText('over 2 months')).toBeInTheDocument();
        expect(sheet.getByText('RWF 5,550,000')).toBeInTheDocument();
        expect(
            sheet.getByText('Due 5 Oct 2026 · in 12 days'),
        ).toBeInTheDocument();
        expect(sheet.getByRole('link', { name: 'Pay' })).toHaveAttribute(
            'href',
            '/preview/business-repayments',
        );
        expect(
            sheet.queryByRole('link', { name: /View all/ }),
        ).not.toBeInTheDocument();
    });

    it('offers Pay once that screen is open', () => {
        const page = props(repayingFixture);
        const progress = page.note.progress as Extract<
            NoteProgress,
            { phase: 'repaying' }
        >;

        progress.health = 'late';
        progress.remaining_months = 1;
        progress.next_payment = {
            amount: money(5550000),
            due_on: '2026-09-24',
            days_until: 1,
        };
        page.links.pay = link('/business/notes/RNP-2024-0042/repay');

        render(<BusinessNote {...page} />);
        const sheet = within(noteSheet(page));

        expect(sheet.getByText('Late')).toBeInTheDocument();
        expect(sheet.getByText('over 1 month')).toBeInTheDocument();
        expect(
            sheet.getByText('Due 24 Sept 2026 · in 1 day'),
        ).toBeInTheDocument();
        expect(sheet.getByRole('link', { name: 'Pay' })).toHaveAttribute(
            'href',
            '/business/notes/RNP-2024-0042/repay',
        );
    });

    it('marks a completed note as fully repaid with nothing left to pay', () => {
        const page = props(completedFixture);

        render(<BusinessNote {...page} />);
        const sheet = within(noteSheet(page));

        expect(sheet.getByText('4 / 4')).toBeInTheDocument();
        expect(sheet.getByText('Fully repaid')).toBeInTheDocument();
        expect(sheet.queryByText('Upcoming payment')).not.toBeInTheDocument();
    });
});

describe('A note still raising', () => {
    it('shows the raise so far and when the listing closes', () => {
        const page = raising();

        render(<BusinessNote {...page} />);
        const sheet = within(noteSheet(page));

        expect(sheet.getByText('RWF 11.9M')).toBeInTheDocument();
        expect(sheet.getByText('66%')).toBeInTheDocument();
        expect(sheet.getByText('284')).toBeInTheDocument();
        expect(sheet.getByText('9 days')).toBeInTheDocument();
        expect(
            sheet.getByRole('progressbar', { name: 'Funding tracker' }),
        ).toHaveValue(66);
        expect(sheet.getByText('66% funded')).toBeInTheDocument();
        expect(sheet.getByText('RWF 6,100,000')).toBeInTheDocument();
        expect(sheet.getByText('closes 2 Oct 2026')).toBeInTheDocument();
    });

    it('counts down the last day', () => {
        const page = raising();

        (
            page.note.progress as Extract<NoteProgress, { phase: 'raising' }>
        ).days_left = 1;

        render(<BusinessNote {...page} />);

        expect(within(noteSheet(page)).getByText('1 day')).toBeInTheDocument();
    });
});

describe('A note that has stopped raising', () => {
    it('explains a fully funded note is waiting for disbursement', () => {
        const page = withProgress(
            {
                phase: 'funded',
                raised: money(18000000),
                investors: 402,
                funded_on: '2026-09-20',
            },
            'funded',
        );

        page.note.performance = null;

        render(<BusinessNote {...page} />);
        const sheet = within(noteSheet(page));

        expect(sheet.getByText('RWF 18M')).toBeInTheDocument();
        expect(sheet.getByText('402')).toBeInTheDocument();
        expect(sheet.getByRole('status')).toHaveTextContent(
            'Fully funded on 20 Sept 2026. Rozine is preparing your disbursement',
        );
        expect(sheet.queryByText('Performance trend')).not.toBeInTheDocument();
    });

    it('explains an expired listing refunded every investor', () => {
        const page = withProgress(
            {
                phase: 'expired',
                raised: money(11900000),
                target: money(18000000),
                investors: 284,
                closed_on: '2026-09-20',
            },
            'failed',
        );

        page.note.photos = [];

        render(<BusinessNote {...page} />);
        const sheet = within(noteSheet(page));

        expect(sheet.getByText('Failed')).toBeInTheDocument();
        expect(sheet.getByRole('status')).toHaveTextContent(
            'This raise closed on 20 Sept 2026 before reaching RWF 18,000,000. Every investor was refunded in full, without fee.',
        );
        expect(sheet.queryByText('Photos')).not.toBeInTheDocument();
        expect(sheet.queryByText('Recent investors')).not.toBeInTheDocument();
    });
});

describe('Note photos', () => {
    it('opens a photo and steps through the set by button and key', async () => {
        const user = userEvent.setup();
        const page = raising();

        page.note.photos[1].url = '/storage/notes/operations.jpg';
        render(<BusinessNote {...page} />);
        const sheet = within(noteSheet(page));

        await user.click(
            sheet.getByRole('button', { name: 'Fleet & facility · Musanze' }),
        );

        const viewer = () =>
            screen.getByRole('dialog', { name: /·|Operations|Team/ });

        expect(viewer()).toHaveAccessibleName('Fleet & facility · Musanze');

        await user.click(screen.getByRole('button', { name: 'Next photo' }));

        expect(viewer()).toHaveAccessibleName('Operations');
        expect(
            within(viewer()).getByRole('img', { name: 'Operations' }),
        ).toHaveAttribute('src', '/storage/notes/operations.jpg');

        await user.click(
            screen.getByRole('button', { name: 'Previous photo' }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Previous photo' }),
        );

        expect(viewer()).toHaveAccessibleName('Team & leadership');

        await user.keyboard('{ArrowRight}');

        expect(viewer()).toHaveAccessibleName('Fleet & facility · Musanze');

        await user.keyboard('{ArrowLeft}');
        await user.keyboard('a');

        expect(viewer()).toHaveAccessibleName('Team & leadership');

        await user.keyboard('{Escape}');

        expect(
            screen.queryByRole('button', { name: 'Next photo' }),
        ).not.toBeInTheDocument();

        await user.click(sheet.getByRole('button', { name: 'Operations' }));
        await user.click(screen.getByRole('button', { name: 'Close photo' }));

        expect(
            screen.queryByRole('button', { name: 'Close photo' }),
        ).not.toBeInTheDocument();
    });
});

describe('Performance trend', () => {
    it('shows six months, then twelve, and inspects a month', async () => {
        const user = userEvent.setup();
        const page = raising();

        render(<BusinessNote {...page} />);
        const sheet = within(noteSheet(page));

        expect(sheet.getByText('Performance trend')).toBeInTheDocument();
        expect(sheet.getByRole('button', { name: '6M' })).toHaveAttribute(
            'aria-pressed',
            'true',
        );
        expect(sheet.getAllByRole('button', { name: /2026$/ })).toHaveLength(6);
        expect(sheet.getByText('RWF 212M')).toBeInTheDocument();
        expect(sheet.getByText('RWF 178M')).toBeInTheDocument();
        expect(
            sheet.getByText('Tap a bar to inspect that month'),
        ).toBeInTheDocument();

        await user.click(sheet.getByRole('button', { name: 'Aug 2026' }));

        expect(
            sheet.getByText('Aug 2026 · RWF 212M revenue · payment on time'),
        ).toBeInTheDocument();

        await user.click(sheet.getByRole('button', { name: 'Aug 2026' }));

        expect(
            sheet.getByText('Tap a bar to inspect that month'),
        ).toBeInTheDocument();

        await user.click(sheet.getByRole('button', { name: '12M' }));

        expect(
            sheet.getAllByRole('button', { name: /20(25|26)$/ }),
        ).toHaveLength(12);
        expect(sheet.getByText('RWF 149M')).toBeInTheDocument();

        await user.click(sheet.getByRole('button', { name: 'Nov 2025' }));

        expect(
            sheet.getByText('Nov 2025 · RWF 149M revenue · payment late'),
        ).toBeInTheDocument();
    });

    it('draws a flat period without dividing by nothing', () => {
        const page = raising();
        const flat = {
            month: '2026-08-01',
            revenue: money(150000000),
            paid_on_time: true,
        };

        page.note.performance = {
            six_months: {
                months: [flat],
                high: money(150000000),
                low: money(150000000),
            },
            twelve_months: {
                months: [flat],
                high: money(150000000),
                low: money(150000000),
            },
        };

        render(<BusinessNote {...page} />);
        const sheet = within(noteSheet(page));

        expect(sheet.getAllByText('RWF 150M')).toHaveLength(2);
    });
});

describe('Investor identities (H16)', () => {
    it('never names, types or sizes an Investor, even if a record arrives', () => {
        const page = props(repayingFixture);

        expect(page.note.recent_investors).toEqual([]);

        page.note.recent_investors = [
            {
                initials: 'JK',
                name: 'Jean Kamanzi',
                kind: 'individual',
                amount: money(500000),
            },
            {
                initials: 'PF',
                name: 'Pension Fund RW',
                kind: 'institution',
                amount: money(5000000),
            },
        ];
        page.links.investors = link('/business/notes/RNP-2024-0042/investors');

        render(<BusinessNote {...page} />);
        const sheet = noteSheet(page);

        for (const text of [
            'Jean Kamanzi',
            'Pension Fund RW',
            'Institution',
            'Individual',
            'RWF 500,000',
            'Recent investors',
        ]) {
            expect(sheet).not.toHaveTextContent(text);
        }

        expect(
            within(sheet).queryByRole('link', { name: /View all/ }),
        ).not.toBeInTheDocument();
    });
});
