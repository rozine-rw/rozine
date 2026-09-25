import { OperationNotice } from '@/components/rozine/operation-notice';
import type { CommandNotice } from '@/hooks/use-operation-command';
import { useTranslation } from '@/hooks/use-translation';

/** The refusal codes with their own explanation; any other reads as a denial or a failure. */
const REFUSALS = [
    'VERSION_CONFLICT',
    'IDEMPOTENCY_CONFLICT',
    'DIGEST_STALE',
    'MANDATE_STALE',
    'ACTION_FORBIDDEN',
    'MANDATE_REQUIRED',
    'NOT_FOUND',
] as const;

type Refusal = (typeof REFUSALS)[number];

const isRefusal = (code: string): code is Refusal =>
    (REFUSALS as readonly string[]).includes(code);

/**
 * What happened to the co-signature, through the shared operation notice. A refusal names the
 * server's code exactly as sent beside its explanation, so a new report-specific code still
 * reaches the business before it has its own words.
 */
export function CosignNotice({
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

    const reason = (code: string, status: number): string => {
        if (isRefusal(code)) {
            return t(`business.audit_cosign.refused.${code}`);
        }

        return status === 403
            ? t('business.audit_cosign.refused.denied')
            : t('business.audit_cosign.refused.failed');
    };

    return (
        <OperationNotice
            notice={notice}
            busy={busy}
            onCheckAgain={onCheckAgain}
            onRetry={onRetry}
            className="mt-4"
            copy={{
                refused: (code, status) =>
                    t('business.audit_cosign.refused.with_code', {
                        reason: reason(code, status),
                        code,
                    }),
                title: (kind) => t(`business.apply.outcome.${kind}.title`),
                body: (kind) => t(`business.apply.outcome.${kind}.body`),
                checkAgain: t('business.apply.outcome.check_again'),
                tryAgain: t('business.apply.outcome.try_again'),
            }}
        />
    );
}
