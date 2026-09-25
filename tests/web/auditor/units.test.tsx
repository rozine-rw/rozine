import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { formatCountdown } from '@/components/auditor/clock';
import { compactAmount, compactRwf } from '@/components/auditor/money';
import { catalogFor } from '@/lib/i18n';
import { I18nContext } from '@/lib/i18n/context';
import AuditorHome from '@/pages/auditor/home';
import AuditorPortfolio from '@/pages/auditor/portfolio';
import type { AuditorHomeProps, AuditorPortfolioProps } from '@/types/auditor';
import homeFixture from '../../../resources/fixtures/ui/auditor-home.json';
import portfolioFixture from '../../../resources/fixtures/ui/auditor-portfolio.json';

vi.mock('@inertiajs/react', () => import('./inertia'));

const rwf = (amount: number) => ({
    currency: 'RWF' as const,
    amount: String(amount),
});

describe('Auditor display formatting', () => {
    it('writes money compactly, as the design does', () => {
        expect(compactAmount(rwf(950))).toBe('950');
        expect(compactAmount(rwf(102_400))).toBe('102K');
        expect(compactAmount(rwf(51_200_000))).toBe('51.2M');
        expect(compactRwf(rwf(1_640_000_000))).toBe('RWF 1.6B');
        /* A net outflow keeps its sign, compacted like any other figure. */
        expect(compactRwf(rwf(-10_400_000))).toBe('RWF −10.4M');
        expect(compactAmount(rwf(-950))).toBe('−950');
    });

    it('never counts a clock below zero', () => {
        expect(formatCountdown(-5000)).toBe('00:00:00');
        expect(formatCountdown(75_599_000)).toBe('20:59:59');
    });

    it('formats months in the partner’s own language', () => {
        const fr = { locale: 'fr' as const, catalog: catalogFor('fr') };

        render(
            <I18nContext value={fr}>
                <AuditorHome
                    {...(structuredClone(
                        homeFixture.props,
                    ) as AuditorHomeProps)}
                />
                <AuditorPortfolio
                    {...(structuredClone(
                        portfolioFixture.props,
                    ) as AuditorPortfolioProps)}
                />
            </I18nContext>,
        );

        expect(screen.getByText('Rendement · OCT')).toBeInTheDocument();
        expect(
            screen.getAllByText('SEP', { selector: 'span' }).length,
        ).toBeGreaterThan(0);
    });
});
