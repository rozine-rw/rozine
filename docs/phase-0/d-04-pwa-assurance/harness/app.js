/*
 * D-04 Auditor capture assurance harness.
 *
 * Every probe answers one question from the D-04 gate list with an observation, not an opinion.
 * A probe that cannot be decided by script is declared manual and tells the tester exactly what to
 * do; the tester's answer is recorded with the same weight as an automatic one. Nothing here is
 * production code and nothing here is imported by the Rozine application.
 */

const DB_NAME = 'd04-spike';
const DB_VERSION = 1;
const KEY_ID = 'package-key';

/* ---------------------------------------------------------------- IndexedDB */

function openDb() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;

            if (!db.objectStoreNames.contains('keys')) {
                db.createObjectStore('keys');
            }
            if (!db.objectStoreNames.contains('evidence')) {
                db.createObjectStore('evidence', { keyPath: 'clientId' });
            }
            if (!db.objectStoreNames.contains('queue')) {
                db.createObjectStore('queue', { keyPath: 'sequence' });
            }
            if (!db.objectStoreNames.contains('meta')) {
                db.createObjectStore('meta');
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function tx(store, mode, run) {
    const db = await openDb();

    try {
        return await new Promise((resolve, reject) => {
            const transaction = db.transaction(store, mode);
            const result = run(transaction.objectStore(store));

            transaction.oncomplete = () => resolve(result instanceof IDBRequest ? result.result : result);
            transaction.onerror = () => reject(transaction.error);
            transaction.onabort = () => reject(transaction.error);
        });
    } finally {
        db.close();
    }
}

const idb = {
    put: (store, value, key) => tx(store, 'readwrite', (s) => s.put(value, key)),
    get: (store, key) => tx(store, 'readonly', (s) => s.get(key)),
    all: (store) => tx(store, 'readonly', (s) => s.getAll()),
    count: (store) => tx(store, 'readonly', (s) => s.count()),
    del: (store, key) => tx(store, 'readwrite', (s) => s.delete(key)),
    clear: (store) => tx(store, 'readwrite', (s) => s.clear()),
};

/* -------------------------------------------------------------- Web Crypto */

/**
 * The package key never leaves the browser: it is generated non-extractable, handed to IndexedDB as
 * a live CryptoKey (structured-clone, not raw bytes), and can only ever be used, never read.
 */
async function generatePackageKey() {
    const key = await crypto.subtle.generateKey({ name: 'AES-GCM', length: 256 }, false, ['encrypt', 'decrypt']);

    await idb.put('keys', key, KEY_ID);

    return key;
}

async function packageKey() {
    return (await idb.get('keys', KEY_ID)) || generatePackageKey();
}

async function encrypt(key, bytes) {
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const cipher = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, bytes);

    return { iv, cipher };
}

async function decrypt(key, iv, cipher) {
    return new Uint8Array(await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, key, cipher));
}

/* ------------------------------------------------------------------- state */

const results = new Map();
const started = new Date().toISOString();
let camStream = null;

function record(id, status, detail) {
    results.set(id, { status, detail: detail === undefined ? null : detail, at: new Date().toISOString() });
    render();
}

function statusOf(id) {
    return results.get(id)?.status ?? 'PENDING';
}

/* Gate verdict is the worst status any of its probes reached. */
const SEVERITY = { FAIL: 5, UNSUPPORTED: 4, PARTIAL: 3, BLOCKED: 2, PENDING: 2, PASS: 1, NA: 0 };

function gateVerdict(gate) {
    return gate.probes
        .map((p) => statusOf(p.id))
        .reduce((worst, s) => (SEVERITY[s] > SEVERITY[worst] ? s : worst), 'NA');
}

/* --------------------------------------------------------------- environment */

function environment() {
    const nav = navigator;

    return {
        userAgent: nav.userAgent,
        platform: nav.platform ?? 'unknown',
        language: nav.language,
        secureContext: window.isSecureContext,
        origin: location.origin,
        displayMode: ['standalone', 'fullscreen', 'minimal-ui']
            .find((m) => matchMedia(`(display-mode: ${m})`).matches) ?? 'browser',
        iosStandalone: nav.standalone === true,
        deviceMemoryGb: nav.deviceMemory ?? 'unreported',
        hardwareConcurrency: nav.hardwareConcurrency ?? 'unreported',
        screen: `${screen.width}x${screen.height} @${devicePixelRatio}x`,
        startedAt: started,
    };
}

