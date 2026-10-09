import { render, screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { catalogFor } from '@/lib/i18n';
import { I18nContext } from '@/lib/i18n/context';
import AuditorPortfolio from '@/pages/auditor/portfolio';
import type { AuditorPortfolioProps } from '@/types/auditor';
import awaitingFixture from '../../../resources/fixtures/ui/auditor-portfolio-awaiting.json';
import conflictFixture from '../../../resources/fixtures/ui/auditor-portfolio-conflict.json';
import emptyFixture from '../../../resources/fixtures/ui/auditor-portfolio-empty.json';
import lateFixture from '../../../resources/fixtures/ui/auditor-portfolio-late.json';
import liveMinimalFixture from '../../../resources/fixtures/ui/auditor-portfolio-live-minimal.json';
import publishedFixture from '../../../resources/fixtures/ui/auditor-portfolio-published.json';
import rejectedFixture from '../../../resources/fixtures/ui/auditor-portfolio-rejected.json';
import scopedFixture from '../../../resources/fixtures/ui/auditor-portfolio-scoped.json';
import portfolioFixture from '../../../resources/fixtures/ui/auditor-portfolio.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, fails, inertia, operation } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

/** Opens the declaration on GreenLeaf Agro and sends it into a stale refusal. */
const declareStale = async (
    user: ReturnType<typeof renderWithUser>['user'],
) => {
    inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
    await user.click(screen.getByRole('button', { name: 'GreenLeaf Agro' }));

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
    await waitFor(() => expect(inertia.reloads).toEqual([undefined]));
};

/** The page after a refresh, with GreenLeaf Agro changed as the server now has it. */
const refreshed = (
    change: (
        file: AuditorPortfolioProps['conflicts']['files'][number],
    ) => AuditorPortfolioProps['conflicts']['files'][number] | null,
): AuditorPortfolioProps => {
    const base = props();

    return {
        ...base,
        conflicts: {
            ...base.conflicts,
            files: base.conflicts.files.flatMap((file) => {
                if (file.id !== 'mr_greenleaf') {
                    return [file];
                }

                const next = change(file);

                return next === null ? [] : [next];
            }),
        },
    };
};

const props = (fixture: { props: unknown } = portfolioFixture) =>
    structuredClone(fixture.props) as AuditorPortfolioProps;

beforeEach(() => inertia.reset());

describe('Auditor Portfolio', () => {
    it("lays out the design's Portfolio with an empty state wherever there is no read yet", () => {
        render(<AuditorPortfolio {...props()} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Portfolio');
        expect(
            screen.getByRole('heading', { name: 'Portfolio' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'You earn two ways: verification and monitoring on the files you grade, and origination commission on the deals you bring in.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /^Your CPA card/u }),
        ).toHaveAttribute('href', '/preview/auditor-profile');
        expect(screen.getByText('Proof you are ours')).toBeInTheDocument();
        expect(screen.queryByText('List a deal')).not.toBeInTheDocument();

        const sourced = screen.getByRole('region', {
            name: 'Deals you sourced',
        });

        expect(within(sourced).getByText('Origination')).toBeInTheDocument();
        expect(
            within(sourced).getByText(
                /earn origination commission from repayments/u,
            ),
        ).toBeInTheDocument();

        const earned = screen.getByRole('region', {
            name: 'Verification earned · this month',
        });

        expect(within(earned).getAllByText('—')).toHaveLength(3);
        expect(within(earned).getByText('Managed deals')).toBeInTheDocument();
        expect(within(earned).getByText('Next payout')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Yield-share payouts appear here once they are paid.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('region', { name: 'Managed deals' }),
        ).toHaveTextContent(
            "No managed deals yet. Pass a Flash Audit to become a business's account manager.",
        );
    });

    it('lists what falls due this month: owed verifications by due date and filed reports with their state', () => {
        render(<AuditorPortfolio {...props()} />);

        expect(screen.getByText('October 2026')).toBeInTheDocument();
        expect(
            screen.getByText('Every monthly verification you owe, and when.'),
        ).toBeInTheDocument();

        const due = screen.getByRole('region', { name: 'Due in October' });

        expect(within(due).getByText('3 deals')).toBeInTheDocument();
        expect(
            within(due)
                .getAllByRole('link')
                .map((link) => link.textContent),
        ).toEqual([
            '2OCTHuye MotorsFlash Audit · filed 1 OctAwaiting co-sign',
            '7OCTGreenLeaf AgroMonthly verificationIn 4 days',
            '7OCTKivu Coffee RoastersSeptember 2026 report · filed 2 OctPublished',
        ]);
        expect(
            within(due).getByRole('link', { name: /GreenLeaf Agro/u }),
        ).toHaveAttribute('href', '/preview/auditor-file');
        expect(
            within(due).getByRole('link', { name: /Kivu Coffee Roasters/u }),
        ).toHaveAttribute('href', '/preview/auditor-audit-sealed-monthly');
        expect(within(due).getAllByText('Your share')).toHaveLength(3);
        expect(within(due).getAllByText('—')).toHaveLength(3);
        expect(within(due).getByText('Rwamagana')).toBeInTheDocument();
        expect(within(due).getAllByText('7 Oct')).toHaveLength(2);

        expect(screen.getByRole('status')).toHaveTextContent(
            '1 verification due within 5 daysFile by the due date: GreenLeaf Agro, due 7 Oct.',
        );
        expect(
            screen.getByRole('button', { name: '3 Oct 2026' }),
        ).toBeDisabled();
        expect(
            screen.getByRole('button', {
                name: '7 Oct 2026, 2 verifications due',
            }),
        ).toBeEnabled();
        expect(
            screen.getByRole('button', {
                name: '2 Oct 2026, 1 verification due',
            }),
        ).toBeEnabled();
    });

    it('browses the calendar month by month, with the late and rejected reports of September', async () => {
        const { user } = renderWithUser(<AuditorPortfolio {...props()} />);

        await user.click(
            screen.getByRole('button', { name: 'Previous month' }),
        );

        const september = screen.getByRole('region', {
            name: 'Due in September',
        });

        expect(screen.getByText('September 2026')).toBeInTheDocument();
        expect(within(september).getByText('3 deals')).toBeInTheDocument();
        expect(
            within(september).getByText(
                'August 2026 report · filed 2 days late',
            ),
        ).toBeInTheDocument();
        expect(within(september).getByText('Late')).toBeInTheDocument();
        expect(within(september).getByText('Rejected')).toBeInTheDocument();
        expect(
            within(september).getByText(
                /cash count sheet attached is for July/u,
            ),
        ).toBeInTheDocument();
        expect(
            within(september).getByRole('link', {
                name: 'Start a linked amendment →',
            }),
        ).toHaveAttribute('href', '/preview/auditor-audit-count');

        await user.click(screen.getByRole('button', { name: 'Next month' }));
        await user.click(screen.getByRole('button', { name: 'Next month' }));

        const november = screen.getByRole('region', {
            name: 'Due in November',
        });

        expect(within(november).getByText('1 deal')).toBeInTheDocument();
        expect(
            within(november).getByRole('link', { name: /Intare Supply/u }),
        ).toHaveTextContent('In 35 days');

        await user.click(screen.getByRole('button', { name: 'Next month' }));

        const december = screen.getByRole('region', {
            name: 'Due in December',
        });

        expect(within(december).getByText('0 deals')).toBeInTheDocument();
        expect(
            within(december).getByText('Nothing due in this month.'),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            '1 verification due within 5 days',
        );
    });

    it("opens a day's entries from the grid and closes them again", async () => {
        const { user } = renderWithUser(<AuditorPortfolio {...props()} />);
        const seventh = screen.getByRole('button', {
            name: '7 Oct 2026, 2 verifications due',
        });

        await user.click(seventh);

        const day = screen.getByRole('dialog', { name: '7 Oct 2026' });

        expect(seventh).toHaveAttribute('aria-pressed', 'true');
        expect(
            within(day).getByText('2 verifications due'),
        ).toBeInTheDocument();
        expect(
            within(day)
                .getAllByRole('link')
                .map((link) => link.textContent),
        ).toEqual([
            'GreenLeaf AgroMonthly verification · RwamaganaIn 4 days',
            'Kivu Coffee RoastersSeptember 2026 report · filed 2 Oct · RubavuPublished',
        ]);

        await user.click(seventh);
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', {
                name: '2 Oct 2026, 1 verification due',
            }),
        );
        expect(
            within(
                screen.getByRole('dialog', { name: '2 Oct 2026' }),
            ).getByText('1 verification due'),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Close' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

        await user.click(seventh);
        await user.click(screen.getByRole('button', { name: 'Next month' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it.each([
        {
            name: 'overdue, due today and two coming up',
            owed: [
                ['2026-10-01T10:00:00Z', 'flash'],
                ['2026-10-02T10:00:00Z', 'monthly'],
                ['2026-10-03T12:00:00Z', 'monthly'],
                ['2026-10-04T12:00:00Z', 'monthly'],
                ['2026-10-05T12:00:00Z', 'monthly'],
            ],
            pills: [
                'Overdue 2 days',
                'Overdue 1 day',
                'Due today',
                'In 1 day',
                'In 2 days',
            ],
            alert: '3 verifications due within 5 daysFile each by its due date. The first is File 2, due 3 Oct.',
        },
        {
            name: 'only work further ahead',
            owed: [['2026-10-20T12:00:00Z', 'monthly']],
            pills: ['In 17 days'],
            alert: 'Nothing due in the next five daysYou are clear. The next filing is File 0 on 20 Oct.',
        },
        {
            name: 'nothing owed',
            owed: [],
            pills: [],
            alert: 'Nothing due in the next five daysYou are clear. The next filing is not scheduled yet.',
        },
    ])(
        'says how far off each owed verification is: $name',
        ({ owed, pills, alert }) => {
            const base = props(emptyFixture);

            render(
                <AuditorPortfolio
                    {...base}
                    owed={owed.map(([due_at, kind], index) => ({
                        id: `as_${index}`,
                        business: `File ${index}`,
                        district: 'Gasabo',
                        kind: kind as 'flash' | 'monthly',
                        due_at,
                        link: {
                            url: `/auditor/jobs/as_${index}`,
                            method: 'get',
                        },
                    }))}
                />,
            );

            const due = screen.getByRole('region', { name: 'Due in October' });

            for (const [index, pill] of pills.entries()) {
                expect(
                    within(due).getByRole('link', {
                        name: new RegExp(`File ${index}`, 'u'),
                    }),
                ).toHaveTextContent(pill);
            }

            expect(screen.getByRole('status')).toHaveTextContent(alert);
        },
    );

    it('places a report with no recorded due date on the day it was filed', () => {
        const live = props(liveMinimalFixture);
        const report = props().reports[1];

        render(
            <AuditorPortfolio
                {...live}
                reports={[{ ...report, due_on: null }]}
            />,
        );

        expect(
            screen.getByRole('button', {
                name: '1 Oct 2026, 1 verification due',
            }),
        ).toBeEnabled();
        expect(
            within(
                screen.getByRole('region', { name: 'Due in October' }),
            ).getByRole('link', { name: /Huye Motors/u }),
        ).toHaveTextContent('Awaiting co-sign');
    });

    it.each([
        { fixture: awaitingFixture, shown: ['Huye Motors'] },
        { fixture: publishedFixture, shown: ['Kivu Coffee Roasters'] },
        { fixture: rejectedFixture, shown: [] },
        { fixture: lateFixture, shown: [] },
    ])('shows only the reports the server sends', ({ fixture, shown }) => {
        render(<AuditorPortfolio {...props(fixture)} />);

        const due = screen.getByRole('region', { name: 'Due in October' });

        expect(
            within(due)
                .getAllByRole('link')
                .map(
                    (link) =>
                        link.textContent?.match(
                            /OCT(.+?)(Flash|September|Monthly)/u,
                        )?.[1],
                ),
        ).toEqual(
            ['GreenLeaf Agro', ...shown].sort((a, b) => {
                const order = [
                    'Huye Motors',
                    'GreenLeaf Agro',
                    'Kivu Coffee Roasters',
                ];

                return order.indexOf(a) - order.indexOf(b);
            }),
        );
    });

    it('explains a first month with nothing filed and nothing assigned', () => {
        render(<AuditorPortfolio {...props(emptyFixture)} />);

        expect(
            screen.getByText('Nothing due in this month.'),
        ).toBeInTheDocument();
        expect(screen.getByText('0 deals')).toBeInTheDocument();
        expect(
            screen.getByText('You have no files assigned to verify right now.'),
        ).toBeInTheDocument();
        expect(screen.queryByText('On the record')).not.toBeInTheDocument();
    });

    it('renders the live empty Portfolio with the Portfolio tab current', () => {
        render(<AuditorPortfolio {...props(liveMinimalFixture)} />);

        expect(
            screen.getByText('Nothing due in this month.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('You have no files assigned to verify right now.'),
        ).toBeInTheDocument();

        for (const nav of screen.getAllByRole('navigation', {
            name: 'App navigation',
        })) {
            expect(
                within(nav).getByRole('link', { name: /Portfolio/ }),
            ).toHaveAttribute('aria-current', 'page');
        }
    });

    it('names the weekdays, months and states in the reader’s language', () => {
        render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <AuditorPortfolio {...props()} />
            </I18nContext>,
        );

        expect(screen.getByText('octobre 2026')).toBeInTheDocument();
        expect(screen.getByText('LUN')).toBeInTheDocument();

        const due = screen.getByRole('region', { name: 'À rendre en octobre' });

        expect(
            within(due).getByRole('link', { name: /GreenLeaf Agro/u }),
        ).toHaveTextContent(
            '7OCTGreenLeaf AgroVérification mensuelleDans 4 jours',
        );
    });

    it("declares through the file's own command and names a private record by its reference", async () => {
        const live = props(liveMinimalFixture);
        const report = props().reports[1];
        const { user } = renderWithUser(
            <AuditorPortfolio
                {...live}
                reports={[report]}
                conflicts={{
                    files: [
                        {
                            id: '01k6zv7c4w3n8q5r2t9y6x1m0d',
                            revision: 2,
                            business: 'Synthetic business',
                            note_id: null,
                            allowed_actions: ['conflict.declare'],
                        },
                    ],
                    record: [
                        {
                            conflict_id: 'cf_live',
                            assignment_id: '01k6zv7c4w3n8q5r2t9y7qk2m4',
                            business: null,
                            note_id: null,
                            kind: 'other',
                            declared_on: '2026-10-02T09:00:00Z',
                        },
                    ],
                    declare: {
                        url: '/auditor/jobs/{assignment}/conflict',
                        method: 'post',
                    },
                }}
            />,
        );

        expect(
            screen.getByText('Business on record · Ref. …7qk2m4'),
        ).toBeInTheDocument();
        expect(screen.getByText('Other · 2 Oct 2026')).toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'Synthetic business' }),
        );
        const sheet = screen.getByRole('dialog', {
            name: 'Declare an interest in Synthetic business',
        });

        await user.click(within(sheet).getByRole('radio', { name: 'Other' }));
        await user.click(
            within(sheet).getByLabelText('Factual explanation (required)'),
        );
        await user.paste('A private relationship.');
        await user.click(
            within(sheet).getByRole('button', { name: 'Declare interest' }),
        );

        expect(inertia.calls[0]).toMatchObject({
            url: '/auditor/jobs/01k6zv7c4w3n8q5r2t9y6x1m0d/conflict',
            body: {
                assignment_id: '01k6zv7c4w3n8q5r2t9y6x1m0d',
                expected_revision: 2,
                kind: 'other',
                reason: 'A private relationship.',
            },
        });
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
                reason: 'My cousin keeps their books.',
                identity_context_revision: 3,
            },
        });

        await user.click(within(sheet).getByRole('button', { name: 'Cancel' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('sends a stale declaration again with the refreshed revision, a new request and the same words', async () => {
        const view = renderWithUser(<AuditorPortfolio {...props()} />);

        await declareStale(view.user);

        view.rerender(
            <AuditorPortfolio
                {...refreshed((file) => ({
                    ...file,
                    revision: file.revision + 1,
                }))}
            />,
        );

        const sheet = screen.getByRole('dialog', {
            name: 'Declare an interest in GreenLeaf Agro',
        });

        expect(
            within(sheet).getByLabelText('Factual explanation (required)'),
        ).toHaveValue('My cousin keeps their books.');
        expect(inertia.calls).toHaveLength(1);

        inertia.queue.push(
            answers(
                operation({
                    code: 'CONFLICT_RECORDED',
                    data: {
                        next: {
                            url: '/preview/auditor-portfolio',
                            method: 'get',
                        },
                        conflict: {
                            conflict_id: 'cf_9',
                            kind: 'other',
                            declared_at: '2026-10-03T17:00:00Z',
                            note: 'My cousin keeps their books.',
                            blocking: false,
                            status: 'recorded',
                        },
                    },
                }),
            ),
        );
        await view.user.click(
            within(sheet).getByRole('button', { name: 'Declare interest' }),
        );

        expect(inertia.calls[1].body).toMatchObject({
            assignment_id: 'mr_greenleaf',
            expected_revision: 6,
            reason: 'My cousin keeps their books.',
        });
        expect(
            (inertia.calls[1].body as { request_id: string }).request_id,
        ).not.toBe(
            (inertia.calls[0].body as { request_id: string }).request_id,
        );
    });

    it('closes the declaration when its file is gone after the refresh', async () => {
        const view = renderWithUser(<AuditorPortfolio {...props()} />);

        await declareStale(view.user);
        view.rerender(<AuditorPortfolio {...refreshed(() => null)} />);

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        expect(inertia.calls).toHaveLength(1);
    });

    it('says why a declaration cannot be sent once its file no longer allows one', async () => {
        const view = renderWithUser(<AuditorPortfolio {...props()} />);

        await declareStale(view.user);
        view.rerender(
            <AuditorPortfolio
                {...refreshed((file) => ({
                    ...file,
                    revision: file.revision + 1,
                    allowed_actions: [],
                }))}
            />,
        );

        const sheet = screen.getByRole('dialog', {
            name: 'Declare an interest in GreenLeaf Agro',
        });

        expect(within(sheet).getByRole('note')).toHaveTextContent(
            "You can no longer declare on this file, so your declaration hasn't been sent.",
        );
        expect(
            within(sheet).getByRole('button', { name: 'Declare interest' }),
        ).toBeDisabled();
        expect(inertia.calls).toHaveLength(1);
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
