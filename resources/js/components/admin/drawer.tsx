import { Link, router } from '@inertiajs/react';
import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';

type DrawerProps = {
    label: string;
    close: RouteLink;
    children: ReactNode;
    className?: string;
};

/**
 * The console drawer (design T3167–3168): a blurred scrim over the content column and a 552px
 * panel inset 10px from its edges, sliding in from the right. It sits inside the content column,
 * so the sidebar and top bar stay visible. On a phone it fills the screen. Closing is a visit to
 * the page without the record, so the URL always says what is open.
 */
export function Drawer({ label, close, children, className }: DrawerProps) {
    const { t } = useTranslation();

    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                router.visit(close.url, { preserveScroll: true });
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [close.url]);

    return (
        <>
            <Link
                href={close}
                preserveScroll
                tabIndex={-1}
                aria-label={t('admin.drawer.close')}
                className="fixed inset-0 z-[52] animate-[rz-scrim_.18s_ease] bg-[rgba(8,14,28,.34)] backdrop-blur-[2px] lg:absolute"
            />
            <div
                role="dialog"
                aria-modal="true"
                aria-label={label}
                className={cn(
                    'fixed inset-0 z-[53] flex animate-[rz-slide_.26s_cubic-bezier(.16,1,.3,1)] flex-col overflow-hidden bg-rz-page-console lg:absolute lg:inset-auto lg:top-2.5 lg:right-2.5 lg:bottom-2.5 lg:w-[552px] lg:max-w-[calc(100%-20px)] lg:rounded-2xl lg:border lg:border-[#e2e8f4] lg:shadow-[0_2px_6px_rgba(16,32,58,.06),0_26px_60px_-18px_rgba(16,32,58,.34)] dark:lg:border-rz-border',
                    className,
                )}
            >
                {children}
            </div>
        </>
    );
}

/** The drawer's ✕: 34px, white, hairline. `dark` is the review header's translucent variant. */
export function DrawerClose({
    close,
    variant = 'light',
}: {
    close: RouteLink;
    variant?: 'light' | 'dark';
}) {
    const { t } = useTranslation();

    return (
        <Link
            href={close}
            preserveScroll
            aria-label={t('admin.drawer.close')}
            className={cn(
                'flex shrink-0 items-center justify-center rounded-[9px] border',
                variant === 'light'
                    ? 'size-[34px] border-rz-hairline bg-rz-surface text-rz-body'
                    : 'size-8 border-[rgba(255,255,255,.16)] bg-[rgba(255,255,255,.1)] text-[#cdd9f0]',
            )}
        >
            <svg
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden
            >
                <path
                    d="M6 6l12 12M18 6 6 18"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                />
            </svg>
        </Link>
    );
}
