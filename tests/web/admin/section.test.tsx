import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { SECTION_DESIGNS } from '@/components/admin/section-designs';
import en from '@/lib/i18n/catalogs/en';
import type { MessageCode } from '@/lib/i18n/types';
import AdminPendingSection from '@/pages/admin/section';
import type { AdminPendingSectionProps } from '@/types/admin';
import sectionFixture from '../../../resources/fixtures/ui/admin-section.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (overrides: Partial<AdminPendingSectionProps> = {}) => ({
    ...(structuredClone(sectionFixture.props) as AdminPendingSectionProps),
    ...overrides,
});

/** The English text of a plain message code. */
const text = (code: MessageCode): string => String(en[code]);

const designed = Object.entries(SECTION_DESIGNS).flatMap(([section, design]) =>
    design === undefined
        ? []
        : [[section as AdminPendingSectionProps['section'], design] as const],
);

beforeEach(resetInertia);

describe('A designed section that is not wired yet', () => {
    it('lays out every console section the design shows, Engines included', () => {
        expect(designed.map(([section]) => section)).toEqual([
            'notes',
            'primary_market',
            'secondary_market',
            'reports',
            'risk',
            'compliance',
            'payments',
            'ratings',
            'deferrals',
            'plus',
            'finance',
            'messaging',
            'academies',
            'app_control',
            'engines',
            'policies',
            'system_health',
        ]);
    });

    it.each(designed)(
        '%s shows its design with blank figures, empty tables and no actions',
        (section, design) => {
            render(<AdminPendingSection {...props({ section })} />);

            const title = text(`admin.section.${section}.title`);

            expect(screen.getByTestId('head')).toHaveTextContent(title);

            const region = screen.getByRole('region', { name: title });
            const kpis = design.kpis ?? [];

            // Every tile keeps its place with no figure in it.
            expect(within(region).queryAllByText('—')).toHaveLength(
                kpis.length,
            );

            for (const kpi of kpis) {
                // A tile label is the one bare span carrying its text.
                expect(
                    within(region).getByText(text(kpi.label), {
                        selector: 'span:not([role])',
                    }),
                ).toBeVisible();

                if (kpi.sub !== undefined) {
                    expect(
                        within(region).getByText(text(kpi.sub)),
                    ).toBeVisible();
                }
            }

            const tabs = within(region).queryAllByRole('tab');

            expect(tabs.map((tab) => tab.textContent)).toEqual(
                (design.tabs ?? []).map((tab) => text(tab.label)),
            );
            expect(within(region).queryAllByRole('button')).toEqual([]);
            expect(within(region).queryAllByRole('link')).toEqual([]);

            const first = design.tabs?.[0]?.key;

            for (const block of design.blocks) {
                if (block.tab !== undefined && block.tab !== first) {
                    expect(
                        within(region).queryByText(text(block.empty)),
                    ).not.toBeInTheDocument();

                    continue;
                }

                expect(
                    within(region).getByRole('heading', {
                        name: text(block.title),
                    }),
                ).toBeInTheDocument();
                expect(
                    within(region).getByText(text(block.empty)),
                ).toBeVisible();

                if (block.sub !== undefined) {
                    expect(
                        within(region).getByText(text(block.sub)),
                    ).toBeVisible();
                }

                if (block.chart === true) {
                    expect(
                        within(region).getByRole('region', {
                            name: text(block.title),
                        }),
                    ).toHaveTextContent(text(block.empty));

                    continue;
                }

                const table = within(region).getByRole('table', {
                    name: text(block.title),
                });

                expect(
                    within(table)
                        .getAllByRole('columnheader')
                        .map((head) => head.textContent),
                ).toEqual(
                    (block.columns ?? []).map((column) =>
                        text(`admin.design.col.${column}`),
                    ),
                );
                expect(within(table).getAllByRole('row')).toHaveLength(1);
            }

            expect(region).not.toHaveTextContent(/admin\.|RWF|%/u);
        },
    );

    it('switches the tabbed table in place, keeping the page', () => {
        render(<AdminPendingSection {...props({ section: 'finance' })} />);

        const tabs = screen.getByRole('tablist', {
            name: 'Finance Dashboard views',
        });
        expect(
            within(tabs).getByRole('tab', { name: 'Revenue streams' }),
        ).toHaveAttribute('aria-selected', 'true');
        expect(screen.getByText('No revenue to show yet.')).toBeVisible();

        fireEvent.click(
            within(tabs).getByRole('tab', { name: 'Ledger & payouts' }),
        );

        expect(
            within(tabs).getByRole('tab', { name: 'Ledger & payouts' }),
        ).toHaveAttribute('aria-selected', 'true');
        expect(
            within(tabs).getByRole('tab', { name: 'Revenue streams' }),
        ).toHaveAttribute('aria-selected', 'false');
        expect(
            screen.getByText('No ledger entries to show yet.'),
        ).toBeVisible();
        expect(
            screen.queryByText('No revenue to show yet.'),
        ).not.toBeInTheDocument();
        expect(screen.getAllByRole('tab', { selected: true })).toHaveLength(1);
    });

    it('keeps an untabbed table in view whichever tab is chosen', () => {
        render(<AdminPendingSection {...props({ section: 'notes' })} />);

        fireEvent.click(screen.getByRole('tab', { name: 'Matured' }));

        expect(screen.getByRole('tab', { name: 'Matured' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(screen.getByText('No notes to show yet.')).toBeVisible();
    });

    it('lists Engines in the console group between App Control and Policies, and lights it', () => {
        render(<AdminPendingSection {...props({ section: 'engines' })} />);

        const nav = screen.getByRole('navigation', {
            name: 'Console navigation',
        });
        const names = within(nav)
            .getAllByRole('link')
            .map((link) => link.textContent);
        expect(names).toEqual(
            expect.arrayContaining(['App Control', 'Engines', 'Policies']),
        );
        expect(names.indexOf('Engines')).toBe(names.indexOf('App Control') + 1);
        expect(names.indexOf('Policies')).toBe(names.indexOf('Engines') + 1);
        expect(
            within(nav).getByRole('link', { name: 'Engines' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(screen.getByTestId('head')).toHaveTextContent('Engines');
    });
});

describe('The title ⓘ', () => {
    it('takes no layout while closed and opens on hover or keyboard focus', () => {
        render(<AdminPendingSection {...props({ section: 'reports' })} />);

        const tip = screen.getByRole('tooltip', { hidden: true });

        expect(tip).toHaveTextContent(
            'Business performance reports published to investors',
        );
        // Closed it is display:none, not just invisible, so it never widens a phone screen.
        expect(tip).toHaveClass(
            'hidden',
            'group-hover:block',
            'group-focus:block',
        );
        expect(tip).not.toHaveClass('invisible');
    });
});

describe('Policies', () => {
    it("lists the rules in effect under the selected tab, with each rule's version", () => {
        render(
            <AdminPendingSection
                {...props({
                    section: 'policies',
                    rules: [
                        {
                            tab: 'fees',
                            key: 'platform_fee',
                            value: '3%',
                            version: 'brs-2.0-fee-r01',
                        },
                        {
                            tab: 'fees',
                            key: 'loss_reserve',
                            value: '',
                            term: 'none',
                            version: 'brs-2.0-gen-r01',
                        },
                        {
                            tab: 'kyc',
                            key: 'minimum_age',
                            value: '18',
                            version: null,
                        },
                    ],
                })}
            />,
        );

        const rules = screen.getByRole('table', { name: 'Rules' });
        expect(within(rules).getAllByRole('row')).toHaveLength(3);
        expect(within(rules).getAllByRole('row')[1]).toHaveTextContent(
            'Platform fee · at disbursement3%brs-2.0-fee-r01',
        );
        expect(within(rules).getAllByRole('row')[2]).toHaveTextContent(
            'Loss reserveNonebrs-2.0-gen-r01',
        );
        expect(
            screen.queryByText('No rules to show yet.'),
        ).not.toBeInTheDocument();

        fireEvent.click(screen.getByRole('tab', { name: 'KYC requirements' }));
        expect(
            within(screen.getByRole('table', { name: 'Rules' })).getAllByRole(
                'row',
            )[1],
        ).toHaveTextContent('Minimum investor age18—');

        fireEvent.click(
            screen.getByRole('tab', { name: 'Investor eligibility' }),
        );
        expect(screen.getByText('No rules to show yet.')).toBeVisible();
    });
});

describe('Finance', () => {
    it('shows the fee revenue the ledger holds, stream by stream, and leaves uncharged fees blank', () => {
        render(
            <AdminPendingSection
                {...props({
                    section: 'finance',
                    figures: {
                        revenue: { currency: 'RWF', amount: '1950000' },
                        primary: { currency: 'RWF', amount: '1800000' },
                    },
                    streams: [
                        {
                            key: 'platform_fee',
                            rate: { key: 'platform_fee', value: '3%' },
                            paid_by: 'business',
                            collected: { currency: 'RWF', amount: '1800000' },
                        },
                        {
                            key: 'deposit_fees',
                            rate: null,
                            paid_by: 'investor',
                            collected: { currency: 'RWF', amount: '150000' },
                        },
                        {
                            key: 'resale_fee',
                            rate: { key: 'resale_fee', value: '2%' },
                            paid_by: 'seller',
                            collected: null,
                        },
                        {
                            key: 'loss_reserve',
                            rate: {
                                key: 'loss_reserve',
                                value: '',
                                term: 'none',
                            },
                            paid_by: null,
                            collected: null,
                        },
                    ],
                })}
            />,
        );

        const region = screen.getByRole('region', {
            name: 'Finance Dashboard',
        });
        expect(within(region).getAllByText('—')).toHaveLength(6);
        expect(region).toHaveTextContent('All revenueRWF 2M');
        expect(region).toHaveTextContent('Primary marketRWF 1.8M');

        const streams = screen.getByRole('table', { name: 'Revenue streams' });
        expect(within(streams).getAllByRole('row')[1]).toHaveTextContent(
            'Platform fee · Rozine share3%BusinessesRWF 1,800,000',
        );
        expect(within(streams).getAllByRole('row')[2]).toHaveTextContent(
            'Deposit fees—InvestorsRWF 150,000',
        );
        expect(within(streams).getAllByRole('row')[3]).toHaveTextContent(
            'Resale fee2%Seller—',
        );
        expect(within(streams).getAllByRole('row')[4]).toHaveTextContent(
            'Loss reserveNone——',
        );
    });
});

describe('Engines and Ratings', () => {
    it("lists the engine's settings under their tab, with the rule set each comes from", () => {
        render(
            <AdminPendingSection
                {...props({
                    section: 'engines',
                    engine: [
                        {
                            tab: 'sizing',
                            key: 'target_dscr',
                            value: '1.50×',
                            source: 'loan-capacity-v1',
                        },
                        {
                            tab: 'cpa',
                            key: 'cpa_share',
                            value: '40%',
                            source: 'brs-2.0-fee-r01',
                        },
                    ],
                })}
            />,
        );

        const sizing = screen.getByRole('table', { name: 'Sizing' });
        expect(within(sizing).getAllByRole('row')).toHaveLength(2);
        expect(within(sizing).getAllByRole('row')[1]).toHaveTextContent(
            'Target DSCR1.50×loan-capacity-v1',
        );

        fireEvent.click(screen.getByRole('tab', { name: 'CPA split' }));
        expect(
            within(
                screen.getByRole('table', { name: 'CPA split' }),
            ).getAllByRole('row')[1],
        ).toHaveTextContent('Audit Partner share of the fee40%brs-2.0-fee-r01');

        fireEvent.click(screen.getByRole('tab', { name: 'Refinance' }));
        expect(
            screen.getByText('No refinance inputs to show yet.'),
        ).toBeVisible();
    });

    it('explains how a rating is computed: the weights, the caps and the bands', () => {
        render(
            <AdminPendingSection
                {...props({
                    section: 'ratings',
                    engine: [
                        {
                            tab: 'weights',
                            key: 'coverage',
                            value: '30% · 1.00×–1.50×',
                            source: 'scorecard-v1',
                        },
                        {
                            tab: 'movers',
                            key: 'default_cap',
                            value: '1.9',
                            source: 'scorecard-v1',
                        },
                        {
                            tab: 'bands',
                            key: 'distressed',
                            value: '< 2.0',
                            source: 'scorecard-v1',
                        },
                    ],
                })}
            />,
        );

        expect(
            within(
                screen.getByRole('table', { name: 'How a rating is computed' }),
            ).getAllByRole('row')[1],
        ).toHaveTextContent('Debt coverage30% · 1.00×–1.50×scorecard-v1');
        expect(
            within(screen.getByRole('table', { name: 'Bands' })).getAllByRole(
                'row',
            )[1],
        ).toHaveTextContent('Distressed< 2.0scorecard-v1');
        expect(
            within(
                screen.getByRole('table', { name: 'What moves a rating' }),
            ).getAllByRole('row')[1],
        ).toHaveTextContent('Rating cap · default1.9scorecard-v1');
        expect(screen.getByText('No ratings to show yet.')).toBeVisible();
    });
});

describe('The rating book', () => {
    it('lists every rated Business under All, counts them, and keeps band changes empty', () => {
        render(
            <AdminPendingSection
                {...props({
                    section: 'ratings',
                    counts: { rated: 1 },
                    book: [
                        {
                            key: 'b-1',
                            business: 'GreenLeaf Agro',
                            sector: 'Agriculture',
                            rating: { band: 'strong', score: '4.6' },
                        },
                    ],
                })}
            />,
        );

        expect(
            screen.getByRole('region', { name: 'Ratings' }),
        ).toHaveTextContent('Businesses rated1');
        expect(
            within(
                screen.getByRole('table', { name: 'The rating book' }),
            ).getAllByRole('row')[1],
        ).toHaveTextContent('GreenLeaf AgroAgricultureStrong · 4.6——');

        fireEvent.click(screen.getByRole('tab', { name: 'Band changed' }));
        expect(screen.getByText('No ratings to show yet.')).toBeVisible();
    });
});

describe('A designed screen whose live data is wired elsewhere', () => {
    it('opens the console frame with its title, its sidebar entry lit and the plain empty state', () => {
        render(<AdminPendingSection {...props({ section: 'businesses' })} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Business Directory',
        );
        expect(
            within(
                screen.getByRole('navigation', { name: 'Console navigation' }),
            ).getByRole('link', { name: 'Businesses' }),
        ).toHaveAttribute('aria-current', 'page');
        const section = screen.getByRole('region', {
            name: 'Business Directory',
        });
        expect(
            within(section).getByText('Nothing here yet'),
        ).toBeInTheDocument();
        expect(
            within(section).getByText(
                /isn’t connected yet|isn't connected yet/u,
            ),
        ).toBeInTheDocument();
        expect(within(section).queryByRole('tab')).not.toBeInTheDocument();
    });
});

describe('App Control', () => {
    it('shows how much of each sandbox limit is used, as counts and RWF', () => {
        render(
            <AdminPendingSection
                {...props({
                    section: 'app_control',
                    sandbox: [
                        {
                            key: 'businesses',
                            used: '12',
                            cap: '100',
                            used_pct: 12,
                        },
                        {
                            key: 'investors',
                            used: '2400',
                            cap: '10000',
                            used_pct: 24,
                        },
                        {
                            key: 'principal',
                            used: '250000000',
                            cap: '5000000000',
                            used_pct: 5,
                        },
                    ],
                })}
            />,
        );

        const limits = screen.getByRole('table', { name: 'Sandbox limits' });
        const rows = within(limits).getAllByRole('row');

        expect(rows).toHaveLength(4);
        expect(rows[1]).toHaveTextContent(
            'Businesses with an active mandate1210012%',
        );
        expect(rows[2]).toHaveTextContent('Verified investors2,40010,00024%');
        expect(rows[3]).toHaveTextContent(
            'Principal outstandingRWF 250,000,000RWF 5,000,000,0005%',
        );
        expect(screen.getByText('No app settings to show yet.')).toBeVisible();
    });
});
