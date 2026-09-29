import { useState } from 'react';
import type { ComponentProps, ReactNode } from 'react';

/**
 * A shared stand-in for `@inertiajs/react` in the console tests. Links render as anchors (or
 * buttons for `as="button"`), `router` calls and form posts are recorded on `inertia` so each
 * test can assert what the page asked the server to do.
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
    reload: (options: Record<string, unknown>) => {
        inertia.reload.push(options);
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
