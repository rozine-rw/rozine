import { useRef, useState } from 'react';
import type { ComponentProps } from 'react';
import { vi } from 'vite-plus/test';

/**
 * A recording stand-in for `@inertiajs/react` shared by the Auditor tests. Every command the pages
 * send is captured with its payload, so tests assert what reaches the server rather than how.
 */
type VisitOptions = {
    onStart?: () => void;
    onFinish?: () => void;
    onError?: () => void;
    [key: string]: unknown;
};

type Transform = (data: Record<string, unknown>) => Record<string, unknown>;

export const inertia = {
    posts: [] as {
        url: string;
        data: Record<string, unknown>;
        options: VisitOptions;
    }[],
    reloads: [] as Record<string, unknown>[],
    errors: {} as Record<string, string>,
    processing: false,
    /** Hold `onFinish` so a test can see the in-flight state. */
    hold: false,
    finish: [] as (() => void)[],
    poll: { start: vi.fn(), stop: vi.fn() },
    reset() {
        this.posts = [];
        this.reloads = [];
        this.errors = {};
        this.processing = false;
        this.hold = false;
        this.finish = [];
        this.poll = { start: vi.fn(), stop: vi.fn() };
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
    reload: (options: Record<string, unknown>) => {
        inertia.reloads.push(options);
    },
};

export const usePoll = () => inertia.poll;

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
