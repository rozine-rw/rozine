import { useState } from 'react';
import { vi } from 'vite-plus/test';

/**
 * An `@inertiajs/react` stand-in for the landing page tests: `Head` renders nothing and
 * `useHttp` keeps its form state in React, handing each post to `landing.answer`.
 */
type Payload = Record<string, string>;

export type PostOptions = {
    onNetworkError?: () => void;
    onHttpException?: (response: { status: number }) => void;
};

export type Controls = {
    setErrors: (errors: Record<string, string>) => void;
    finish: () => void;
};

export const landing = {
    posts: [] as { url: string; data: Payload }[],
    answer: vi.fn(
        (_options: PostOptions, _controls: Controls): Promise<unknown> =>
            new Promise(() => undefined),
    ),
};

export function Head(): null {
    return null;
}

export function useHttp(initial: Payload) {
    const [data, setAll] = useState<Payload>(initial);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    return {
        data,
        errors,
        processing,
        setData: (field: string, value: string) =>
            setAll((current) => ({ ...current, [field]: value })),
        clearErrors: () => setErrors({}),
        post: (url: string, options: PostOptions) => {
            landing.posts.push({ url, data });
            setProcessing(true);

            return landing.answer(options, {
                setErrors,
                finish: () => setProcessing(false),
            });
        },
    };
}
