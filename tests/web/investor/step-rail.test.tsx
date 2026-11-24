import { fireEvent, render, screen, within } from '@testing-library/react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { StepRail } from '@/components/investor/auth/step-rail';

/*
 * The desktop rail beside a long KYC/KYB form: the whole journey at once, so a wide screen is used
 * for orientation instead of a narrow centred column (Erastus, 24 Nov 2026).
 */
describe('Step rail', () => {
    const steps = [
        { key: 'personal', label: 'Personal details' },
        { key: 'document', label: 'Verify your ID' },
        { key: 'liveness', label: 'Liveness check' },
    ];

    it('marks done, current and upcoming steps and says where the person is', () => {
        render(<StepRail kicker="Identity check" steps={steps} current={1} />);

        const rail = screen.getByRole('navigation', { name: 'Steps' });
        expect(within(rail).getByText('Step 2 of 3')).toBeInTheDocument();
        const items = within(rail).getAllByRole('listitem');
        expect(items[0]).toHaveTextContent('✓Personal details');
        expect(items[1]).toHaveAttribute('aria-current', 'step');
        expect(items[2]).toHaveTextContent('3Liveness check');
    });

    it('opens a finished step when the form allows going back', () => {
        const open = vi.fn();
        render(
            <StepRail
                kicker="Identity check"
                steps={steps}
                current={2}
                onOpen={open}
            />,
        );

        fireEvent.click(
            screen.getByRole('button', { name: /Verify your ID/u }),
        );
        expect(open).toHaveBeenCalledWith('document');
        expect(
            screen.queryByRole('button', { name: /Liveness check/u }),
        ).not.toBeInTheDocument();
    });

    it('shows every step done once the form is sent', () => {
        render(<StepRail kicker="Identity check" steps={steps} current={3} />);

        expect(screen.getByText('Step 3 of 3')).toBeInTheDocument();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(
            screen
                .getAllByRole('listitem')
                .every((item) => item.textContent?.startsWith('✓')),
        ).toBe(true);
    });
});
