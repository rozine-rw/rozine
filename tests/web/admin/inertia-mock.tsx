import { useRef, useState } from 'react';
import type { ComponentProps, ReactNode } from 'react';

type HttpOptions = {
    onError?: (errors: Record<string, string>) => void;
    onHttpException?: (response: {
        status: number;
        data: string;
        headers: Record<string, string>;
    }) => void;
};

/** Answers one JSON request sent through `useHttp`. */
export type Responder = (options: HttpOptions) => Promise<unknown>;

/**
 * A shared stand-in for `@inertiajs/react` in the console tests. Links render as anchors (or
 * buttons for `as="button"`), `router` calls and form posts are recorded on `inertia` so each
 * test can assert what the page asked the server to do. JSON commands go through `useHttp`,
 * answered in order by the responders a test queues on `inertia.queue`.
 */
export const inertia = {
    reload: [] as Record<string, unknown>[],
    visits: [] as string[],
    posts: [] as { url: string; data: Record<string, unknown> }[],
    errors: {} as Record<string, string>,
    processing: false,
    succeed: false,
    forms: [] as { action?: string; method?: string }[],
    formState: { processing: false, errors: {} as Record<string, string> },
    /** Every JSON request sent through `useHttp`, with the body it carried. */
    calls: [] as { url: string; method: string; body: unknown }[],
    queue: [] as Responder[],
    /** Field errors `useHttp` reports after a 422. */
    httpErrors: {} as Record<string, string>,
};

export function resetInertia() {
    inertia.reload = [];
    inertia.visits = [];
    inertia.posts = [];
    inertia.errors = {};
    inertia.processing = false;
    inertia.succeed = false;
    inertia.forms = [];
    inertia.formState = { processing: false, errors: {} };
    inertia.calls = [];
    inertia.queue = [];
    inertia.httpErrors = {};
}

type LinkProps = Omit<ComponentProps<'a'>, 'href'> & {
    href: { url: string; method?: string };
    as?: string;
    method?: string;
    preserveScroll?: boolean;
};

export function Link({
    href,
    children,
    as,
    method,
    preserveScroll,
    ...props
}: LinkProps) {
    if (as === 'button') {
        return (
            <button
                type="button"
                data-href={href.url}
                data-method={method ?? href.method}
                className={props.className}
            >
                {children}
            </button>
        );
    }

    return (
        <a
            href={href.url}
            data-preserve-scroll={preserveScroll ? 'true' : undefined}
            {...props}
        >
            {children}
        </a>
    );
}

export function Head({ title }: { title: string }) {
    return <span data-testid="head">{title}</span>;
}

export function Form({
    children,
    action,
    method,
}: {
    children: (state: typeof inertia.formState) => ReactNode;
    action?: string;
    method?: string;
}) {
    inertia.forms.push({ action, method });

    return <form aria-label="sign in form">{children(inertia.formState)}</form>;
}

export const router = {
    reload: (options: Record<string, unknown> & { onFinish?: () => void }) => {
        inertia.reload.push(options);
        options.onFinish?.();
    },
    visit: (url: string) => {
        inertia.visits.push(url);
    },
};

export function useForm<T extends Record<string, unknown>>(initial: T) {
    const [data, setData] = useState(initial);

    return {
        data,
        setData: (next: (current: T) => T) => setData(next),
        errors: inertia.errors,
        processing: inertia.processing,
        post: (url: string, options?: { onSuccess?: () => void }) => {
            inertia.posts.push({ url, data });

            if (inertia.succeed) {
                options?.onSuccess?.();
            }
        },
    };
}

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

/* ------------------------------------------------------------------------------------------ */
/* Responders                                                                                   */
/* ------------------------------------------------------------------------------------------ */

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
