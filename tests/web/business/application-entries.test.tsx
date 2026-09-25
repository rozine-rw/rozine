import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import RoleHome from '@/pages/identity/role-home';
import type { BusinessApplications } from '@/types/business';
import type { IdentityContext, MarketplaceRole } from '@/types/identity';
import roleHomeFixture from '../../../resources/fixtures/ui/role-home-business.json';

type Responder = (options: {
    onHttpException?: (response: {
        status: number;
        data: string;
        headers: Record<string, string>;
    }) => void;
}) => Promise<unknown>;

const inertia = vi.hoisted(() => ({
    calls: [] as { url: string; method: string; body: unknown }[],
    body: undefined as unknown,
    queue: [] as Responder[],
    visit: vi.fn(),
    reload: vi.fn(),
}));

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
    router: {
        visit: inertia.visit,
        reload: inertia.reload,
        on: () => () => undefined,
    },
    useHttp: () => ({
        processing: false,
        errors: {},
        clearErrors: () => undefined,
        transform: (callback: () => unknown) => {
            inertia.body = callback();
        },
        submit: (
            route: { url: string; method: string },
            options: Parameters<Responder>[0],
        ) => {
            inertia.calls.push({ ...route, body: inertia.body });
            const respond = inertia.queue.shift();

            return respond ? respond(options) : new Promise(() => undefined);
        },
    }),
}));

type RoleHomeProps = {
    identity: IdentityContext;
    role: MarketplaceRole;
    section: 'overview' | 'access';
    business_applications: BusinessApplications | null;
};

const props = () => structuredClone(roleHomeFixture.props) as RoleHomeProps;

const KIGALI = '01k6q1a2b3c4d5e6f7g8h9j0k1';

const link = (url: string) => ({ url, method: 'get' as const });

const resource = (data: unknown, code = 'APPLICATION_CREATED') => ({
    operation_id: 'op-1',
    status: 'completed',
    code,
    data,
    revision: 1,
    policy_version: 'engineering-2026-09-23.4',
    recorded_at: '2026-09-25T09:16:00+02:00',
    server_time: '2026-09-25T09:16:00+02:00',
    allowed_actions: [],
    field_errors: {},
});

const fails =
    (status: number, code?: string): Responder =>
    (options) => {
        options.onHttpException?.({
            status,
            data: code === undefined ? '' : JSON.stringify({ code }),
            headers: {},
        });

        return Promise.reject(new Error(`HTTP ${status}`));
    };

const entryFor = (name: string) => screen.getByRole('listitem', { name });

beforeEach(() => {
    inertia.calls = [];
    inertia.body = undefined;
    inertia.queue = [];
    inertia.visit.mockReset();
    inertia.reload.mockReset();
});

