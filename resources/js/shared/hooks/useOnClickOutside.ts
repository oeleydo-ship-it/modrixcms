import { useEffect, type RefObject } from 'react';

/**
 * Call `handler` when a pointer press lands outside every given element.
 * Uses `pointerdown` so the callback fires before focus moves.
 */
export function useOnClickOutside(
    refs: Array<RefObject<HTMLElement | null>>,
    handler: (event: PointerEvent) => void,
    enabled = true,
): void {
    useEffect(() => {
        if (!enabled) {
            return;
        }

        const listener = (event: PointerEvent) => {
            const target = event.target as Node | null;
            if (!target || !target.isConnected) {
                return;
            }
            const inside = refs.some((ref) => ref.current?.contains(target));
            if (!inside) {
                handler(event);
            }
        };

        document.addEventListener('pointerdown', listener, true);
        return () => document.removeEventListener('pointerdown', listener, true);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [enabled, handler, ...refs]);
}
