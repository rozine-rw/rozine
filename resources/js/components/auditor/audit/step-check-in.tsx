import { EvidenceList } from '@/components/auditor/audit/evidence';
import {
    AmberNote,
    CaptureHandoff,
    STEP_FORM,
    StepHeading,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { formatKigaliTime } from '@/components/auditor/clock';
import { Tick } from '@/components/auditor/ui';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import type { CheckInStage } from '@/types/auditor';

/**
 * Check in on site (design L1095–1104). The design's button only sets a flag; the real check-in is
 * the capture app's signed location observation, so this step shows its state and hands off.
 */
export function StepCheckIn({
    stage,
    context,
}: {
    stage: CheckInStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { submit } = useStepForm(context, {});
    const recorded =
        stage.check_in.state === 'recorded' ? stage.check_in : null;

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.checkin.title')}
                lead={t('auditor.checkin.lead')}
            />
            <div className="relative mt-3.5 h-[180px] overflow-hidden rounded-2xl border border-[#dbe3ee] bg-rz-surface dark:border-rz-border">
                <div className="absolute inset-0 bg-[linear-gradient(rgba(194,102,31,.06)_1px,transparent_1px),linear-gradient(90deg,rgba(194,102,31,.06)_1px,transparent_1px)] bg-[size:24px_24px]" />
                <svg
                    viewBox="0 0 24 24"
                    aria-hidden
                    className="absolute top-[46%] left-1/2 size-[34px] -translate-x-1/2 -translate-y-full"
                >
                    <path
                        d="M12 22s7-6.6 7-12a7 7 0 1 0-14 0c0 5.4 7 12 7 12z"
                        fill={recorded === null ? '#c2661f' : '#17795a'}
                        stroke="#fff"
                        strokeWidth="1.4"
                    />
                    <circle cx="12" cy="10" r="2.6" fill="#fff" />
                </svg>
                <span className="absolute bottom-2.5 left-3 rounded-[10px] bg-white/85 px-2 py-1 text-[10px] font-semibold text-[#5b6680] dark:bg-rz-surface/90 dark:text-rz-secondary">
                    <Icon name="pin" tone="amber" />{' '}
                    {recorded === null
                        ? t('auditor.checkin.waiting')
                        : recorded.evidence.position === null
                          ? t('auditor.checkin.position_unavailable')
                          : recorded.evidence.accuracy_m === null
                            ? recorded.evidence.position
                            : t('auditor.checkin.position', {
                                  position: recorded.evidence.position,
                                  accuracy: recorded.evidence.accuracy_m,
                              })}
                </span>
            </div>
            {recorded !== null && (
                <div className="mt-3.5 flex h-[50px] w-full items-center justify-center gap-2 rounded-2xl bg-[#17795a] text-[14px] font-bold text-white">
                    <Tick className="size-4" strokeWidth={3} />
                    {t('auditor.checkin.done', {
                        time: formatKigaliTime(recorded.at),
                    })}
                </div>
            )}
            {recorded !== null && (
                <EvidenceList items={[recorded.evidence]} className="mt-3.5" />
            )}
            {recorded?.review && (
                <AmberNote title={t('auditor.checkin.review_title')}>
                    {t('auditor.checkin.review_body')}
                </AmberNote>
            )}
            <CaptureHandoff
                capture={stage.package}
                serverTime={context.serverTime}
                action={
                    recorded === null
                        ? t('auditor.checkin.open')
                        : t('auditor.capture.open')
                }
            />
        </form>
    );
}
