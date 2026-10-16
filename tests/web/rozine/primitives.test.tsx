/* eslint-disable testing-library/no-node-access -- SVG internals (stroke paint, gradient stops) have no accessible role to query by. */
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import {
    FieldError,
    PrimaryButton,
    TextAction,
    TextField,
} from '@/components/rozine/form';
import { Icon, IconGradients } from '@/components/rozine/icon';
import { ICON_SHAPES } from '@/components/rozine/icon-shapes';

describe('Rozine icons', () => {
    it('paints with the audience gradient, drawing mass and solid fills from the design set', () => {
        render(
            <span data-testid="icon">
                <Icon name="warning" tone="red" />
            </span>,
        );

        const svg = screen.getByTestId('icon').firstElementChild;

        expect(svg).toHaveAttribute('stroke', 'url(#rz-g-red)');
        expect(svg).toHaveAttribute('aria-hidden', 'true');
        expect(ICON_SHAPES.warning.some(([, , fill]) => fill === 'solid')).toBe(
            true,
        );
    });

    it('paints white icons for dark tiles', () => {
        render(
            <span data-testid="icon">
                <Icon name="check-badge" tone="white" className="size-4" />
            </span>,
        );

        expect(screen.getByTestId('icon').firstElementChild).toHaveAttribute(
            'stroke',
            '#ffffff',
        );
        expect(screen.getByTestId('icon').firstElementChild).toHaveClass(
            'size-4',
        );
    });

    it('declares the canonical gradients, with the Business app overriding blue and red', () => {
        const { rerender } = render(
            <span data-testid="defs">
                <IconGradients />
            </span>,
        );
        const stopsOf = (tone: string) =>
            [
                ...screen
                    .getByTestId('defs')
                    .querySelectorAll(`#rz-g-${tone} stop`),
            ].map((stop) => stop.getAttribute('stop-color'));

        expect(stopsOf('blue')).toEqual(['#5b74ff', '#1832d6']);
        expect(stopsOf('red')).toEqual(['#ff8285', '#e5484d']);

        rerender(
            <span data-testid="defs">
                <IconGradients app="business" />
            </span>,
        );

        expect(stopsOf('blue')).toEqual(['#5b74ff', '#1e3aff']);
        expect(stopsOf('red')).toEqual(['#ff8285', '#b3383c']);
        expect(stopsOf('green')).toEqual(['#3fcda0', '#1d9e75']);
    });
});

describe('Rozine form primitives', () => {
    it('renders nothing for an absent field error', () => {
        render(
            <div data-testid="slot">
                <FieldError id="x" />
            </div>,
        );

        expect(screen.getByTestId('slot')).toBeEmptyDOMElement();
    });

    it('greys out a genuinely unavailable action but not a busy one', () => {
        render(
            <>
                <PrimaryButton disabled>Continue</PrimaryButton>
                <PrimaryButton size="lg" type="button">
                    Pay
                </PrimaryButton>
            </>,
        );

        expect(
            screen.getByRole('button', { name: 'Continue' }),
        ).toHaveAttribute('data-inactive', 'true');
        expect(screen.getByRole('button', { name: 'Pay' })).toHaveClass(
            'h-[54px]',
        );
        expect(screen.getByRole('button', { name: 'Pay' })).not.toHaveAttribute(
            'aria-busy',
        );
    });

    it('marks invalid fields and keeps text actions from submitting', () => {
        render(
            <form>
                <TextField aria-label="code" invalid />
                <TextField aria-label="name" />
                <TextAction>Resend</TextAction>
            </form>,
        );

        expect(screen.getByLabelText('code')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(screen.getByLabelText('name')).not.toHaveAttribute(
            'aria-invalid',
        );
        expect(screen.getByRole('button', { name: 'Resend' })).toHaveAttribute(
            'type',
            'button',
        );
    });
});
