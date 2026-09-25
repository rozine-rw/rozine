import { useRef, useState } from 'react';
import type { ComponentProps } from 'react';
import { vi } from 'vite-plus/test';
import type { AuditorOperationResource } from '@/types/auditor';

/**
 * A recording stand-in for `@inertiajs/react` shared by the Auditor tests. Every command the pages
 * send is captured with its payload, so tests assert what reaches the server rather than how.
 * JSON commands go through `useHttp`, answered in order by the responders a test queues.
 */
type VisitOptions = {
    onStart?: () => void;
    onFinish?: () => void;
    onError?: () => void;
    [key: string]: unknown;
};

type Transform = (data: Record<string, unknown>) => Record<string, unknown>;

type HttpOptions = {
    onError?: (errors: Record<string, string>) => void;
    onHttpException?: (response: {
        status: number;
        data: string;
        headers: Record<string, string>;
    }) => void;
};

export type Responder = (options: HttpOptions) => Promise<unknown>;

export const inertia = {
    posts: [] as {
        url: string;
        data: Record<string, unknown>;
        options: VisitOptions;
    }[],
    reloads: [] as (Record<string, unknown> | undefined)[],
    visits: [] as { url: string }[],
    errors: {} as Record<string, string>,
    processing: false,
    /** Hold `onFinish` so a test can see the in-flight state. */
    hold: false,
    finish: [] as (() => void)[],
    poll: { start: vi.fn(), stop: vi.fn() },
    /** Every JSON request sent through `useHttp`, with the body it carried. */
    calls: [] as { url: string; method: string; body: unknown }[],
    queue: [] as Responder[],
    /** Field errors `useHttp` reports after a 422. */
    httpErrors: {} as Record<string, string>,
    /** Hold a reload's `onFinish` so a test can see the page while it refreshes. */
    holdReload: false,
    finishReload: [] as (() => void)[],
    /** The props a finished reload delivers; null delivers no page, as a failed reload would. */
    reloadProps: null as Record<string, unknown> | null,
    reset() {
        this.posts = [];
        this.reloads = [];
        this.visits = [];
        this.errors = {};
        this.processing = false;
        this.hold = false;
        this.finish = [];
        this.poll = { start: vi.fn(), stop: vi.fn() };
        this.calls = [];
        this.queue = [];
        this.httpErrors = {};
        this.holdReload = false;
        this.finishReload = [];
        this.reloadProps = null;
    },
};

const run = (options: VisitOptions = {}) => {
    options.onStart?.();

    if (options.onFinish) {
        if (inertia.hold) {
            inertia.finish.push(options.onFinish);
        } else {
            options.onFinish();
        }
    }
};

export const Head = ({ title }: { title: string }) => (
    <span data-testid="head">{title}</span>
);

export const Link = ({
    href,
    children,
    ...props
}: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
    <a href={href.url} {...props}>
        {children}
    </a>
);

export const router = {
    post: (
        url: string,
        data: Record<string, unknown>,
        options: VisitOptions = {},
    ) => {
        inertia.posts.push({ url, data, options });
        run(options);
    },
    reload: (options?: {
        onSuccess?: (page: { props: Record<string, unknown> }) => void;
        onFinish?: () => void;
    }) => {
        inertia.reloads.push(options);

        if (options?.onFinish) {
            const { onSuccess, onFinish } = options;
            /* The page is delivered as the reload finishes, so a held reload can still change it. */
            const finish = () => {
                if (inertia.reloadProps !== null) {
                    onSuccess?.({ props: inertia.reloadProps });
                }

                onFinish();
            };

            if (inertia.holdReload) {
                inertia.finishReload.push(finish);
            } else {
                finish();
            }
        }
    },
    visit: (link: { url: string }) => {
        inertia.visits.push({ url: link.url });
    },
};

export const usePoll = () => inertia.poll;

export function useHttp() {
    const body = useRef<() => unknown>(() => ({}));
    const [errors, setErrors] = useState<Record<string, string>>({});

    return {
        errors,
        clearErrors: () => setErrors({}),
        transform: (callback: () => unknown) => {
            body.current = callback;
        },
        submit: async (
            route: { url: string; method: string },
            options: HttpOptions,
        ) => {
            inertia.calls.push({
                url: route.url,
                method: route.method,
                body: body.current(),
            });
            const respond = inertia.queue.shift();
            const result = await (respond
                ? respond(options)
                : new Promise(() => undefined));

            if (result === undefined) {
                setErrors(inertia.httpErrors);
                options.onError?.(inertia.httpErrors);
            }

            return result;
        },
    };
}

export function useForm<T extends Record<string, unknown>>(
    ...args: [T] | [string, T]
) {
    const initial = (args.length === 2 ? args[1] : args[0]) as T;
    const [data, setState] = useState<T>(initial);
    const transform = useRef<Transform>((value) => value);

    return {
        data,
        setData: (key: keyof T | ((current: T) => T), value?: unknown) =>
            setState((current) =>
                typeof key === 'function'
                    ? key(current)
                    : { ...current, [key]: value },
            ),
        errors: inertia.errors,
        processing: inertia.processing,
        transform: (fn: Transform) => {
            transform.current = fn;
        },
        post: (url: string, options: VisitOptions = {}) => {
            inertia.posts.push({
                url,
                data: transform.current(data),
                options,
            });
            run(options);
        },
    };
}

/* ------------------------------------------------------------------------------------------ */
/* Responders                                                                                   */
/* ------------------------------------------------------------------------------------------ */

/** The shared operation Resource, completed unless the test says otherwise. */
export const operation = (
    overrides: Partial<AuditorOperationResource> = {},
): AuditorOperationResource => ({
    operation_id: 'op-1',
    status: 'completed',
    code: 'AUDIT_STEP_SAVED',
    data: { next: { url: '/preview/next', method: 'get' } },
    revision: 8,
    policy_version: 'engineering-2026-09-24.1',
    recorded_at: '2026-10-03T17:00:02Z',
    server_time: '2026-10-03T17:00:03Z',
    allowed_actions: [],
    field_errors: {},
    ...overrides,
});

export const answers =
    (result: unknown): Responder =>
    () =>
        Promise.resolve(result);

/** A 422: the field errors land in `errors` and the request resolves without a body. */
export const invalid =
    (errors: Record<string, string>): Responder =>
    () => {
        inertia.httpErrors = errors;

        return Promise.resolve(undefined);
    };

export const fails =
    (
        status: number,
        body?: unknown,
        headers: Record<string, string> = {},
    ): Responder =>
    (options) => {
        options.onHttpException?.({
            status,
            data: body === undefined ? '' : JSON.stringify(body),
            headers,
        });

        return Promise.reject(new Error(`HTTP ${status}`));
    };

export const offline = (): Responder => () =>
    Promise.reject(new Error('Network error'));
