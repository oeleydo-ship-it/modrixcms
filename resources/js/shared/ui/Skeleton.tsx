import type { HTMLAttributes } from 'react';
import { cn } from '../lib/cn';

export interface SkeletonProps extends HTMLAttributes<HTMLDivElement> {
    /** `text` rounds like a line of copy, `circle` for avatars. */
    shape?: 'text' | 'block' | 'circle';
}

/**
 * Loading placeholder. Marked `aria-hidden` — announce loading state once, at
 * the container level, rather than once per placeholder.
 *
 * A flat pulse rather than a sweeping shimmer: the design system uses no
 * gradients, and a solid block fading between two surface tokens reads just as
 * clearly as "not loaded yet" while respecting `prefers-reduced-motion`.
 */
export function Skeleton({ shape = 'text', className, ...props }: SkeletonProps) {
    return (
        <div
            aria-hidden
            className={cn(
                'bg-[var(--color-surface-sunken)]',
                'animate-[fnl-skeleton-pulse_1.6s_ease-in-out_infinite]',
                'motion-reduce:animate-none',
                shape === 'text' && 'h-4 rounded',
                shape === 'block' && 'rounded-lg',
                shape === 'circle' && 'rounded-full',
                className,
            )}
            {...props}
        />
    );
}

/** A card-shaped skeleton block for dashboard widgets. */
export function SkeletonCard({ className }: { className?: string }) {
    return (
        <div
            className={cn(
                'rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-4',
                className,
            )}
        >
            <Skeleton className="w-24" />
            <Skeleton shape="block" className="mt-3 h-8 w-32" />
            <Skeleton className="mt-3 w-full" />
        </div>
    );
}
