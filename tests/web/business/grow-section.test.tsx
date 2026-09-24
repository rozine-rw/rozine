import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { GrowSection } from '@/components/business/home/grow-section';
import type { CreateApplicationEntry } from '@/types/business';

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
    Link: ({
        href,
        children,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
        <a href={href.url} {...props}>
            {children}
        </a>
    ),
    router: { visit: inertia.visit, reload: inertia.reload },
    useHttp: () => ({
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

const link = (url: string) => ({ url, method: 'get' as const });

const entry: CreateApplicationEntry = {
    action: { url: '/business/applications', method: 'post' },
    operation: link('/business/operations/{request_id}'),
    identity_context_revision: 4,
    expected_revision: 0,
};

const resource = (data: unknown) => ({
    operation_id: 'op-1',
    status: 'completed',
    code: 'APPLICATION_CREATED',
    data,
    revision: 1,
    policy_version: 'engineering-2026-09-23.4',
    recorded_at: '2026-09-24T09:16:00+02:00',
    server_time: '2026-09-24T09:16:00+02:00',
    allowed_actions: ['application.save'],
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

const renderGrow = (
    apply: ReturnType<typeof link> | null,
    createApplication: CreateApplicationEntry | null,
) =>
    render(
        <GrowSection
            headroom={null}
            links={{ rating: link('/rating'), apply }}
            createApplication={createApplication}
        />,
    );

beforeEach(() => {
    inertia.calls = [];
    inertia.body = undefined;
    inertia.queue = [];
    inertia.visit.mockReset();
    inertia.reload.mockReset();
});

describe('Home — starting a raise', () => {
    it('resumes an open draft by link, without a command', () => {
        renderGrow(link('/business/apply'), entry);

        expect(
            screen.getByRole('link', { name: 'Apply for a raise' }),
        ).toHaveAttribute('href', '/business/apply');
        expect(
            screen.queryByRole('button', { name: 'Apply for a raise' }),
        ).not.toBeInTheDocument();
    });

    it('offers nothing when there is no draft and creating is not allowed', () => {
        renderGrow(null, null);

        expect(screen.queryByText('Apply for a raise')).not.toBeInTheDocument();
    });

    it('creates the application with the common command fields and follows next', async () => {
        const user = userEvent.setup();

        inertia.queue.push(() =>
            Promise.resolve(resource({ next: link('/business/apply') })),
        );
        renderGrow(null, entry);

        await user.click(
            screen.getByRole('button', { name: 'Apply for a raise' }),
        );

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith(link('/business/apply')),
        );
        expect(inertia.calls).toEqual([
            {
                url: '/business/applications',
                method: 'post',
                body: {
                    identity_context_revision: 4,
                    expected_revision: 0,
                    request_id: expect.any(String),
                },
            },
        ]);
    });

    it('follows next when a concurrent create resumed the existing draft instead', async () => {
        const user = userEvent.setup();

        inertia.queue.push(() =>
            Promise.resolve({
                ...resource({ next: link('/business/apply?resume=1') }),
                code: 'APPLICATION_RESUMED',
            }),
        );
        renderGrow(null, entry);

        await user.click(
            screen.getByRole('button', { name: 'Apply for a raise' }),
        );

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith(
                link('/business/apply?resume=1'),
            ),
        );
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('shows that it is starting while the command runs', async () => {
        const user = userEvent.setup();

        renderGrow(null, entry);
        await user.click(
            screen.getByRole('button', { name: 'Apply for a raise' }),
        );

        expect(
            screen.getByRole('button', { name: /Starting…/u }),
        ).toBeDisabled();
    });

    it('reloads when a completed create carries no snapshot', async () => {
        const user = userEvent.setup();

        inertia.queue.push(() => Promise.resolve(resource(null)));
        renderGrow(null, entry);
        await user.click(
            screen.getByRole('button', { name: 'Apply for a raise' }),
        );

        await waitFor(() => expect(inertia.reload).toHaveBeenCalled());
        expect(inertia.visit).not.toHaveBeenCalled();
    });

    it('refreshes Home when another draft was opened first, and explains', async () => {
        const user = userEvent.setup();

        inertia.queue.push(fails(409, 'VERSION_CONFLICT'));
        renderGrow(null, entry);
        await user.click(
            screen.getByRole('button', { name: 'Apply for a raise' }),
        );

        expect(await screen.findByRole('alert')).toBeInTheDocument();
        expect(inertia.reload).toHaveBeenCalled();
    });

    it('does not refresh on a denial', async () => {
        const user = userEvent.setup();

        inertia.queue.push(fails(403, 'ACTION_FORBIDDEN'));
        renderGrow(null, entry);
        await user.click(
            screen.getByRole('button', { name: 'Apply for a raise' }),
        );

        expect(await screen.findByRole('alert')).toBeInTheDocument();
        expect(inertia.reload).not.toHaveBeenCalled();
    });

    it('looks up a lost answer before offering the same create again', async () => {
        const user = userEvent.setup();

        inertia.reload.mockImplementation(
            (options?: { onFinish?: () => void }) => options?.onFinish?.(),
        );
        inertia.queue.push(fails(503), fails(404, 'OPERATION_NOT_FOUND'));
        renderGrow(null, entry);
        await user.click(
            screen.getByRole('button', { name: 'Apply for a raise' }),
        );

        const retry = await screen.findByRole('button', { name: 'Try again' });

        expect(inertia.calls[1].url).toBe(
            `/business/operations/${(inertia.calls[0].body as { request_id: string }).request_id}`,
        );
        expect(inertia.calls[1].body).toEqual({ command: 'create' });
        expect(
            screen.getByRole('button', { name: 'Apply for a raise' }),
        ).toBeDisabled();

        await user.click(retry);
        expect(inertia.calls[2].body).toEqual(inertia.calls[0].body);
    });
});
