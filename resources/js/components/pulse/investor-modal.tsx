import {
    PassActions,
    PassCard,
    PassDomain,
    PassStat,
} from '@/components/pulse/pass-card';
import {
    ModalHeading,
    ModalSubmit,
    PulseModal,
} from '@/components/pulse/pulse-modal';
import { SignupFields } from '@/components/pulse/signup-fields';
import type { SignupDetails } from '@/components/pulse/signup-fields';
import { BLENDED_YIELD, formatCompact } from '@/lib/pulse';

type InvestorModalProps = {
    step: 'notes' | 'pledged';
    pledge: number;
    payout: number;
    queueNumber: string;
    districts: Record<string, string[]>;
    details: SignupDetails;
    contactError?: string;
    canSubmit: boolean;
    processing: boolean;
    onChange: (details: Partial<SignupDetails>) => void;
    onSubmit: () => void;
    onShare: (message: string) => void;
    onClose: () => void;
};

/**
 * The investor flow: confirm a pledge, then collect the pass.
 */
export function InvestorModal({
    step,
    pledge,
    payout,
    queueNumber,
    districts,
    details,
    contactError,
    canSubmit,
    processing,
    onChange,
    onSubmit,
    onShare,
    onClose,
}: InvestorModalProps) {
    return (
        <PulseModal label="INVESTOR · PLEDGE INTENT" onClose={onClose}>
            {step === 'notes' && (
                <>
                    <ModalHeading
                        title="Confirm your pledge"
                        subtitle="Reserve your allocation for launch. No funds move now."
                    />
                    <div className="mt-3 overflow-hidden rounded-[14px] border border-[rgba(10,92,255,0.26)] bg-[linear-gradient(135deg,rgba(10,92,255,0.18),rgba(10,92,255,0.04))]">
                        <div className="flex items-end justify-between gap-3 px-[15px] py-3">
                            <div>
                                <div className="text-[9.5px] font-bold tracking-[0.14em] text-[var(--rz-blue-deep)]">
                                    YOUR PLEDGE
                                </div>
                                <div className="rz-num mt-[5px] text-[30px] leading-[0.95] font-bold tracking-[-0.03em] text-[var(--rz-fg-strong)]">
                                    {formatCompact(pledge)}
                                </div>
                            </div>
                            <div className="rz-num text-right">
                                <div className="text-[20px] font-bold text-[var(--rz-blue-fg)]">
                                    {formatCompact(payout)}
                                </div>
                                <div className="mt-0.5 text-[10px] text-[var(--rz-muted)]">
                                    projected back
                                </div>
                            </div>
                        </div>
                    </div>
                    <SignupFields
                        accent="investor"
                        nameLabel="Your name"
                        namePlaceholder="e.g. Diane Uwase"
                        districts={districts}
                        details={details}
                        contactError={contactError}
                        onChange={onChange}
                    />
                    <ModalSubmit
                        accent="#0a5cff"
                        enabled={canSubmit}
                        processing={processing}
                        onClick={onSubmit}
                    >
                        Confirm {formatCompact(pledge)} pledge →
                    </ModalSubmit>
                </>
            )}

            {step === 'pledged' && (
                <div className="text-center">
                    <div className="text-[11px] font-bold tracking-[0.16em] text-[var(--rz-blue-fg)]">
                        ✓ PLEDGED · SPOT {queueNumber}
                    </div>
                    <PassCard
                        tone="investor"
                        tag={`PLEDGING INVESTOR ${queueNumber}`}
                    >
                        <div className="text-[9px] font-semibold tracking-[0.1em] text-[rgba(255,255,255,0.82)] uppercase">
                            {details.name.trim() || 'Pledging investor'}
                        </div>
                        <div className="rz-num mt-2 text-[33px] leading-none font-extrabold tracking-[-0.025em] text-white">
                            {formatCompact(pledge)}
                        </div>
                        <div className="mt-2 flex items-end justify-between">
                            <div className="flex gap-[13px]">
                                <PassStat
                                    label="PROJECTED"
                                    value={formatCompact(payout)}
                                />
                                <PassStat
                                    label="AVG YIELD"
                                    value={`${BLENDED_YIELD.toFixed(1)}%`}
                                />
                            </div>
                            <PassDomain />
                        </div>
                    </PassCard>
                    <PassActions
                        card={{
                            tone: 'investor',
                            tag: `PLEDGING INVESTOR ${queueNumber}`,
                            holder: details.name.trim() || 'Pledging investor',
                            amount: formatCompact(pledge),
                            stats: [
                                {
                                    label: 'PROJECTED',
                                    value: formatCompact(payout),
                                },
                                {
                                    label: 'AVG YIELD',
                                    value: `${BLENDED_YIELD.toFixed(1)}%`,
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
