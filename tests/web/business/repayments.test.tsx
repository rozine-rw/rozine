import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import BusinessRepayments from '@/pages/business/repayments';
import type { BusinessRepaymentsProps } from '@/types/business';
import aheadFixture from '../../../resources/fixtures/ui/business-repayments-ahead.json';
import defaultedFixture from '../../../resources/fixtures/ui/business-repayments-defaulted.json';
import overdueFixture from '../../../resources/fixtures/ui/business-repayments-overdue.json';
import paidFixture from '../../../resources/fixtures/ui/business-repayments-paid.json';
import dueFixture from '../../../resources/fixtures/ui/business-repayments.json';

const inertia = vi.hoisted(() => ({
    posts: [] as { url: string; data: Record<string, unknown> }[],
    errors: {} as Record<string, string>,
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
        }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
            <a href={href.url} {...props}>
                {children}
            </a>
        ),
        useForm: <T extends Record<string, unknown>>(initial: T) => {
            const [data, setState] = useState(initial);
            const [box] = useState(() => ({
                transform: (current: T): T => current,
            }));

            return {
                data,
                setData: (
                    key: keyof T | ((current: T) => T),
                    value?: unknown,
                ) =>
                    setState((current) =>
                        typeof key === 'function'
                            ? key(current)
                            : { ...current, [key]: value },
                    ),
                transform: (callback: (current: T) => T) => {
                    box.transform = callback;
                },
                errors: inertia.errors,
                processing: inertia.processing,
                post: (url: string) =>
                    inertia.posts.push({ url, data: box.transform(data) }),
            };
        },
    };
});

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessRepaymentsProps;

const repayments = () => screen.getByRole('dialog', { name: 'Repayments' });

beforeEach(() => {
    inertia.posts = [];
    inertia.errors = {};
    inertia.processing = false;
});

