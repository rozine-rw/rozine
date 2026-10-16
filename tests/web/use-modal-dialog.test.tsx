import { fireEvent, render, screen } from '@testing-library/react';
import { useState } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { useModalDialog } from '@/hooks/use-modal-dialog';

function Dialog({
    label,
    onEscape,
    controls = true,
    autoFocusInside = false,
    children,
}: {
    label: string;
    onEscape?: () => void;
    controls?: boolean;
    autoFocusInside?: boolean;
    children?: React.ReactNode;
}) {
    const dialog = useModalDialog<HTMLDivElement>(onEscape);

    return (
        <div ref={dialog} tabIndex={-1} role="dialog" aria-label={label}>
            {controls && (
                <>
                    <button type="button" autoFocus={autoFocusInside}>
                        {label} first
                    </button>
                    <button type="button" disabled>
                        {label} disabled
                    </button>
                    <button type="button">{label} last</button>
                </>
            )}
            {children}
        </div>
    );
}

function Opener({
    onEscape,
    controls,
}: {
    onEscape?: () => void;
    controls?: boolean;
}) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <button type="button" onClick={() => setOpen(true)}>
                Open
            </button>
            <button type="button" onClick={() => setOpen(false)}>
                Outside
            </button>
            {open && (
                <Dialog
                    label="Sheet"
                    controls={controls}
                    onEscape={() => {
                        onEscape?.();
                        setOpen(false);
                    }}
                />
            )}
        </>
    );
}

const tab = (shiftKey = false) =>
    fireEvent.keyDown(window, { key: 'Tab', shiftKey });

describe('useModalDialog', () => {
    it('moves focus into the dialog, and back to its opener when it closes', () => {
        const onEscape = vi.fn();
        render(<Opener onEscape={onEscape} />);
        const opener = screen.getByRole('button', { name: 'Open' });
        opener.focus();

        fireEvent.click(opener);

        expect(screen.getByRole('dialog', { name: 'Sheet' })).toHaveFocus();

        fireEvent.keyDown(window, { key: 'Escape' });

        expect(onEscape).toHaveBeenCalledOnce();
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        expect(opener).toHaveFocus();
    });

    it('leaves focus on a control inside that already took it', () => {
        render(<Dialog label="Form" autoFocusInside />);

        expect(
            screen.getByRole('button', { name: 'Form first' }),
        ).toHaveFocus();
    });

    it('keeps Tab and Shift+Tab inside the dialog, skipping disabled controls', () => {
        render(<Dialog label="Sheet" />);
        const dialog = screen.getByRole('dialog');
        const first = screen.getByRole('button', { name: 'Sheet first' });
        const last = screen.getByRole('button', { name: 'Sheet last' });

        expect(tab(true)).toBe(false);
        expect(last).toHaveFocus();

        expect(tab()).toBe(false);
        expect(first).toHaveFocus();

        expect(tab()).toBe(true);
        first.focus();
        expect(tab(true)).toBe(false);
        expect(last).toHaveFocus();

        dialog.focus();
        expect(tab(true)).toBe(false);
        expect(last).toHaveFocus();
    });

    it('brings focus that escaped the dialog back to its first control', () => {
        render(<Opener />);
        fireEvent.click(screen.getByRole('button', { name: 'Open' }));
        screen.getByRole('button', { name: 'Outside' }).focus();

        expect(tab()).toBe(false);
        expect(
            screen.getByRole('button', { name: 'Sheet first' }),
        ).toHaveFocus();
    });

    it('holds focus on a dialog with nothing to focus', () => {
        render(<Dialog label="Notice" controls={false} />);

        expect(tab()).toBe(false);
        expect(screen.getByRole('dialog')).toHaveFocus();
    });

    it('ignores other keys, and Escape on a dialog that cannot be dismissed', () => {
        render(<Dialog label="Locked" />);

        expect(fireEvent.keyDown(window, { key: 'Escape' })).toBe(true);
        expect(fireEvent.keyDown(window, { key: 'Enter' })).toBe(true);
        expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('lets only the innermost of nested dialogs answer the keyboard', () => {
        const outer = vi.fn();
        const inner = vi.fn();
        render(
            <Dialog label="Outer" onEscape={outer}>
                <Dialog label="Inner" onEscape={inner} />
            </Dialog>,
        );

        fireEvent.keyDown(window, { key: 'Escape' });

        expect(inner).toHaveBeenCalledOnce();
        expect(outer).not.toHaveBeenCalled();
    });

    it('does nothing while inactive, and skips a focus return to a removed opener', () => {
        function Inactive() {
            const dialog = useModalDialog<HTMLDivElement>(undefined, false);

            return (
                <div
                    ref={dialog}
                    tabIndex={-1}
                    role="dialog"
                    aria-label="Off"
                />
            );
        }

        render(<Inactive />);

        expect(screen.getByRole('dialog')).not.toHaveFocus();

        const { unmount } = render(
            <>
                <Dialog label="Gone" />
            </>,
        );
        const stray = document.createElement('button');
        document.body.append(stray);
        stray.focus();
        stray.remove();

        expect(() => unmount()).not.toThrow();
    });
});
