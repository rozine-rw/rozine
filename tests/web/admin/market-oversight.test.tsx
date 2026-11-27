import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminPendingSection from '@/pages/admin/section';
import type { AdminPendingSectionProps, MarketOversight } from '@/types/admin';
import sectionFixture from '../../../resources/fixtures/ui/admin-section.json';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const NOTE = '01jx5b3m2k4q6r8t0v2w4y6z8a';

const oversight = (
    overrides: Partial<MarketOversight> = {},
): MarketOversight => ({
    trading: true,
    mode: 'live',
    market_halted: false,
    halt: { url: '/admin/secondary-market/halt', method: 'post' },
    halts: [],
    listings: [
        {
            listing_id: '01jx5b3m2k4q6r8t0v2w4y6z8b',
            note_id: NOTE,
            deal_name: 'Kivu Harvest',
            remaining_units: '12',
            price: { currency: 'RWF', amount: '5000' },
            listed_at: '2026-11-25T08:00:00+00:00',
            expires_at: '2026-12-02T08:00:00+00:00',
            halt_note: { url: '/admin/secondary-market/halt', method: 'post' },
            cancel: {
                url: '/admin/secondary-market/listings/01jx5b3m2k4q6r8t0v2w4y6z8b/cancel',
                method: 'post',
            },
        },
    ],
    notes: [],
    ...overrides,
});

const page = (market?: MarketOversight) => (
    <AdminPendingSection
        {...({
            ...structuredClone(sectionFixture.props),
            section: 'secondary_market',
            market_oversight: market,
        } as AdminPendingSectionProps)}
    />
);

beforeEach(resetInertia);

