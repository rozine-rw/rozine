import type { ComponentProps, ReactNode } from 'react';
import { Icon } from '@/components/rozine/icon';
import { cn } from '@/lib/utils';

/** Uppercase field label: 12px/600 in the design's label grey, 6px above its control. */
export function FieldLabel({
    htmlFor,
    children,
}: {
    htmlFor: string;
    children: ReactNode;
}) {
    return (
        <label
            htmlFor={htmlFor}
            className="mb-1.5 block text-xs font-semibold text-rz-label uppercase"
        >
            {children}
        </label>
    );
}

const FIELD_VARIANTS = {
    /** The standard input: white, 12px radius, 13×14 padding, 14px text. */
    default:
        'rounded-xl border-rz-field-border bg-rz-field px-3.5 py-[13px] text-sm',
    /** Registry codes: 15px/600 with .06em tracking. */
    code: 'rounded-xl border-rz-field-border bg-rz-field px-3.5 py-[13px] text-[15px] font-semibold tracking-[.06em]',
    /** One-time codes: 20px/600, .5em tracking, centred. */
    otp: 'rounded-xl border-rz-field-border bg-rz-field px-3.5 py-[13px] text-center text-xl font-semibold tracking-[.5em]',
    /** Inset fields inside a card: soft fill, 10px radius, 14px/600. */
    soft: 'rounded-[10px] border-rz-field-soft-border bg-rz-field-soft px-[13px] py-3 text-sm font-semibold',
} as const;

type TextFieldProps = Omit<ComponentProps<'input'>, 'className'> & {
    variant?: keyof typeof FIELD_VARIANTS;
    invalid?: boolean;
};

export function TextField({
    variant = 'default',
    invalid = false,
    ...props
}: TextFieldProps) {
    return (
        <input
            aria-invalid={invalid || undefined}
            className={cn(
                'w-full border text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border focus:shadow-[0_0_0_3.5px_var(--rz-focus-ring)]',
                FIELD_VARIANTS[variant],
                invalid && 'border-rz-danger',
            )}
            {...props}
        />
    );
}

/** Red banner for a request-level failure, with the warning glyph. */
export function ErrorBanner({ children }: { children: ReactNode }) {
    return (
        <div
            role="alert"
            className="flex items-center gap-2 rounded-[10px] border border-[#fdeaea] bg-[rgba(229,72,77,.08)] px-[13px] py-[11px] text-[12.5px] font-semibold text-rz-danger-text dark:border-[rgba(255,107,111,.25)]"
        >
            <Icon name="warning" tone="red" />
            {children}
        </div>
    );
}

/** Inline field error: 11.5px/600 red with the warning glyph. */
export function FieldError({
    id,
    children,
}: {
    id: string;
    children?: string;
}) {
    if (!children) {
        return null;
    }

    return (
        <p
            id={id}
            className="mt-1.5 flex items-center gap-1 text-[11.5px] font-semibold text-rz-danger-text"
        >
            <Icon name="warning" tone="red" />
            {children}
        </p>
    );
}

type PrimaryButtonProps = Omit<ComponentProps<'button'>, 'className'> & {
    busy?: boolean;
    size?: 'md' | 'lg';
};

/** The one primary action per view: full width, 16px radius, the audience fill. */
export function PrimaryButton({
    busy = false,
    size = 'md',
    disabled,
    children,
    ...props
}: PrimaryButtonProps) {
    return (
        <button
            type="submit"
            disabled={disabled || busy}
            aria-busy={busy || undefined}
            data-inactive={disabled || undefined}
            className={cn(
                'w-full rounded-2xl bg-rz-accent-fill font-semibold text-white transition-[filter] hover:brightness-[.985] active:translate-y-[.5px] data-inactive:cursor-not-allowed data-inactive:bg-rz-disabled data-inactive:text-rz-secondary',
                size === 'md'
                    ? 'h-[52px] text-[15px]'
                    : 'h-[54px] text-[15.5px]',
            )}
            {...props}
        >
            {children}
        </button>
    );
}

/** Inline text action in the audience colour. */
export function TextAction({ className, ...props }: ComponentProps<'button'>) {
    return (
        <button
            type="button"
            className={cn(
                'cursor-pointer border-0 bg-transparent p-0 font-semibold text-rz-accent-app-text',
                className,
            )}
            {...props}
        />
    );
}
