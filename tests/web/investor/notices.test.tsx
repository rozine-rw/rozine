import { fireEvent, render, screen, within } from '@testing-library/react';
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
    it('counts each notice’s age from the server clock, keeping the exact time on hover', async () => {
        const user = userEvent.setup();
        const page = notices();
        const times = [
            '2026-10-13T08:59:30+02:00',
            '2026-10-13T08:42:00+02:00',
            '2026-10-13T08:00:00+02:00',
            '2026-10-13T05:00:00+02:00',
            '2026-10-12T08:00:00+02:00',
            '2026-10-08T09:00:00+02:00',
        ];

        page.notices = page.notices
            .slice(0, times.length)
            .map((notice, index) => ({ ...notice, created_at: times[index] }));
        inertia.shared = { server_time: '2026-10-13T09:00:00+02:00' };
        render(<InvestorDeals {...deals()} />);
        inertia.queue.push(answers({ data: page }));

        const panel = await openBell(user);
        const stamps = await within(panel).findAllByText(/ago|Just now/u);

        expect(stamps.map((stamp) => stamp.textContent)).toEqual([
            'Just now',
            '18 min ago',
            '1 hour ago',
            '4 hours ago',
            '1 day ago',
            '5 days ago',
        ]);
        expect(stamps[1]).toHaveAttribute('title', '13 Oct 2026 · 08:42');
    });

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
            name: /RWF|verified|another look|password/u,
        });

        expect(items).toHaveLength(8);
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
        expect(items[3]).toHaveTextContent(
            'Investment confirmedYour 40 notes in Karongi Freight Ltd are issued: RWF 200,000 invested, RWF 211,400 expected at maturity on 10 Apr 2027.',
        );
        expect(items[4]).toHaveTextContent(
            'Raise fundedThe Karongi Freight Ltd raise is fully funded. Your RWF 200,000 for 40 notes is set aside until the funds are sent.',
        );
        expect(items[5]).toHaveTextContent(
            'Monthly report publishedKarongi Freight Ltd published its September 2026 report, verified by Diane Uwase, CPA.',
        );
        expect(items[6]).toHaveTextContent(
            'Raise refundedThe Karongi Freight Ltd raise closed without reaching its target. RWF 150,000 is back in your wallet, with no fee.',
        );
        expect(items[7]).toHaveTextContent(
            'Your password was changedIf this was not you, reset your password now and contact Rozine support.',
        );
        expect(within(items[0]).getByLabelText('Unread')).toBeInTheDocument();
        expect(
            within(items[2]).queryByLabelText('Unread'),
        ).not.toBeInTheDocument();
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
            ).not.toBeInTheDocument(),
        );
        expect(
            within(panel).queryByLabelText('Unread'),
        ).not.toBeInTheDocument();
        expect(
            within(panel).queryByLabelText(/unread$/u),
        ).not.toBeInTheDocument();
    });

    it('marks a notice read without leaving the page when this transport has no screen for it', async () => {
        const user = userEvent.setup();
        render(<InvestorDeals {...deals()} />);
        const inbox = notices();

        inbox.notices[0].link = null;
        inertia.queue.push(answers({ data: inbox }));
        const panel = await openBell(user);
        const items = await within(panel).findAllByRole('button', {
            name: /RWF/u,
        });

        inertia.queue.push(answers({ data: { unread: 1 } }));
        await user.click(items[0]);

        expect(inertia.calls[1]).toMatchObject({
            url: '/preview/investor-notices/read',
            method: 'post',
        });
        expect(inertia.visit).not.toHaveBeenCalled();
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
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

        await openBell(user);
        fireEvent.keyDown(window, { key: 'Enter' });
        expect(screen.getByRole('dialog')).toBeInTheDocument();
        fireEvent.keyDown(window, { key: 'Escape' });
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

        await openBell(user);
        fireEvent.click(screen.getByTestId('notices-scrim'));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('opens the same panel from the wide-screen bell', async () => {
        setWide(true);
        const user = userEvent.setup();
        render(<InvestorDeals {...deals()} />);

        expect(await openBell(user)).toBeInTheDocument();
    });

    it('words a sent and a returned withdrawal with the account they went to', async () => {
        const user = userEvent.setup();
        const inbox = notices();
        const [first] = inbox.notices;

        inbox.notices = [
            {
                ...first,
                id: 'sent',
                kind: 'wallet.withdrawal_sent',
                facts: {
                    amount: '100000',
                    currency: 'RWF',
                    account: 'MTN MoMo +250 788 ···· 456',
                    received: '100000',
                },
            },
            {
                ...first,
                id: 'returned',
                kind: 'wallet.withdrawal_returned',
                facts: {
                    amount: '50000',
                    currency: 'RWF',
                    account: 'Bank of Kigali ···· 4417',
                },
            },
        ] as InvestorNotices['notices'];
        render(<InvestorDeals {...deals()} />);
        const panel = await openBell(user);

        inertia.queue.push(answers({ data: inbox }));
        await user.click(
            screen.getByRole('button', { name: 'Notifications, 2 unread' }),
        );

        const items = await within(panel).findAllByRole('button', {
            name: /Withdrawal/u,
        });

        expect(items[0]).toHaveTextContent(
            'Withdrawal sentRWF 100,000 was sent to MTN MoMo +250 788 ···· 456.',
        );
        expect(items[1]).toHaveTextContent(
            "Withdrawal returnedThe payout of RWF 50,000 to Bank of Kigali ···· 4417 didn't go through. It's back in your wallet.",
        );
    });
    it('words a funds-sent notice with the platform fee taken, and one sent before the fee without it', async () => {
        const user = userEvent.setup();
        const inbox = notices();
        const [first] = inbox.notices;

        inbox.notices = [
            {
                ...first,
                id: 'fee',
                kind: 'business.disbursement_sent',
                facts: {
                    amount: '11640000',
                    currency: 'RWF',
                    title: 'Bakery ovens',
                    account: 'Bank of Kigali ···· 4417',
                    principal: '12000000',
                    platform_fee: '360000',
                },
            },
            {
                ...first,
                id: 'before-fee',
                kind: 'business.disbursement_sent',
                facts: {
                    amount: '3000000',
                    currency: 'RWF',
                    title: 'Cold room',
                    account: 'Bank of Kigali ···· 4417',
                },
            },
        ] as InvestorNotices['notices'];
        render(<InvestorDeals {...deals()} />);
        const panel = await openBell(user);

        inertia.queue.push(answers({ data: inbox }));
        await user.click(
            screen.getByRole('button', { name: 'Notifications, 2 unread' }),
        );

        const items = await within(panel).findAllByRole('button', {
            name: /Funds sent/u,
        });

        expect(items[0]).toHaveTextContent(
            'Funds sentRWF 11,640,000 for Bakery ovens was sent to Bank of Kigali ···· 4417: the RWF 12,000,000 raised less the 3% platform fee of RWF 360,000.',
        );
        expect(items[1]).toHaveTextContent(
            'Funds sentRWF 3,000,000 for Cold room was sent to Bank of Kigali ···· 4417.',
        );
    });

    it('words a linked and an unlinked payout account as security notices', async () => {
        const user = userEvent.setup();
        const inbox = notices();
        const [first] = inbox.notices;

        inbox.notices = [
            {
                ...first,
                id: 'linked',
                kind: 'security.payout_account_linked',
                facts: { account: 'Airtel Money +250 731 ···· 567' },
            },
            {
                ...first,
                id: 'unlinked',
                kind: 'security.payout_account_unlinked',
                facts: { account: 'MTN MoMo +250 788 ···· 456' },
            },
        ] as InvestorNotices['notices'];
        render(<InvestorDeals {...deals()} />);
        const panel = await openBell(user);

        inertia.queue.push(answers({ data: inbox }));
        await user.click(
            screen.getByRole('button', { name: 'Notifications, 2 unread' }),
        );

        const items = await within(panel).findAllByRole('button', {
            name: /Payout account/u,
        });

        expect(items[0]).toHaveTextContent(
            'Payout account linkedAirtel Money +250 731 ···· 567 is now linked for deposits and withdrawals. If this was not you, contact Rozine support now.',
        );
        expect(items[1]).toHaveTextContent(
            'Payout account unlinkedMTN MoMo +250 788 ···· 456 is no longer linked. If this was not you, contact Rozine support now.',
        );
    });
    it('words a new device sign-in as a security notice', async () => {
        const user = userEvent.setup();
        const inbox = notices();
        const [first] = inbox.notices;

        inbox.unread = 1;
        inbox.notices = [
            {
                ...first,
                id: 'device',
                kind: 'security.new_device',
                facts: { device: 'Firefox on Android' },
            },
        ] as InvestorNotices['notices'];
        render(<InvestorDeals {...deals()} />);
        const panel = await openBell(user);

        inertia.queue.push(answers({ data: inbox }));
        await user.click(
            screen.getByRole('button', { name: /Notifications, \d+ unread/u }),
        );

        expect(
            await within(panel).findByRole('button', {
                name: /A new device signed in/u,
            }),
        ).toHaveTextContent(
            'A new device signed inFirefox on Android signed in to your account. If this was not you, reset your password now and contact Rozine support.',
        );
    });

    it('words an answered and a resolved complaint with its reference', async () => {
        const user = userEvent.setup();
        const inbox = notices();
        const [first] = inbox.notices;

        inbox.unread = 2;
        inbox.notices = [
            {
                ...first,
                id: 'answered',
                kind: 'support.complaint_answered',
                facts: { reference: 'CMP-00000001' },
            },
            {
                ...first,
                id: 'resolved',
                kind: 'support.complaint_resolved',
                facts: { reference: 'CMP-00000002' },
            },
        ] as InvestorNotices['notices'];
        render(<InvestorDeals {...deals()} />);
        const panel = await openBell(user);

        inertia.queue.push(answers({ data: inbox }));
        await user.click(
            screen.getByRole('button', { name: /Notifications, \d+ unread/u }),
        );

        expect(
            await within(panel).findByRole('button', {
                name: /Rozine answered your complaint/u,
            }),
        ).toHaveTextContent(
            'Rozine answered your complaintThere is a new answer on complaint CMP-00000001.',
        );
        expect(
            within(panel).getByRole('button', { name: /Complaint resolved/u }),
        ).toHaveTextContent(
            'Complaint resolvedComplaint CMP-00000002 is resolved. Open it to read the resolution.',
        );
    });
});
