import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import {
    LoginFields,
    StartFields,
    SwitchLine,
} from '@/components/investor/auth/auth-forms';
import { InvestorAuthFrame } from '@/components/investor/auth/auth-frame';
import { LogoStack } from '@/components/investor/logo-stack';
import { LogoMark } from '@/components/rozine/logo';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { InvestorAuthProps, InvestorType } from '@/types/investor';

const TYPE_GLYPH: Record<InvestorType, ReactNode> = {
    individual: (
        <>
            <circle
                cx="12"
                cy="8"
                r="3.6"
                stroke="currentColor"
                strokeWidth="1.9"
            />
            <path
                d="M4.5 20c0-3.7 3.6-5.6 7.5-5.6s7.5 1.9 7.5 5.6"
                stroke="currentColor"
                strokeWidth="1.9"
                strokeLinecap="round"
            />
        </>
    ),
    institution: (
        <>
            <path
                d="M3.5 20h17M5 20V9.5l7-4.5 7 4.5V20"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M9.5 20v-5h5v5"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinejoin="round"
            />
        </>
    ),
};

function TypeGlyph({
    type,
    className,
}: {
    type: InvestorType;
    className: string;
}) {
    return (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden className={className}>
            {TYPE_GLYPH[type]}
        </svg>
    );
}

/** Phone role choice (design L3303–3358): who is investing. */
function RoleChoice({
    pitch,
    links,
}: Pick<InvestorAuthProps, 'pitch' | 'links'>) {
    const { t } = useTranslation();

    return (
        <div className="flex min-h-svh flex-col px-6 pt-[calc(env(safe-area-inset-top)+42px)] pb-[calc(env(safe-area-inset-bottom)+30px)]">
            <div className="flex flex-col items-center">
                <LogoStack
                    title={t('common.brand.name')}
                    className="w-[60px] text-rz-accent-lockup"
                />
                <p className="mt-3 text-center text-[13px] text-rz-secondary">
                    {t('investor.auth.tagline')}
                </p>
            </div>
            <div className="mt-[18px] flex flex-col gap-[13px]">
                {(['individual', 'institution'] as const).map((type) => (
                    <Link
                        key={type}
                        href={links[type]}
                        className={cn(
                            'flex items-start gap-3.5 rounded-2xl border-[1.5px] bg-rz-surface p-[18px] text-left',
                            type === 'individual'
                                ? 'border-[#cfe0ff] dark:border-rz-investor'
                                : 'border-[#d3dae6] dark:border-rz-border',
                        )}
                    >
                        <span
                            className={cn(
                                'flex size-12 shrink-0 items-center justify-center rounded-2xl',
                                type === 'individual'
                                    ? 'bg-rz-accent-soft text-rz-accent-app-text'
                                    : 'bg-[rgba(12,24,48,.09)] text-rz-ink dark:bg-rz-surface-muted',
                            )}
                        >
                            <TypeGlyph type={type} className="size-6" />
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="block text-[16.5px] font-bold text-rz-ink">
                                {t(`investor.auth.role.${type}`)}
                            </span>
                            <span className="mt-1 block text-[12.5px] leading-normal text-rz-secondary">
                                {t(`investor.auth.role.${type}_body`, {
                                    price: formatRwf(pitch.unit_price),
                                })}
                            </span>
                        </span>
                        <span
                            aria-hidden
                            className={cn(
                                'shrink-0 text-[22px]',
                                type === 'individual'
                                    ? 'text-rz-accent-app-text'
                                    : 'text-rz-ink',
                            )}
                        >
                            ›
                        </span>
                    </Link>
                ))}
            </div>
            <SwitchLine
                prompt={t('investor.auth.have_account')}
                action={t('investor.auth.log_in')}
                href={links.login}
                className="mt-5 border-t border-[#eef2f9] pt-4 text-[13.5px] dark:border-rz-divider"
            />
        </div>
    );
}

/** Phone login (design L3489–3552). */
function PhoneLogin({ actions, links, status }: InvestorAuthProps) {
    const { t } = useTranslation();

    return (
        <div className="flex min-h-svh flex-col px-6 pt-[calc(env(safe-area-inset-top)+14px)] pb-[calc(env(safe-area-inset-bottom)+30px)]">
            <Link
                href={links.register}
                aria-label={t('investor.common.back')}
                className="flex size-[38px] items-center justify-center self-start rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
            >
                <span aria-hidden>←</span>
            </Link>
            <div className="mt-[18px] flex flex-col items-center">
                <LogoMark className="w-14 text-rz-accent-lockup" />
                <h1 className="mt-3.5 text-[22px] font-semibold text-rz-ink">
                    {t('investor.auth.login.title')}
                </h1>
                <p className="mt-[5px] text-[13.5px] text-rz-secondary">
                    {t('investor.auth.login.subtitle')}
                </p>
            </div>
            <LoginFields
                action={actions.login}
                variant="phone"
                status={status}
            />
            <SwitchLine
                prompt={t('investor.auth.new_to_rozine')}
                action={t('investor.auth.create_account')}
                href={links.register}
                className="mt-auto pt-[26px] text-[13.5px]"
            />
        </div>
    );
}

/**
 * The wide-screen auth panel (design L3215–3301): investor-type toggles and the log-in or sign-up
 * card beside the brand panel. The brand panel's three bullets are the design's clean strings — its
 * markup there is corrupted — and they sit white on the blue gradient, which the design's recolour
 * had turned grey.
 */
