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

const WING = '/images/rozine-wing-white.png';

const TONES = {
    investor: {
        background:
            'radial-gradient(150% 130% at 14% 0%, #3f83ff 0%, #0a44c4 40%, #061640 100%)',
        glow: 'rgba(10,92,255,.6)',
    },
    business: {
        background:
            'radial-gradient(150% 130% at 14% 0%, #17c268 0%, #0a7c47 40%, #052a1c 100%)',
        glow: 'rgba(16,178,90,.55)',
    },
};

/**
 * The shareable pass a visitor walks away with.
 *
 * The design is drawn at a 900px width, so the card carries that width as a
 * unit — `--rz-pass-u` is one design pixel — and every box below is the figure
 * the design gives it. The card then holds its proportions at any width.
 */
export function PassCard({
    tone,
    tag,
    badge,
    children,
}: {
    tone: 'investor' | 'business';
    tag: string;
    badge: string;
    children: ReactNode;
}) {
    const { background, glow } = TONES[tone];

    return (
        <div className="@container mx-auto mt-3 w-full [--rz-pass-u:calc(100cqw/900)]">
            <div
                className="relative aspect-[1.6] w-full overflow-hidden rounded-[calc(38*var(--rz-pass-u))] text-left"
                style={{
                    background,
                    boxShadow: `0 calc(50*var(--rz-pass-u)) calc(100*var(--rz-pass-u)) calc(-30*var(--rz-pass-u)) ${glow}, inset 0 calc(2*var(--rz-pass-u)) 0 rgba(255,255,255,.3)`,
                }}
            >
                {/*
                 * The mark is hung on the style attribute rather than a
                 * utility class: Vite rebases a url() it finds in CSS onto its
                 * own dev origin, which laravel-vite-plugin leaves without a
                 * public directory to serve it from, so the tile 404s in dev.
                 */}
                <div
                    className="absolute -top-[30%] -left-[30%] h-[160%] w-[160%] rotate-[-30deg] bg-[size:calc(36*var(--rz-pass-u))_calc(20*var(--rz-pass-u))] bg-repeat opacity-[0.11]"
                    style={{ backgroundImage: `url(${WING})` }}
                />
                <div className="absolute top-[calc(-140*var(--rz-pass-u))] right-[calc(-140*var(--rz-pass-u))] h-[calc(460*var(--rz-pass-u))] w-[calc(460*var(--rz-pass-u))] rounded-full bg-[radial-gradient(closest-side,rgba(255,255,255,0.2),transparent)]" />
                <div className="relative flex h-full flex-col px-[calc(56*var(--rz-pass-u))] py-[calc(52*var(--rz-pass-u))]">
                    <div className="flex items-start justify-between">
                        <div className="flex items-center">
                            <img
                                src="/images/rozine-wordmark-white.png"
                                alt="rozine"
                                className="block h-[calc(63*var(--rz-pass-u))] w-auto"
                            />
                        </div>
                        <div className="flex items-center gap-[calc(12*var(--rz-pass-u))]">
                            <span className="text-[calc(22.8*var(--rz-pass-u))] font-bold tracking-[0.14em] whitespace-nowrap text-[rgba(255,255,255,0.72)]">
                                {tag}
                            </span>
                            <span className="inline-flex items-center rounded-full border-[calc(1.5*var(--rz-pass-u))] border-[rgba(255,255,255,0.34)] bg-[rgba(255,255,255,0.16)] px-[calc(14*var(--rz-pass-u))] pt-[calc(7*var(--rz-pass-u))] pb-[calc(8*var(--rz-pass-u))] text-[calc(26*var(--rz-pass-u))] leading-none font-extrabold tracking-[0.02em] whitespace-nowrap text-white shadow-[inset_0_calc(1*var(--rz-pass-u))_0_rgba(255,255,255,0.25)]">
                                {badge}
                            </span>
                        </div>
                    </div>
                    <div className="mt-auto">{children}</div>
                </div>
            </div>
        </div>
    );
}

/**
 * The name a pass is made out to.
 */
export function PassHolder({ children }: { children: ReactNode }) {
    return (
        <div className="text-[calc(27*var(--rz-pass-u))] font-semibold tracking-[0.1em] text-[rgba(255,255,255,0.82)] uppercase">
            {children}
        </div>
    );
}

/**
 * The headline figure a pass is claimed for, under an optional caption naming
 * what the figure is.
 */
export function PassAmount({
    caption,
    children,
}: {
    caption?: string;
    children: ReactNode;
}) {
    return (
        <>
            {caption && (
                <div className="mt-[calc(18*var(--rz-pass-u))] text-[calc(22.5*var(--rz-pass-u))] font-bold tracking-[0.13em] text-[rgba(255,255,255,0.62)]">
                    {caption}
                </div>
            )}
            <div
                className={`${caption ? 'mt-[calc(6*var(--rz-pass-u))]' : 'mt-[calc(24*var(--rz-pass-u))]'} text-[calc(99*var(--rz-pass-u))] leading-none font-extrabold tracking-[-0.025em] text-white`}
            >
                {children}
            </div>
        </>
    );
}

/**
 * The row of figures and the mark that close a pass.
 */
export function PassFooter({ children }: { children: ReactNode }) {
    return (
        <div className="mt-[calc(24*var(--rz-pass-u))] flex items-end justify-between">
            <div className="flex gap-[calc(39*var(--rz-pass-u))]">
                {children}
            </div>
            <PassDomain />
        </div>
    );
}

/**
 * One of the figures printed along the bottom of a pass.
 */
export function PassStat({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <div className="text-[calc(21*var(--rz-pass-u))] tracking-[0.1em] text-[rgba(255,255,255,0.6)]">
                {label}
            </div>
            <div className="mt-[calc(4.8*var(--rz-pass-u))] text-[calc(34.5*var(--rz-pass-u))] font-bold text-white">
                {value}
            </div>
        </div>
    );
}

/**
 * The rozine.rw mark that closes a pass.
 */
function PassDomain() {
    return (
        <div className="flex items-center gap-[calc(12*var(--rz-pass-u))] text-[calc(31.2*var(--rz-pass-u))] font-bold tracking-[0.03em] text-white">
            <GlobeIcon className="h-[calc(42*var(--rz-pass-u))] w-[calc(42*var(--rz-pass-u))]" />
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
