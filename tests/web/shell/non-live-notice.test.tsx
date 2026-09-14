import { render, screen, within } from '@testing-library/react';
import { describe, expect, it, vi } from 'vite-plus/test';
import I18nProvider from '@/components/i18n-provider';
import NonLiveNotice from '@/components/non-live-notice';
import PublicLayout from '@/layouts/public-layout';

const state = vi.hoisted(() => ({ environment: null as string | null }));
vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { nonLiveEnvironment: state.environment } }),
}));

describe('non-live environment notice', () => {
    it.each(['demo', 'uat'])(
        'clearly identifies %s without a dismiss action',
        (environment) => {
            state.environment = environment;
            render(
                <PublicLayout>
                    <h1>Page content</h1>
                </PublicLayout>,
            );
            const notice = screen.getByRole('note', {
                name:
                    environment === 'demo'
                        ? 'Demo — not live'
                        : 'UAT — not live',
            });
            expect(notice).toHaveTextContent(
                'Use synthetic data only. No real-money transactions.',
            );
            expect(
                within(notice).queryByRole('button'),
            ).not.toBeInTheDocument();
            expect(
                screen.getByRole('heading', { name: 'Page content' }),
            ).toBeVisible();
        },
    );

    it('removes the notice when the authoritative shared prop is cleared', () => {
        state.environment = 'demo';
        const { rerender } = render(<NonLiveNotice />);
        expect(screen.getByRole('note')).toBeVisible();
        state.environment = null;
        rerender(<NonLiveNotice />);
        expect(screen.queryByRole('note')).not.toBeInTheDocument();
    });

    it.each([
        ['fr', 'Démo — hors production', 'Aucune transaction en argent réel.'],
        [
            'rw',
            'Demo — si urubuga nyarwo',
            'Nta guhererekanya amafaranga nyayo.',
        ],
        ['en-XA', '[Ðëmô — nôt lïvë]', ''],
    ])('supports the %s catalog', (locale, label, text) => {
        state.environment = 'demo';
        render(
            <I18nProvider locale={locale}>
                <NonLiveNotice />
            </I18nProvider>,
        );
        const notice = screen.getByRole('note');

        if (locale === 'en-XA') {
            expect(notice).not.toHaveAccessibleName('Demo — not live');
        } else {
            expect(notice).toHaveAccessibleName(label);
            expect(notice).toHaveTextContent(text);
        }
    });
});
