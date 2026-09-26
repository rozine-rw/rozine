import {
    CaptureHandoff,
    STEP_FORM,
    StepEyebrow,
    StepHeading,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { Tick } from '@/components/auditor/ui';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { PhotoSlot, PhotosStage } from '@/types/auditor';

/** The design's stand-in shot tints until a thumbnail syncs (L3294). */
const SHOTS = [
    'linear-gradient(135deg,#3a5a7a,#0c1830)',
    'linear-gradient(135deg,#5a7a3a,#1a2810)',
    'linear-gradient(135deg,#7a5a3a,#281810)',
    'linear-gradient(135deg,#5a3a7a,#1a1028)',
    'linear-gradient(135deg,#3a6a7a,#0c2028)',
    'linear-gradient(135deg,#7a3a4a,#280c14)',
];

const CameraGlyph = (
    <svg viewBox="0 0 24 24" fill="none" aria-hidden className="size-[26px]">
        <rect
            x="3"
            y="6.5"
            width="18"
            height="13"
            rx="2.5"
            stroke="currentColor"
            strokeWidth="1.8"
        />
        <circle
            cx="12"
            cy="13"
            r="3.2"
            stroke="currentColor"
            strokeWidth="1.8"
        />
        <path
            d="M8.5 6.5l1.2-2h4.6l1.2 2"
            stroke="currentColor"
            strokeWidth="1.8"
        />
    </svg>
);

function Slot({
    slot,
    index,
    compact,
}: {
    slot: PhotoSlot;
    index: number;
    compact: boolean;
}) {
    const { t } = useTranslation();
    const label = slot.extra
        ? slot.title || t('auditor.photos.untitled')
        : slot.label;

    if (slot.captured_at === null) {
        return (
            <li className="flex aspect-square flex-col items-center justify-center gap-[7px] overflow-hidden rounded-2xl border-[1.5px] border-dashed border-rz-secondary bg-rz-surface text-rz-secondary">
                {CameraGlyph}
                <span className="px-1 text-center text-[11px] font-semibold">
                    {slot.label}
                </span>
                <span className="sr-only">{t('auditor.photos.pending')}</span>
            </li>
        );
    }

    return (
        <li
            className="relative flex aspect-square items-center justify-center overflow-hidden rounded-2xl border-[1.5px] border-rz-positive bg-black"
            style={
                slot.thumbnail_url === null
                    ? { background: SHOTS[index % SHOTS.length] }
                    : undefined
            }
        >
            {slot.thumbnail_url !== null && (
                <img
                    src={slot.thumbnail_url}
                    alt={label}
                    className="absolute inset-0 size-full object-cover"
                />
            )}
            <span
                className={cn(
                    'absolute top-[7px] right-2 left-2 truncate text-left font-bold text-white [text-shadow:0_1px_3px_rgba(0,0,0,.7)]',
                    compact ? 'text-[10px]' : 'text-[10.5px]',
                )}
            >
                {label}
            </span>
            {slot.position !== null && (
                <span className="absolute right-1.5 bottom-1.5 left-1.5 truncate text-left text-[10px] text-white [text-shadow:0_1px_3px_rgba(0,0,0,.7)]">
                    <Icon name="pin" tone="white" /> {slot.position}
                </span>
            )}
            <span className="relative flex size-[26px] items-center justify-center rounded-full bg-[#17795a] text-white">
                <Tick className="size-[15px]" strokeWidth={3} />
                <span className="sr-only">{t('auditor.photos.captured')}</span>
            </span>
        </li>
    );
}

/**
 * Geo-tagged site photos (design L1106–1142, monthly L1371–1403). Captures happen only in the
 * capture app — camera-locked, geo and time signed on the device — and appear here as they sync.
 * Extra photos carry the partner's own title; nothing captured can be removed from the web.
 */
export function StepPhotos({
    stage,
    context,
    kind,
}: {
    stage: PhotosStage;
    context: StepContext;
    kind: 'flash' | 'monthly';
}) {
    const { t } = useTranslation();
    const extras = stage.slots.filter(
        (slot) => slot.extra && slot.captured_at !== null,
    );
    const { form, submit } = useStepForm(context, {
        titles: Object.fromEntries(
            extras.map((slot) => [slot.key, slot.title]),
        ),
    });
    const captured = stage.slots.filter(
        (slot) => slot.required && slot.captured_at !== null,
    ).length;
    const compact = kind === 'monthly';

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.photos.title')}
                lead={
                    extras.length === 0
                        ? t('auditor.photos.lead', {
                              required: stage.required,
                              captured,
                          })
                        : `${t('auditor.photos.lead', {
                              required: stage.required,
                              captured,
                          })} ${t('auditor.photos.extras', {
                              count: extras.length,
                          })}`
                }
            />
            <ul
                aria-label={t('auditor.photos.grid')}
                className={cn(
                    'mt-3.5 grid',
                    compact ? 'grid-cols-3 gap-[9px]' : 'grid-cols-2 gap-2.5',
                )}
            >
                {stage.slots.map((slot, index) => (
                    <Slot
                        key={slot.key}
                        slot={slot}
                        index={index}
                        compact={compact}
                    />
                ))}
                {stage.package.handoff === null ? (
                    <li className="flex aspect-square flex-col items-center justify-center gap-[7px] overflow-hidden rounded-2xl border-[1.5px] border-dashed border-rz-border bg-rz-surface px-2 text-center text-rz-faint">
                        {CameraGlyph}
                        <span className="text-[11px] font-semibold">
                            {t('auditor.photos.add_unavailable')}
                        </span>
                    </li>
                ) : (
                    <li className="flex aspect-square overflow-hidden rounded-2xl border-[1.5px] border-dashed border-[#1e3aff] bg-[#f7faff] dark:border-rz-investor-text dark:bg-rz-surface-sunken">
                        <a
                            href={stage.package.handoff.url}
                            className="flex size-full flex-col items-center justify-center gap-[7px] text-[#1e3aff] dark:text-rz-investor-text"
                        >
                            {CameraGlyph}
                            <span className="text-[11px] font-semibold">
                                {t('auditor.photos.add')}
                            </span>
                        </a>
                    </li>
                )}
            </ul>
            {extras.length > 0 && (
                <>
                    <StepEyebrow className="mt-3.5">
                        {t('auditor.photos.titles')}
                    </StepEyebrow>
                    <div className="mt-2 flex flex-col gap-[9px]">
                        {extras.map((slot, index) => {
                            const value = form.data.titles[slot.key];
                            const id = `auditor-photo-title-${slot.key}`;

                            return (
                                <div
                                    key={slot.key}
                                    className="rounded-xl border border-rz-border bg-rz-surface px-3 py-[11px]"
                                >
                                    <div className="flex items-center justify-between gap-2.5">
                                        <label
                                            htmlFor={id}
                                            className="text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase"
                                        >
                                            {t('auditor.photos.extra', {
                                                n: index + 1,
                                            })}
                                        </label>
                                        <span className="text-[10px] text-rz-secondary">
                                            {t('auditor.photos.left', {
                                                count:
                                                    stage.title_max -
                                                    value.length,
                                            })}
                                        </span>
                                    </div>
                                    <input
                                        id={id}
                                        value={value}
                                        maxLength={stage.title_max}
                                        placeholder={t(
                                            'auditor.photos.placeholder',
                                        )}
                                        onChange={(event) =>
                                            form.setData('titles', {
                                                ...form.data.titles,
                                                [slot.key]: event.target.value,
                                            })
                                        }
                                        className="mt-[7px] w-full rounded-[10px] border border-rz-border bg-[#f6f9fd] px-3 py-[11px] text-[13px] text-rz-ink outline-none placeholder:text-rz-faint dark:bg-rz-surface-sunken"
                                    />
                                </div>
                            );
                        })}
                    </div>
                </>
            )}
            <CaptureHandoff
                capture={stage.package}
                serverTime={context.serverTime}
                action={t('auditor.capture.open')}
            />
        </form>
    );
}
