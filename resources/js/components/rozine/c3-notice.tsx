import { Icon } from '@/components/rozine/icon';
import { OperationNotice } from '@/components/rozine/operation-notice';
import type { OperationNoticeCopy } from '@/components/rozine/operation-notice';
import type { CommandNotice } from '@/hooks/use-operation-command';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

/** The C3 refusal codes with their own explanation (v2 §2 status groups and the staff codes). */
export const C3_REFUSAL_CODES = [
    'VALIDATION_FAILED',
    'EXPOSURE_LIMIT',
    'INSUFFICIENT_AVAILABLE_FUNDS',
    'VERSION_CONFLICT',
    'IDEMPOTENCY_CONFLICT',
    'RESERVATION_EXPIRED',
    'CAMPAIGN_CLOSED',
    'UNITS_UNAVAILABLE',
    'COMMITMENT_LOCKED',
    'NOTE_INELIGIBLE',
    'DISCLOSURE_STALE',
    'POLICY_INPUT_REQUIRED',
    'DEPOSIT_METHOD_UNVERIFIED',
    'APPLICATION_NOT_RELEASED',
    'DISBURSEMENT_IN_FLIGHT',
    'PROVIDER_OUTCOME_UNRESOLVED',
    'ACTION_FORBIDDEN',
    'IDENTITY_VERIFICATION_REQUIRED',
    'RESTRICTION_ACTIVE',
    'CONNECTED_PARTY',
    'SELF_APPROVAL_FORBIDDEN',
    'STEP_UP_REQUIRED',
    'MANDATE_REQUIRED',
    'STAFF_ACCESS_REQUIRED',
    'STAFF_PERMISSION_REQUIRED',
    'STAFF_VERIFIED_EMAIL_AND_MFA_REQUIRED',
    'MFA_REQUIRED',
    'NOT_FOUND',
] as const;

export type C3RefusalCode = (typeof C3_REFUSAL_CODES)[number];

export const isC3Refusal = (code: string): code is C3RefusalCode =>
    (C3_REFUSAL_CODES as readonly string[]).includes(code);

/** The localized explanation of a refusal code; an unlisted code reads as a generic refusal. */
export function useRefusalText(): (code: string) => string {
    const { t } = useTranslation();

    return (code) =>
        isC3Refusal(code)
            ? t(`settlement.refusal.${code}`)
            : t('settlement.refusal.other');
}

type C3Command = {
    notice: CommandNotice | null;
    busy: boolean;
    retryAllowed: boolean;
    checkAgain: () => void;
    retry: () => void;
};

/**
 * What happened to a C3 command (v2 §2 Recovery). An unknown outcome is looked up before anything
 * is sent again; after the lookup finds no recorded result, the identical request may be sent
 * again only while the page's current `allowed_actions` still permit it.
 */
export function C3Notice({
    command,
    className = 'mb-3',
}: {
    command: C3Command;
    className?: string;
}) {
    const { t } = useTranslation();
    const refusal = useRefusalText();

    if (command.notice === null) {
        return null;
    }

    if (command.notice.kind === 'not_recorded' && !command.retryAllowed) {
        return (
            <p
                role="status"
                className={cn(
                    className,
                    'rounded-2xl border border-rz-border bg-rz-surface p-4 text-xs leading-[1.55] text-rz-secondary',
                )}
            >
                {t('settlement.notice.no_longer_allowed')}
            </p>
        );
    }

    const copy: OperationNoticeCopy = {
        refused: (code) => refusal(code),
        title: (kind) => t(`settlement.notice.${kind}.title`),
        body: (kind) => t(`settlement.notice.${kind}.body`),
        checkAgain: t('settlement.notice.check_again'),
        tryAgain: t('settlement.notice.try_again'),
    };

    return (
        <OperationNotice
            notice={command.notice}
            busy={command.busy}
            copy={copy}
            onCheckAgain={command.checkAgain}
            onRetry={command.retry}
            className={className}
        />
    );
}

/**
 * Shown once bounded polling of a pending or unknown record has stopped: nothing changed on its
 * own, and a refresh reads the current facts.
 */
export function PollStopped({
    exhausted,
    refresh,
    className,
}: {
    exhausted: boolean;
    refresh: () => void;
    className?: string;
}) {
    const { t } = useTranslation();

    if (!exhausted) {
        return null;
    }

    return (
        <div
            role="status"
            className={cn(
                'flex items-center gap-2.5 rounded-xl border border-rz-border bg-rz-surface px-3 py-2.5 text-xs text-rz-secondary',
                className,
            )}
        >
            <Icon name="hourglass" />
            <span className="flex-1">{t('settlement.poll.stopped')}</span>
            <button
                type="button"
                onClick={refresh}
                className="shrink-0 font-bold text-rz-accent-app-text"
            >
                {t('settlement.poll.refresh')}
            </button>
        </div>
    );
}
