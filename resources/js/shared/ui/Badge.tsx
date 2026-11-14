import type { HTMLAttributes } from 'react';
import { cn } from '../lib/cn';

export type BadgeTone = 'neutral' | 'brand' | 'success' | 'warning' | 'danger' | 'info';

export interface BadgeProps extends HTMLAttributes<HTMLSpanElement> {
    tone?: BadgeTone;
    size?: 'sm' | 'md';
    /** Leading status dot — useful for funnel/campaign states. */
    dot?: boolean;
}

const tones: Record<BadgeTone, string> = {
    neutral: 'bg-[var(--color-surface-sunken)] text-[var(--color-text-muted)] border-[var(--color-border)]',
    brand: 'bg-[var(--color-primary-soft)] text-[var(--color-text-brand)] border-transparent',
    success: 'bg-[var(--color-success-soft)] text-[var(--color-success)] border-transparent',
    warning: 'bg-[var(--color-warning-soft)] text-[var(--color-warning)] border-transparent',
    danger: 'bg-[var(--color-danger-soft)] text-[var(--color-danger)] border-transparent',
    info: 'bg-[var(--color-info-soft)] text-[var(--color-info)] border-transparent',
};

export function Badge({ tone = 'neutral', size = 'sm', dot = false, className, children, ...props }: BadgeProps) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full border font-medium whitespace-nowrap',
                size === 'sm' ? 'px-2 py-0.5 text-[length:var(--text-2xs)]' : 'px-2.5 py-1 text-[length:var(--text-xs)]',
                tones[tone],
                className,
            )}
            {...props}
        >
            {dot && <span className="h-1.5 w-1.5 rounded-full bg-current" aria-hidden />}
            {children}
        </span>
    );
}
