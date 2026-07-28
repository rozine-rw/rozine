import type { ReactNode } from 'react';
import {
    DownloadIcon,
    GlobeIcon,
    InstagramIcon,
    WhatsAppIcon,
    XIcon,
} from '@/components/pulse/icons';
import { downloadPassCard } from '@/lib/pass-card-image';
import type { PassCardSpec } from '@/lib/pass-card-image';

const TONES = {
    investor: {
        background:
            'radial-gradient(150% 130% at 14% 0%, #3f83ff 0%, #0a44c4 40%, #061640 100%)',
        shadow: '0 30px 60px -18px rgba(10,92,255,.6), inset 0 1px 0 rgba(255,255,255,.3)',
        pattern: 'rz-wing-investor',
    },
    business: {
        background:
            'radial-gradient(150% 130% at 14% 0%, #17c268 0%, #0a7c47 40%, #052a1c 100%)',
        shadow: '0 30px 60px -18px rgba(16,178,90,.55), inset 0 1px 0 rgba(255,255,255,.3)',
        pattern: 'rz-wing-business',
    },
};

/**
 * The shareable pass a visitor walks away with.
 */
export function PassCard({
    tone,
    tag,
    children,
}: {
    tone: 'investor' | 'business';
    tag: string;
    children: ReactNode;
}) {
    const { background, shadow, pattern } = TONES[tone];

    return (
        <div
            className="relative mx-auto mt-3 aspect-[1.585] w-full overflow-hidden rounded-[20px] text-left"
            style={{ background, boxShadow: shadow }}
        >
            <svg
                width="100%"
                height="100%"
                preserveAspectRatio="xMidYMid slice"
                className="absolute inset-0 opacity-[0.11]"
            >
                <defs>
                    <pattern
                        id={pattern}
                        width="19"
                        height="10.5"
                        patternUnits="userSpaceOnUse"
                        patternTransform="rotate(-30)"
                    >
                        <image
                            href="/images/rozine-wing-white.png"
                            x="0.5"
                            y="0.75"
                            width="18"
                            height="9.4"
                            preserveAspectRatio="xMidYMid meet"
                        />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill={`url(#${pattern})`} />
            </svg>
            <div className="absolute top-0 bottom-0 left-0 w-[46%] animate-[rzp-sheen_5s_ease-in-out_infinite] bg-[linear-gradient(100deg,rgba(255,255,255,0.16),transparent_72%)]" />
            <div className="absolute -top-[70px] -right-[70px] h-[230px] w-[230px] rounded-full bg-[radial-gradient(closest-side,rgba(255,255,255,0.2),transparent)]" />
            <div className="relative flex h-full flex-col px-4 py-[13px]">
                <div className="flex items-start justify-between">
                    <div className="flex items-center">
                        <img
                            src="/images/rozine-wordmark-white.png"
                            alt="rozine"
                            className="block h-[17px] w-auto"
                        />
                    </div>
                    <span className="text-[7.5px] font-bold tracking-[0.14em] text-[rgba(255,255,255,0.72)]">
                        {tag}
                    </span>
                </div>
                <div className="mt-auto">{children}</div>
            </div>
        </div>
    );
}

/**
 * One of the figures printed along the bottom of a pass.
 */
export function PassStat({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <div className="text-[7px] tracking-[0.1em] text-[rgba(255,255,255,0.6)]">
                {label}
            </div>
            <div className="rz-num mt-px text-[11.5px] font-bold text-white">
                {value}
            </div>
        </div>
    );
}

/**
 * The rozine.rw mark that closes a pass.
 */
export function PassDomain() {
    return (
        <div className="flex items-center gap-1 text-[10.5px] font-bold tracking-[0.03em] text-white">
            <GlobeIcon />
            rozine.rw
        </div>
    );
}

/**
 * The share row and the way out of a pass.
 */
export function PassActions({
    card,
    onShare,
    onDone,
}: {
    card: PassCardSpec;
    onShare: (message: string) => void;
    onDone: () => void;
}) {
    const download = async (): Promise<void> => {
        onShare('Saving your card…');

        try {
            await downloadPassCard(card);
        } catch {
            onShare('Your card could not be saved.');
        }
    };

    return (
        <>
            <div className="mt-3 flex gap-[7px]">
                <ShareButton
                    title="WhatsApp"
                    onClick={() => onShare('Shared to WhatsApp')}
                    style={{ background: '#25d366', color: '#04240f' }}
                >
                    <WhatsAppIcon />
                </ShareButton>
                <ShareButton
                    title="X"
                    onClick={() => onShare('Ready to post on X')}
                >
                    <XIcon />
                </ShareButton>
                <ShareButton
                    title="Instagram"
                    onClick={() =>
                        onShare('Card saved for your Instagram story')
                    }
                >
                    <InstagramIcon />
                </ShareButton>
                <ShareButton title="Download" onClick={download}>
                    <DownloadIcon />
                    Download
                </ShareButton>
            </div>
            <button
                type="button"
                onClick={onDone}
                className="rz-ghost mt-2 h-9 w-full cursor-pointer rounded-[10px] border border-[var(--rz-ghost-border)] bg-transparent text-[12px] font-semibold text-[var(--rz-muted)]"
            >
                Done
            </button>
        </>
    );
}

function ShareButton({
    title,
    onClick,
    style,
    children,
}: {
    title: string;
    onClick: () => void;
    style?: { background: string; color: string };
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            title={title}
            onClick={onClick}
            style={
                style ?? {
                    background: 'rgba(255,255,255,.06)',
                    color: 'var(--rz-fg)',
                }
            }
            className="flex h-[38px] flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-[10px] border border-[var(--rz-ghost-border)] text-[11.5px] font-semibold"
        >
            {children}
        </button>
    );
}
