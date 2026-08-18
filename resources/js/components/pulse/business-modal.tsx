import { RatingPill } from '@/components/pulse/business-panel';
import { ListingConsent } from '@/components/pulse/listing-consent';
import {
    PassActions,
    PassAmount,
    PassCard,
    PassFooter,
    PassHolder,
    PassStat,
} from '@/components/pulse/pass-card';
import {
    ModalHeading,
    ModalSubmit,
    PulseModal,
} from '@/components/pulse/pulse-modal';
import { SignupFields } from '@/components/pulse/signup-fields';
import type { SignupDetails } from '@/components/pulse/signup-fields';
import { formatCompact } from '@/lib/pulse';
import type { Rating } from '@/lib/pulse';

type BusinessModalProps = {
    step: 'parsing' | 'result' | 'pass';
    progress: number;
    progressLabel: string;
    qualifiedAmount: number;
    belowMinimum: boolean;
    rating: Rating;
    termLabel: string;
    flatRate: string;
    queueNumber: string;
    loanNumber: string;
    districts: Record<string, string[]>;
    details: SignupDetails;
    anonymous: boolean;
    contactError?: string;
    canSubmit: boolean;
    processing: boolean;
    onAnonymousChange: (anonymous: boolean) => void;
    onChange: (details: Partial<SignupDetails>) => void;
    onSubmit: () => void;
    onShare: (message: string) => void;
    onClose: () => void;
};

/**
 * The business flow: confirm the pre-qualification, then collect the pass.
 */
export function BusinessModal({
    step,
    progress,
    progressLabel,
    qualifiedAmount,
    belowMinimum,
    rating,
    termLabel,
    flatRate,
    queueNumber,
    loanNumber,
    districts,
    details,
    anonymous,
    contactError,
    canSubmit,
    processing,
    onAnonymousChange,
    onChange,
    onSubmit,
    onShare,
    onClose,
}: BusinessModalProps) {
    // A business under the smallest loan Rozine writes takes a place in the
    // queue rather than an amount.
    const passCaption = belowMinimum ? 'EARLY ACCESS' : 'PRE-QUALIFIED LOAN';
    const passAmount = belowMinimum
        ? 'WAITLIST'
        : formatCompact(qualifiedAmount);

    return (
        <PulseModal label="BUSINESS · PRE-QUALIFY" onClose={onClose}>
            {step === 'parsing' && (
                <div className="pt-[22px] pb-3.5 text-center">
                    <div className="mx-auto h-[46px] w-[46px] animate-[rzp-spin_0.8s_linear_infinite] rounded-full border-[3px] border-[rgba(16,161,80,0.18)] border-t-[#12a150]" />
                    <div className="rz-num mt-5 text-[34px] font-bold tracking-[-0.03em] text-[var(--rz-fg-title)]">
                        {progress}%
                    </div>
                    <div className="mt-1.5 text-[12px] tracking-[0.06em] text-[var(--rz-dim)]">
                        {progressLabel}
                    </div>
                    <div className="mx-auto mt-4 h-0.5 max-w-[280px] overflow-hidden bg-[var(--rz-track)]">
                        <div
                            className="h-full bg-[#12a150] transition-[width] duration-300"
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                </div>
            )}

            {step === 'result' && (
                <>
                    <ModalHeading
                        title="Reserve your spot"
                        subtitle="A few details and your early-access spot is confirmed."
                    />
                    <div className="mt-[18px] overflow-hidden rounded-[14px] border border-[rgba(16,161,80,0.24)] bg-[linear-gradient(135deg,rgba(16,161,80,0.16),rgba(16,161,80,0.04))]">
                        <div className="flex items-end justify-between gap-3 px-[15px] py-3">
                            <div>
                                <div className="text-[9.5px] font-bold tracking-[0.14em] text-[var(--rz-green-deep)]">
                                    PRE-QUALIFIED
                                </div>
                                <div className="rz-num mt-[5px] text-[30px] leading-[0.95] font-bold tracking-[-0.03em] text-[var(--rz-fg-strong)]">
                                    {formatCompact(qualifiedAmount)}
                                </div>
                            </div>
                            <RatingPill rating={rating} compact />
                        </div>
                    </div>
                    <SignupFields
                        accent="business"
                        nameLabel="Business name"
                        namePlaceholder="e.g. GreenLeaf Agro"
                        districts={districts}
                        details={details}
                        contactError={contactError}
                        onChange={onChange}
                    >
                        <ListingConsent
                            anonymous={anonymous}
                            district={details.district}
                            onChange={onAnonymousChange}
                        />
                    </SignupFields>
                    <ModalSubmit
                        accent="#0f7a3d"
                        enabled={canSubmit}
                        processing={processing}
                        onClick={onSubmit}
                    >
                        Reserve my spot →
                    </ModalSubmit>
                </>
            )}

            {step === 'pass' && (
                <div className="text-center">
                    <div className="text-[11px] font-bold tracking-[0.16em] text-[var(--rz-green-fg)]">
                        ✓ YOU&apos;RE IN · SPOT {queueNumber}
                    </div>
                    <PassCard
                        tone="business"
                        tag="BUSINESS LOAN"
                        badge={loanNumber}
                    >
                        <PassHolder>
                            {details.name.trim() || 'Pre-qualified business'}{' '}
                            <span className="text-[rgba(255,255,255,0.9)]">
                                ·
                            </span>{' '}
                            <span className="font-normal">
                                {details.district || 'Gasabo'}
                            </span>
                        </PassHolder>
                        <PassAmount caption={passCaption}>
                            {passAmount}
                        </PassAmount>
                        <PassFooter>
                            <PassStat label="TERM" value={termLabel} />
                            <PassStat label="FLAT" value={flatRate} />
                            <PassStat
                                label="RATING"
                                value={`${rating.band} ${rating.score}`}
                            />
                        </PassFooter>
                    </PassCard>
                    <PassActions
                        card={{
                            tone: 'business',
                            tag: 'BUSINESS LOAN',
                            badge: loanNumber,
                            holder: `${details.name.trim() || 'Pre-qualified business'} · ${details.district || 'Gasabo'}`,
                            caption: passCaption,
                            amount: passAmount,
                            stats: [
                                { label: 'TERM', value: termLabel },
                                { label: 'FLAT', value: flatRate },
                                {
                                    label: 'RATING',
                                    value: `${rating.band} ${rating.score}`,
                                },
                            ],
                        }}
                        onShare={onShare}
                        onDone={onClose}
                    />
                </div>
            )}
        </PulseModal>
    );
}
