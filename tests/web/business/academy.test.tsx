import { render, screen, within } from '@testing-library/react';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import BusinessAcademy from '@/pages/business/academy';
import type { BusinessAcademyProps } from '@/types/business';
import fixture from '../../../resources/fixtures/ui/business-academy.json';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({
        href,
        children,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & {
        href: string | { url: string };
    }) => (
        <a href={typeof href === 'string' ? href : href.url} {...props}>
            {children}
        </a>
    ),
    useHttp: () => ({ submit: () => new Promise(() => undefined) }),
}));

const props = (
    overrides: Partial<BusinessAcademyProps> = {},
): BusinessAcademyProps => ({
    ...(structuredClone(fixture.props) as BusinessAcademyProps),
    ...overrides,
});

describe('Academy', () => {
    it("lists the stage's core with where each test stands, and the next stage as a preview", () => {
        render(<BusinessAcademy {...props()} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Academy' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Short lessons for your stage. Each module is a set of flashcards, then a test of 10 questions. 8 correct answers pass.',
            ),
        ).toBeInTheDocument();
        const core = within(
            screen.getByRole('list', {
                name: 'Core modules for Stage 1 · Foundation',
            }),
        ).getAllByRole('listitem');

        expect(core[0]).toHaveTextContent(
            'Keeping your booksPassedRecord every sale and every payment on the day it happens.9 cards · Best score: 9 of 10',
        );
        expect(core[1]).toHaveTextContent(
            /^Business money, tax and RSSBRetake from .+8 cards · Best score: 6 of 10$/u,
        );
        expect(within(core[0]).getByRole('link')).toHaveAttribute(
            'href',
            '/preview/business-academy-module',
        );

        const preview = within(
            screen.getByRole('list', { name: 'Preview of Stage 2 · Growth' }),
        ).getAllByRole('listitem');

        expect(preview[0]).toHaveTextContent(
            'Accountant and management accountsPreviewWork with a qualified accountant and use accounting software.8 cards',
        );
        expect(
            screen.getByText(
                'Cards only. The test opens when you reach this stage.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/business-readiness',
        );
    });

    it('shows an open test with no score yet, and no preview at the top stage', () => {
        const open = props();
        open.core[0].status = {
            state: 'open',
            attempts: 0,
            best: null,
            retake_at: null,
        };

        render(<BusinessAcademy {...open} stage={4} level={4} preview={[]} />);

        expect(
            screen.getByRole('list', {
                name: 'Core modules for Stage 4 · Market',
            }),
        ).toHaveTextContent('Keeping your booksTest open');
        expect(screen.queryByText(/^Preview of/u)).not.toBeInTheDocument();
        expect(
            within(
                screen.getByRole('list', {
                    name: 'Core modules for Stage 4 · Market',
                }),
            ).getAllByRole('listitem')[0],
        ).not.toHaveTextContent('Best score');
    });
});
