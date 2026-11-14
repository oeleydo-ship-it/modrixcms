import { useEffect, type RefObject } from 'react';

const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
    '[contenteditable="true"]',
].join(',');

export function getFocusable(container: HTMLElement): HTMLElement[] {
    return Array.from(container.querySelectorAll<HTMLElement>(FOCUSABLE)).filter(
        (el) => el.offsetParent !== null || el.getClientRects().length > 0,
    );
}

/**
 * Keep Tab focus inside `ref` while `active`, move focus in on mount and
 * restore it to the previously focused element on unmount. Used by Modal and
 * Drawer so both satisfy the dialog keyboard contract.
 */
export function useFocusTrap(ref: RefObject<HTMLElement | null>, active: boolean): void {
    useEffect(() => {
        if (!active) {
            return;
        }
        const container = ref.current;
        if (!container) {
            return;
        }

        const previouslyFocused = document.activeElement as HTMLElement | null;

        const focusables = getFocusable(container);
        (focusables[0] ?? container).focus({ preventScroll: true });

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key !== 'Tab') {
                return;
            }
            const items = getFocusable(container);
            if (items.length === 0) {
                event.preventDefault();
                container.focus({ preventScroll: true });
                return;
            }
            const first = items[0]!;
            const last = items[items.length - 1]!;
            const active_ = document.activeElement;

            if (event.shiftKey && (active_ === first || active_ === container)) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && active_ === last) {
                event.preventDefault();
                first.focus();
            }
        };

        container.addEventListener('keydown', onKeyDown);
        return () => {
            container.removeEventListener('keydown', onKeyDown);
            previouslyFocused?.focus?.({ preventScroll: true });
        };
    }, [ref, active]);
}