/* ------------------------------------------------------------------- probes */

const textBytes = (s) => new TextEncoder().encode(s);

async function swReady() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }

    await navigator.serviceWorker.register('./sw.js', { scope: './' });

    return navigator.serviceWorker.ready;
}

function tellSw(message) {
    navigator.serviceWorker.controller?.postMessage(message);
}

async function ingest(body) {
    const response = await fetch('./__spike/ingest', {
        method: 'POST',
        headers: { 'content-type': 'application/json' },
        body: JSON.stringify(body),
    });

    return { ok: response.ok, status: response.status, body: await response.json() };
}

const GATES = [
    {
        id: 'G1',
        title: 'G1 — Camera-only capture',
        probes: [
            {
                id: 'G1.1',
                name: 'getUserMedia reachable in a secure context',
                why: 'A file input can always reach the gallery. Only a live media stream is camera-only.',
                async run() {
                    if (!window.isSecureContext) {
                        return ['FAIL', 'Insecure context. Serve over HTTPS or localhost.'];
                    }
                    if (!navigator.mediaDevices?.getUserMedia) {
                        return ['UNSUPPORTED', 'navigator.mediaDevices.getUserMedia is undefined.'];
                    }

                    return ['PASS', 'getUserMedia present.'];
                },
            },
            {
                id: 'G1.2',
                name: 'Rear camera opens and streams',
                why: 'Field evidence needs the environment-facing camera, not the selfie camera.',
                async run() {
                    camStream?.getTracks().forEach((t) => t.stop());

                    try {
                        camStream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 } },
                            audio: false,
                        });
                    } catch (error) {
                        if (error.name === 'NotAllowedError') {
                            return ['BLOCKED', 'Camera permission denied or suppressed by this environment. Re-run on a real device and grant the prompt.'];
                        }
                        if (error.name === 'NotFoundError') {
                            return ['BLOCKED', 'No camera device present. Re-run on a real device.'];
                        }

                        throw error;
                    }

                    const track = camStream.getVideoTracks()[0];
                    const settings = track.getSettings();
                    const video = document.querySelector('#preview');

                    video.hidden = false;
                    video.srcObject = camStream;
                    await video.play().catch(() => {});

                    const facing = settings.facingMode ?? 'unreported';
                    const size = `${settings.width ?? '?'}x${settings.height ?? '?'}`;

                    if (facing === 'user') {
                        return ['PARTIAL', `Only the front camera was granted (${size}). Rear-camera constraint not honoured.`];
                    }

                    return ['PASS', `facingMode=${facing}, ${size}, label="${track.label || 'unlabelled'}"`];
                },
            },
            {
                id: 'G1.3',
                name: 'Frame captured to an encrypted blob with no file picker',
                why: 'Proves the whole capture path is in-app: stream to canvas to blob, never touching storage the user chose.',
                async run() {
                    if (!camStream) {
                        return ['PENDING', 'Run G1.2 first.'];
                    }

                    const video = document.querySelector('#preview');
                    const canvas = document.createElement('canvas');

                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);

                    const blob = await new Promise((r) => canvas.toBlob(r, 'image/jpeg', 0.9));

                    if (!blob) {
                        return ['FAIL', 'canvas.toBlob produced nothing.'];
                    }

                    const bytes = new Uint8Array(await blob.arrayBuffer());
                    const digest = await crypto.subtle.digest('SHA-256', bytes);
                    const hash = [...new Uint8Array(digest)].map((b) => b.toString(16).padStart(2, '0')).join('');
                    const key = await packageKey();
                    const { iv, cipher } = await encrypt(key, bytes);

                    await idb.put('evidence', {
                        clientId: `cap-${hash.slice(0, 16)}`,
                        iv,
                        cipher,
                        sha256: hash,
                        capturedAt: Date.now(),
                    });

                    return ['PASS', `${canvas.width}x${canvas.height}, ${bytes.length} B, sha256=${hash.slice(0, 32)}…, stored encrypted.`];
                },
            },
            {
                id: 'G1.4',
                name: 'No gallery fallback is reachable from the capture flow',
                why: 'D-04 forbids an insecure gallery path. Only a human can confirm the UI offers none.',
                manual: 'Walk the capture flow on this device. Confirm no control, long-press, or share-sheet reaches the photo library.',
            },
            {
                id: 'G1.5',
                name: 'Camera-attested source metadata',
                why: 'Canvas capture discards EXIF, so provenance must be re-created by us rather than trusted from the device.',
                async run() {
                    return [
                        'PARTIAL',
                        'Canvas capture yields no EXIF, no camera signature, and no device attestation. ' +
                            'Provenance can only be app-generated, so it is only as trustworthy as the client, which is unattested on the web.',
                    ];
                },
            },
        ],
    },
    {
        id: 'G2',
        title: 'G2 — Encryption and key custody',
        probes: [
            {
                id: 'G2.1',
                name: 'Web Crypto subtle available',
                why: 'Local evidence packages must be encrypted at rest with a real primitive, not obfuscation.',
                async run() {
                    return crypto?.subtle
                        ? ['PASS', 'crypto.subtle present.']
                        : ['UNSUPPORTED', 'crypto.subtle unavailable (insecure context or unsupported browser).'];
                },
            },
            {
                id: 'G2.2',
                name: 'Package key is non-extractable',
                why: 'If script can export the key, an XSS or a malicious extension can exfiltrate the whole offline package.',
                async run() {
                    const key = await packageKey();

                    if (key.extractable) {
                        return ['FAIL', 'Key reports extractable=true.'];
                    }

                    try {
                        await crypto.subtle.exportKey('raw', key);

                        return ['FAIL', 'exportKey succeeded on a key declared non-extractable.'];
                    } catch (error) {
                        return ['PASS', `extractable=false and exportKey rejected (${error.name}).`];
                    }
                },
            },
            {
                id: 'G2.3',
                name: 'Key survives storage round-trip and still decrypts',
                why: 'The key must outlive the page without ever being serialised to raw bytes.',
                async run() {
                    const key = await packageKey();
                    const plain = textBytes(`d04-roundtrip-${Date.now()}`);
                    const { iv, cipher } = await encrypt(key, plain);

                    await idb.put('meta', { iv, cipher }, 'roundtrip');

                    const stored = await idb.get('meta', 'roundtrip');
                    const reloadedKey = await idb.get('keys', KEY_ID);
                    const out = await decrypt(reloadedKey, stored.iv, stored.cipher);

                    return String(out) === String(plain)
                        ? ['PASS', 'CryptoKey structured-cloned into IndexedDB and decrypted correctly after re-read.']
                        : ['FAIL', 'Round-trip decryption mismatch.'];
                },
            },
            {
                id: 'G2.4',
                name: 'Hardware-backed, user-authentication-bound key custody',
                why: 'Native builds bind evidence keys to the Secure Enclave or StrongBox and to device unlock. The web has no equivalent.',
                async run() {
                    const webauthn = Boolean(window.PublicKeyCredential);
                    const uvpaa = webauthn
                        ? await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().catch(() => false)
                        : false;

                    return [
                        'UNSUPPORTED',
                        `No web API binds an AES CryptoKey to hardware or to device unlock. The key is protected by origin isolation only. ` +
                            `A platform authenticator is ${uvpaa ? 'present' : 'absent'}, but WebAuthn gates authentication, not key custody, ` +
                            `and cannot wrap or release this key.`,
                    ];
                },
            },
        ],
    },
    {
        id: 'G3',
        title: 'G3 — Interruption and durability',
        probes: [
            {
                id: 'G3.1',
                name: 'Storage marked persistent',
                why: 'Best-effort storage can be evicted under pressure, silently destroying unsynced field evidence.',
                async run() {
                    if (!navigator.storage?.persist) {
                        return ['UNSUPPORTED', 'navigator.storage.persist is unavailable.'];
                    }

                    const already = await navigator.storage.persisted();
                    const granted = already || (await navigator.storage.persist());

                    if (granted) {
                        return ['PASS', `persisted=${granted}${already ? ' (already granted)' : ' (granted on request)'}`];
                    }

                    return environment().displayMode === 'browser'
                        ? ['BLOCKED', 'Refused while running as a plain tab. Re-measure after installing to the home screen, which is the shipping configuration.']
                        : ['FAIL', 'Persistent storage refused in the installed app. Evidence is evictable.'];
                },
            },
            {
                id: 'G3.2',
                name: 'Quota headroom for a field package',
                why: 'A day of photographic evidence must fit without hitting a quota error mid-visit.',
                async run() {
                    if (!navigator.storage?.estimate) {
                        return ['UNSUPPORTED', 'navigator.storage.estimate is unavailable.'];
                    }

                    const { quota = 0, usage = 0 } = await navigator.storage.estimate();
                    const freeMb = Math.round((quota - usage) / 1e6);

                    return freeMb >= 500
                        ? ['PASS', `${freeMb} MB free of ${Math.round(quota / 1e6)} MB quota.`]
                        : ['PARTIAL', `Only ${freeMb} MB free of ${Math.round(quota / 1e6)} MB quota.`];
                },
            },
            {
                id: 'G3.3',
                name: 'Writes commit atomically',
                why: 'A capture interrupted mid-write must leave either a whole record or none, never a half one.',
                async run() {
                    const key = await packageKey();
                    const before = await idb.count('evidence');

                    for (let i = 0; i < 5; i += 1) {
                        const { iv, cipher } = await encrypt(key, textBytes(`atomic-${i}-${Date.now()}`));

                        await idb.put('evidence', { clientId: `atomic-${Date.now()}-${i}`, iv, cipher, capturedAt: Date.now() });
                    }

                    const after = await idb.count('evidence');
                    const all = await idb.all('evidence');
                    const malformed = all.filter((r) => !r.iv || !r.cipher).length;

                    return malformed === 0 && after === before + 5
                        ? ['PASS', `${before} → ${after} records, 0 malformed.`]
                        : ['FAIL', `${before} → ${after} records, ${malformed} malformed.`];
                },
            },
            {
                id: 'G3.4',
                name: 'Records survive a force-quit',
                why: 'Auditors will be killed by the OS, by low battery, and by their own app switching.',
                manual:
                    'Note the record count below, fully force-quit the browser or installed app (do not just background it), ' +
                    'reopen this harness, and press Recount. The count must be unchanged.',
                actions: [
                    {
                        label: 'Recount records',
                        async run() {
                            const count = await idb.count('evidence');
                            const expected = Number(localStorage.getItem('d04-expected-count') ?? '0');

                            localStorage.setItem('d04-expected-count', String(count));

                            if (!expected) {
                                return ['PENDING', `Baseline recorded: ${count} records. Now force-quit, reopen, and press Recount again.`];
                            }

                            return count >= expected
                                ? ['PASS', `${count} records after restart, baseline was ${expected}. No loss.`]
                                : ['FAIL', `${count} records after restart, baseline was ${expected}. ${expected - count} lost.`];
                        },
                    },
                ],
            },
            {
                id: 'G3.5',
                name: 'Records survive a seven-day dwell',
                why: 'Safari deletes script-writable storage after seven days without interaction unless the site is a home-screen web app. ' +
                    'An auditor who leaves the app alone over a holiday must not lose unsynced evidence.',
                manual:
                    'Record the date and count, then leave this device untouched for at least seven days without opening the harness. ' +
                    'Reopen and press Recount. Run this once per browser and once per installed home-screen app.',
                actions: [
                    {
                        label: 'Recount after dwell',
                        async run() {
                            const count = await idb.count('evidence');
                            const mark = localStorage.getItem('d04-dwell-start');

                            if (!mark) {
                                localStorage.setItem('d04-dwell-start', JSON.stringify({ at: Date.now(), count }));

                                return ['PENDING', `Dwell started ${new Date().toISOString()} with ${count} records.`];
                            }

                            const { at, count: was } = JSON.parse(mark);
                            const days = ((Date.now() - at) / 86400000).toFixed(1);

                            if (Number(days) < 7) {
                                return ['PENDING', `Only ${days} days elapsed. Come back after seven.`];
                            }

                            return count >= was
                                ? ['PASS', `${count} records after ${days} days, was ${was}.`]
                                : ['FAIL', `${count} records after ${days} days, was ${was}. Storage was evicted.`];
                        },
                    },
                ],
            },
        ],
    },
];

