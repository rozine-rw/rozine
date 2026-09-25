import { render, screen } from '@testing-library/react';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import RoleHome from '@/pages/identity/role-home';
import auditorFixture from '../../../resources/fixtures/ui/role-home-auditor-engagement-required.json';
import businessFixture from '../../../resources/fixtures/ui/role-home-business.json';

const inertia = vi.hoisted(() => ({ reload: vi.fn() }));

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
    router: { visit: vi.fn(), reload: inertia.reload },
    useHttp: () => ({
        processing: false,
        errors: {},
        transform: () => undefined,
        submit: () => new Promise(() => undefined),
    }),
}));

type Props = ComponentProps<typeof RoleHome>;

const props = (fixture: { props: unknown }): Props =>
    structuredClone(fixture.props) as Props;

const REQUIRED = 'Review and accept the engagement terms to take new work';

describe('The Auditor role home', () => {
    it('links to the agreement page while acceptance is required', () => {
        render(<RoleHome {...props(auditorFixture)} />);

        expect(
            screen.getByRole('link', { name: new RegExp(REQUIRED, 'u') }),
        ).toHaveAttribute('href', '/preview/auditor-engagement');
    });

    it('reads the engagement summary afresh with the identity on focus', () => {
        render(<RoleHome {...props(auditorFixture)} />);

        window.dispatchEvent(new Event('focus'));

        expect(inertia.reload).toHaveBeenCalledWith(
            expect.objectContaining({
                only: ['identity', 'business_applications', 'engagement'],
            }),
        );
    });

    it('shows no engagement banner on a role home that is not the Auditor’s', () => {
        render(<RoleHome {...props(businessFixture)} engagement={null} />);

        expect(
            screen.queryByRole('link', { name: new RegExp(REQUIRED, 'u') }),
        ).not.toBeInTheDocument();
    });
});