describe('Secondary Market resale controls (SEC-R08, SEC-R09)', () => {
    it('shows no controls to staff the server sent none for', () => {
        render(page());

        expect(
            screen.queryByRole('region', { name: 'Resale controls' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Halt whole market' }),
        ).not.toBeInTheDocument();
    });

    it('halts the whole market only with a written reason', () => {
        render(page(oversight()));

        fireEvent.click(
            screen.getByRole('button', { name: 'Halt whole market' }),
        );
        const stage = screen.getByRole('form', {
            name: 'Halt the whole resale market',
        });
        const confirm = within(stage).getByRole('button', {
            name: 'Halt market',
        });
        expect(confirm).toBeDisabled();

        fireEvent.change(within(stage).getByRole('textbox'), {
            target: { value: 'Suspected wash trading.' },
        });
        fireEvent.click(confirm);

        expect(inertia.posts).toEqual([
            {
                url: '/admin/secondary-market/halt',
                data: expect.objectContaining({
                    reason: 'Suspected wash trading.',
                    request_id: expect.any(String),
                }),
            },
        ]);
        expect(inertia.posts[0]?.data).not.toHaveProperty('note_id');
    });

    it('halts one note, naming it in the request', () => {
        render(page(oversight()));

        fireEvent.click(
            screen.getByRole('button', { name: 'Halt resale of Kivu Harvest' }),
        );
        const stage = screen.getByRole('form', {
            name: 'Halt resale of Kivu Harvest',
        });
        fireEvent.change(within(stage).getByRole('textbox'), {
            target: { value: 'Business under review.' },
        });
        fireEvent.click(
            within(stage).getByRole('button', { name: 'Halt note' }),
        );

        expect(inertia.posts[0]).toEqual({
            url: '/admin/secondary-market/halt',
            data: expect.objectContaining({
                note_id: NOTE,
                reason: 'Business under review.',
            }),
        });
    });

    it('cancels a listing with the reason the seller will see', () => {
        render(page(oversight()));

        expect(screen.getByText('RWF 5,000')).toBeInTheDocument();
        fireEvent.click(
            screen.getByRole('button', {
                name: 'Cancel the listing on Kivu Harvest',
            }),
        );
        const stage = screen.getByRole('form', {
            name: 'Cancel the listing on Kivu Harvest',
        });
        fireEvent.change(within(stage).getByRole('textbox'), {
            target: { value: 'Price looks manipulated.' },
        });
        fireEvent.click(
            within(stage).getByRole('button', { name: 'Cancel listing' }),
        );

        expect(inertia.posts[0]?.url).toBe(
            '/admin/secondary-market/listings/01jx5b3m2k4q6r8t0v2w4y6z8b/cancel',
        );
    });

    it('lifts a market halt in force and offers no second halt meanwhile', () => {
        render(
            page(
                oversight({
                    market_halted: true,
                    halt: null,
                    halts: [
                        {
                            halt_id: '01jx5b3m2k4q6r8t0v2w4y6z8c',
                            note_id: null,
                            deal_name: null,
                            reason: 'Suspected wash trading.',
                            halted_at: '2026-11-26T09:00:00+00:00',
                            lift: {
                                url: '/admin/secondary-market/lift',
                                method: 'post',
                            },
                        },
                    ],
                    listings: [],
                }),
            ),
        );

        expect(
            screen.getByText(
                'The whole market is halted. Nothing trades until the halt is lifted.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Halt whole market' }),
        ).not.toBeInTheDocument();
        expect(screen.getByText('No listing is open.')).toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('button', {
                name: 'Lift the halt on Whole market',
            }),
        );
        const stage = screen.getByRole('form', {
            name: 'Lift the halt on Whole market',
        });
        fireEvent.change(within(stage).getByRole('textbox'), {
            target: { value: 'Review closed.' },
        });
        fireEvent.click(
            within(stage).getByRole('button', { name: 'Lift halt' }),
        );

        expect(inertia.posts[0]).toEqual({
            url: '/admin/secondary-market/lift',
            data: expect.not.objectContaining({ note_id: expect.anything() }),
        });
    });

    it('halts an issued note that has no open listing, and marks a halted one', () => {
        render(
            page(
                oversight({
                    listings: [],
                    notes: [
                        {
                            note_id: NOTE,
                            deal_name: 'Kivu Harvest',
                            halt: {
                                url: '/admin/secondary-market/halt',
                                method: 'post',
                            },
                        },
                        {
                            note_id: '01jx5b3m2k4q6r8t0v2w4y6z8d',
                            deal_name: 'Ruhango Dairy',
                            halt: null,
                        },
                    ],
                }),
            ),
        );

        const notes = screen.getByRole('group', { name: 'Issued notes' });
        expect(within(notes).getByText('Halted')).toBeInTheDocument();
        expect(
            within(notes).queryByRole('button', {
                name: 'Halt resale of Ruhango Dairy',
            }),
        ).not.toBeInTheDocument();

        fireEvent.click(
            within(notes).getByRole('button', {
                name: 'Halt resale of Kivu Harvest',
            }),
        );
        const stage = screen.getByRole('form', {
            name: 'Halt resale of Kivu Harvest',
        });
        fireEvent.change(within(stage).getByRole('textbox'), {
            target: { value: 'Business under review.' },
        });
        fireEvent.click(
            within(stage).getByRole('button', { name: 'Halt note' }),
        );

        expect(inertia.posts[0]).toEqual({
            url: '/admin/secondary-market/halt',
            data: expect.objectContaining({
                note_id: NOTE,
                reason: 'Business under review.',
            }),
        });
    });

    it('says resale runs in the staging simulation rather than that it is off', () => {
        render(page(oversight({ mode: 'simulated' })));

        expect(
            screen.getByText(
                'Resale is running in the staging money simulation. A halt cancels every open listing it covers and releases reservations. The people affected are told the reason in the app and by email.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByText(/Resale trading is switched off/u),
        ).not.toBeInTheDocument();
    });

    it('says when resale trading is switched off', () => {
        render(page(oversight({ trading: false, mode: 'off', listings: [] })));

        expect(
            screen.getByText(
                'Resale trading is switched off. Halts and cancels are refused until it is switched on.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('No halt is in force.')).toBeInTheDocument();
    });
});
