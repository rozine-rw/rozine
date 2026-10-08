import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminPendingSection from '@/pages/admin/section';
import type { AdminPendingSectionProps } from '@/types/admin';
import sectionFixture from '../../../resources/fixtures/ui/admin-section.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (overrides: Partial<AdminPendingSectionProps> = {}) => ({
    ...(structuredClone(sectionFixture.props) as AdminPendingSectionProps),
    ...overrides,
});

beforeEach(resetInertia);

describe('A pending design section', () => {
    it('opens the console frame with its title, its sidebar entry lit and an empty state', () => {
        render(<AdminPendingSection {...props()} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Notes');
        expect(
            within(
                screen.getByRole('navigation', { name: 'Console navigation' }),
            ).getByRole('link', { name: 'Notes' }),
        ).toHaveAttribute('aria-current', 'page');
        const section = screen.getByRole('region', { name: 'Notes' });
        expect(
            within(section).getByText('Nothing here yet'),
        ).toBeInTheDocument();
        expect(
            within(section).getByText(
                /isn’t connected yet|isn't connected yet/u,
            ),
        ).toBeInTheDocument();
    });

    it('names a designed screen whose live data is still being wired', () => {
        render(<AdminPendingSection {...props({ section: 'businesses' })} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Business Directory',
        );
        expect(
            screen.getByRole('region', { name: 'Business Directory' }),
        ).toBeInTheDocument();
    });
});