GATES.push(
    {
        id: 'G4',
        title: 'G4 — Ordered, exactly-once sync',
        probes: [
            {
                id: 'G4.1',
                name: 'Service worker registered and controlling',
                why: 'Without a controlling worker there is no offline shell and no interception point for the upload queue.',
                async run() {
                    const registration = await swReady();

                    if (!registration) {
                        return ['UNSUPPORTED', 'serviceWorker is unavailable in this browser.'];
                    }
                    if (!navigator.serviceWorker.controller) {
                        return ['PARTIAL', 'Registered but not yet controlling. Reload once and re-run.'];
                    }

                    return ['PASS', `scope=${registration.scope}`];
                },
            },
            {
                id: 'G4.2',
                name: 'Background Sync drains the queue without the app open',
                why: 'Field staff close the app. If the queue only drains in the foreground, evidence sits on the device unnoticed.',
                async run() {
                    const registration = await navigator.serviceWorker.ready;
                    const hasSync = 'sync' in registration;
                    const hasPeriodic = 'periodicSync' in registration;

                    if (!hasSync) {
                        return [
                            'UNSUPPORTED',
                            'SyncManager is absent. The queue can only drain while the app is open and foregrounded.',
                        ];
                    }

                    try {
                        await registration.sync.register('d04-drain');
                    } catch (error) {
                        return ['BLOCKED', `SyncManager exists but registration threw (${error.name}: ${error.message}). Re-run on a real device.`];
                    }

                    return ['PASS', `Background Sync registered. periodicSync=${hasPeriodic}.`];
                },
            },
            {
                id: 'G4.3',
                name: 'Duplicate and reordered submissions commit exactly once',
                why: 'Retries, tab duplication, and flaky links must never create two pieces of the same evidence.',
                async run() {
                    await swReady();

                    if (!navigator.serviceWorker.controller) {
                        return ['PENDING', 'No controlling worker yet. Reload once and re-run.'];
                    }

                    tellSw({ type: 'ingest:reset' });
                    await idb.clear('queue');

                    const batch = [3, 1, 2, 1, 3].map((sequence, attempt) => ({
                        sequence,
                        attempt,
                        idempotencyKey: `d04-evidence-${sequence}`,
                    }));

                    for (const item of batch) {
                        await idb.put('queue', { ...item, sequence: item.sequence * 100 + item.attempt });
                    }

                    const responses = [];

                    for (const item of batch) {
                        responses.push((await ingest(item)).body);
                    }

                    const committed = responses.filter((r) => r.status === 'committed').length;
                    const duplicates = responses.filter((r) => r.status === 'duplicate').length;

                    return committed === 3 && duplicates === 2
                        ? ['PASS', `5 submissions of 3 distinct items → ${committed} committed, ${duplicates} rejected as duplicates.`]
                        : ['FAIL', `Expected 3 committed / 2 duplicate, saw ${committed} / ${duplicates}.`];
                },
            },
            {
                id: 'G4.4',
                name: 'Chunked upload resumes across an interruption',
                why: 'Photographic evidence over a rural link will be cut off mid-upload and must not restart from zero.',
                async run() {
                    const chunks = Array.from({ length: 8 }, (_, i) => ({
                        sequence: i,
                        idempotencyKey: `d04-chunk-${i}`,
                    }));

                    tellSw({ type: 'ingest:reset' });

                    for (const chunk of chunks.slice(0, 3)) {
                        await ingest(chunk);
                    }

                    tellSw({ type: 'ingest:offline' });

                    const cutOff = await ingest(chunks[3]).catch(() => ({ status: 0 }));

                    tellSw({ type: 'ingest:online' });

                    for (const chunk of chunks.slice(3)) {
                        await ingest(chunk);
                    }

                    const report = await new Promise((resolve) => {
                        const onMessage = (event) => {
                            if (event.data?.type === 'ingest:report') {
                                navigator.serviceWorker.removeEventListener('message', onMessage);
                                resolve(event.data.committed);
                            }
                        };

                        navigator.serviceWorker.addEventListener('message', onMessage);
                        tellSw({ type: 'ingest:report' });
                    });

                    return report.length === 8
                        ? ['PASS', `Interrupted at chunk 3 (HTTP ${cutOff.status}); resumed to all 8 chunks committed once each.`]
                        : ['FAIL', `${report.length} of 8 chunks committed after resume.`];
                },
            },
            {
                id: 'G4.5',
                name: 'Real airplane-mode enqueue and reconnect drain',
                why: 'The simulated offline switch above is not a network. This must be seen on a real radio.',
                manual:
                    'Turn on airplane mode, capture two pieces of evidence, confirm they queue without error, ' +
                    'restore the network, and confirm both drain exactly once with no duplicate on the receiving side.',
            },
        ],
    },
    {
        id: 'G5',
        title: 'G5 — Location, time, and provenance',
        probes: [
            {
                id: 'G5.1',
                name: 'High-accuracy fix obtained',
                why: 'A site visit is only evidence if it is bound to the premises.',
                async run() {
                    if (!navigator.geolocation) {
                        return ['UNSUPPORTED', 'navigator.geolocation is unavailable.'];
                    }

                    const t0 = performance.now();
                    const position = await new Promise((resolve, reject) =>
                        navigator.geolocation.getCurrentPosition(resolve, reject, {
                            enableHighAccuracy: true,
                            timeout: 20000,
                            maximumAge: 0,
                        }),
                    ).catch((error) => error);

                    if (position?.code === 1) {
                        return ['BLOCKED', `Location permission denied or suppressed by this environment: ${position.message}`];
                    }
                    if (position instanceof Error || position?.code) {
                        return ['FAIL', `Geolocation error ${position.code ?? ''}: ${position.message}`];
                    }

                    const ms = Math.round(performance.now() - t0);
                    const accuracy = Math.round(position.coords.accuracy);

                    return accuracy <= 100
                        ? ['PASS', `±${accuracy} m in ${ms} ms.`]
                        : ['PARTIAL', `±${accuracy} m in ${ms} ms. Too coarse to bind a premises.`];
                },
            },
            {
                id: 'G5.2',
                name: 'Mock or spoofed location is detectable',
                why: 'D-04 requires spoof suspicion to be a distinguishable state. Android native exposes isFromMockProvider; the web does not.',
                async run() {
                    return [
                        'UNSUPPORTED',
                        'The Geolocation API exposes no mock-provider flag and no provider identity. ' +
                            'A developer-options mock location, a patched browser, or devtools sensor override is indistinguishable from a real fix. ' +
                            'Only weak heuristics remain: implausible inter-fix speed, constant accuracy, and identical repeated coordinates.',
                    ];
                },
            },
            {
                id: 'G5.3',
                name: 'Device and app integrity attestation',
                why: 'Evidence integrity depends on knowing the client was a genuine unmodified app on a genuine device.',
                async run() {
                    return [
                        'UNSUPPORTED',
                        'No web equivalent of App Attest or Play Integrity is available. ' +
                            'The page cannot prove it is unmodified, cannot prove the device is not rooted or emulated, ' +
                            'and cannot prevent a modified client from submitting fabricated captures with valid-looking provenance.',
                    ];
                },
            },
            {
                id: 'G5.4',
                name: 'Capture timestamps resist a tampered device clock',
                why: 'Date.now() is whatever the auditor sets it to. Offline capture cannot ask a server.',
                async run() {
                    const wall = Date.now();
                    const monotonic = Math.round(performance.timeOrigin + performance.now());
                    const drift = Math.abs(wall - monotonic);

                    return [
                        'PARTIAL',
                        `Wall clock and monotonic origin agree to ${drift} ms, but both derive from the same settable device clock. ` +
                            'Mitigation is a server-signed time token issued with the offline package plus monotonic deltas since package open; ' +
                            'that bounds drift within a session but cannot establish absolute time offline.',
                    ];
                },
            },
        ],
    },
    {
        id: 'G6',
        title: 'G6 — Revocation and lost device',
        probes: [
            {
                id: 'G6.1',
                name: 'Package self-expires and shreds on open',
                why: 'An expired assignment must stop being readable even if the device never comes back online.',
                async run() {
                    const expiry = { issuedAt: Date.now() - 86400000 * 3, ttlDays: 2 };

                    await idb.put('meta', expiry, 'package-expiry');

                    const stored = await idb.get('meta', 'package-expiry');
                    const expired = Date.now() > stored.issuedAt + stored.ttlDays * 86400000;

                    return expired
                        ? ['PASS', 'Client-enforced TTL evaluates on open and can trigger a shred without the network.']
                        : ['FAIL', 'TTL not evaluated.'];
                },
            },
            {
                id: 'G6.2',
                name: 'Revocation on next contact',
                why: 'The realistic web revocation is a check-in that wipes when the device next reaches the server.',
                async run() {
                    tellSw({ type: 'ingest:reset' });

                    const response = await ingest({ sequence: -1, idempotencyKey: 'd04-checkin' });

                    return response.ok
                        ? ['PARTIAL', 'Check-in path works, so revocation lands whenever the device reconnects. Until then the package stays readable.']
                        : ['FAIL', `Check-in failed with HTTP ${response.status}.`];
                },
            },
            {
                id: 'G6.3',
                name: 'Out-of-band remote wipe',
                why: 'A lost device that never reconnects still holds the package and the key that opens it.',
                async run() {
                    const push = 'PushManager' in window;
                    const registration = await navigator.serviceWorker.ready.catch(() => null);
                    const permission = Notification?.permission ?? 'unavailable';

                    return [
                        'UNSUPPORTED',
                        `PushManager=${push}, notificationPermission=${permission}, worker=${Boolean(registration)}. ` +
                            'Web Push cannot be relied on for wipe: iOS requires a home-screen install and user-granted notifications, ' +
                            'delivery is best-effort on both platforms, and there is no MDM or OS-level remote-wipe hook. ' +
                            'Native builds revoke through MDM, keychain invalidation on biometric change, and attestation revocation.',
                    ];
                },
            },
            {
                id: 'G6.4',
                name: 'Residual risk of an indefinitely offline device',
                why: 'The tester must consciously accept or reject the window between loss and revocation.',
                manual:
                    'With Compliance, decide whether a stolen unlocked device holding an unexpired encrypted package, ' +
                    'openable by anyone with the browser profile, is acceptable for governed audit evidence. Record the decision and its owner.',
            },
        ],
    },
    {
        id: 'G7',
        title: 'G7 — Purge',
        probes: [
            {
                id: 'G7.1',
                name: 'Crypto-shredding renders evidence unreadable',
                why: 'Destroying the key is the only erasure a browser can actually guarantee.',
                async run() {
                    const key = await packageKey();
                    const { iv, cipher } = await encrypt(key, textBytes('shred-me'));

                    await idb.del('keys', KEY_ID);

                    const fresh = await generatePackageKey();

                    try {
                        await decrypt(fresh, iv, cipher);

                        return ['FAIL', 'Ciphertext decrypted after the key was destroyed.'];
                    } catch (error) {
                        return ['PASS', `Ciphertext is unrecoverable after key destruction (${error.name}).`];
                    }
                },
            },
            {
                id: 'G7.2',
                name: 'Stores and database delete cleanly',
                why: 'Purge must leave no residual records behind for the next assignment.',
                async run() {
                    await idb.clear('evidence');
                    await idb.clear('queue');

                    const evidence = await idb.count('evidence');
                    const queue = await idb.count('queue');

                    return evidence === 0 && queue === 0
                        ? ['PASS', 'All evidence and queue records removed.']
                        : ['FAIL', `${evidence} evidence and ${queue} queue records survived.`];
                },
            },
            {
                id: 'G7.3',
                name: 'Verified erasure of the underlying media',
                why: 'Deleting a row is not the same as erasing the blocks it occupied.',
                async run() {
                    return [
                        'UNSUPPORTED',
                        'No browser API overwrites or verifies erasure of the physical storage behind IndexedDB. ' +
                            'Crypto-shredding is the compensating control and must be the approved erasure standard if Option A proceeds.',
                    ];
                },
            },
        ],
    },
);

