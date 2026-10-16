import { Chip } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { C3DisbursementState, Tone } from '@/types/admin';
import type { ProviderOutcomeState } from '@/types/settlement';

export const STATE_TONE: Record<C3DisbursementState, Tone> = {
    ready: 'blue',
    awaiting_second_approver: 'purple',
    on_hold: 'amber',
    queued: 'blue',
    dispatched: 'blue',
    succeeded: 'green',
    failed_closing: 'red',
};

/**
 * Pending and unknown are "not yet confirmed" in grey and amber: never the green of a verified
 * success or the red of a verified final failure.
 */
const PROVIDER_TONE: Record<ProviderOutcomeState, Tone> = {
    pending: 'grey',
    unknown: 'amber',
    succeeded: 'green',
    failed: 'red',
};

export function DisbursementStateChip({
    state,
}: {
    state: C3DisbursementState;
}) {
    const { t } = useTranslation();

    return (
        <Chip tone={STATE_TONE[state]}>
            {t(`admin.disbursements.state.${state}`)}
        </Chip>
    );
}

/** What the provider has said about the payment; nothing before the worker has sent it. */
export function ProviderStateChip({
    state,
}: {
    state: ProviderOutcomeState | null;
}) {
    const { t } = useTranslation();

    if (state === null) {
        return (
            <span className="text-[12px] text-rz-faint">
                {t('admin.disbursements.provider.none')}
            </span>
        );
    }

    return (
        <Chip tone={PROVIDER_TONE[state]}>
            {t(`admin.disbursements.provider.${state}`)}
        </Chip>
    );
}
