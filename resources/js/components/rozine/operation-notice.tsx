import { ErrorBanner } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import type { CommandNotice } from '@/hooks/use-operation-command';

/** The words for one notice, already translated by the app that shows it. */
export type OperationNoticeCopy = {
    /** Why a refused command was refused. */
    refused: (code: string, status: number) => string;
    title: (kind: Exclude<CommandNotice['kind'], 'refused'>) => string;
    body: (kind: Exclude<CommandNotice['kind'], 'refused'>) => string;
    checkAgain: string;
    tryAgain: string;
};

/**
 * What happened to the last command (the shared operation contract, point 7), as a card. An
 * unknown outcome is looked up before anything is sent again; "Try again" appears only once the
 * lookup has found no recorded result, and resends the identical request.
 */
export function OperationNotice({
    notice,
    busy,
    copy,
    onCheckAgain,
    onRetry,
    className = 'mb-4',
}: {
    notice: CommandNotice;
    busy: boolean;
    copy: OperationNoticeCopy;
    onCheckAgain: () => void;
    onRetry: () => void;
    className?: string;
}) {
    if (notice.kind === 'refused') {
        return (
            <div className={className}>
                <ErrorBanner>
                    {copy.refused(notice.code, notice.status)}
                </ErrorBanner>
            </div>
        );
    }

    const action =
        notice.kind === 'not_recorded'
            ? { label: copy.tryAgain, run: onRetry }
            : notice.kind === 'checking'
              ? null
              : { label: copy.checkAgain, run: onCheckAgain };

    return (
        <div
            role="status"
            className={`${className} flex items-start gap-3 rounded-2xl border border-[#dbe7ff] bg-rz-surface p-4 dark:border-rz-border`}
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
                    {copy.title(notice.kind)}
                </p>
                <p className="mt-[3px] text-xs leading-[1.55] text-rz-secondary">
                    {copy.body(notice.kind)}
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
