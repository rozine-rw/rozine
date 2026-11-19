import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { StepBar } from '@/components/auditor/audit/step-bar';

vi.mock('@inertiajs/react', () => import('./inertia'));

describe('Audit procedure step bar (QA #685)', () => {
    it('opens a done step from its name, and leaves the current and later steps as plain text', () => {
        render(
            <StepBar
                steps={[
                    {
                        key: 'costs',
                        state: 'done',
                        link: {
                            url: '/auditor/reports/r1?step=costs',
                            method: 'get',
                        },
                    },
                    { key: 'loan_use', state: 'current', link: null },
                    { key: 'photos', state: 'todo' },
                ]}
            />,
        );

        expect(screen.getByRole('link', { name: 'Costs' })).toHaveAttribute(
            'href',
            '/auditor/reports/r1?step=costs',
        );
        expect(
            screen.queryByRole('link', { name: 'Loan money' }),
        ).not.toBeInTheDocument();
        expect(screen.getAllByRole('link')).toHaveLength(1);
    });
});