function DeskPanel(props: InvestorAuthProps) {
    const { t } = useTranslation();
    const login = props.mode === 'login';
    const values = {
        min: props.pitch.rate_min_pct,
        max: props.pitch.rate_max_pct,
        price: formatRwf(props.pitch.unit_price),
    };

    return (
        <div className="flex min-h-[640px] items-stretch gap-[22px] px-6 pt-[18px] pb-6 lg:h-full">
            <section className="relative order-2 flex min-w-0 flex-[0_0_41%] flex-col justify-center overflow-hidden rounded-3xl bg-[linear-gradient(158deg,#2440ff_0%,#1e3aff_56%,#07225f_100%)] px-8 py-[38px]">
                <span className="pointer-events-none absolute -top-[70px] -right-[60px] size-60 rounded-full bg-[radial-gradient(circle,rgba(255,255,255,.14),rgba(255,255,255,0)_70%)]" />
                <span className="pointer-events-none absolute -bottom-[90px] -left-[70px] size-[230px] rounded-full bg-[radial-gradient(circle,rgba(255,255,255,.09),rgba(255,255,255,0)_70%)]" />
                <LogoStack
                    title={t('common.brand.name')}
                    className="relative w-[66px] text-white"
                />
                <h2 className="relative mt-[26px] text-[27px] leading-[1.25] font-bold tracking-[-.4px] text-pretty text-white">
                    {t('investor.auth.brand_headline', values)}
                </h2>
                <ul className="relative mt-6 flex flex-col gap-3.5">
                    {([1, 2, 3] as const).map((point) => (
                        <li key={point} className="flex items-start gap-[11px]">
                            <span
                                aria-hidden
                                className="mt-px flex size-5 shrink-0 items-center justify-center rounded-full bg-white/[.14] text-[11px] font-extrabold text-white"
                            >
                                ✓
                            </span>
                            <span className="text-[13.5px] leading-normal text-white/90">
                                {t(
                                    `investor.auth.brand_point_${point}`,
                                    values,
                                )}
                            </span>
                        </li>
                    ))}
                </ul>
                <p className="relative mt-[30px] border-t border-white/[.18] pt-5 text-xs text-white/80">
                    {t('investor.auth.tagline')}
                </p>
            </section>

            <div className="order-1 flex min-w-0 flex-1 flex-col justify-center">
                <div className="mb-3.5 flex gap-2.5">
                    {(['individual', 'institution'] as const).map((type) => {
                        const on = props.investor_type === type;

                        return (
                            <Link
                                key={type}
                                href={
                                    type === 'individual'
                                        ? props.links.pick_individual
                                        : props.links.pick_institution
                                }
                                preserveScroll
                                aria-current={on ? 'true' : undefined}
                                className={cn(
                                    'min-w-0 flex-1 rounded-2xl border-[1.5px] p-3.5 text-left',
                                    on
                                        ? 'border-rz-accent-fill bg-[#f5f9ff] dark:bg-rz-accent-soft'
                                        : 'border-rz-border bg-rz-surface',
                                )}
                            >
                                <span className="flex items-center gap-[9px]">
                                    <span
                                        className={cn(
                                            'flex size-[30px] shrink-0 items-center justify-center rounded-[10px]',
                                            on
                                                ? 'bg-rz-accent-soft text-rz-accent-app-text'
                                                : 'bg-[#f3f6fc] text-rz-secondary dark:bg-rz-surface-muted',
                                        )}
                                    >
                                        <TypeGlyph
                                            type={type}
                                            className="size-[17px]"
                                        />
                                    </span>
                                    <span className="text-[13.5px] font-bold text-rz-ink">
                                        {t(`investor.auth.type.${type}`)}
                                    </span>
                                </span>
                                <span className="mt-2 block text-[11.5px] leading-[1.45] text-rz-secondary">
                                    {t(
                                        `investor.auth.role.${type}_body`,
                                        values,
                                    )}
                                </span>
                            </Link>
                        );
                    })}
                </div>
                <div className="rounded-3xl border border-rz-border bg-rz-surface px-[26px] pt-[26px] pb-6 shadow-[0_26px_60px_-34px_rgba(20,45,95,.45)]">
                    <h1 className="text-lg font-bold text-rz-ink">
                        {t(
                            login
                                ? 'investor.auth.login.title'
                                : 'investor.auth.create_title',
                        )}
                    </h1>
                    {login ? (
                        <LoginFields
                            action={props.actions.login}
                            variant="desk"
                            status={props.status}
                        />
                    ) : (
                        <StartFields
                            action={props.actions.start}
                            investorType={props.investor_type}
                        />
                    )}
                    <SwitchLine
                        prompt={t(
                            login
                                ? 'investor.auth.new_here'
                                : 'investor.auth.have_account',
                        )}
                        action={t(
                            login
                                ? 'investor.auth.create_an_account'
                                : 'investor.auth.log_in',
                        )}
                        href={login ? props.links.register : props.links.login}
                        className="mt-3.5 text-[13px]"
                    />
                </div>
            </div>
        </div>
    );
}

/**
 * Investor log in and sign-up entry, as the Investor app designs them: on a phone the role choice
 * (register) or the log-in screen; on a wide screen the two-column auth panel.
 */
export default function InvestorAccess(props: InvestorAuthProps) {
    const { t } = useTranslation();
    const wide = useWide();
    const title = t(
        props.mode === 'login'
            ? 'investor.auth.login.head_title'
            : 'investor.auth.register_head_title',
    );

    if (wide) {
        return (
            <InvestorAuthFrame title={title} layout="panel">
                <DeskPanel {...props} />
            </InvestorAuthFrame>
        );
    }

    return (
        <InvestorAuthFrame title={title} layout="column">
            {props.mode === 'login' ? (
                <PhoneLogin {...props} />
            ) : (
                <RoleChoice pitch={props.pitch} links={props.links} />
            )}
        </InvestorAuthFrame>
    );
}