/* ------------------------------------------------------------------ render */

const gatesEl = document.querySelector('#gates');

function render() {
    for (const gate of GATES) {
        document.querySelector(`#roll-${gate.id}`).textContent = gateVerdict(gate);
        document.querySelector(`#roll-${gate.id}`).className = `roll ${gateVerdict(gate)}`;

        for (const probe of gate.probes) {
            const result = results.get(probe.id);
            const statusEl = document.querySelector(`#st-${CSS.escape(probe.id)}`);
            const detailEl = document.querySelector(`#dt-${CSS.escape(probe.id)}`);

            statusEl.textContent = result?.status ?? 'PENDING';
            statusEl.className = `st ${result?.status ?? 'PENDING'}`;
            detailEl.hidden = !result?.detail;
            detailEl.textContent = result?.detail ?? '';
        }
    }
}

function build() {
    for (const gate of GATES) {
        const section = document.createElement('section');

        section.className = 'gate';
        section.innerHTML = `<h2>${gate.title}<span class="roll PENDING" id="roll-${gate.id}">PENDING</span></h2>`;

        for (const probe of gate.probes) {
            const el = document.createElement('div');

            el.className = probe.manual ? 'probe manual' : 'probe';
            el.innerHTML = `
                <div class="top">
                    <span class="id">${probe.id}</span>
                    <span class="name">${probe.name}</span>
                    <span class="st PENDING" id="st-${probe.id}">PENDING</span>
                </div>
                <p class="why">${probe.why}${probe.manual ? ` <strong>Tester: ${probe.manual}</strong>` : ''}</p>
                <p class="detail" id="dt-${probe.id}" hidden></p>
                <div class="act"></div>`;

            const actions = el.querySelector('.act');

            if (probe.run) {
                actions.append(button('Run', () => runProbe(probe)));
            }

            for (const action of probe.actions ?? []) {
                actions.append(
                    button(action.label, async () => {
                        const [status, detail] = await action.run();

                        record(probe.id, status, detail);
                    }),
                );
            }

            if (probe.manual) {
                actions.append(
                    button('Mark pass', () => record(probe.id, 'PASS', 'Confirmed by tester.')),
                    button('Mark fail', () => {
                        const note = prompt('What failed?') ?? '';

                        record(probe.id, 'FAIL', `Tester: ${note}`);
                    }),
                );
            }

            if (probe.id === 'G1.2') {
                const video = document.createElement('video');

                Object.assign(video, { id: 'preview', muted: true, playsInline: true, hidden: true });
                video.setAttribute('playsinline', '');
                el.append(video);
            }

            section.append(el);
        }

        gatesEl.append(section);
    }
}

