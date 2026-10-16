import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

type FilterPillProps<T extends string> = {
    label: string;
    value: T;
    options: readonly { value: T; label: string }[];
    onChange: (value: T) => void;
    /** The value that means "no filter", drawn in the quieter colour. */
    neutral?: T;
    className?: string;
};

/**
 * The design's filter pill (Market L1709–1726, L1782–1796): an uppercase label over a button that
 * opens its options below. Picking an option, Escape or a click outside closes it.
 */
export function FilterPill<T extends string>({
    label,
    value,
    options,
    onChange,
    neutral,
    className,
}: FilterPillProps<T>) {
    const [open, setOpen] = useState(false);
    const root = useRef<HTMLDivElement>(null);
    const current = options.find((option) => option.value === value);

    useEffect(() => {
        if (!open) {
            return;
        }

        const close = (event: MouseEvent | KeyboardEvent) => {
            if (
                event instanceof KeyboardEvent
                    ? event.key === 'Escape'
                    : !root.current?.contains(event.target as Node)
            ) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', close);
        document.addEventListener('keydown', close);

        return () => {
            document.removeEventListener('mousedown', close);
            document.removeEventListener('keydown', close);
        };
    }, [open]);

    return (
        <div ref={root} className={cn('relative min-w-0', className)}>
            <p className="mb-[5px] ml-[3px] truncate text-[10.5px] font-bold tracking-[.05em] text-rz-body uppercase">
                {label}
            </p>
            <button
                type="button"
                aria-haspopup="listbox"
                aria-expanded={open}
                onClick={() => setOpen(!open)}
                className={cn(
                    'flex w-full items-center gap-1.5 rounded-[10px] border px-[11px] py-[9px] text-left',
                    open
                        ? 'border-[#f0dcb8] bg-[#f4f7fc]'
                        : 'border-rz-hairline bg-rz-surface',
                )}
            >
                <span
                    className={cn(
                        'min-w-0 flex-1 truncate text-[12.5px] font-semibold',
                        value === neutral ? 'text-rz-secondary' : 'text-rz-ink',
                    )}
                >
                    {current?.label}
                </span>
                <span aria-hidden className="text-[10px] text-rz-secondary">
                    {open ? '▲' : '▼'}
                </span>
            </button>
            {open && (
                <ul
                    role="listbox"
                    aria-label={label}
                    className="absolute top-[calc(100%+6px)] left-0 z-[60] min-w-full rounded-xl border border-rz-hairline bg-rz-surface p-[5px] shadow-[0_16px_36px_-12px_rgba(20,45,95,.3)]"
                >
                    {options.map((option) => (
                        <li key={option.value}>
                            <button
                                type="button"
                                role="option"
                                aria-selected={option.value === value}
                                onClick={() => {
                                    onChange(option.value);
                                    setOpen(false);
                                }}
                                className={cn(
                                    'block w-full rounded-[10px] px-[11px] py-2 text-left text-[12.5px] whitespace-nowrap',
                                    option.value === value
                                        ? 'bg-rz-accent-soft font-semibold text-rz-ink'
                                        : 'font-medium text-rz-body',
                                )}
                            >
                                {option.label}
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
