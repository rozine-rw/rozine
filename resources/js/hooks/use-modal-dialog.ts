import { useEffect, useRef } from 'react';
import type { RefObject } from 'react';

const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

/** Open modal dialogs in the order they opened. */
const openDialogs: HTMLElement[] = [];

/**
 * The dialog that owns the keyboard: the latest to open among those holding no other open dialog.
 * A sheet nested inside another opens first (child effects run first) yet still wins.
 */
function innermost(): HTMLElement | undefined {
    return openDialogs
        .filter(
            (dialog) =>
                !openDialogs.some(
                    (other) => other !== dialog && dialog.contains(other),
                ),
        )
        .at(-1);
}

/**
 * Keyboard behaviour for a modal dialog (WCAG 2.2 SC 2.1.1, 2.4.3): on opening, focus moves into
 * the dialog unless something inside already took it; Tab and Shift+Tab cycle inside it; Escape
 * calls `onEscape` when the dialog can be dismissed; and on closing, focus returns to whatever
 * opened it. Put the returned ref on the element with `role="dialog"`, and give that element
 * `tabIndex={-1}` so it can hold focus itself. `active` is for a dialog rendered conditionally
 * inside its opener's component.
 */
export function useModalDialog<T extends HTMLElement>(
    onEscape?: () => void,
    active = true,
): RefObject<T | null> {
    const ref = useRef<T>(null);
    const escape = useRef(onEscape);

    useEffect(() => {
        escape.current = onEscape;
    });

    useEffect(() => {
        const dialog = ref.current;

        if (!active || dialog === null) {
            return;
        }

        const opener = document.activeElement;
        openDialogs.push(dialog);

        if (!dialog.contains(opener)) {
            dialog.focus({ preventScroll: true });
        }

        const onKey = (event: KeyboardEvent) => {
            if (innermost() !== dialog) {
                return;
            }

            if (event.key === 'Escape') {
                if (escape.current !== undefined) {
                    event.preventDefault();
                    escape.current();
                }

                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const focusable = [
                ...dialog.querySelectorAll<HTMLElement>(FOCUSABLE),
            ];
            const current = document.activeElement;

            if (focusable.length === 0) {
                event.preventDefault();
                dialog.focus({ preventScroll: true });

                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            const outside = !dialog.contains(current);
            const atEdge = event.shiftKey
                ? current === first || current === dialog
                : current === last;

            if (outside || atEdge) {
                event.preventDefault();
                (event.shiftKey ? last : first).focus();
            }
        };

        window.addEventListener('keydown', onKey);

        return () => {
            window.removeEventListener('keydown', onKey);
            openDialogs.splice(openDialogs.indexOf(dialog), 1);

            if (opener instanceof HTMLElement && opener.isConnected) {
                opener.focus({ preventScroll: true });
            }
        };
    }, [active]);

    return ref;
}
