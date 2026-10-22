import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { AUDITOR_ACADEMY } from '@/components/auditor/profile/academy-section';
import { lessonsOf } from '@/components/rozine/academy';
import en from '@/lib/i18n/catalogs/en';
import AuditorProfile from '@/pages/auditor/profile';
import type { AuditorProfileProps } from '@/types/auditor';
import learnFixture from '../../../resources/fixtures/ui/auditor-profile-learn.json';
import { inertia } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const STORAGE_KEY = 'rozine.auditor.academy.v1';

const LESSONS = lessonsOf(AUDITOR_ACADEMY);

const props = () => structuredClone(learnFixture.props) as AuditorProfileProps;

beforeEach(() => {
    inertia.reset();
    window.localStorage.clear();
});

describe('Auditor Academy', () => {
    it('separates current upfront credits from future repayment-fee terms', () => {
        render(<AuditorProfile {...props()} />);
        fireEvent.click(
            screen.getByRole('button', { name: 'Browse all lessons' }),
        );
        expect(screen.getByText(/^Short lessons/u)).toHaveTextContent(
            'current rules and documented future repayment terms',
        );
        fireEvent.click(screen.getByRole('button', { name: /Your earnings/u }));

        const lesson = screen.getByRole('region', { name: 'Your earnings' });
        expect(lesson).toHaveTextContent('40% of that fee is credited to you');
        expect(lesson).toHaveTextContent('at disbursement');
        expect(lesson).toHaveTextContent('your share is 144,000');
        fireEvent.click(screen.getByRole('button', { name: 'Got it' }));
        fireEvent.click(screen.getByRole('button', { name: 'RWF 120,000' }));
        expect(screen.getByRole('status')).toHaveTextContent('Correct');
        fireEvent.click(screen.getByRole('button', { name: 'Next' }));

        expect(lesson).toHaveTextContent(
            '25% of the earnings fee (future terms)',
        );
        expect(lesson).toHaveTextContent('These credits are not active yet.');
        fireEvent.click(screen.getByRole('button', { name: 'Got it' }));
        expect(lesson).toHaveTextContent(
            'Under the documented future repayment terms, when would the 25% earnings-fee share be credited?',
        );
        fireEvent.click(
            screen.getByRole('button', {
                name: 'On each monthly payout of notes you verify, once active',
            }),
        );
        expect(screen.getByRole('status')).toHaveTextContent('Correct');
    });

    it('shows the Academy section with nothing done, and resumes on the first lesson', () => {
        render(<AuditorProfile {...props()} />);

        const section = screen.getByRole('region', {
            name: 'Auditor Academy',
        });

        expect(section).toHaveTextContent(
            'Learn how verification works on Rozine',
        );
        expect(section).toHaveTextContent('6 tracks');
        expect(section).not.toHaveTextContent(/pts|points/iu);
        fireEvent.click(
            within(section).getByRole('button', {
                name: 'Resume: Who you are',
            }),
        );

        const lesson = screen.getByRole('region', { name: 'Who you are' });

        expect(lesson).toHaveTextContent('An independent CPA');
        fireEvent.click(screen.getByRole('button', { name: 'Got it' }));
        fireEvent.click(
            screen.getByRole('button', {
                name: 'Only licensed, independent CPAs',
            }),
        );
        expect(screen.getByRole('status')).toHaveTextContent('Correct');
        expect(JSON.parse(window.localStorage.getItem(STORAGE_KEY)!)).toEqual({
            role: 1,
        });
        fireEvent.click(screen.getByRole('button', { name: 'Close lesson' }));

        expect(
            screen.getByRole('heading', { name: 'Auditor Academy' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: /Who you are/u }),
        ).toHaveTextContent('1/2');
        fireEvent.click(screen.getByRole('button', { name: 'Back' }));
        expect(
            screen.getByRole('button', { name: 'Browse all lessons' }),
        ).toBeInTheDocument();
    });

    it('browses the BRS tracks and counts lessons done', () => {
        window.localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({ role: 2, independence: 2 }),
        );
        render(<AuditorProfile {...props()} />);

        expect(
            screen.getByRole('region', { name: 'Auditor Academy' }),
        ).toHaveTextContent('2 of 11 lessons done');
        expect(
            screen.getByRole('button', { name: 'Resume: Assignments' }),
        ).toBeInTheDocument();
        fireEvent.click(
            screen.getByRole('button', { name: 'Browse all lessons' }),
        );

        expect(
            screen
                .getAllByRole('heading', { level: 3 })
                .map((heading) => heading.textContent),
        ).toEqual([
            'Your role',
            'How work reaches you',
            'Verifying a business',
            'Every month',
            'Flash Audits and arrears visits',
            'How you are paid',
        ]);
        expect(
            screen.getByRole('region', { name: 'Your role' }),
        ).toHaveTextContent('2/2 done');
        fireEvent.click(screen.getByRole('button', { name: /Assignments/u }));
        expect(
            screen.getByRole('region', { name: 'Assignments' }),
        ).toHaveTextContent('Within 30 km');
    });

    it('offers a review once every lesson is done', () => {
        window.localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify(
                Object.fromEntries(LESSONS.map((lesson) => [lesson.key, 2])),
            ),
        );
        render(<AuditorProfile {...props()} />);

        expect(
            screen.getByRole('region', { name: 'Auditor Academy' }),
        ).toHaveTextContent('11 of 11 lessons done');
        fireEvent.click(
            screen.getByRole('button', { name: 'Review the Academy' }),
        );
        expect(
            screen.getByRole('heading', { name: 'Auditor Academy' }),
        ).toBeInTheDocument();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    it('teaches the current calendar deadline and accepts its date in the quick check', () => {
        render(<AuditorProfile {...props()} />);
        fireEvent.click(
            screen.getByRole('button', { name: 'Browse all lessons' }),
        );
        fireEvent.click(
            screen.getByRole('button', { name: /The monthly cycle/u }),
        );

        const lesson = screen.getByRole('region', {
            name: 'The monthly cycle',
        });
        expect(lesson).toHaveTextContent(
            'by the end of the 7th of the following month (Kigali time)',
        );
        expect(lesson).not.toHaveTextContent('D−3');
        fireEvent.click(screen.getByRole('button', { name: 'Got it' }));
        expect(lesson).toHaveTextContent('September’s monthly audit');
        fireEvent.click(screen.getByRole('button', { name: '7 October' }));
        expect(screen.getByRole('status')).toHaveTextContent('Correct');

        fireEvent.click(screen.getByRole('button', { name: 'Next' }));
        expect(lesson).toHaveTextContent(
            'uses the same deadline for your on-time record',
        );
        fireEvent.click(screen.getByRole('button', { name: 'Got it' }));
        fireEvent.click(
            screen.getByRole('button', {
                name: 'The audit is overdue and a later seal counts as late',
            }),
        );
        expect(screen.getByRole('status')).toHaveTextContent('Correct');
    });

    it('teaches the BRS figures, not the older design copy', () => {
        const copy = LESSONS.flatMap((lesson) =>
            [1, 2].flatMap((topic) =>
                ['title', 'body', 'q', 'option1', 'option2', 'option3'].map(
                    (part) =>
                        en[
                            `${lesson.code}.topic${topic}.${part}` as keyof typeof en
                        ],
                ),
            ),
        );

        expect(copy.filter((text) => text === undefined)).toEqual([]);

        const text = copy.join(' ');

        expect(text).toContain('within 30 km');
        expect(text).toContain('1.50 coverage');
        expect(text).toContain('40% of that fee');
        expect(text).not.toMatch(/15 ?km|1\.25×|2% service fee|points/u);
    });
});
