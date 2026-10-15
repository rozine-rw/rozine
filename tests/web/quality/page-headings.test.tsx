/* eslint-disable testing-library/no-node-access -- jsdom applies no stylesheet, so phone visibility is read from the Tailwind classes on a heading's ancestors. */
import { render, screen } from '@testing-library/react';
import type { ComponentType } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';

vi.mock('@inertiajs/react', async () => {
    const investor = await import('../investor/inertia-mock');
    const auditor = await import('../auditor/inertia');

    return { ...investor, ...auditor, Form: investor.Form };
});

vi.setConfig({ testTimeout: 30_000 });

const fixtures = import.meta.glob<{ component: string; props: object }>(
    '../../../resources/fixtures/ui/*.json',
    { eager: true, import: 'default' },
);
const pages = import.meta.glob<{ default: ComponentType<object> }>(
    '../../../resources/js/pages/**/*.tsx',
);

/** The first preview fixture of every page, so each page renders once. */
const firstFixture = new Map<string, object>();

for (const [, fixture] of Object.entries(fixtures).sort(([a], [b]) =>
    a.localeCompare(b),
)) {
    if (!firstFixture.has(fixture.component)) {
        firstFixture.set(fixture.component, fixture.props);
    }
}

/** Hidden on a phone by a Tailwind class on it or an ancestor (jsdom applies no stylesheet). */
const hiddenOnPhone = (element: Element | null): boolean =>
    element !== null &&
    (element.classList.contains('max-lg:hidden') ||
        element.classList.contains('hidden') ||
        hiddenOnPhone(element.parentElement));

describe('Every page names itself with one top heading (WCAG 2.2 SC 1.3.1, 2.4.6)', () => {
    it('renders every page that has a preview fixture', () => {
        expect(firstFixture.size).toBeGreaterThan(50);
    });

    it.each([...firstFixture.keys()])(
        'gives %s exactly one h1 on a phone',
        async (component) => {
            const loader =
                pages[`../../../resources/js/pages/${component}.tsx`];
            const { default: Page } = await loader();

            render(<Page {...structuredClone(firstFixture.get(component))} />);

            const titles = screen
                .queryAllByRole('heading', { level: 1 })
                .filter((heading) => !hiddenOnPhone(heading));

            expect(titles.map((heading) => heading.textContent)).toHaveLength(
                1,
            );
        },
    );
});
