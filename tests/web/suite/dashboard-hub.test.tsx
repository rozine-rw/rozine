import { fireEvent, render, screen, within } from '@testing-library/react';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    actionHref,
    HubAside,
    HubOverview,
    HubWork,
    UrgentFlashcards,
} from '@/components/rozine/dashboard-hub';
import Launcher from '@/pages/dashboard';
import type { HubAction, IdentityContext, OnboardingHub } from '@/types';
import readyFixture from '../../../resources/fixtures/ui/launcher-ready.json';

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <span>{title}</span>,
    Link: ({
        children,
        href,
        as,
        preserveState: _preserveState,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & {
        href: string | { url: string };
        as?: string;
        preserveState?: boolean;
    }) =>
        as === 'button' ? (
            <button>{children}</button>
        ) : (
            <a href={typeof href === 'string' ? href : href.url} {...props}>
                {children}
            </a>
        ),
    useHttp: () => ({ processing: false, transform: vi.fn(), submit: vi.fn() }),
    useForm: () => ({ data: {}, errors: {}, processing: false }),
    router: { visit: vi.fn(), reload: vi.fn() },
}));

const action = (overrides: Partial<HubAction>): HubAction => ({
    id: 'kyc_changes@2026-11-24T08:27:00+02:00',
    kind: 'kyc_changes',
    urgent: true,
    role: null,
    company: null,
    note: null,
    items: [],
    at: '2026-11-24T08:27:00+02:00',
    ...overrides,
});

const hub = (overrides: Partial<OnboardingHub> = {}): OnboardingHub => ({
    name: 'Aline',
    steps: [
        {
            key: 'account',
            state: 'done',
            role: null,
            at: '2026-11-20T09:00:00+02:00',
        },
        { key: 'email', state: 'done', role: null, at: null },
        { key: 'kyc', state: 'attention', role: null, at: null },
        { key: 'role', state: 'review', role: 'business', at: null },
        { key: 'open', state: 'upcoming', role: 'business', at: null },
    ],
    actions: [
        action({
            note: 'The back of your ID is blurred.',
            items: [
                { key: 'id_back', label: 'ID back photo', note: 'Too dark' },
                { key: 'date_of_birth', label: 'Date of birth', note: '' },
            ],
        }),
        action({
            id: 'role_changes@x',
            kind: 'role_changes',
            role: 'business',
            company: 'Kivu Harvest Ltd',
            items: [],
            at: null,
        }),
        action({
            id: 'two_factor@',
            kind: 'two_factor',
            urgent: false,
            at: null,
        }),
    ],
    next: ['kyc_resend', 'role_review', 'role_switch'],
    done: [
        { key: 'account', role: null, at: '2026-11-20T09:00:00+02:00' },
        { key: 'role_sent', role: 'business', at: null },
    ],
    role: 'business',
    company: 'Kivu Harvest Ltd',
    recent: [
        {
            id: 'n1',
            kind: 'verification.rejected',
            created_at: '2026-11-24T08:27:00+02:00',
            read: false,
        },
        {
            id: 'n2',
            kind: 'role.application_submitted',
            created_at: '2026-11-23T17:02:00+02:00',
            read: true,
        },
    ],
    ...overrides,
});

beforeEach(() => {
    window.sessionStorage.clear();
    vi.restoreAllMocks();
});

