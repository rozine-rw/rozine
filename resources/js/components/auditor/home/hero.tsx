import type { ReactNode } from 'react';
import { kigaliHour } from '@/components/auditor/clock';
import { compactAmount } from '@/components/auditor/money';
import { initials } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatMonthYear } from '@/lib/rozine/format';
import type { Money } from '@/types';
import type { AuditorIdentity } from '@/types/auditor';

type HeroProps = {
    serverTime: string;
    auditor: AuditorIdentity;
    qualityScore: number | null;
    earned: Money | null;
    activeDeals: number;
    licenceExpiresOn: string;
};

function GlassTile({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="min-w-0 flex-1 rounded-xl bg-white/15 px-[11px] py-2.5">
            <p className="text-[10px] font-bold tracking-[.03em] whitespace-nowrap text-white/78 uppercase">
                {label}
            </p>
            <div className="mt-[3px] flex items-baseline gap-[3px] text-white">
                {children}
            </div>
        </div>
    );
}

/** The avatar disc: the passport photo, or the partner's initials until one is on file. */
export function Avatar({
    auditor,
    className,
}: {
    auditor: AuditorIdentity;
    className: string;
}) {
    return (
        <span className={className}>
            {auditor.avatar_url === null ? (
                <span aria-hidden>{initials(auditor.name)}</span>
            ) : (
                <img
                    src={auditor.avatar_url}
                    alt=""
                    className="size-full object-cover"
                />
            )}
        </span>
    );
}

/**
 * The orange identity card (design L114–134): greeting, name, firm and accreditation, the quality
 * ring, and three glass tiles. The design's "Pass rate" tile is replaced by the licence expiry:
 * the partner no longer passes or fails anyone (MVP-AUDITOR-AC-02).
 */
export function Hero({
    serverTime,
    auditor,
    qualityScore,
    earned,
    activeDeals,
    licenceExpiresOn,
}: HeroProps) {
    const { t, locale } = useTranslation();
    const hour = kigaliHour(serverTime);
    const greeting =
        hour < 12
            ? t('auditor.home.greeting.morning')
            : hour < 18
              ? t('auditor.home.greeting.afternoon')
              : t('auditor.home.greeting.evening');
    const month = new Intl.DateTimeFormat(locale === 'en' ? 'en-GB' : locale, {
        month: 'short',
        timeZone: 'Africa/Kigali',
    }).format(new Date(serverTime));

    return (
        <div className="relative overflow-hidden rounded-[20px] bg-[#c2661f] p-[17px] shadow-[inset_0_0_0_1px_rgba(255,255,255,.14)]">
            <div className="relative flex items-center gap-[13px]">
                <Avatar
                    auditor={auditor}
                    className="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-full border-[1.5px] border-white/35 text-[18px] font-bold text-white/85 shadow-[0_6px_16px_-6px_rgba(0,0,0,.4)]"
                />
                <div className="min-w-0 flex-1">
                    <p className="text-[11.5px] font-medium text-white/82">
                        {greeting}
                    </p>
                    <p className="truncate text-[17px] font-bold text-white">
                        {auditor.name}
                    </p>
                    <p className="mt-0.5 truncate text-[11px] text-white/80">
                        {auditor.firm} · {auditor.accreditation}
                    </p>
                </div>
                {qualityScore !== null && (
                    <div
                        role="img"
                        aria-label={t('auditor.rating.label', {
                            score: qualityScore,
                        })}
                        className="flex size-[52px] shrink-0 items-center justify-center rounded-full"
                        style={{
                            background: `conic-gradient(#ffffff ${(qualityScore / 100) * 360}deg, rgba(255,255,255,.28) 0)`,
                        }}
                    >
                        <div className="flex size-[41px] flex-col items-center justify-center rounded-full bg-[#8a3d00]">
                            <span className="text-[15px] leading-none font-bold text-white">
                                {qualityScore}
                            </span>
                            <span className="text-[10px] font-bold tracking-[.06em] text-white uppercase">
                                {t('auditor.rating.word')}
                            </span>
                        </div>
                    </div>
                )}
            </div>
            <div className="relative mt-[15px] flex gap-2">
                <GlassTile
                    label={t('auditor.home.tile.yield', {
                        month: month.replace('.', '').slice(0, 3).toUpperCase(),
                    })}
                >
                    {earned === null ? (
                        <span className="text-[15px] font-bold">—</span>
                    ) : (
                        <>
                            <span className="text-[10px] font-bold opacity-80">
                                {t('common.currency.rwf')}
                            </span>
                            <span className="text-[15px] font-bold">
                                {compactAmount(earned)}
                            </span>
                        </>
                    )}
                </GlassTile>
                <GlassTile label={t('auditor.home.tile.deals')}>
                    <span className="text-[15px] font-bold">{activeDeals}</span>
                </GlassTile>
                <GlassTile label={t('auditor.home.tile.licence')}>
                    <span className="truncate text-[15px] font-bold">
                        {formatMonthYear(licenceExpiresOn, locale)}
                    </span>
                </GlassTile>
            </div>
        </div>
    );
}
