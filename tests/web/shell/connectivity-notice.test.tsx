import { act, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { ConnectivityNotice } from '@/components/rozine/connectivity-notice';

const goOffline = (offline: boolean) => {
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(!offline);
    act(() => {
        window.dispatchEvent(new Event(offline ? 'offline' : 'online'));
    });
};

afterEach(() => {
    vi.restoreAllMocks();
});

describe('The connectivity notice', () => {
    it('says nothing while online, then speaks up offline and clears on reconnect', () => {
        render(<ConnectivityNotice />);

        expect(screen.queryByRole('status')).not.toBeInTheDocument();

        goOffline(true);
        expect(screen.getByRole('status')).toHaveTextContent(
            "You're offline. What you see may be out of date, and nothing can be sent until you're back online.",
        );

        goOffline(false);
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    it("uses a page's own wording when it has one", () => {
        render(<ConnectivityNotice message="auditor.audit.offline" />);
        goOffline(true);

        expect(screen.getByRole('status')).toHaveTextContent(
            /the capture app keeps your evidence encrypted/u,
        );
    });
});