describe('Dashboard hub', () => {
    it('greets the person, counts the steps done and flags what is urgent', () => {
        render(<HubOverview hub={hub()} />);
        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(
            'Hello, Aline',
        );
        expect(screen.getByText(/You are 2 of 5 steps/u)).toHaveTextContent(
            '2 things need you now.',
        );
        expect(
            screen.getByRole('progressbar', { value: { now: 40 } }),
        ).toBeInTheDocument();
        const steps = screen.getAllByRole('listitem');
        expect(steps.map((step) => step.dataset.state)).toEqual([
            'done',
            'done',
            'attention',
            'review',
            'upcoming',
        ]);
        expect(steps[2]).toHaveTextContent('Identity checkChanges requested');
        expect(steps[3]).toHaveTextContent('Business checkWith Compliance');
        expect(steps[4]).toHaveTextContent('Open BusinessAfter approval');
    });

    it('says when setup is complete, with one urgent item and an unnamed person', () => {
        render(
            <HubOverview
                hub={hub({
                    name: '',
                    steps: [
                        { key: 'kyc', state: 'done', role: null, at: null },
                        {
                            key: 'role',
                            state: 'rejected',
                            role: 'auditor',
                            at: null,
                        },
                        { key: 'open', state: 'current', role: null, at: null },
                    ],
                    actions: [action({})],
                })}
            />,
        );
        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(
            'Welcome',
        );
        expect(screen.getByText('1 thing needs you now.')).toBeInTheDocument();
        expect(screen.getByText('Auditor licence check')).toBeInTheDocument();
        expect(screen.getByText('Not approved')).toBeInTheDocument();
        expect(screen.getByText('Start investing')).toBeInTheDocument();
        expect(screen.getByText('To do')).toBeInTheDocument();
    });

    it('lists what needs the person now with its items and links, then what happens next', () => {
        render(<HubWork hub={hub()} />);
        const needs = screen.getByRole('region', { name: 'Needs you now' });
        const cards = within(needs).getAllByRole('article');
        expect(cards[0]).toHaveTextContent(
            'Compliance needs changes to your identity check',
        );
        expect(cards[0]).toHaveTextContent('“The back of your ID is blurred.”');
        expect(
            within(cards[0]).getByRole('list', { name: 'Items to change' }),
        ).toHaveTextContent('ID back photoDate of birth');
        expect(
            within(cards[0]).getByRole('link', { name: 'Fix now' }),
        ).toHaveAttribute('href', '/investor/verification');
        expect(cards[1]).toHaveTextContent(
            'Compliance needs changes to your business application',
        );
        expect(cards[1]).toHaveTextContent(
            'Change the items below, then send it again.',
        );
        expect(
            within(cards[1]).getByRole('link', { name: 'Fix now' }),
        ).toHaveAttribute('href', '/role');
        expect(cards[2]).toHaveTextContent('Recommended');
        expect(
            within(cards[2]).getByRole('link', { name: 'Set up' }),
        ).toHaveAttribute('href', '/settings/security');
        const next = screen.getByRole('region', { name: 'What happens next' });
        expect(next).toHaveTextContent('Compliance re-checks your identity');
        expect(next).toHaveTextContent('Kivu Harvest Ltd is reviewed');
        expect(next).toHaveTextContent('Your account switches to Business');
    });

    it('words the business and Auditor reviews for after the identity check is approved', () => {
        const { rerender } = render(
            <HubWork
                hub={hub({ actions: [], next: ['role_review', 'role_switch'] })}
            />,
        );
        expect(
            screen.getByText(
                'Compliance is reviewing your business now. We email you as soon as it is decided.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByText(/once your identity check is approved/u),
        ).not.toBeInTheDocument();
        rerender(
            <HubWork
                hub={hub({
                    role: 'auditor',
                    company: null,
                    actions: [],
                    next: ['role_send', 'role_review', 'role_switch'],
                })}
            />,
        );
        expect(
            screen.getByText(
                'Your identity is approved, so Compliance reviews your ICPAR licence as soon as you send it.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Compliance is reviewing your ICPAR licence now. We email you as soon as it is decided.',
            ),
        ).toBeInTheDocument();
        rerender(
            <HubWork
                hub={hub({
                    actions: [],
                    next: ['kyc_review', 'role_send', 'role_switch'],
                })}
            />,
        );
        expect(
            screen.getByText(
                'Compliance reviews it once your identity check is approved.',
            ),
        ).toBeInTheDocument();
        rerender(<HubWork hub={hub({ actions: [], next: ['role_send'] })} />);
        expect(
            screen.getByText(
                'Your identity is approved, so Compliance reviews it as soon as you send it.',
            ),
        ).toBeInTheDocument();
    });

    it('words every other action and an empty list', () => {
        const { rerender } = render(
            <HubWork
                hub={hub({
                    actions: [],
                    next: ['investor_ready'],
                    company: null,
                })}
            />,
        );
        expect(
            screen.getByText(/Nothing needs you right now/u),
        ).toBeInTheDocument();
        expect(screen.getByText('Browse open deals')).toBeInTheDocument();
        rerender(
            <HubWork
                hub={hub({
                    role: 'auditor',
                    company: null,
                    next: [
                        'kyc_send',
                        'role_send',
                        'role_review',
                        'role_switch',
                        'role_resend',
                        'role_reapply',
                        'kyc_review',
                        'business_ready',
                        'auditor_ready',
                    ],
                    actions: [
                        action({
                            id: 'a',
                            kind: 'role_rejected',
                            urgent: false,
                            role: 'auditor',
                            note: 'Licence expired.',
                        }),
                        action({
                            id: 'b',
                            kind: 'kyc_start',
                            urgent: false,
                            at: null,
                        }),
                        action({
                            id: 'c',
                            kind: 'kyc_continue',
                            urgent: false,
                            at: null,
                        }),
                        action({
                            id: 'd',
                            kind: 'role_continue',
                            urgent: false,
                            role: 'business',
                            at: null,
                        }),
                        action({
                            id: 'e',
                            kind: 'two_factor',
                            urgent: false,
                            role: 'auditor',
                            at: null,
                        }),
                    ],
                })}
            />,
        );
        const cards = screen.getAllByRole('article');
        expect(cards[0]).toHaveTextContent(
            'Your Auditor application was not approved',
        );
        expect(
            within(cards[0]).getByRole('link', {
                name: 'Review and apply again',
            }),
        ).toHaveAttribute('href', '/role');
        expect(cards[1]).toHaveTextContent('Verify your identity');
        expect(
            within(cards[1]).getByRole('link', { name: 'Start' }),
        ).toBeInTheDocument();
        expect(cards[2]).toHaveTextContent('Finish your identity check');
        expect(cards[3]).toHaveTextContent('Finish your business application');
        expect(cards[4]).toHaveTextContent(
            'Auditors need it before they can open the Auditor app.',
        );
        const next = screen.getByRole('region', { name: 'What happens next' });
        expect(next).toHaveTextContent('Send your Auditor application');
        expect(next).toHaveTextContent('Your ICPAR licence is reviewed');
        expect(next).toHaveTextContent('Your account switches to Auditor');
        expect(next).toHaveTextContent('You are an active Audit Partner.');
    });

    it('shows what is done, the newest updates and where to get help', () => {
        const { rerender } = render(<HubAside hub={hub()} />);
        const done = screen.getByRole('region', { name: 'Done' });
        expect(done).toHaveTextContent('Account created');
        expect(done).toHaveTextContent('Business application sent');
        const recent = screen.getByRole('region', { name: 'Recent updates' });
        expect(within(recent).getAllByRole('listitem')).toHaveLength(2);
        expect(recent).toHaveTextContent('Emailed');
        expect(
            screen.getByRole('link', { name: 'Contact support' }),
        ).toHaveAttribute('href', 'mailto:hello@rozine.rw');
        rerender(
            <HubAside
                hub={hub({
                    recent: [],
                    done: [
                        { key: 'role_approved', role: 'auditor', at: null },
                        { key: 'kyc_approved', role: null, at: null },
                    ],
                })}
            />,
        );
        expect(
            screen.getByText('Updates from Compliance show here.'),
        ).toBeInTheDocument();
        expect(screen.getByText('ICPAR licence verified')).toBeInTheDocument();
    });

    it('links every action to where it is done', () => {
        expect(actionHref(action({ kind: 'kyc_continue' }))).toBe(
            '/investor/verification',
        );
        expect(actionHref(action({ kind: 'role_continue' }))).toBe('/role');
    });
});

describe('Urgent flashcards', () => {
    const urgent = hub().actions;

    it('shows one urgent card at a time with its actions, pages through them and sets one aside', () => {
        const scroll = vi.fn();
        const target = document.createElement('div');
        target.id = 'hub-action-kyc_changes';
        target.scrollIntoView = scroll;
        document.body.append(target);
        render(<UrgentFlashcards actions={urgent} />);
        const card = screen.getByRole('alertdialog');
        expect(card).toHaveTextContent('Your identity check needs changes');
        expect(card).toHaveTextContent(
            'Compliance flagged: ID back photo, Date of birth.',
        );
        expect(card).toHaveTextContent('1 of 2');
        expect(
            within(card).getByRole('link', { name: 'Fix now' }),
        ).toHaveAttribute('href', '/investor/verification');
        fireEvent.click(
            within(card).getByRole('button', { name: 'View details' }),
        );
        expect(scroll).toHaveBeenCalled();
        fireEvent.click(within(card).getByRole('button', { name: 'Next' }));
        expect(card).toHaveTextContent(
            'Your business application needs changes',
        );
        expect(card).toHaveTextContent(
            'Compliance sent it back. Fix it so your review can finish.',
        );
        fireEvent.click(within(card).getByRole('button', { name: 'Previous' }));
        expect(card).toHaveTextContent('Your identity check needs changes');
        fireEvent.click(within(card).getByRole('button', { name: 'Later' }));
        expect(screen.getByRole('alertdialog')).toHaveTextContent(
            'Your business application needs changes',
        );
        expect(
            screen.queryByRole('button', { name: 'Next' }),
        ).not.toBeInTheDocument();
        expect(
            JSON.parse(
                window.sessionStorage.getItem('rozine.hub.later') ?? '[]',
            ),
        ).toEqual([urgent[0].id]);
        target.remove();
    });

    it('remembers cards set aside in this tab and shows nothing without urgent items', () => {
        window.sessionStorage.setItem(
            'rozine.hub.later',
            JSON.stringify([urgent[0].id, 7]),
        );
        const { rerender } = render(<UrgentFlashcards actions={urgent} />);
        expect(screen.getByRole('alertdialog')).toHaveTextContent(
            'Your business application needs changes',
        );
        fireEvent.click(screen.getByRole('button', { name: 'Later' }));
        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        rerender(<UrgentFlashcards actions={[urgent[2]]} />);
        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
    });

    it('still works when this browser blocks storage or holds something unreadable', () => {
        window.sessionStorage.setItem('rozine.hub.later', '{"not":"a list"}');
        render(<UrgentFlashcards actions={[urgent[0]]} />);
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('blocked');
        });
        fireEvent.click(screen.getByRole('button', { name: 'Later' }));
        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('blocked');
        });
        render(<UrgentFlashcards actions={[urgent[0]]} />);
        expect(screen.getByRole('alertdialog')).toBeInTheDocument();
    });
});

