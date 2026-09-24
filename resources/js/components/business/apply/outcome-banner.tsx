import type { CommandNotice } from '@/components/business/apply/use-application-command';
import { ErrorBanner } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';

/** The refusal codes with their own explanation; any other 403 reads as a changed access. */
const REFUSALS = [
    'VERSION_CONFLICT',
    'IDEMPOTENCY_CONFLICT',
    'QUOTE_STALE',
    'DOCUMENT_VERSION_STALE',
    'MANDATE_STALE',
    'ACTION_FORBIDDEN',
    'MANDATE_REQUIRED',
    'NOT_FOUND',
] as const;

type Refusal = (typeof REFUSALS)[number];

const isRefusal = (code: string): code is Refusal =>
    (REFUSALS as readonly string[]).includes(code);

/**
 * What happened to the last command (business-application-v1 point 7), in the wizard's card
 * style. An unknown outcome is looked up before anything is sent again; "Try again" appears only
 * once the lookup has found no recorded result, and resends the identical request.
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

    if (notice.kind === 'refused') {
        return (
            <div className="mb-4">
                <ErrorBanner>
                    {isRefusal(notice.code)
                        ? t(`business.apply.outcome.refused.${notice.code}`)
                        : notice.status === 403
                          ? t('business.apply.outcome.refused.denied')
                          : t('business.apply.outcome.refused.failed')}
                </ErrorBanner>
            </div>
        );
    }

    const action =
        notice.kind === 'not_recorded'
            ? { label: t('business.apply.outcome.try_again'), run: onRetry }
            : notice.kind === 'checking'
              ? null
              : {
                    label: t('business.apply.outcome.check_again'),
                    run: onCheckAgain,
                };

    return (
        <div
            role="status"
            className="mb-4 flex items-start gap-3 rounded-2xl border border-[#dbe7ff] bg-rz-surface p-4 dark:border-rz-border"
        >
            <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-base">
                <Icon
                    name={
                        notice.kind === 'not_recorded' ? 'refresh' : 'hourglass'
                    }
                />
            </span>
            <div className="min-w-0 flex-1">
                <p className="text-[13.5px] font-semibold text-rz-ink">
                    {t(`business.apply.outcome.${notice.kind}.title`)}
                </p>
                <p className="mt-[3px] text-xs leading-[1.55] text-rz-secondary">
                    {t(`business.apply.outcome.${notice.kind}.body`)}
                </p>
                {action && (
                    <button
                        type="button"
                        onClick={action.run}
                        disabled={busy}
                        aria-busy={busy || undefined}
                        className="mt-2 p-0 text-[12.5px] font-bold text-rz-accent-app-text"
                    >
                        {action.label}
                    </button>
                )}
            </div>
        </div>
    );
}
