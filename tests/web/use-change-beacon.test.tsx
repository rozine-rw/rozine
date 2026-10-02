import { http } from '@inertiajs/core';
import type { HttpRequestConfig, HttpResponse } from '@inertiajs/core';
import { act, renderHook } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { POLL_INTERVAL_MS } from '@/hooks/use-bounded-poll';
import {
    MAX_POLL_MS,
    MIN_POLL_MS,
    useChangeBeacon,
} from '@/hooks/use-change-beacon';
import type { ChangeBeacon } from '@/hooks/use-change-beacon';
import type { ChangeFeed, ChangeTopic } from '@/types/settlement';

/**
 * The change beacon over the real Inertia transport with only the client swapped and the clock
 * faked: it reads the feed at the server's pace, reloads only the props a relevant change names,
 * reloads in full on a reset, and pauses while offline or hidden.
 */
const reload = vi.hoisted(() => vi.fn());
vi.mock('@inertiajs/react', () => ({ router: { reload } }));

const LINK = {
    url: '/changes?topics=wallet&after=1.10.0.100.1',
    method: 'get' as const,
};

type Answer = (config: HttpRequestConfig) => Promise<HttpResponse>;

const sent: HttpRequestConfig[] = [];
const answers: Answer[] = [];

const feed =
    (changes: ChangeTopic[], overrides: Partial<ChangeFeed> = {}): Answer =>
    () =>
        Promise.resolve({
            status: 200,
            headers: {},
            data: JSON.stringify({
                contract_version: 'change-feed-v1',
                changes,
                next_cursor: `1.${11 + sent.length}.0.100.1`,
                reset: false,
                server_time: '2026-10-02T09:00:00Z',
                poll_after_ms: POLL_INTERVAL_MS,
                ...overrides,
            } satisfies ChangeFeed),
        });

const failing: Answer = () => Promise.reject(new Error('offline'));

const wallet = (revision: number, subject = 'w1'): ChangeTopic => ({
    topic: 'wallet',
    subject,
    revision,
});

const elapse = (ms: number) =>
    act(async () => {
        await vi.advanceTimersByTimeAsync(ms);
    });

const setOnline = (online: boolean) => {
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(online);
    act(() => {
        window.dispatchEvent(new Event(online ? 'online' : 'offline'));
    });
};

const setHidden = (hidden: boolean) => {
    vi.spyOn(document, 'hidden', 'get').mockReturnValue(hidden);
    act(() => {
        document.dispatchEvent(new Event('visibilitychange'));
    });
};

const mount = (beacon: ChangeBeacon | undefined) =>
    renderHook(
        (props: { beacon: ChangeBeacon | undefined }) =>
            useChangeBeacon(props.beacon),
        {
            initialProps: { beacon },
        },
    );

const walletBeacon = (extra: Partial<ChangeBeacon> = {}): ChangeBeacon => ({
    link: LINK,
    reloads: { wallet: { only: ['wallet', 'deposits'] } },
    ...extra,
});

const afterOf = (config: HttpRequestConfig) =>
    new URL(config.url, 'http://localhost').searchParams.get('after');

beforeEach(() => {
    vi.useFakeTimers();
    vi.clearAllMocks();
    vi.spyOn(document, 'hidden', 'get').mockReturnValue(false);
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(true);
    http.setClient({
        request: (config) => {
            sent.push(config);
            const answer = answers.shift();

            return answer ? answer(config) : feed([])(config);
        },
    });
});

afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
    sent.length = 0;
    answers.length = 0;
});

