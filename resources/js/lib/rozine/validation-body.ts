import { http as transport } from '@inertiajs/core';

/**
 * Reads the body of each 422 answer while a command is out, and returns the function that stops
 * reading. `useHttp` hands a 422 over as field errors only, so a refusal's `code` and `data` are read
 * here instead. Inertia's own client rejects every 4xx and passes it to the error handlers; a client
 * that resolves a 4xx passes it to the response handlers. Both are read, so neither drops the code.
 */
export function readValidationBodies(
    read: (body: unknown) => void,
): () => void {
    const stopResponses = transport.onResponse((response) => {
        if (response.status === 422) {
            read(response.data);
        }

        return response;
    });
    const stopErrors = transport.onError((error) => {
        if ('response' in error && error.response.status === 422) {
            read(error.response.data);
        }
    });

    return () => {
        stopResponses();
        stopErrors();
    };
}
