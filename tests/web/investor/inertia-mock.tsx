import { useRef, useState } from 'react';
import type { ComponentProps, FormEvent, ReactNode } from 'react';
import { vi } from 'vite-plus/test';

/**
 * A shared `@inertiajs/react` stand-in for the Investor page tests. Each test file mocks the module
 * with `vi.mock('@inertiajs/react', () => import('./inertia-mock'))` and reads what the page sent
 * from `inertia`.
 */
type Payload = Record<string, unknown>;
type Transform = (data: Payload) => Payload;

type HttpOptions = {
    onError?: (errors: Record<string, string>) => void;
    onHttpException?: (response: {
        status: number;
        data: string;
        headers: Record<string, string>;
    }) => void;
};

/** Answers one JSON command sent through `useHttp`, in the order a test queues them. */
export type Responder = (options: HttpOptions) => Promise<unknown>;

export const inertia = {
    posts: [] as { url: string; data: Payload }[],
    reload: vi.fn(),
    visit: vi.fn(),
    routerPost: vi.fn(),
    forms: [] as { action?: string; method?: string }[],
    errors: {} as Record<string, string>,
    processing: false,
    succeed: false,
    /** Every JSON request sent through `useHttp`, with the body it carried. */
    calls: [] as { url: string; method: string; body: unknown }[],
    queue: [] as Responder[],
    /** Field errors `useHttp` reports after a 422. */
    httpErrors: {} as Record<string, string>,
};

export function resetInertia(): void {
    inertia.posts = [];
    inertia.forms = [];
    inertia.errors = {};
    inertia.processing = false;
    inertia.succeed = false;
    inertia.calls = [];
    inertia.queue = [];
    inertia.httpErrors = {};
    inertia.reload.mockReset();
    inertia.visit.mockReset();
    inertia.routerPost.mockReset();
}

/** A reload that finishes at once, so a refresh the page awaits resolves. */
export function finishReloads(): void {
    inertia.reload.mockImplementation((options?: { onFinish?: () => void }) =>
        options?.onFinish?.(),
    );
}

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
    (status: number, body?: unknown): Responder =>
    (options) => {
        options.onHttpException?.({
            status,
            data: body === undefined ? '' : JSON.stringify(body),
            headers: {},
        });

        return Promise.reject(new Error(`HTTP ${status}`));
    };

export const offline = (): Responder => () =>
    Promise.reject(new Error('Network error'));

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

export function Head({ title }: { title: string }) {
    return <span data-testid="head">{title}</span>;
}

export function Link({
    href,
    children,
    preserveScroll: _preserveScroll,
    ...props
}: Omit<ComponentProps<'a'>, 'href'> & {
    href: { url: string };
    preserveScroll?: boolean;
}) {
    return (
        <a href={href.url} {...props}>
            {children}
        </a>
    );
}

type FormState = { processing: boolean; errors: Record<string, string> };

export function Form({
    action,
    method,
    children,
}: {
    action: string;
    method: string;
    resetOnSuccess?: string[];
    children: ReactNode | ((state: FormState) => ReactNode);
}) {
    inertia.forms.push({ action, method });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        inertia.posts.push({
            url: action,
            data: Object.fromEntries(new FormData(event.currentTarget)),
        });
    };

    return (
        <form onSubmit={submit}>
            {typeof children === 'function'
                ? children({
                      processing: inertia.processing,
                      errors: inertia.errors,
                  })
                : children}
        </form>
    );
}

export const router = {
    reload: (...args: unknown[]) => inertia.reload(...args),
    visit: (...args: unknown[]) => inertia.visit(...args),
    post: (...args: unknown[]) => inertia.routerPost(...args),
};

export function useForm<T extends Payload>(initial: T) {
    const [data, setState] = useState(initial);
    const transform = useRef<Transform>((value) => value);

    return {
        data,
        setData: (key: keyof T, value: unknown) =>
            setState((current) => ({ ...current, [key]: value })),
        errors: inertia.errors,
        processing: inertia.processing,
        transform: (fn: Transform) => {
            transform.current = fn;
        },
        post: (url: string, options?: { onSuccess?: () => void }) => {
            inertia.posts.push({ url, data: transform.current(data) });

            if (inertia.succeed) {
                options?.onSuccess?.();
            }
        },
        reset: (...keys: (keyof T)[]) =>
            setState((current) => ({
                ...current,
                ...Object.fromEntries(keys.map((key) => [key, initial[key]])),
            })),
    };
}

/** Pretend the window is desktop-wide (Tailwind `lg`) or phone-narrow for `useWide`. */
export function setWide(wide: boolean): void {
    window.matchMedia = ((query: string) => ({
        matches: wide,
        media: query,
        addEventListener: () => {},
        removeEventListener: () => {},
    })) as unknown as typeof window.matchMedia;
}
