import type { ReactNode } from 'react';
import { cn } from '../lib/cn';

export interface EmptyStateProps {
    icon?: ReactNode;
    title: ReactNode;
    description?: ReactNode;
    /** Primary call to action — usually a <Button>. */
    action?: ReactNode;
    secondaryAction?: ReactNode;
    /** `card` draws a dashed container; `bare` sits inside an existing surface. */
    variant?: 'card' | 'bare';
    className?: string;
}

export function EmptyState({
    icon,
    title,
    description,
    action,
    secondaryAction,
    variant = 'card',
    className,
}: EmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center px-6 py-12 text-center',
                variant === 'card' && 'rounded-lg border border-dashed border-[var(--color-border-strong)]',
                className,
            )}
        >
            {icon && (
                <div
                    aria-hidden
                    className="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-[var(--color-primary-soft)] text-[var(--color-text-brand)]"
                >
                    {icon}
                </div>
            )}
            <h3 className="text-[length:var(--text-md)] font-semibold text-[var(--color-text)]">{title}</h3>
            {description && (
                <p className="mt-1.5 max-w-md text-[length:var(--text-sm)] text-[var(--color-text-muted)]">
                    {description}
                </p>
            )}
            {(action || secondaryAction) && (
                <div className="mt-5 flex flex-wrap items-center justify-center gap-2">
                    {action}
                    {secondaryAction}
                </div>
            )}
        </div>
    );
}