describe('Repayments', () => {
    it('shows progress and this month’s payment, and pays from the chosen source', async () => {
        const user = userEvent.setup();

        render(<BusinessRepayments {...props(dueFixture)} />);

        const page = within(repayments());

        expect(
            page.getByRole('progressbar', { name: 'Repayment progress' }),
        ).toHaveValue(60);
        expect(page.getByText('3 / 5 payments')).toBeInTheDocument();
        expect(page.getByText('over 2 months')).toBeInTheDocument();
        expect(page.getByText('RWF 27,750,000')).toBeInTheDocument();
        expect(page.getByText("This month's payment")).toBeInTheDocument();
        expect(
            page.getByText('Due 5 Oct 2026 · in 12 days'),
        ).toBeInTheDocument();
        expect(
            page.getByText(
                'Repayments are due on the 5th of every month — the day your funds were disbursed — until fully repaid.',
            ),
        ).toBeInTheDocument();
        expect(
            page.getByRole('radio', { name: /Rozine Wallet/ }),
        ).toBeChecked();

        await user.click(page.getByRole('radio', { name: /Bank transfer/ }));
        await user.click(
            page.getByRole('button', {
                name: 'Confirm payment · RWF 5,550,000',
            }),
        );

        expect(inertia.posts).toEqual([
            {
                url: '/preview/business-repayments-paid',
                data: { source: 'bank' },
            },
        ]);
        expect(
            page.getAllByRole('listitem').map((item) => item.textContent),
        ).toEqual([
            'RWF 5,550,000Paid · 5 Jul 2026Paid',
            'RWF 5,550,000Paid · 5 Aug 2026Paid',
            'RWF 5,550,000Paid · 5 Sept 2026Paid',
            'RWF 5,550,000Due · 5 Oct 2026Due',
            'RWF 5,550,0005 Nov 2026Upcoming',
        ]);
    });

    it('shows a payment in progress and a refused source', () => {
        inertia.processing = true;
        inertia.errors = { source: 'Insufficient wallet balance' };
        render(<BusinessRepayments {...props(dueFixture)} />);

        expect(
            within(repayments()).getByRole('button', { name: 'Processing…' }),
        ).toBeDisabled();
        expect(
            within(repayments()).getByText('Insufficient wallet balance'),
        ).toBeInTheDocument();
    });

    it('pays ahead by the next instalments, the whole balance or a custom amount', async () => {
        const user = userEvent.setup();

        render(<BusinessRepayments {...props(dueFixture)} />);

        const toggle = within(repayments()).getByRole('button', {
            name: /Get ahead on repayments/,
        });

        await user.click(toggle);

        const choices = within(
            within(repayments()).getByRole('radiogroup', {
                name: 'Choose an amount',
            }),
        );

        expect(
            choices.getByRole('radio', { name: /Next 1 month/ }),
        ).toBeChecked();

        await user.click(
            within(repayments()).getByRole('button', {
                name: 'Pay ahead · RWF 5,550,000',
            }),
        );
        await user.click(
            choices.getByRole('radio', { name: /Settle in full/ }),
        );
        await user.click(
            within(repayments()).getByRole('button', {
                name: 'Settle in full · RWF 11,100,000',
            }),
        );

        const amount = within(repayments()).getByRole('textbox', {
            name: 'Custom amount',
        });

        await user.click(amount);

        expect(
            within(repayments()).getByRole('button', {
                name: 'Enter an amount',
            }),
        ).toBeDisabled();

        await user.type(amount, '0020000000');

        expect(amount).toHaveValue('20,000,000');
        expect(amount).toHaveAttribute('aria-invalid', 'true');
        expect(
            within(repayments()).getByText(
                'Amount exceeds what you owe (RWF 11,100,000)',
            ),
        ).toBeInTheDocument();

        await user.clear(amount);
        await user.type(amount, '2000000');
        await user.click(
            within(repayments()).getByRole('button', {
                name: 'Pay ahead · RWF 2,000,000',
            }),
        );

        expect(inertia.posts).toEqual([
            {
                url: '/preview/business-repayments-paid',
                data: { choice: 'next', amount: '', source: 'wallet' },
            },
            {
                url: '/preview/business-repayments-paid',
                data: { choice: 'full', amount: '', source: 'wallet' },
            },
            {
                url: '/preview/business-repayments-paid',
                data: { choice: 'custom', amount: '2000000', source: 'wallet' },
            },
        ]);

        await user.click(toggle);

        expect(
            within(repayments()).queryByRole('radiogroup', {
                name: 'Choose an amount',
            }),
        ).not.toBeInTheDocument();
    });

    it('shows a refused pay-ahead amount', async () => {
        const user = userEvent.setup();

        inertia.errors = { amount: 'Pay at least RWF 1,000' };
        render(<BusinessRepayments {...props(dueFixture)} />);

        await user.click(
            within(repayments()).getByRole('button', {
                name: /Get ahead on repayments/,
            }),
        );

        expect(
            within(repayments()).getByText('Pay at least RWF 1,000'),
        ).toBeInTheDocument();
    });

    it('lays out the late-fee ladder the server applies', async () => {
        const user = userEvent.setup();

        render(<BusinessRepayments {...props(dueFixture)} />);

        await user.click(
            within(repayments()).getByRole('button', {
                name: 'If a payment is late',
            }),
        );

        expect(
            within(repayments()).getByText('Due day missed'),
        ).toBeInTheDocument();
        expect(within(repayments()).getAllByText('+10%')).toHaveLength(2);
        expect(
            within(repayments()).getByText('+5% · legal'),
        ).toBeInTheDocument();
        expect(
            within(repayments()).getByText('Total owed → RWF 6,937,500'),
        ).toBeInTheDocument();
        expect(
            within(repayments()).getByText('Profile & deals halted'),
        ).toBeInTheDocument();
        expect(
            within(repayments()).queryByRole('link', {
                name: 'Defer a payment',
            }),
        ).not.toBeInTheDocument();
        expect(
            within(repayments()).queryByRole('link', {
                name: 'Request a manual review',
            }),
        ).not.toBeInTheDocument();
    });

    it('flags an overdue payment and offers a manual review', async () => {
        const user = userEvent.setup();
        const page = props(overdueFixture);

        page.links.defer = { url: '/business/notes/defer', method: 'get' };
        render(<BusinessRepayments {...page} />);

        expect(
            within(repayments()).getByText('Overdue payment'),
        ).toBeInTheDocument();
        expect(
            within(repayments()).getByText(
                'Was due 5 Sept 2026 · 18 days late',
            ),
        ).toBeInTheDocument();
        expect(
            within(repayments()).getByText('Overdue · 5 Sept 2026'),
        ).toBeInTheDocument();
        expect(
            within(repayments()).getByText('over 3 months'),
        ).toBeInTheDocument();

        await user.click(
            within(repayments()).getByRole('button', {
                name: 'If a payment is late',
            }),
        );

        expect(
            within(repayments()).getByRole('link', { name: 'Defer a payment' }),
        ).toHaveAttribute('href', '/business/notes/defer');
        expect(
            within(repayments()).getByRole('link', {
                name: 'Request a manual review',
            }),
        ).toHaveAttribute('href', '/preview/business-home');
    });

    it('reads a single day late and a single month left', () => {
        const page = props(overdueFixture);

        page.this_month.days = 1;
        page.progress.remaining_months = 1;
        render(<BusinessRepayments {...page} />);

        expect(
            within(repayments()).getByText('Was due 5 Sept 2026 · 1 day late'),
        ).toBeInTheDocument();
        expect(
            within(repayments()).getByText('over 1 month'),
        ).toBeInTheDocument();
    });

    it('says when nothing is due after paying ahead', () => {
        const page = props(aheadFixture);

        page.this_month.days = 1;
        render(<BusinessRepayments {...page} />);

        expect(
            within(repayments()).getByText('Next payment'),
        ).toBeInTheDocument();
        expect(
            within(repayments()).getByText(
                'Paid ahead — nothing due until 5 Nov 2026',
            ),
        ).toBeInTheDocument();
        expect(
            within(repayments()).queryByRole('button', {
                name: /Confirm payment/,
            }),
        ).not.toBeInTheDocument();
    });

    it('names several instalments paid ahead', async () => {
        const user = userEvent.setup();
        const page = props(dueFixture);

        page.pay_ahead = {
            options: [
                {
                    key: 'next',
                    months: 3,
                    amount: { currency: 'RWF', amount: '16650000' },
                },
            ],
            max: { currency: 'RWF', amount: '16650000' },
        };
        render(<BusinessRepayments {...page} />);

        await user.click(
            within(repayments()).getByRole('button', {
                name: /Get ahead on repayments/,
            }),
        );

        expect(
            within(repayments()).getByText('Next 3 months'),
        ).toBeInTheDocument();
        expect(
            within(repayments()).getByText('Pay 3 payments upfront'),
        ).toBeInTheDocument();
    });

    it('offers only settling the last payment', async () => {
        const user = userEvent.setup();

        render(<BusinessRepayments {...props(aheadFixture)} />);

        await user.click(
            within(repayments()).getByRole('button', {
                name: /Get ahead on repayments/,
            }),
        );

        expect(
            within(repayments()).getByText('One payment left'),
        ).toBeInTheDocument();
    });

    it('marks a defaulted note and a due tomorrow payment', () => {
        render(<BusinessRepayments {...props(defaultedFixture)} />);

        expect(
            within(repayments()).getByText('Amount owed now'),
        ).toBeInTheDocument();
        expect(
            within(repayments()).getByText(
                'This note is in default — legal recovery has begun',
            ),
        ).toBeInTheDocument();
        expect(
            within(repayments()).queryByRole('button', { name: /Get ahead/ }),
        ).not.toBeInTheDocument();
    });

    it('counts down the last day and marks a fully repaid note', () => {
        const page = props(dueFixture);

        page.this_month.days = 1;
        page.progress.remaining_months = 0;
        render(<BusinessRepayments {...page} />);

        expect(
            within(repayments()).getByText('Due 5 Oct 2026 · in 1 day'),
        ).toBeInTheDocument();
        expect(
            within(repayments()).getByText('Fully repaid'),
        ).toBeInTheDocument();
    });

    it('confirms a processed payment', () => {
        render(<BusinessRepayments {...props(paidFixture)} />);

        expect(within(repayments()).getByRole('status')).toHaveTextContent(
            'Payment processed',
        );
        expect(
            within(repayments()).getByText(
                'RWF 5,550,000 paid to 647 investors.',
            ),
        ).toBeInTheDocument();
        expect(within(repayments()).getByText('4 / 5')).toBeInTheDocument();
        expect(
            within(repayments()).getByRole('link', {
                name: 'Back to dashboard',
            }),
        ).toHaveAttribute('href', '/preview/business-home');
    });
});