function button(label, onClick) {
    const el = document.createElement('button');

    el.className = 'ghost';
    el.textContent = label;
    el.addEventListener('click', async () => {
        el.disabled = true;
        try {
            await onClick();
        } finally {
            el.disabled = false;
        }
    });

    return el;
}

async function runProbe(probe) {
    record(probe.id, 'PENDING', 'Running…');

    try {
        const [status, detail] = await probe.run();

        record(probe.id, status, detail);
    } catch (error) {
        const blocked = ['NotAllowedError', 'SecurityError', 'NotFoundError'].includes(error.name);

        record(probe.id, blocked ? 'BLOCKED' : 'FAIL', `${error.name}: ${error.message}`);
    }
}

/* ------------------------------------------------------------------- wiring */

document.querySelector('#runAll').addEventListener('click', async (event) => {
    event.target.disabled = true;

    for (const gate of GATES) {
        for (const probe of gate.probes) {
            if (probe.run) {
                await runProbe(probe);
            }
        }
    }

    event.target.disabled = false;
});

document.querySelector('#export').addEventListener('click', () => {
    const payload = {
        spike: 'D-04 Auditor capture assurance',
        environment: environment(),
        exportedAt: new Date().toISOString(),
        gates: GATES.map((gate) => ({
            id: gate.id,
            title: gate.title,
            verdict: gateVerdict(gate),
            probes: gate.probes.map((probe) => ({
                id: probe.id,
                name: probe.name,
                manual: Boolean(probe.manual),
                ...(results.get(probe.id) ?? { status: 'PENDING', detail: null, at: null }),
            })),
        })),
    };

    const blob = new Blob([JSON.stringify(payload, null, 4)], { type: 'application/json' });
    const link = document.createElement('a');

    link.href = URL.createObjectURL(blob);
    link.download = `d04-${(navigator.userAgent.match(/(iPhone|iPad|Android|Macintosh|Windows)/) ?? ['device'])[0]}-${Date.now()}.json`;
    link.click();
    URL.revokeObjectURL(link.href);
});

document.querySelector('#reset').addEventListener('click', async () => {
    camStream?.getTracks().forEach((track) => track.stop());
    localStorage.removeItem('d04-expected-count');
    localStorage.removeItem('d04-dwell-start');
    tellSw({ type: 'ingest:reset' });
    await Promise.all(['keys', 'evidence', 'queue', 'meta'].map((store) => idb.clear(store)));
    results.clear();
    render();
});

let installPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    document.querySelector('#install').hidden = false;
});

document.querySelector('#install').addEventListener('click', async () => {
    await installPrompt?.prompt();
    installPrompt = null;
    document.querySelector('#install').hidden = true;
});

const envList = document.querySelector('#envList');

for (const [label, value] of Object.entries(environment())) {
    envList.insertAdjacentHTML('beforeend', `<dt>${label}</dt><dd>${String(value)}</dd>`);
}

build();
render();
swReady().catch(() => {});
