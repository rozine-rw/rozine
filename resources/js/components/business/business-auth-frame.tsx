import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { IconGradients } from '@/components/rozine/icon';
import { LogoMark } from '@/components/rozine/logo';
import { useTranslation } from '@/hooks/use-translation';

type BusinessAuthFrameProps = {
    headTitle: string;
    title: string;
    subtitle: string;
    children: ReactNode;
};

/**
 * The Business app's sign-in surface (design L1524–1566): green star, "rozine / FOR BUSINESS",
 * title, form, and the RDB VERIFIED · SECURE footer. On a phone it fills the screen; on a wide
 * screen the same column sits centred, because the design only draws this flow on mobile.
 */
export function BusinessAuthFrame({
    headTitle,
    title,
    subtitle,
    children,
}: BusinessAuthFrameProps) {
    const { t } = useTranslation();

    return (
        <div
            data-audience="business"
            className="rz-surface min-h-svh bg-rz-surface"
        >
            <Head title={headTitle} />
            <IconGradients app="business" />
            <div className="mx-auto flex min-h-svh w-full max-w-[412px] flex-col px-6 pt-[calc(env(safe-area-inset-top)+10px)] pb-7 lg:pt-[60px]">
                <div className="flex items-center gap-[11px]">
                    <LogoMark className="h-[54px] w-[52px] shrink-0 text-rz-accent-fill" />
                    <div>
                        <p className="text-base font-semibold tracking-[.06em] text-rz-ink">
                            {t('business.auth.wordmark')}
                        </p>
                        <p className="text-[11px] font-semibold tracking-[.08em] text-rz-secondary uppercase">
                            {t('business.auth.for_business')}
                        </p>
                    </div>
                </div>
                <h1 className="mt-[34px] text-2xl font-semibold text-rz-ink">
                    {title}
                </h1>
                <p className="mt-[7px] text-sm leading-[1.55] text-rz-secondary">
                    {subtitle}
                </p>

                {children}

                <div className="mt-auto flex items-center justify-center gap-[22px] pt-[34px]">
                    {(
                        [
                            'business.auth.rdb_verified',
                            'business.auth.secure',
                        ] as const
                    ).map((code) => (
                        <span
                            key={code}
                            className="flex items-center gap-[7px] text-[11.5px] font-semibold text-rz-secondary uppercase"
                        >
                            <span className="size-1.5 rounded-full bg-rz-accent-fill" />
                            {t(code)}
                        </span>
                    ))}
                </div>
            </div>
        </div>
    );
}
