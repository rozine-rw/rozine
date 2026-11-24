import { fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import BusinessMonthlyFigures from '@/pages/business/monthly-figures';
import type { BusinessMonthlyFiguresProps } from '@/types/business';
import fixture from '../../../resources/fixtures/ui/business-monthly-figures.json';

const inertia = vi.hoisted(() => ({
    posts: [] as { url: string; data: Record<string, unknown> }[],
    errors: {} as Record<string, string>,
    refused: null as Record<string, string> | null,
    processing: false,
}));

vi.mock('@inertiajs/react', async () => {
    const { useState } = await import('react');

    return {
        Head: () => null,
        Link: ({
            href,
            children,
            ...props
        }: Omit<ComponentProps<'a'>, 'href'> & { href: string }) => (
            <a href={href} {...props}>
                {children}
            </a>
        ),
        useHttp: () => ({ submit: () => new Promise(() => undefined) }),
        useForm: <T extends Record<string, unknown>>(initial: T) => {
            const [data, setState] = useState(initial);
            let transform = (current: T): Record<string, unknown> => current;

            return {
                data,
                setData: (key: keyof T, value: unknown) =>
                    setState((current) => ({ ...current, [key]: value })),
                errors: inertia.errors,
                processing: inertia.processing,
                transform: (
                    callback: (current: T) => Record<string, unknown>,
                ) => {
                    transform = callback;
                },
                post: (
                    url: string,
                    options: {
                        onError: (errors: Record<string, string>) => void;
                    },
                ) => {
                    inertia.posts.push({ url, data: transform(data) });

                    if (inertia.refused !== null) {
                        options.onError(inertia.refused);
                    }
                },
            };
        },
    };
});

const props = (
    overrides: Partial<BusinessMonthlyFiguresProps> = {},
): BusinessMonthlyFiguresProps => ({
    ...(structuredClone(fixture.props) as BusinessMonthlyFiguresProps),
    ...overrides,
});

const next = () => screen.getByRole('button', { name: 'Next' });

const heading = (name: string) =>
    expect(screen.getByRole('heading', { name })).toBeInTheDocument();

/** Fills every screen up to the summary, answering "Yes" to the changes listed. */
const fillToSummary = async (
    user: ReturnType<typeof userEvent.setup>,
    { forecast, order }: { forecast?: string; order?: string } = {},
) => {
    await user.type(screen.getByLabelText('Money in'), '12500000');
    await user.click(next());

    for (const [label, value] of [
        ['Stock and materials', '4000000'],
        ['Staff pay', '2000000'],
        ['Rent and utilities', '800000'],
        ['Transport', '300000'],
        ['Taxes and fees', '150000'],
        ['Other costs', '50000'],
    ]) {
        await user.type(screen.getByLabelText(label), value);
    }

    await user.click(next());
    await user.type(screen.getByLabelText('Paid to owners'), '1000000');
    await user.click(next());

    if (forecast !== undefined) {
        const field = screen.getByLabelText(
            'Money in expected in October 2026',
        );

        await user.clear(field);
        await user.type(field, forecast);
    }

    if (order !== undefined) {
        await user.type(screen.getByLabelText('Signed order'), order);
    }

    await user.click(next());

    for (const name of [
        'Owners or directors changed',
        'Moved premises, or opened or closed a location',
        'Lost a main customer or supplier',
    ]) {
        await user.click(
            within(screen.getByRole('group', { name })).getByRole('radio', {
                name: 'No',
            }),
        );
    }

    await user.click(
        within(
            screen.getByRole('group', { name: 'Took a new loan or credit' }),
        ).getByRole('radio', { name: 'Yes' }),
    );
    await user.click(next());
};

beforeEach(() => {
    inertia.posts = [];
    inertia.errors = {};
    inertia.refused = null;
    inertia.processing = false;
});

describe('Monthly figures', () => {
    it('walks the six screens and sends the figures once, with a fresh request id', async () => {
        const user = userEvent.setup();

        render(<BusinessMonthlyFigures {...props()} />);

        expect(
            screen.getByText(
                /Send what came in and went out in September 2026\. Your Audit Partner checks these figures at the visit, before 7 Oct/u,
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('Step 1 of 6')).toBeInTheDocument();
        heading('Money in for September 2026');
        expect(next()).toBeDisabled();
        expect(
            screen.queryByRole('button', { name: 'Back' }),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/business-audit-prep',
        );

        await user.type(screen.getByLabelText('Money in'), '12a5');
        expect(screen.getByLabelText('Money in')).toHaveValue('125');
        await user.clear(screen.getByLabelText('Money in'));
        await user.click(next());
        heading('Money in for September 2026');

        await user.type(screen.getByLabelText('Money in'), '12500000');
        await user.click(next());
        heading('Costs for September 2026');
        expect(screen.getByText('Total costs: RWF 0')).toBeInTheDocument();
        await user.type(screen.getByLabelText('Stock and materials'), '4000');
        expect(screen.getByText('Total costs: RWF 4,000')).toBeInTheDocument();
        expect(next()).toBeDisabled();

        await user.click(screen.getByRole('button', { name: 'Back' }));
        heading('Money in for September 2026');
        expect(screen.getByLabelText('Money in')).toHaveValue('12500000');
    });

    it('pre-fills the forecast and asks for the signed order only past 25%', async () => {
        const user = userEvent.setup();

        render(<BusinessMonthlyFigures {...props()} />);
        await fillToSummary(user, { forecast: '13000000', order: 'Order 114' });

        heading('Check and send');
        expect(
            screen.getByText(
                'September 2026: money in RWF 12,500,000, costs RWF 7,300,000, paid to owners RWF 1,000,000. Forecast for October 2026: RWF 13,000,000.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Once sent, these figures are final for the month.',
            ),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Send figures' }));

        expect(inertia.posts).toHaveLength(1);
        expect(inertia.posts[0].url).toBe('/preview/business-monthly-figures');
        expect(inertia.posts[0].data).toMatchObject({
            money_in: '12500000',
            costs: {
                stock: '4000000',
                staff: '2000000',
                premises: '800000',
                transport: '300000',
                taxes: '150000',
                other: '50000',
            },
            paid_to_owners: '1000000',
            forecast: '13000000',
            order: 'Order 114',
            changes: {
                owners: false,
                premises: false,
                borrowing: true,
                customers: false,
            },
            identity_context_revision: 3,
            period: '2026-09-01',
        });
        expect(inertia.posts[0].data.request_id).toMatch(/^[0-9a-f-]{36}$/u);
    });

    it('holds a forecast far from the average until the order is named, and sends it', async () => {
        const user = userEvent.setup();

        render(<BusinessMonthlyFigures {...props()} />);
        await user.type(screen.getByLabelText('Money in'), '1');
        await user.click(next());

        for (const label of [
            'Stock and materials',
            'Staff pay',
            'Rent and utilities',
            'Transport',
            'Taxes and fees',
            'Other costs',
        ]) {
            await user.type(screen.getByLabelText(label), '0');
        }

        await user.click(next());
        await user.type(screen.getByLabelText('Paid to owners'), '0');
        await user.click(next());

        heading('Forecast for October 2026');
        expect(
            screen.getByText(
                'Pre-filled with your verified average for the last three months: RWF 10,000,000.',
            ),
        ).toBeInTheDocument();
        const forecast = screen.getByLabelText(
            'Money in expected in October 2026',
        );

        expect(forecast).toHaveValue('10000000');
        expect(screen.queryByLabelText('Signed order')).not.toBeInTheDocument();

        await user.clear(forecast);
        expect(next()).toBeDisabled();
        await user.type(forecast, '7000000');
        expect(screen.getByLabelText('Signed order')).toBeInTheDocument();
        expect(next()).toBeDisabled();
        await user.clear(forecast);
        await user.type(forecast, '12500000');
        expect(screen.queryByLabelText('Signed order')).not.toBeInTheDocument();
        await user.clear(forecast);
        await user.type(forecast, '12500001');
        await user.type(screen.getByLabelText('Signed order'), '   ');
        expect(next()).toBeDisabled();
        await user.type(
            screen.getByLabelText('Signed order'),
            'Order 114 from Simba',
        );
        await user.click(next());

        heading('Changes in September 2026');
        expect(next()).toBeDisabled();

        for (const name of [
            'Owners or directors changed',
            'Moved premises, or opened or closed a location',
            'Took a new loan or credit',
            'Lost a main customer or supplier',
        ]) {
            await user.click(
                within(screen.getByRole('group', { name })).getByRole('radio', {
                    name: 'Yes',
                }),
            );
        }

        await user.click(next());
        await user.click(screen.getByRole('button', { name: 'Send figures' }));

        expect(inertia.posts[0].data).toMatchObject({
            forecast: '12500001',
            order: '   Order 114 from Simba',
            changes: {
                owners: true,
                premises: true,
                borrowing: true,
                customers: true,
            },
        });
    });

    it('starts the forecast empty and asks for no order with no verified average', async () => {
        const user = userEvent.setup();

        render(<BusinessMonthlyFigures {...props({ forecast_basis: null })} />);
        await user.type(screen.getByLabelText('Money in'), '1');
        await user.click(next());

        for (const label of [
            'Stock and materials',
            'Staff pay',
            'Rent and utilities',
            'Transport',
            'Taxes and fees',
            'Other costs',
        ]) {
            await user.type(screen.getByLabelText(label), '0');
        }

        await user.click(next());
        await user.type(screen.getByLabelText('Paid to owners'), '0');
        await user.click(next());

        expect(
            screen.getByText(
                'Enter the money you expect to come in. There is no verified average yet.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByLabelText('Money in expected in October 2026'),
        ).toHaveValue('');
        await user.type(
            screen.getByLabelText('Money in expected in October 2026'),
            '99000000',
        );
        expect(screen.queryByLabelText('Signed order')).not.toBeInTheDocument();
    });

    it('never asks for an order against a zero average', async () => {
        const user = userEvent.setup();

        render(<BusinessMonthlyFigures {...props({ forecast_basis: '0' })} />);
        await fillToSummary(user, { forecast: '5000000' });

        heading('Check and send');
    });

    it.each([
        ['money_in', 'Money in for September 2026'],
        ['costs.staff', 'Costs for September 2026'],
        ['paid_to_owners', 'Paid to owners in September 2026'],
        ['forecast', 'Forecast for October 2026'],
        ['order', 'Forecast for October 2026'],
        ['changes.owners', 'Changes in September 2026'],
        ['form', 'Check and send'],
    ])(
        'returns to the screen that holds a refused %s',
        async (field, screenTitle) => {
            const user = userEvent.setup();

            render(<BusinessMonthlyFigures {...props()} />);
            await fillToSummary(user);
            inertia.refused = { [field]: 'Refused.' };
            await user.click(
                screen.getByRole('button', { name: 'Send figures' }),
            );

            heading(screenTitle);
        },
    );

    it('stays on the summary when a send is refused with no field named', async () => {
        const user = userEvent.setup();

        render(<BusinessMonthlyFigures {...props()} />);
        await fillToSummary(user);
        inertia.refused = {};
        await user.click(screen.getByRole('button', { name: 'Send figures' }));

        heading('Check and send');
    });

    it('marks every refused field with its reason', async () => {
        inertia.errors = {
            money_in: 'Enter a whole amount in Rwandan francs, 0 or more.',
            'costs.stock': 'Stock refused.',
            paid_to_owners: 'Paid refused.',
            forecast: 'Forecast refused.',
            order: 'Name the signed order.',
            'changes.owners': 'Answer yes or no.',
            form: "This month's figures were already sent.",
        };
        const user = userEvent.setup();

        render(<BusinessMonthlyFigures {...props()} />);
        expect(screen.getByLabelText('Money in')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(
            screen.getByText(
                'Enter a whole amount in Rwandan francs, 0 or more.',
            ),
        ).toBeInTheDocument();
        await fillToSummary(user, { forecast: '20000000', order: 'Order 1' });
        expect(
            screen.getByText("This month's figures were already sent."),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Back' }));
        expect(screen.getByText('Answer yes or no.')).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Back' }));
        expect(screen.getByLabelText('Signed order')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(screen.getByText('Forecast refused.')).toBeInTheDocument();
    });

    it('shows the send in progress and ignores a stray submit before a screen is complete', () => {
        inertia.processing = true;

        render(<BusinessMonthlyFigures {...props()} />);

        fireEvent.submit(
            screen.getByRole('form', { name: 'Money in for September 2026' }),
        );
        heading('Money in for September 2026');
        expect(next()).toHaveAttribute('aria-busy', 'true');
    });

    it('labels the summary button as sending while the send is in flight', async () => {
        const user = userEvent.setup();
        const { rerender } = render(<BusinessMonthlyFigures {...props()} />);

        await fillToSummary(user);
        inertia.processing = true;
        rerender(<BusinessMonthlyFigures {...props()} />);

        expect(screen.getByRole('button', { name: 'Sending…' })).toBeDisabled();
    });

    it('shows what was sent, with the order, once the month is final', () => {
        render(
            <BusinessMonthlyFigures
                {...props({
                    sent: {
                        money_in: '12500000',
                        costs: {
                            stock: '4000000',
                            staff: '2000000',
                            premises: '800000',
                            transport: '300000',
                            taxes: '150000',
                            other: '50000',
                        },
                        costs_total: '7300000',
                        paid_to_owners: '1000000',
                        forecast: '20000000',
                        forecast_basis: '10000000',
                        order: 'Order 114 from Simba Supermarket',
                        changes: {
                            owners: false,
                            premises: false,
                            borrowing: true,
                            customers: false,
                        },
                        submitted_at: '2026-10-02T08:00:00Z',
                    },
                })}
            />,
        );

        heading('Figures for September 2026');
        expect(screen.getByRole('status')).toHaveTextContent(
            /^Sent .+\. Your Audit Partner checks them at the visit\.$/u,
        );
        expect(screen.queryByRole('form')).not.toBeInTheDocument();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(screen.getByText('Total costs')).toBeInTheDocument();
        expect(screen.getByText('RWF 7,300,000')).toBeInTheDocument();
        expect(
            screen.getByText('Order 114 from Simba Supermarket'),
        ).toBeInTheDocument();
        expect(screen.getAllByText('Yes')).toHaveLength(1);
        expect(screen.getAllByText('No')).toHaveLength(3);
    });

    it('leaves the order line out of a send that needed none', () => {
        render(
            <BusinessMonthlyFigures
                {...props({
                    sent: {
                        money_in: '1',
                        costs: {
                            stock: '0',
                            staff: '0',
                            premises: '0',
                            transport: '0',
                            taxes: '0',
                            other: '0',
                        },
                        costs_total: '0',
                        paid_to_owners: '0',
                        forecast: '10000000',
                        forecast_basis: '10000000',
                        order: null,
                        changes: {
                            owners: false,
                            premises: false,
                            borrowing: false,
                            customers: false,
                        },
                        submitted_at: '2026-10-02T08:00:00Z',
                    },
                })}
            />,
        );

        expect(screen.queryByText('Signed order')).not.toBeInTheDocument();
        expect(screen.getAllByText('No')).toHaveLength(4);
    });
});
