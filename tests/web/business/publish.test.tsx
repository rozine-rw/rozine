import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import BusinessPublish from '@/pages/business/publish';
import type { BusinessPublishProps } from '@/types/business';
import paidFixture from '../../../resources/fixtures/ui/business-publish-paid.json';
import freeFixture from '../../../resources/fixtures/ui/business-publish.json';

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

            return {
                data,
                setData: (key: keyof T, value: unknown) =>
                    setState((current) => ({ ...current, [key]: value })),
                errors: inertia.errors,
                processing: inertia.processing,
                post: (url: string) => inertia.posts.push({ url, data }),
            };
        },
    };
});

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessPublishProps;

beforeEach(() => {
    inertia.posts = [];
    inertia.errors = {};
    inertia.processing = false;
});

describe('Publish to the Investor feed', () => {
    it('discloses a zero fee and publishes without asking how to pay', async () => {
        const user = userEvent.setup();

        render(<BusinessPublish {...props(freeFixture)} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Publish to the Investor feed',
        });

        expect(sheet).toHaveTextContent(
            'Warehouse Robotics passed vetting. There is no application fee to pay',
        );
        expect(within(sheet).getByText('RWF 30,000,000')).toBeInTheDocument();
        expect(within(sheet).getByText('RWF 0')).toBeInTheDocument();
        expect(within(sheet).queryByRole('radiogroup')).not.toBeInTheDocument();
        expect(
            within(sheet).getByRole('link', { name: 'Not yet' }),
        ).toHaveAttribute('href', '/preview/business-home');
        expect(screen.getByRole('link', { name: 'Close' })).toHaveAttribute(
            'href',
            '/preview/business-home',
        );

        await user.click(
            within(sheet).getByRole('button', { name: 'Publish' }),
        );

        expect(inertia.posts).toEqual([
            { url: '/preview/business-home', data: { source: null } },
        ]);
    });

    it('asks how to pay a charged fee and sends the chosen source', async () => {
        const user = userEvent.setup();

        render(<BusinessPublish {...props(paidFixture)} />);

        const sources = screen.getByRole('radiogroup', { name: 'Pay with' });

        expect(screen.getByText('RWF 300,000')).toBeInTheDocument();
        expect(
            within(sources).getByRole('radio', { name: 'Wallet' }),
        ).toBeChecked();
        expect(
            screen.getByText('Rozine Wallet · RWF 12,383,800 available'),
        ).toBeInTheDocument();

        await user.click(
            within(sources).getByRole('radio', { name: 'MTN MoMo' }),
        );

        expect(
            screen.getByText(
                'MTN Mobile Money · +250 788 ···· 211 · charged instantly',
            ),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Pay & publish' }));

        expect(inertia.posts).toEqual([
            { url: '/preview/business-home', data: { source: 'mtn' } },
        ]);
    });

    it('shows progress while publishing and a payment refusal', () => {
        inertia.processing = true;
        inertia.errors = {
            source: 'Insufficient wallet balance — pick MoMo or card',
        };
        render(<BusinessPublish {...props(paidFixture)} />);

        expect(
            screen.getByRole('button', { name: 'Publishing…' }),
        ).toBeDisabled();
        expect(
            screen.getByText('Insufficient wallet balance — pick MoMo or card'),
        ).toBeInTheDocument();
    });
});
