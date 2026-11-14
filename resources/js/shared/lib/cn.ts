import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Merge conditional class names, letting later Tailwind utilities win over
 * earlier conflicting ones. Every component in the design system funnels its
 * `className` prop through this so callers can always override.
 */
export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}
