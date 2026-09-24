import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorHome from '@/pages/auditor/home';
import AuditorProfile from '@/pages/auditor/profile';
import type {
    AuditorHomeProps,
    AuditorProfileProps,
    StandingReason,
} from '@/types/auditor';
import lapsedHome from '../../../resources/fixtures/ui/auditor-home-standing-lapsed.json';
import expiredProfile from '../../../resources/fixtures/ui/auditor-profile-standing-expired.json';
import { renderWithUser } from '../helpers/render-with-user';
import { fails, inertia } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const home = () => structuredClone(lapsedHome.props) as AuditorHomeProps;
const profile = () =>
    structuredClone(expiredProfile.props) as AuditorProfileProps;

const REASONS: [StandingReason, string][] = [
    ['ACCREDITATION_REQUIRED', "You don't have an approved accreditation yet."],
    [
        'ACCREDITATION_EXPIRED',
        'Your licence has expired. Renew it to receive work again.',
    ],
    [
        'ACCREDITATION_SUSPENDED',
        'Your accreditation is suspended by Audit Operations.',
    ],
    ['STANDING_CHECK_REQUIRED', 'A standing check by Audit Operations is due.'],
];

const PAUSED_UNTIL =
    'Dispatch is paused until your standing is restored. Your choice to accept audits is kept.';

beforeEach(() => inertia.reset());

describe('Receiving work needs both the saved choice and current standing', () => {
    it.each(REASONS)('says why dispatch is paused: %s', (reason, copy) => {
        const base = home();

        render(
            <AuditorHome
                {...base}
                standing={{ ...base.standing, current: false, reason }}
            />,
        );

        expect(screen.getByText(copy)).toBeInTheDocument();
    });

    it('shows an accepting partner with lapsed standing as paused, never receiving', () => {
        render(<AuditorHome {...home()} />);

        expect(screen.getByText('Paused')).toBeInTheDocument();
        expect(screen.queryByText('Accepting audits')).not.toBeInTheDocument();
        expect(screen.queryByText(/km radius · max/u)).not.toBeInTheDocument();
        expect(screen.getByRole('note')).toHaveTextContent(PAUSED_UNTIL);
        /* The switch keeps the saved preference. */
        expect(
            screen.getByRole('switch', { name: 'Accepting audits' }),
        ).toBeChecked();
    });

    it('lets a lapsed partner pause', async () => {
        const { user } = renderWithUser(<AuditorHome {...home()} />);

        await user.click(
            screen.getByRole('switch', { name: 'Accepting audits' }),
        );

        expect(inertia.calls[0].body).toMatchObject({ accepting: false });
    });

    it('says before turning on that no work will come, and shows the server’s refusal', async () => {
        inertia.queue.push(fails(403, { code: 'STANDING_CHECK_REQUIRED' }));
        const base = home();
        const { user } = renderWithUser(
            <AuditorHome
                {...base}
                availability={{ ...base.availability, accepting: false }}
            />,
        );

        expect(screen.getByRole('note')).toHaveTextContent(
            "Turning this on won't bring offers until your standing is restored.",
        );
        expect(
            screen.getByText('A standing check by Audit Operations is due.'),
        ).toBeInTheDocument();

        await user.click(
            screen.getByRole('switch', { name: 'Accepting audits' }),
        );

        expect(inertia.calls[0].body).toMatchObject({ accepting: true });
        expect(
            await screen.findByText(
                "A standing check by Audit Operations is due. Dispatch can't offer you work until then, so this wasn't changed.",
            ),
        ).toBeInTheDocument();
        expect(inertia.reloads).toHaveLength(0);
    });

    it('reads the same on Profile, where the licence has expired', () => {
        render(<AuditorProfile {...profile()} />);

        expect(screen.getByText('Paused')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Your licence has expired. Renew it to receive work again.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByRole('note')).toHaveTextContent(PAUSED_UNTIL);
        expect(
            screen.getByRole('switch', { name: 'Accepting audits' }),
        ).toBeChecked();
    });

    it('reads a partner paused by choice, in standing, who cannot change it here', () => {
        const base = home();

        render(
            <AuditorHome
                {...base}
                allowed_actions={[]}
                availability={{ ...base.availability, accepting: false }}
                standing={{ ...base.standing, current: true, reason: null }}
            />,
        );

        expect(
            screen.getByText('Dispatch is not offering you flash audits'),
        ).toBeInTheDocument();
        expect(screen.queryByRole('note')).not.toBeInTheDocument();
    });

    it('keeps Profile receiving when standing is current', () => {
        render(
            <AuditorProfile
                {...profile()}
                standing={{ current: true, reason: null }}
            />,
        );

        expect(
            screen.getByText(
                'You appear in the dispatch pool and can be offered flash audits.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('note')).not.toBeInTheDocument();
    });
});