describe('Change beacon', () => {
    it('does nothing without a beacon link, as in a fixture preview', async () => {
        mount(undefined);
        mount({ link: null, reloads: { wallet: { only: ['wallet'] } } });
        mount({ link: undefined, reloads: {} });
        await elapse(MAX_POLL_MS * 2);
        act(() => {
            window.dispatchEvent(new Event('focus'));
        });

        expect(sent).toHaveLength(0);
        expect(reload).not.toHaveBeenCalled();
    });

    it('reads from the render cursor and reloads only the props a newer change names', async () => {
        answers.push(
            feed([wallet(2)]),
            feed([wallet(2), wallet(1, 'w2')]),
            feed([wallet(3)]),
        );
        mount(walletBeacon());

        await elapse(POLL_INTERVAL_MS - 1);
        expect(sent).toHaveLength(0);

        await elapse(1);
        expect(sent).toHaveLength(1);
        expect(sent[0].url).toBe('/changes?topics=wallet&after=1.10.0.100.1');
        expect(sent[0].method).toBe('get');
        expect(reload).toHaveBeenCalledExactlyOnceWith({
            only: ['wallet', 'deposits'],
        });

        await elapse(POLL_INTERVAL_MS);
        expect(afterOf(sent[1])).toBe('1.12.0.100.1');
        // w1@2 was already seen; w2@1 is new.
        expect(reload).toHaveBeenCalledTimes(2);

        await elapse(POLL_INTERVAL_MS);
        expect(reload).toHaveBeenCalledTimes(3);
    });

    it('ignores a revision it has already seen and a topic the page does not reload', async () => {
        answers.push(
            feed([wallet(5)]),
            feed([wallet(4), wallet(5)]),
            feed([{ topic: 'campaign', subject: 'c1', revision: 2 }]),
        );
        mount(walletBeacon());

        await elapse(POLL_INTERVAL_MS);
        await elapse(POLL_INTERVAL_MS);
        await elapse(POLL_INTERVAL_MS);

        expect(sent).toHaveLength(3);
        expect(reload).toHaveBeenCalledOnce();
    });

    it('reloads only for its own subject when the page names one', async () => {
        answers.push(
            feed([{ topic: 'campaign', subject: 'other', revision: 2 }]),
            feed([{ topic: 'campaign', subject: 'mine', revision: 2 }]),
        );
        mount({
            link: {
                url: '/changes?topics=campaign&after=1.10.0.100.1',
                method: 'get',
            },
            reloads: {
                campaign: { only: ['campaign', 'note'], subject: 'mine' },
            },
        });

        await elapse(POLL_INTERVAL_MS);
        expect(reload).not.toHaveBeenCalled();

        await elapse(POLL_INTERVAL_MS);
        expect(reload).toHaveBeenCalledExactlyOnceWith({
            only: ['campaign', 'note'],
        });
    });

    it('tracks a purchase apart from a wallet change under the same subject id', async () => {
        answers.push(
            feed([
                wallet(1, 'r1'),
                { topic: 'purchase', subject: 'r1', revision: 1 },
            ]),
            feed([{ topic: 'purchase', subject: 'r1', revision: 2 }]),
        );
        mount({
            link: {
                url: '/changes?topics=wallet,purchase&after=1.10.0.100.1',
                method: 'get',
            },
            reloads: {
                wallet: { only: ['wallet'] },
                purchase: { only: ['reservation'], subject: 'r1' },
            },
        });

        await elapse(POLL_INTERVAL_MS);
        expect(reload).toHaveBeenCalledExactlyOnceWith({
            only: ['wallet', 'reservation'],
        });

        await elapse(POLL_INTERVAL_MS);
        expect(reload).toHaveBeenLastCalledWith({ only: ['reservation'] });
    });

    it('reloads the page in full on a reset and forgets what it had seen', async () => {
        answers.push(
            feed([wallet(7)]),
            feed([], { reset: true }),
            feed([wallet(1)]),
        );
        mount(walletBeacon());

        await elapse(POLL_INTERVAL_MS);
        await elapse(POLL_INTERVAL_MS);
        expect(reload).toHaveBeenLastCalledWith();

        await elapse(POLL_INTERVAL_MS);
        expect(reload).toHaveBeenLastCalledWith({
            only: ['wallet', 'deposits'],
        });
        expect(reload).toHaveBeenCalledTimes(3);
    });

    it('pauses while offline and reads at once when the connection returns', async () => {
        mount(walletBeacon());
        setOnline(false);

        await elapse(MAX_POLL_MS * 2);
        expect(sent).toHaveLength(0);

        answers.push(feed([wallet(2)]));
        setOnline(true);
        // The reconnect itself refreshes the reloadable props through useAccessRefresh.
        expect(reload).toHaveBeenCalledWith(
            expect.objectContaining({ only: ['wallet', 'deposits'] }),
        );
        reload.mock.calls[0][0].onFinish();
        await elapse(0);

        expect(sent).toHaveLength(1);
        expect(reload).toHaveBeenLastCalledWith({
            only: ['wallet', 'deposits'],
        });
    });

    it('pauses while hidden and reads at once when shown again', async () => {
        mount(walletBeacon());
        setHidden(true);

        await elapse(MAX_POLL_MS * 2);
        expect(sent).toHaveLength(0);

        setHidden(false);
        await elapse(0);
        expect(sent).toHaveLength(1);
    });

    it('holds a reload while a command is unsettled and runs it once the command settles', async () => {
        answers.push(feed([wallet(2)]));
        const view = mount(walletBeacon({ settled: false }));

        await elapse(POLL_INTERVAL_MS);
        expect(sent).toHaveLength(1);
        expect(reload).not.toHaveBeenCalled();

        view.rerender({ beacon: walletBeacon({ settled: true }) });
        expect(reload).toHaveBeenCalledExactlyOnceWith({
            only: ['wallet', 'deposits'],
        });

        view.rerender({ beacon: walletBeacon({ settled: true }) });
        expect(reload).toHaveBeenCalledOnce();
    });

    it('follows the server pace within bounds and backs off after a failed read', async () => {
        answers.push(
            feed([], { poll_after_ms: 1 }),
            feed([], { poll_after_ms: 10 * MAX_POLL_MS }),
            failing,
            failing,
        );
        mount({
            link: { url: '/changes?topics=wallet', method: 'get' },
            reloads: {},
        });

        await elapse(POLL_INTERVAL_MS);
        expect(sent).toHaveLength(1);
        expect(afterOf(sent[0])).toBeNull();

        await elapse(MIN_POLL_MS);
        expect(sent).toHaveLength(2);

        await elapse(MAX_POLL_MS - 1);
        expect(sent).toHaveLength(2);
        await elapse(1);
        expect(sent).toHaveLength(3);

        // A failed read waits twice as long, never beyond the maximum.
        await elapse(MAX_POLL_MS);
        expect(sent).toHaveLength(4);
        expect(reload).not.toHaveBeenCalled();
    });

    it('abandons a read in flight when the page goes away', async () => {
        answers.push(
            (config) =>
                new Promise((_, reject) => {
                    config.signal?.addEventListener('abort', () =>
                        reject(new DOMException('Aborted', 'AbortError')),
                    );
                }),
        );
        const view = mount(walletBeacon());

        await elapse(POLL_INTERVAL_MS);
        expect(sent).toHaveLength(1);

        view.unmount();
        await elapse(MAX_POLL_MS * 2);

        expect(sent).toHaveLength(1);
        expect(sent[0].signal?.aborted).toBe(true);
        expect(reload).not.toHaveBeenCalled();
    });

    it('starts again from a new link, forgetting the old cursor', async () => {
        answers.push(feed([wallet(3)]), feed([wallet(3)]));
        const view = mount(walletBeacon());

        await elapse(POLL_INTERVAL_MS);
        view.rerender({
            beacon: walletBeacon({
                link: {
                    url: '/changes?topics=wallet&after=1.50.0.100.1',
                    method: 'get',
                },
            }),
        });
        await elapse(POLL_INTERVAL_MS);

        expect(afterOf(sent[1])).toBe('1.50.0.100.1');
        expect(reload).toHaveBeenCalledTimes(2);
    });
});
