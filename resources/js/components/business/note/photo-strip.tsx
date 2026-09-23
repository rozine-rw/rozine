import { useEffect, useState } from 'react';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import type { NotePhoto } from '@/types/business';

/** The design's placeholder tiles (L3681–3685) stand in until the business adds a photo. */
const PLACEHOLDERS: { background: string; icon: IconName }[] = [
    {
        background:
            'linear-gradient(135deg,#1e3aff 0%,#1e3aff 70%,#f4f7fc 100%)',
        icon: 'truck',
    },
    {
        background:
            'radial-gradient(130% 120% at 18% 12%,#1e3aff 0%,#1e3aff 55%,#f4f7fc 100%)',
        icon: 'factory',
    },
    {
        background:
            'linear-gradient(200deg,#1e3aff 0%,#eef3fb 60%,#f4f7fc 100%)',
        icon: 'people',
    },
];

function Picture({
    photo,
    index,
    size,
}: {
    photo: NotePhoto;
    index: number;
    size: 'tile' | 'full';
}) {
    const placeholder = PLACEHOLDERS[index % PLACEHOLDERS.length];

    if (photo.url !== null) {
        return (
            <img
                src={photo.url}
                alt={size === 'full' ? photo.caption : ''}
                className="absolute inset-0 size-full object-cover"
            />
        );
    }

    return (
        <span
            aria-hidden
            className="absolute inset-0 flex items-center justify-center"
            style={{ background: placeholder.background }}
        >
            <span
                className={
                    size === 'tile'
                        ? 'text-[50px] opacity-20'
                        : 'text-[108px] opacity-[.28]'
                }
            >
                <Icon name={placeholder.icon} tone="white" />
            </span>
        </span>
    );
}

/**
 * "PHOTOS · Reported by business" (design L662–673) and the photo lightbox (L2220–2235). The
 * lightbox fills the page on a phone and the detail sheet on a wide screen.
 */
export function PhotoStrip({ photos }: { photos: NotePhoto[] }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState<number | null>(null);
    const step = (from: number, by: number) =>
        setOpen((from + by + photos.length) % photos.length);

    useEffect(() => {
        if (open === null) {
            return;
        }

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(null);
            } else if (event.key === 'ArrowRight') {
                step(open, 1);
            } else if (event.key === 'ArrowLeft') {
                step(open, -1);
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    });

    if (photos.length === 0) {
        return null;
    }

    return (
        <>
            <div className="mt-[18px] flex items-center justify-between">
                <p className="text-[13px] font-semibold tracking-[.04em] text-rz-secondary uppercase">
                    {t('business.note.photos')}
                </p>
                <span className="inline-flex shrink-0 items-center gap-[5px] rounded-[10px] border border-rz-border bg-[#eef2f8] px-[9px] py-1 text-[10px] font-bold tracking-[.02em] text-rz-slate dark:bg-rz-page">
                    {t('business.note.reported_by_business')}
                </span>
            </div>
            <ul className="rz-hscroll -mx-5 mt-2.5 flex gap-2.5 overflow-x-auto px-5">
                {photos.map((photo, index) => (
                    <li key={`${photo.caption}-${index}`} className="shrink-0">
                        <button
                            type="button"
                            onClick={() => setOpen(index)}
                            className="relative block h-[130px] w-[200px] overflow-hidden rounded-2xl text-left"
                        >
                            <Picture photo={photo} index={index} size="tile" />
                            <span className="absolute inset-x-0 bottom-0 bg-[linear-gradient(180deg,rgba(10,15,28,0),rgba(10,15,28,.85))] px-[11px] pt-[18px] pb-2 text-[11px] font-semibold text-white">
                                {photo.caption}
                            </span>
                        </button>
                    </li>
                ))}
            </ul>

            {open !== null && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label={photos[open].caption}
                    className="fixed inset-0 z-[80] flex animate-[rz-fade_.2s_ease] flex-col bg-[rgba(6,9,16,.92)] lg:absolute lg:rounded-[20px_20px_18px_18px]"
                >
                    <div className="flex justify-end px-[18px] pt-[46px] lg:pt-[18px]">
                        <button
                            type="button"
                            onClick={() => setOpen(null)}
                            aria-label={t('business.note.close_photo')}
                            className="size-[38px] rounded-[10px] bg-[rgba(255,255,255,.14)] text-[17px] text-white"
                        >
                            <span aria-hidden>✕</span>
                        </button>
                    </div>
                    <div className="flex min-h-0 flex-1 items-center gap-1 px-1">
                        <button
                            type="button"
                            onClick={() => step(open, -1)}
                            aria-label={t('business.note.previous_photo')}
                            className="flex size-[38px] shrink-0 items-center justify-center rounded-full bg-[rgba(255,255,255,.14)] text-xl text-white"
                        >
                            <span aria-hidden>‹</span>
                        </button>
                        <div className="relative aspect-[4/3] max-h-[70%] min-w-0 flex-1 overflow-hidden rounded-2xl">
                            <Picture
                                photo={photos[open]}
                                index={open}
                                size="full"
                            />
                        </div>
                        <button
                            type="button"
                            onClick={() => step(open, 1)}
                            aria-label={t('business.note.next_photo')}
                            className="flex size-[38px] shrink-0 items-center justify-center rounded-full bg-[rgba(255,255,255,.14)] text-xl text-white"
                        >
                            <span aria-hidden>›</span>
                        </button>
                    </div>
                    <p className="px-6 pb-14 text-center text-sm font-semibold text-white">
                        {photos[open].caption}
                    </p>
                </div>
            )}
        </>
    );
}
