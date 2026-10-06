import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { InvestorAuthFrame } from '@/components/investor/auth/auth-frame';
import { LogoLockup } from '@/components/rozine/logo';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { InvestorIntroProps } from '@/types/investor';

type Slide = 'audit' | 'rate' | 'exit';

const SLIDES: Slide[] = ['audit', 'rate', 'exit'];

/** Icon tile and check colours per slide (design L10186–10198; the exit slide's ink check is fixed to amber). */
const TONE: Record<Slide, { tile: string; dot: string; stroke: string }> = {
    audit: {
        tile: 'bg-[rgba(30,58,255,.10)]',
        dot: 'bg-[rgba(30,58,255,.10)] text-rz-accent-app-text',
        stroke: '#1e3aff',
    },
    rate: {
        tile: 'bg-[rgba(29,158,117,.10)]',
        dot: 'bg-[#17795a] text-white',
        stroke: '#17795a',
    },
    exit: {
        tile: 'bg-[rgba(194,102,31,.10)]',
        dot: 'bg-[rgba(194,102,31,.10)] text-[#c2661f]',
        stroke: '#c2661f',
    },
};

function SlideIcon({ slide }: { slide: Slide }) {
    const stroke = TONE[slide].stroke;

    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden
            className="size-[34px]"
        >
            {slide === 'audit' && (
                <>
                    <path
                        d="M12 3 20 6v5c0 5-4 8-8 10-4-2-8-5-8-10V6Z"
                        stroke={stroke}
                        strokeWidth="1.8"
                        strokeLinejoin="round"
                    />
                    <path
                        d="M9 11.5l2 2 4-4"
                        stroke={stroke}
                        strokeWidth="1.9"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </>
            )}
            {slide === 'rate' && (
                <>
                    <path
                        d="M4 17 10 11l4 4 6-7"
                        stroke={stroke}
                        strokeWidth="1.9"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                    <path
                        d="M15 7h5v5"
                        stroke={stroke}
                        strokeWidth="1.9"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </>
            )}
            {slide === 'exit' && (
                <path
                    d="M13 3 5 14h5l-1 7 8-11h-5l1-7z"
                    stroke={stroke}
                    strokeWidth="1.8"
                    strokeLinejoin="round"
                />
            )}
        </svg>
    );
}

/**
 * What Rozine is, before sign-up (design L3166–3212): three slides, then role choice. The copy is
 * the design's, with the rate range, terms and note price taken from policy.
 */
export default function InvestorIntro({ pitch, links }: InvestorIntroProps) {
    const { t } = useTranslation();
    const [index, setIndex] = useState(0);
    const slide = SLIDES[index];
    const last = index === SLIDES.length - 1;
    const values = {
        min: pitch.rate_min_pct,
        max: pitch.rate_max_pct,
        term_min: pitch.term_min_months,
        term_max: pitch.term_max_months,
        price: formatRwf(pitch.unit_price),
    };

    return (
        <InvestorAuthFrame
            title={t('investor.intro.head_title')}
            layout="column"
        >
            <div className="flex min-h-svh flex-1 flex-col px-6 pt-[calc(env(safe-area-inset-top)+26px)] pb-[calc(env(safe-area-inset-bottom)+30px)] lg:min-h-[694px] lg:pt-[70px]">
                <div className="flex items-center justify-between gap-2.5">
                    <LogoLockup
                        title={t('common.brand.name')}
                        className="block h-[25px] w-auto text-rz-accent-lockup"
                    />
                    <Link
                        href={links.start}
                        className="px-0.5 py-1.5 text-[13px] font-semibold text-rz-secondary"
                    >
                        {t('investor.intro.skip')}
                    </Link>
                </div>

                <section
                    aria-roledescription={t('investor.intro.slide')}
                    aria-label={t('investor.intro.slide_of', {
                        index: index + 1,
                        count: SLIDES.length,
                    })}
                    className="flex min-h-0 flex-1 flex-col justify-center py-8"
                >
                    <span
                        className={cn(
                            'flex size-[76px] items-center justify-center rounded-[20px]',
                            TONE[slide].tile,
                        )}
                    >
                        <SlideIcon slide={slide} />
                    </span>
                    <h1 className="mt-[26px] text-[27px] leading-[1.2] font-bold tracking-[-.5px] text-rz-ink">
                        {t(`investor.intro.${slide}.title`, values)}
                    </h1>
                    <p className="mt-3 max-w-[330px] text-[15px] leading-[1.6] text-[#5b6680] dark:text-rz-body">
                        {t(`investor.intro.${slide}.body`, values)}
                    </p>
                    <ul className="mt-[22px] flex flex-col gap-2.5">
                        {([1, 2, 3] as const).map((point) => (
                            <li
                                key={point}
                                className="flex items-start gap-2.5"
                            >
                                <span
                                    aria-hidden
                                    className={cn(
                                        'mt-0.5 flex size-[18px] shrink-0 items-center justify-center rounded-full text-[11px] font-extrabold',
                                        TONE[slide].dot,
                                    )}
                                >
                                    ✓
                                </span>
                                <span className="text-[13.5px] leading-normal text-rz-slate">
                                    {t(
                                        `investor.intro.${slide}.point_${point}`,
                                        values,
                                    )}
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>

                <div className="shrink-0">
                    <div aria-hidden className="flex items-center gap-1.5">
                        {SLIDES.map((dot, at) => (
                            <span
                                key={dot}
                                className={cn(
                                    'h-[7px] rounded-full transition-[width] duration-200',
                                    at === index
                                        ? 'w-[22px] bg-rz-accent-fill'
                                        : 'w-[7px] bg-rz-border',
                                )}
                            />
                        ))}
                    </div>
                    <div className="mt-[18px] flex items-center gap-2.5">
                        <button
                            type="button"
                            disabled={index === 0}
                            onClick={() => setIndex(index - 1)}
                            className="h-[52px] shrink-0 rounded-2xl border border-rz-border bg-rz-surface px-5 text-[15px] font-semibold text-rz-slate disabled:opacity-40"
                        >
                            {t('investor.intro.back')}
                        </button>
                        {last ? (
                            <Link
                                href={links.start}
                                className="flex h-[52px] flex-1 items-center justify-center rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white"
                            >
                                {t('investor.intro.start')}
                            </Link>
                        ) : (
                            <button
                                type="button"
                                onClick={() => setIndex(index + 1)}
                                className="h-[52px] flex-1 rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white"
                            >
                                {t('investor.intro.next')}
                            </button>
                        )}
                    </div>
                    <p className="mt-4 text-center text-[13.5px] text-rz-secondary">
                        {t('investor.auth.have_account')}{' '}
                        <Link
                            href={links.login}
                            className="font-semibold text-rz-accent-app-text"
                        >
                            {t('investor.auth.log_in')}
                        </Link>
                    </p>
                </div>
            </div>
        </InvestorAuthFrame>
    );
}
