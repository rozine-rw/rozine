/*
 * D-04 spike service worker.
 *
 * Two jobs only:
 *   1. Cache the harness shell so the offline / interruption gates can run with no network.
 *   2. Act as a mock evidence-ingest server at ./__spike/ingest so the ordered-sync gate can be
 *      exercised without a backend. The mock records every idempotency key it has seen and reports
 *      whether a submission was a first write or a duplicate, which is exactly the property the
 *      real Phase 1 server contract has to guarantee.
 *
 * The mock is a measurement instrument, not a proposed implementation. Exactly-once behaviour must
 * be re-proven against the real server at the Phase 1 integration checkpoints.
 */

const SHELL = 'd04-shell-v2';
const ASSETS = ['./', './index.html', './app.js', './manifest.webmanifest', './icon-192.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(SHELL).then((c) => c.addAll(ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== SHELL).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

/** Idempotency keys the mock server has already committed, and whether ingest is reachable. */
const committed = new Map();
let ingestReachable = true;

self.addEventListener('message', (event) => {
    const { type } = event.data || {};
    if (type === 'ingest:offline') {
        ingestReachable = false;
    } else if (type === 'ingest:online') {
        ingestReachable = true;
    } else if (type === 'ingest:reset') {
        committed.clear();
        ingestReachable = true;
    } else if (type === 'ingest:report') {
        event.source.postMessage({
            type: 'ingest:report',
            committed: [...committed.entries()].map(([key, value]) => ({ key, ...value })),
        });
    }
});

async function handleIngest(request) {
    if (!ingestReachable) {
        return Response.json({ error: 'simulated-offline' }, { status: 503 });
    }

    const body = await request.json();
    const key = body.idempotencyKey;

    if (committed.has(key)) {
        const existing = committed.get(key);
        return Response.json({ status: 'duplicate', sequence: existing.sequence, attempts: ++existing.attempts });
    }

    committed.set(key, { sequence: body.sequence, receivedAt: Date.now(), attempts: 1 });

    return Response.json({ status: 'committed', sequence: body.sequence, attempts: 1 });
}

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    if (url.pathname.endsWith('/__spike/ingest')) {
        event.respondWith(handleIngest(event.request.clone()));

        return;
    }

    if (event.request.method !== 'GET') {
        return;
    }

    /*
     * Network-first, cache-fallback. Cache-first would let a stale harness build answer probes about
     * a newer one, which is exactly the kind of quiet wrong answer this spike cannot afford. The
     * fallback still gives the offline shell the interruption and sync gates need.
     */
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                const copy = response.clone();

                caches.open(SHELL).then((cache) => cache.put(event.request, copy));

                return response;
            })
            .catch(() => caches.match(event.request).then((hit) => hit || caches.match('./index.html'))),
    );
});

/* Chromium-only. Its absence on Safari is itself a recorded G4 finding. */
self.addEventListener('sync', (event) => {
    if (event.tag === 'd04-drain') {
        event.waitUntil(
            self.clients.matchAll().then((cs) => cs.forEach((c) => c.postMessage({ type: 'sync:fired', at: Date.now() }))),
        );
    }
});
