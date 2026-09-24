import { Link } from '@inertiajs/react';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { ApplicationSubmission } from '@/types/business';

/**
 * "Your note has been submitted" (design L603–627), with the application's audit timeline. The
 * stages and the note ID are the server's record, so the timeline moves as review progresses.
 */
export function Submitted({
    submission,
    home,
}: {
    submission: ApplicationSubmission;
    home: RouteLink;
}) {
    const { t } = useTranslation();
    const done = submission.timeline.filter(
        (stage) => stage.state === 'done',
    ).length;
    const fill = Math.max(0, done - 1) / (submission.timeline.length - 1);

    return (
        <div className="pt-5 text-center">
            <div className="mx-auto flex size-[88px] animate-[rz-pop_.5s_ease] items-center justify-center rounded-full bg-rz-accent-soft">
                <div className="flex size-[60px] items-center justify-center rounded-full bg-rz-accent-fill">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                        className="size-[30px]"
                    >
                        <path
                            d="M5 13l4 4L19 7"
                            stroke="#fff"
                            strokeWidth="3"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                </div>
            </div>
            <h2 className="mt-[22px] text-[23px] font-semibold text-rz-ink">
                {t('business.apply.submitted.title')}
            </h2>
            <p className="mt-2.5 text-sm leading-[1.6] text-rz-secondary">
                {t('business.apply.submitted.body')}
            </p>
            <p className="mt-4 inline-flex items-center gap-1.5 rounded-[10px] border border-rz-border bg-rz-surface px-[13px] py-2 text-[12.5px] font-semibold text-rz-slate">
                {t('business.apply.submitted.note_id', {
                    id: submission.note_id,
                })}
            </p>

            <div className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4 text-left">
                <div className="flex items-start gap-[11px]">
                    <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-base">
                        <Icon name="bank" />
                    </span>
                    <div className="flex-1">
                        <p className="text-[13.5px] font-semibold text-rz-ink">
                            {t('business.apply.submitted.funded_title')}
                        </p>
                        <p className="mt-[3px] text-xs leading-[1.55] text-rz-secondary">
                            {t('business.apply.submitted.funded_body')}
                        </p>
                    </div>
                </div>
            </div>

            <div className="mt-6 rounded-2xl border border-rz-border bg-rz-surface px-[18px] pt-[22px] pb-[18px] text-left">
                <ol
                    aria-label={t('business.apply.submitted.timeline')}
                    className="relative flex justify-between"
                >
                    <span
                        aria-hidden
                        className="absolute inset-x-[9px] top-[9px] h-[3px] rounded-[2px] bg-rz-border"
                    />
                    <span
                        aria-hidden
                        className="absolute top-[9px] left-[9px] h-[3px] rounded-[2px] bg-rz-accent-fill"
                        style={{ width: `calc((100% - 18px) * ${fill})` }}
                    />
                    {submission.timeline.map(({ stage, state }) => (
                        <li
                            key={stage}
                            aria-current={
                                state === 'current' ? 'step' : undefined
                            }
                            className="relative z-[1] flex flex-1 flex-col items-center"
                        >
                            <span
                                className={cn(
                                    'flex size-[18px] shrink-0 items-center justify-center rounded-full',
                                    state === 'done' && 'bg-rz-accent-fill',
                                    state === 'current' && 'bg-[#c2661f]',
                                    state === 'pending' &&
                                        'border border-rz-border bg-rz-surface',
                                )}
                            >
                                {state === 'done' && (
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden
                                        className="size-2.5"
                                    >
                                        <path
                                            d="M5 13l4 4L19 7"
                                            stroke="#fff"
                                            strokeWidth="3.5"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                        />
                                    </svg>
                                )}
                            </span>
                            <span
                                className={cn(
                                    'mt-2 text-center text-[11px] leading-[1.3] font-semibold',
                                    state === 'pending'
                                        ? 'text-rz-secondary'
                                        : 'text-rz-ink',
                                )}
                            >
                                {t(`business.apply.submitted.stage.${stage}`)}
                            </span>
                        </li>
                    ))}
                </ol>
            </div>

            <Link
                href={home}
                className="mt-6 flex h-[50px] w-full items-center justify-center rounded-xl bg-rz-accent-fill text-[14.5px] font-semibold text-white"
            >
                {t('business.apply.submitted.back_home')}
            </Link>
        </div>
    );
}
