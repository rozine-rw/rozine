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

        fireEvent.click(within(tabs).getByRole('tab', { name: 'RAMP' }));

        expect(within(tabs).getByRole('tab', { name: 'RAMP' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(
            within(tabs).getByRole('tab', { name: 'Revenue streams' }),
        ).toHaveAttribute('aria-selected', 'false');
        expect(
            screen.getByText('No RAMP positions to show yet.'),
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
