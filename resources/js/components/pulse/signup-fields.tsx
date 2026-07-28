import type { CSSProperties, ReactNode } from 'react';
import { MailIcon, PhoneIcon, WarningIcon } from '@/components/pulse/icons';
import type { ContactMethod } from '@/lib/pulse';

export type SignupDetails = {
    name: string;
    contact: string;
    contactMethod: ContactMethod;
    province: string;
    district: string;
};

type SignupFieldsProps = {
    accent: 'investor' | 'business';
    nameLabel: string;
    namePlaceholder: string;
    districts: Record<string, string[]>;
    details: SignupDetails;
    contactError?: string;
    onChange: (details: Partial<SignupDetails>) => void;
    children?: ReactNode;
};

const ACCENTS = {
    investor: {
        tint: 'rgba(10,92,255,.16)',
        line: 'rgba(10,92,255,.6)',
        dot: 'var(--rz-blue-fg)',
    },
    business: {
        tint: 'rgba(16,161,80,.14)',
        line: 'rgba(16,161,80,.55)',
        dot: 'var(--rz-green-fg)',
    },
};

/**
 * The details every waitlist signup collects, whichever side it comes from.
 */
export function SignupFields({
    accent,
    nameLabel,
    namePlaceholder,
    districts,
    details,
    contactError,
    onChange,
    children,
}: SignupFieldsProps) {
    const provinces = Object.keys(districts);

    return (
        <div className="mt-3 flex flex-col gap-2">
            <div>
                <FieldLabel>{nameLabel}</FieldLabel>
                <input
                    value={details.name}
                    onChange={(event) => onChange({ name: event.target.value })}
                    className="rz-ipt h-[42px] w-full rounded-[10px] border border-[var(--rz-input-border)] bg-[var(--rz-input-bg)] px-3.5 text-[14px] text-[var(--rz-fg)] outline-none"
                    placeholder={namePlaceholder}
                />
            </div>
            <div>
                <FieldLabel>Location</FieldLabel>
                <div className="flex gap-[9px]">
                    <select
                        value={details.province}
                        aria-label="Province"
                        onChange={(event) =>
                            onChange({
                                province: event.target.value,
                                district: '',
                            })
                        }
                        className="rz-ipt h-[42px] flex-1 cursor-pointer appearance-none rounded-[10px] border border-[var(--rz-input-border)] bg-[var(--rz-input-bg)] px-3 text-[14px] text-[var(--rz-fg)] outline-none"
                    >
                        <option value="" style={{ background: '#12141b' }}>
                            Province
                        </option>
                        {provinces.map((province) => (
                            <option
                                key={province}
                                value={province}
                                style={{ background: '#12141b' }}
                            >
                                {province}
                            </option>
                        ))}
                    </select>
                    <select
                        value={details.district}
                        aria-label="District"
                        onChange={(event) =>
                            onChange({ district: event.target.value })
                        }
                        className="rz-ipt h-[42px] flex-1 cursor-pointer appearance-none rounded-[10px] border border-[var(--rz-input-border)] bg-[var(--rz-input-bg)] px-3 text-[14px] text-[var(--rz-fg)] outline-none"
                    >
                        <option value="" style={{ background: '#12141b' }}>
                            District
                        </option>
                        {(districts[details.province] ?? []).map((district) => (
                            <option
                                key={district}
                                value={district}
                                style={{ background: '#12141b' }}
                            >
                                {district}
                            </option>
                        ))}
                    </select>
                </div>
            </div>
            <div>
                <FieldLabel>How should we reach you?</FieldLabel>
                <div className="flex gap-[9px]">
                    <ContactToggle
                        accent={accent}
                        active={details.contactMethod === 'phone'}
                        onClick={() =>
                            onChange({ contactMethod: 'phone', contact: '' })
                        }
                    >
                        <PhoneIcon />
                        Phone
                    </ContactToggle>
                    <ContactToggle
                        accent={accent}
                        active={details.contactMethod === 'email'}
                        onClick={() =>
                            onChange({ contactMethod: 'email', contact: '' })
                        }
                    >
                        <MailIcon />
                        Email
                    </ContactToggle>
                </div>
                <input
                    value={details.contact}
                    onChange={(event) =>
                        onChange({ contact: event.target.value })
                    }
                    aria-invalid={contactError ? true : undefined}
                    aria-describedby={
                        contactError ? 'rz-contact-error' : undefined
                    }
                    className={`rz-ipt mt-[9px] h-[42px] w-full rounded-[10px] border bg-[var(--rz-input-bg)] px-3.5 text-[14px] text-[var(--rz-fg)] outline-none ${
                        contactError
                            ? 'border-[var(--rz-error)]'
                            : 'border-[var(--rz-input-border)]'
                    }`}
                    placeholder={
                        details.contactMethod === 'email'
                            ? 'you@email.com'
                            : '07xx xxx xxx'
                    }
                />
                {contactError && (
                    <div
                        id="rz-contact-error"
                        className="mt-1.5 flex items-start gap-1.5 text-[11px] font-semibold text-[var(--rz-error)]"
                    >
                        <WarningIcon />
                        {contactError}
                    </div>
                )}
            </div>
            {children}
        </div>
    );
}

function FieldLabel({ children }: { children: ReactNode }) {
    return (
        <div className="mb-1.5 text-[11px] font-semibold text-[var(--rz-muted)]">
            {children}
        </div>
    );
}

function ContactToggle({
    accent,
    active,
    onClick,
    children,
}: {
    accent: 'investor' | 'business';
    active: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    const tone = ACCENTS[accent];

    return (
        <button
            type="button"
            onClick={onClick}
            style={{
                borderColor: active ? tone.line : 'rgba(255,255,255,.1)',
                background: active ? tone.tint : 'rgba(255,255,255,.02)',
                color: active ? 'var(--rz-seg-active-fg)' : 'var(--rz-muted)',
            }}
            className="flex h-10 flex-1 cursor-pointer items-center gap-[9px] rounded-[11px] border-[1.5px] px-[13px] text-[13.5px] font-semibold transition-all duration-150"
        >
            <span
                className="h-[15px] w-[15px] shrink-0 rounded-full border-2"
                style={
                    {
                        borderColor: active ? tone.dot : '#5a6070',
                        background: `radial-gradient(circle, ${active ? tone.dot : 'transparent'} 0 42%, transparent 46%)`,
                    } as CSSProperties
                }
            />
            {children}
        </button>
    );
}
