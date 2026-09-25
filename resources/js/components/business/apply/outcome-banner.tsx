import type { CommandNotice } from '@/components/business/apply/use-application-command';
import { OperationNotice } from '@/components/rozine/operation-notice';
import { useTranslation } from '@/hooks/use-translation';

/** The refusal codes with their own explanation; any other 403 reads as a changed access. */
const REFUSALS = [
    'VERSION_CONFLICT',
    'IDEMPOTENCY_CONFLICT',
    'QUOTE_STALE',
    'DOCUMENT_VERSION_STALE',
    'MANDATE_STALE',
    /* Another application of this business is still under review (one at a time in C2). */
    'APPLICATION_PENDING_REVIEW',
    /* A submit that reached the server outside Review: a fresh read shows where it stands. */
    'APPLICATION_STEP_INVALID',
    'ACTION_FORBIDDEN',
    'MANDATE_REQUIRED',
    'NOT_FOUND',
] as const;

type Refusal = (typeof REFUSALS)[number];

const isRefusal = (code: string): code is Refusal =>
    (REFUSALS as readonly string[]).includes(code);

/**
 * What happened to the last command (business-application-v1 point 7), in the wizard's card
 * style, through the shared operation notice.
 */
export function OutcomeBanner({
    notice,
    busy,
    onCheckAgain,
    onRetry,
}: {
    notice: CommandNotice;
    busy: boolean;
    onCheckAgain: () => void;
    onRetry: () => void;
}) {
    const { t } = useTranslation();

    return (
        <OperationNotice
            notice={notice}
            busy={busy}
            onCheckAgain={onCheckAgain}
            onRetry={onRetry}
            copy={{
                refused: (code, status) =>
                    isRefusal(code)
                        ? t(`business.apply.outcome.refused.${code}`)
                        : status === 403
                          ? t('business.apply.outcome.refused.denied')
                          : t('business.apply.outcome.refused.failed'),
                title: (kind) => t(`business.apply.outcome.${kind}.title`),
                body: (kind) => t(`business.apply.outcome.${kind}.body`),
                checkAgain: t('business.apply.outcome.check_again'),
                tryAgain: t('business.apply.outcome.try_again'),
            }}
        />
    );
}