describe('Business role landing — raise applications', () => {
    it('lists each business with the way in its state allows, and nothing financial', () => {
        render(<RoleHome {...props()} />);

        const section = screen.getByRole('region', {
            name: 'Raise applications',
        });

        expect(within(section).getAllByRole('listitem')).toHaveLength(5);
        expect(
            within(entryFor('Kigali Fresh Foods')).getByRole('button', {
                name: 'Apply for a raise',
            }),
        ).toBeEnabled();

        const draft = entryFor('GreenLeaf Agro');

        expect(
            within(draft).getByText('Saved at Your raise'),
        ).toBeInTheDocument();
        expect(
            within(draft).getByRole('link', {
                name: 'Continue your application',
            }),
        ).toHaveAttribute(
            'href',
            '/business/01k6p4b7r2c9d3f8g1h5j0k6m2/applications/01k6p4c8s3d0f4g9h2j6k1m7n3',
        );

        const submitted = entryFor('Huye Motors');

        expect(
            within(submitted).getByText('Submitted · under review'),
        ).toBeInTheDocument();
        expect(
            within(submitted).getByRole('link', {
                name: 'View your application',
            }),
        ).toHaveAttribute(
            'href',
            '/business/01k6q2m3n4p5q6r7s8t9v0w1x2/applications/01k6q2m3n4p5q6r7s8t9v0w1x3',
        );
        expect(within(submitted).queryByRole('button')).not.toBeInTheDocument();

        const viewOnly = entryFor('Nyamirambo Crafts');

        expect(within(viewOnly).queryByRole('button')).not.toBeInTheDocument();
        expect(
            within(viewOnly).getByText(
                'You can view this business, but starting a raise is not open to you.',
            ),
        ).toBeInTheDocument();

        const viewOnlyDraft = entryFor('Musanze Dairy');

        expect(
            within(viewOnlyDraft).getByText('Saved at Review & sign'),
        ).toBeInTheDocument();
        expect(
            within(viewOnlyDraft).getByRole('link', {
                name: 'Continue your application',
            }),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Show more' })).toHaveAttribute(
            'href',
            `/business?cursor=01k6q4j5k6m7n8p9q0r1s2t3v4`,
        );
        expect(screen.queryByText(/RWF/u)).not.toBeInTheDocument();
    });

    it('offers no start without the server’s create action, and names no unknown step', () => {
        const page = props();
        const [first, second] = page.business_applications!.entries;

        page.business_applications!.entries = [
            { ...first, actions: { create: null } },
            {
                ...second,
                application: { ...second.application!, step: 'business' },
            },
            {
                ...second,
                business_id: 'other',
                name: 'Other Draft',
                application: { ...second.application!, step: 'elsewhere' },
            },
        ];
        page.business_applications!.pagination = { next: null };
        render(<RoleHome {...page} />);

        expect(
            screen.queryByRole('button', { name: 'Apply for a raise' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText('Saved at Business & finances'),
        ).toBeInTheDocument();
        expect(
            within(entryFor('Other Draft')).queryByText(/^Saved at/u),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Show more' }),
        ).not.toBeInTheDocument();
    });

    it('says so honestly when no business is listed', () => {
        const page = props();

        page.business_applications!.entries = [];
        page.business_applications!.pagination = { next: null };
        render(<RoleHome {...page} />);

        expect(
            screen.getByText(
                'No business you can act for is linked to this account yet.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('list')).not.toBeInTheDocument();
    });

    it('reads the entries afresh with the identity on focus, so a withdrawn entry leaves', () => {
        render(<RoleHome {...props()} />);

        window.dispatchEvent(new Event('focus'));

        expect(inertia.reload).toHaveBeenCalledWith(
            expect.objectContaining({
                only: ['identity', 'business_applications'],
            }),
        );
    });

    it('shows no applications section for another role', () => {
        const page = props();

        render(
            <RoleHome
                identity={page.identity}
                role="investor"
                section="overview"
            />,
        );

        expect(
            screen.queryByRole('region', { name: 'Raise applications' }),
        ).not.toBeInTheDocument();
    });

    it('creates the application with the common envelope and follows next', async () => {
        const user = userEvent.setup();

        inertia.queue.push(() =>
            Promise.resolve(
                resource({ next: link('/business/x/applications/y') }),
            ),
        );
        render(<RoleHome {...props()} />);

        await user.click(
            within(entryFor('Kigali Fresh Foods')).getByRole('button', {
                name: 'Apply for a raise',
            }),
        );

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith(
                link('/business/x/applications/y'),
            ),
        );
        expect(inertia.calls).toEqual([
            {
                url: `/business/${KIGALI}/applications`,
                method: 'post',
                body: {
                    identity_context_revision: 4,
                    expected_revision: 0,
                    request_id: expect.any(String),
                },
            },
        ]);
    });

    it('follows next when the server resumes an existing draft instead', async () => {
        const user = userEvent.setup();

        inertia.queue.push(() =>
            Promise.resolve(
                resource(
                    { next: link('/business/x/applications/draft') },
                    'APPLICATION_RESUMED',
                ),
            ),
        );
        render(<RoleHome {...props()} />);
        await user.click(
            screen.getByRole('button', { name: 'Apply for a raise' }),
        );

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith(
                link('/business/x/applications/draft'),
            ),
        );
    });

    it('recovers a lost answer through the lookup and follows its next', async () => {
        const user = userEvent.setup();

        inertia.queue.push(fails(503), () =>
            Promise.resolve(
                resource({ next: link('/business/x/applications/y') }),
            ),
        );
        render(<RoleHome {...props()} />);
        await user.click(
            screen.getByRole('button', { name: 'Apply for a raise' }),
        );

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith(
                link('/business/x/applications/y'),
            ),
        );

        const requestId = (inertia.calls[0].body as { request_id: string })
            .request_id;

        expect(inertia.calls[1]).toEqual({
            url: `/business/application-operations/${requestId}`,
            method: 'get',
            body: { command: 'create', identity_context_revision: 4 },
        });
    });

    it('shows a refusal in the Business outcome copy and refreshes stale facts', async () => {
        const user = userEvent.setup();

        inertia.queue.push(fails(409, 'VERSION_CONFLICT'));
        render(<RoleHome {...props()} />);
        await user.click(
            screen.getByRole('button', { name: 'Apply for a raise' }),
        );

        expect(await screen.findByRole('alert')).toBeInTheDocument();
        expect(inertia.reload).toHaveBeenCalled();
        expect(inertia.visit).not.toHaveBeenCalled();
    });
});
