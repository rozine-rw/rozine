import type { CampaignProgressV2 } from '@/types/business';
import type {
    BusinessCampaignLifecycle,
    RaisingLifecycle,
} from '@/types/settlement';

/** Every open lifecycle the server reports (#96 5885379912). */
const RAISING: readonly string[] = [
    'live',
    'fully_reserved',
    'sold_out_pending_settlement',
    'inventory_unavailable',
    'closing_pending_settlement',
] satisfies RaisingLifecycle[];

/** Settling a full or past-deadline raise: the countdown no longer applies. */
export const SETTLING: readonly RaisingLifecycle[] = [
    'sold_out_pending_settlement',
    'closing_pending_settlement',
];

/** A whole-unit count as the server sends it: digits only. */
const UNITS = /^\d+$/;

const isUnits = (value: unknown): boolean =>
    typeof value === 'string' && UNITS.test(value);

const isMoney = (money: unknown): boolean =>
    typeof money === 'object' &&
    money !== null &&
    isUnits((money as { amount?: unknown }).amount);

/** A funded campaign's closing as the page draws it: a known stage, and a known coarse outcome in flight. */
const isClosing = (closing: unknown): boolean => {
    if (typeof closing !== 'object' || closing === null) {
        return false;
    }

    const { stage, provider } = closing as {
        stage?: unknown;
        provider?: unknown;
    };

    return (
        stage === 'awaiting_disbursement' ||
        (stage === 'in_flight' &&
            (provider === 'pending' || provider === 'unknown'))
    );
};

/**
 * Whether a campaign's progress can be shown. The server supplies every figure and the client
 * never subtracts, so a raise with an unknown lifecycle, a missing unit bucket or amount, or a
 * top-level lifecycle that disagrees with its progress fails closed rather than guessing. So does
 * a funded campaign sent without the funding instant or closing the page reads (#96 5905707280:
 * `funded_pending_disbursement` stays closed until it is bound). Other phases are read as they are.
 */
export function isReadableProgress(
    progress: CampaignProgressV2,
    lifecycle: BusinessCampaignLifecycle,
): boolean {
    if (progress.phase === 'funded') {
        const funded: { funded_at?: unknown; closing?: unknown } = progress;

        return (
            (lifecycle === 'funded' || lifecycle === 'disbursing') &&
            typeof funded.funded_at === 'string' &&
            isMoney(progress.committed) &&
            isClosing(funded.closing)
        );
    }

    if (progress.phase !== 'raising') {
        return true;
    }

    const units: Partial<Record<string, unknown>> = progress.units;

    return (
        RAISING.includes(progress.lifecycle) &&
        progress.lifecycle === lifecycle &&
        ['total', 'available', 'reserved', 'committed', 'unavailable'].every(
            (bucket) => isUnits(units[bucket]),
        ) &&
        [progress.committed, progress.reserved, progress.remaining].every(
            isMoney,
        )
    );
}