describe('Launcher with the hub', () => {
    it('puts the hub above the apps, says the page is live and raises the urgent card', () => {
        vi.stubGlobal(
            'fetch',
            vi.fn().mockRejectedValue(new TypeError('offline')),
        );
        render(
            <Launcher
                identity={readyFixture.props.identity as IdentityContext}
                hub={hub()}
            />,
        );
        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(
            'Hello, Aline',
        );
        expect(
            screen.getByRole('heading', { level: 2, name: 'Your apps' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Live')).toBeInTheDocument();
        expect(screen.getByRole('alertdialog')).toHaveTextContent(
            'Your identity check needs changes',
        );
        // The launcher has no app scope: without one the accent buttons and the progress bar are
        // transparent, so the hub and its urgent card carry the Investor accent themselves.
        expect(screen.getByTestId('dashboard-hub')).toHaveAttribute(
            'data-audience',
            'investor',
        );
        // The fixed header carries the updates bell with its unread count, and the profile.
        expect(
            screen.getByRole('link', { name: 'Updates, 1 unread' }),
        ).toHaveAttribute('href', '#hub-recent');
        expect(
            screen.getByRole('link', { name: 'Your profile' }),
        ).toHaveTextContent('A');
        expect(
            screen.queryByText('One live core · every surface'),
        ).not.toBeInTheDocument();
        // The apps sit in the main column under the hub, as designed.
        expect(
            within(screen.getByTestId('dashboard-hub')).getByRole('heading', {
                name: 'Your apps',
            }),
        ).toBeInTheDocument();
        const urgent = screen.getByTestId('dashboard-urgent');
        expect(urgent).toHaveAttribute('data-audience', 'investor');
        expect(within(urgent).getByRole('alertdialog')).toBeInTheDocument();
        vi.unstubAllGlobals();
    });
});
