import type * as InertiaCore from '@inertiajs/core';
import { describe, expect, it, vi } from 'vite-plus/test';
import BusinessCampaign from '@/pages/business/campaign';
import type { BusinessCampaignProps } from '@/types/business';
import awaitingFixture from '../../../resources/fixtures/ui/business-campaign-funded-awaiting.json';
import { renderWithUser } from '../helpers/render-with-user';

vi.mock('@inertiajs/react', () => import('../auditor/inertia'));
vi.mock('@inertiajs/core', async (importOriginal) => ({
    ...(await importOriginal<typeof InertiaCore>()),
    http: { onResponse: () => () => undefined },
}));

/* Review #175 33ba9040..3bb74427: the exact note.progress the server sends for a funded campaign (captured over HTTP). */
const serverFunded = {
    phase: 'funded',
    lifecycle: 'funded_pending_disbursement',
    restriction: null,
    committed: { currency: 'RWF', amount: '10800000' },
    reserved: { currency: 'RWF', amount: '0' },
    remaining: { currency: 'RWF', amount: '0' },
    units: { total: '2160', available: '0', reserved: '0', committed: '2160', unavailable: '0' },
    investors: 2,
    funded_pct: '100.0',
    clock: { starts_at: '2026-09-30T08:49:40+00:00', expires_at: '2026-10-30T08:49:40+00:00' },
};

describe('the funded page as #175 sends it', () => {
    it('control: the agreed funded fixture renders', () => {
        const page = structuredClone(awaitingFixture.props) as BusinessCampaignProps;
        expect(() => renderWithUser(<BusinessCampaign {...page} />)).not.toThrow();
    });

    it('OBSERVE: the server shape does not fail closed, it throws while rendering', () => {
        const page = structuredClone(awaitingFixture.props) as unknown as Record<string, any>;
        page.campaign.lifecycle = 'funded_pending_disbursement';
        page.note.progress = serverFunded;
        page.allowed_actions = [];
        page.actions = { cancel: null };
        const errors = vi.spyOn(console, 'error').mockImplementation(() => undefined);
        let thrown: unknown = null;
        try {
            renderWithUser(<BusinessCampaign {...(page as BusinessCampaignProps)} />);
        } catch (error) {
            thrown = error;
        }
        errors.mockRestore();
        console.log('thrown:', thrown instanceof Error ? `${thrown.name}: ${thrown.message}` : thrown);
        expect(thrown).toBeInstanceOf(TypeError);
    });
});
