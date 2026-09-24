import { useTranslation } from '@/hooks/use-translation';
import type { WalletMethod } from '@/types/business';

/**
 * The payment method's 34px tile. The design points at MTN and Airtel logo files it never
 * shipped, so the networks get their brand colours with the name set in type.
 */
export function MethodTile({ kind }: { kind: WalletMethod['kind'] }) {
    const { t } = useTranslation();

    if (kind === 'bank') {
        return (
            <span className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] bg-[#0c1830] dark:bg-[#2a3a5c]">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden
                    className="size-4"
                >
                    <path
                        d="M4 10l8-5 8 5M6 10v7M10 10v7M14 10v7M18 10v7M4 20h16"
                        stroke="#fff"
                        strokeWidth="1.5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </span>
        );
    }

    return (
        <span
            aria-hidden
            className={
                kind === 'mtn'
                    ? 'flex size-[34px] shrink-0 items-center justify-center rounded-[10px] bg-[#ffcb05] text-[10px] font-extrabold text-[#0c1830]'
                    : 'flex size-[34px] shrink-0 items-center justify-center rounded-[10px] bg-[#e40000] text-[9.5px] font-extrabold text-white'
            }
        >
            {t(`business.wallet.network.${kind}`)}
        </span>
    );
}
