import { act, fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorDeals from '@/pages/investor/deals';
import type { C3InvestorDealsProps, InvestorNotices } from '@/types/investor';
import inbox from '../../../resources/fixtures/investor-notices.json';
import dealsFixture from '../../../resources/fixtures/ui/investor-deals.json';
import { answers, fails, inertia, resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const deals = () =>
    structuredClone(dealsFixture.props) as unknown as C3InvestorDealsProps;
const notices = () => structuredClone(inbox) as unknown as InvestorNotices;

const openBell = async (user: ReturnType<typeof userEvent.setup>) => {
    await user.click(
        screen.getByRole('button', { name: 'Notifications, 2 unread' }),
    );

    return screen.getByRole('dialog', { name: 'Notifications' });
};

beforeEach(() => {
    resetInertia();
    setWide(false);
});

describe('Notices behind the bell (BRS NTF-R01)', () => {
    it('loads the notices as the bell opens them, newest first, worded by kind', async () => {
        const user = userEvent.setup();
        render(<InvestorDeals {...deals()} />);
        const panel = await openBell(user);

        expect(
            within(panel).getByLabelText('Loading notifications'),
        ).toHaveAttribute('aria-busy', 'true');
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/investor-notices',
            method: 'get',
        });

        inertia.queue.push(answers({ data: notices() }));
        await user.click(
            screen.getByRole('button', { name: 'Notifications, 2 unread' }),
        );

        const items = await within(panel).findAllByRole('button', {
            name: /RWF|verified|another look/u,
        });

        expect(items).toHaveLength(3);
        expect(items[0]).toHaveTextContent(
            'Deposit receivedRWF 250,000 is now in your wallet.',
        );
        expect(items[0]).toHaveTextContent('13 Oct 2026 · 08:42');
        expect(items[1]).toHaveTextContent(
            'You are verifiedYour identity is verified and your wallet is ready to invest.',
        );
        expect(items[2]).toHaveTextContent(
            'Compliance could not verify your details: The photo of your ID is blurred. Upload a sharper photo.',
        );
        expect(within(items[0]).getByLabelText('Unread')).toBeInTheDocument();
        expect(within(items[2]).queryByLabelText('Unread')).toBeNull();
        expect(within(panel).getByLabelText('2 unread')).toHaveTextContent('2');
    });

    it('marks a notice read on the way to its record, and opens a read one directly', async () => {
        const user = userEvent.setup();
        render(<InvestorDeals {...deals()} />);
        inertia.queue.push(answers({ data: notices() }));
        const panel = await openBell(user);
        const items = await within(panel).findAllByRole('button', {
            name: /RWF|verified|another look/u,
        });

        inertia.queue.push(answers({ data: { unread: 1 } }));
        await user.click(items[0]);

        expect(inertia.calls[1]).toMatchObject({
            url: '/preview/investor-notices/read',
            method: 'post',
        });
        expect(inertia.visit).toHaveBeenCalledWith({
            url: '/preview/investor-wallet',
            method: 'get',
        });

        await user.click(items[2]);

        expect(inertia.calls).toHaveLength(2);
        expect(inertia.visit).toHaveBeenLastCalledWith({
            url: '/preview/investor-verification',
            method: 'get',
        });

        inertia.queue.push(fails(500));
        await user.click(items[1]);

        expect(inertia.visit).toHaveBeenLastCalledWith({
            url: '/preview/investor-deals',
            method: 'get',
        });
    });

    it('marks everything read, refreshes the bell and reloads the notices', async () => {
        const user = userEvent.setup();
        render(<InvestorDeals {...deals()} />);
        inertia.queue.push(answers({ data: notices() }));
        const panel = await openBell(user);
        const read = notices();

        read.unread = 0;
        read.actions.read_all = null;
        read.notices = read.notices.map((notice) => ({
            ...notice,
            read: true,
            read_action: null,
        }));
        inertia.queue.push(
            answers({ data: { unread: 0 } }),
            answers({ data: read }),
        );
        await user.click(
            await within(panel).findByRole('button', { name: 'Mark all read' }),
        );

        expect(inertia.calls[1]).toMatchObject({
            url: '/preview/investor-notices/read',
            method: 'post',
        });
        expect(inertia.reload).toHaveBeenCalledWith({
            only: ['unread_notifications'],
        });
        await vi.waitFor(() =>
            expect(
                within(panel).queryByRole('button', { name: 'Mark all read' }),
            ).toBeNull(),
        );
        expect(within(panel).queryByLabelText('Unread')).toBeNull();
        expect(within(panel).queryByLabelText(/unread$/u)).toBeNull();
    });

    it('says when the notices could not be loaded, retries, and says when there are none', async () => {
        const user = userEvent.setup();
        render(<InvestorDeals {...deals()} />);
        inertia.queue.push(fails(503));
        const panel = await openBell(user);

        expect(await within(panel).findByRole('alert')).toHaveTextContent(
            'Your notifications could not be loaded.',
        );

        inertia.queue.push(
            answers({
                data: { unread: 0, notices: [], actions: { read_all: null } },
            }),
        );
        await user.click(
            within(panel).getByRole('button', { name: 'Try again' }),
        );

        expect(
            await within(panel).findByText('No notifications yet.'),
        ).toBeInTheDocument();
    });

    it('closes on the close button, the scrim and Escape, and ignores other keys', async () => {
        const user = userEvent.setup();
        render(<InvestorDeals {...deals()} />);

        await openBell(user);
        await user.click(
            screen.getByRole('button', { name: 'Close notifications' }),
        );
        expect(screen.queryByRole('dialog')).toBeNull();

        await openBell(user);
        act(() => {
            fireEvent.keyDown(window, { key: 'Enter' });
        });
        expect(screen.getByRole('dialog')).toBeInTheDocument();
        act(() => {
            fireEvent.keyDown(window, { key: 'Escape' });
        });
        expect(screen.queryByRole('dialog')).toBeNull();

        const panel = await openBell(user);
        fireEvent.click(panel.previousElementSibling as Element);
        expect(screen.queryByRole('dialog')).toBeNull();
    });

    it('opens the same panel from the wide-screen bell', async () => {
        setWide(true);
        const user = userEvent.setup();
        render(<InvestorDeals {...deals()} />);

        expect(await openBell(user)).toBeInTheDocument();
    });
});
