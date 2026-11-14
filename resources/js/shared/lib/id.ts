let counter = 0;

/**
 * Stable-enough id generator for `aria-*` wiring in components that need an id
 * before React's `useId` value is available (e.g. imperative portals).
 */
export function uid(prefix = 'fnl'): string {
    counter += 1;
    return `${prefix}-${counter.toString(36)}`;
}
