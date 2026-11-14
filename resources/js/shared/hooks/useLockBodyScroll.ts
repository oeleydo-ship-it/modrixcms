import { useEffect } from 'react';

let lockCount = 0;
let previousOverflow = '';
let previousPaddingRight = '';

/** Reference-counted body scroll lock so stacked overlays behave. */
export function useLockBodyScroll(active: boolean): void {
    useEffect(() => {
        if (!active) {
            return;
        }

        if (lockCount === 0) {
            const { body } = document;
            const scrollbar = window.innerWidth - document.documentElement.clientWidth;
            previousOverflow = body.style.overflow;
            previousPaddingRight = body.style.paddingRight;
            body.style.overflow = 'hidden';
            if (scrollbar > 0) {
                body.style.paddingRight = `${scrollbar}px`;
            }
        }
        lockCount += 1;

        return () => {
            lockCount -= 1;
            if (lockCount === 0) {
                document.body.style.overflow = previousOverflow;
                document.body.style.paddingRight = previousPaddingRight;
            }
        };
    }, [active]);
}
